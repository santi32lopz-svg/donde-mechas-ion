<?php

namespace App\Livewire\Pos;

use App\Models\Categoria;
use App\Models\Producto;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PosMain extends Component
{
    /** Tope de productos pintados en la cuadrícula, para no ahogar la tablet. */
    private const MAX_PRODUCTOS = 100;

    /** Tope de unidades por línea, como red de seguridad ante toques repetidos. */
    private const MAX_CANTIDAD = 999;

    /**
     * Carrito de la venta en curso.
     *
     * Solo guarda cantidad y notas. Los precios NO viven aquí: se resuelven
     * contra la base de datos en cada render. Antes el precio viajaba dentro
     * de una propiedad pública de Livewire, es decir, dentro del payload que
     * el navegador puede editar, así que un cajero podía cobrarse un combo a
     * $1 desde las devtools. #[Locked] cierra además la puerta a que el
     * cliente modifique el array en la petición.
     *
     * @var array<int, array{cantidad:int, notas:string}>
     */
    #[Locked]
    public array $cart = [];

    /**
     * Negocio activo. Va bloqueado porque es la clave del aislamiento
     * multi-tenant: si el cliente pudiera cambiarlo, vería y vendería el
     * catálogo de otro negocio.
     */
    #[Locked]
    public ?int $negocioId = null;

    // Filtros de búsqueda y navegación en el POS
    public string $search = '';

    public ?int $selectedCategoriaId = null;

    public function mount(): void
    {
        // El negocio ya no es un atributo del usuario sino el contexto de la
        // petición: lo resuelve TenantContext a partir de la sesión. El POS es
        // el mismo para todos los negocios y solo consume el que esté activo.
        $this->negocioId = app(TenantContext::class)->negocioId();

        abort_if(
            $this->negocioId === null,
            403,
            'No hay un negocio activo. Selecciona uno para abrir la terminal.'
        );

        $this->cart = $this->normalizarCarrito(session()->get($this->claveDeSesion(), []));
    }

    /**
     * Agrega un producto al carrito o incrementa su cantidad.
     */
    public function addToCart(int $productoId): void
    {
        // El scope global de negocio ya impide alcanzar productos de otro tenant.
        $producto = Producto::query()
            ->whereKey($productoId)
            ->where('disponible', true)
            ->first();

        if ($producto === null) {
            return;
        }

        $this->cart[$producto->id] = [
            'cantidad' => min(($this->cart[$producto->id]['cantidad'] ?? 0) + 1, self::MAX_CANTIDAD),
            'notas' => $this->cart[$producto->id]['notas'] ?? '',
        ];

        $this->persistirCarrito();
    }

    /**
     * Agrega directamente el producto cuyo código de barras coincide.
     * El lector "teclea" el código y envía Enter, de ahí el wire:keydown.enter.
     */
    public function agregarPorCodigoDeBarras(): void
    {
        $codigo = trim($this->search);

        if ($codigo === '') {
            return;
        }

        $producto = Producto::query()
            ->porCodigoDeBarras($codigo)
            ->where('disponible', true)
            ->first();

        if ($producto === null) {
            return;
        }

        $this->addToCart($producto->id);

        // Se limpia para dejar el campo listo para el siguiente escaneo.
        $this->search = '';
    }

    /**
     * Incrementa o decrementa la cantidad de un ítem existente.
     */
    public function updateQuantity(int $productoId, string $action): void
    {
        if (! isset($this->cart[$productoId])) {
            return;
        }

        $cantidad = $this->cart[$productoId]['cantidad'] + match ($action) {
            'increase' => 1,
            'decrease' => -1,
            default => 0,
        };

        if ($cantidad <= 0) {
            $this->removeFromCart($productoId);

            return;
        }

        $this->cart[$productoId]['cantidad'] = min($cantidad, self::MAX_CANTIDAD);

        $this->persistirCarrito();
    }

    /**
     * Elimina un ítem por completo del carrito.
     */
    public function removeFromCart(int $productoId): void
    {
        if (isset($this->cart[$productoId])) {
            unset($this->cart[$productoId]);
            $this->persistirCarrito();
        }
    }

    /**
     * Vacía el carrito por completo (cancelar pedido / limpieza pos-venta).
     */
    public function clearCart(): void
    {
        $this->cart = [];
        session()->forget($this->claveDeSesion());
    }

    /**
     * Selecciona o deselecciona una categoría.
     */
    public function selectCategoria(?int $categoriaId = null): void
    {
        $this->selectedCategoriaId = $this->selectedCategoriaId === $categoriaId ? null : $categoriaId;
    }

    /**
     * Al buscar se suelta el filtro de categoría: si no, escribir "empanada"
     * con "Hamburguesas" activa no devuelve nada y parece que el buscador falla.
     */
    public function updatedSearch(): void
    {
        if (trim($this->search) !== '') {
            $this->selectedCategoriaId = null;
        }
    }

    /**
     * Construye las líneas de la tirilla leyendo los precios de la base de datos.
     *
     * @return Collection<int, array{id:int, nombre:string, precio:float, cantidad:int, subtotal:float, notas:string}>
     */
    private function lineasDelCarrito(): Collection
    {
        if ($this->cart === []) {
            return collect();
        }

        $productos = Producto::query()
            ->whereIn('id', array_keys($this->cart))
            ->where('disponible', true)
            ->get()
            ->keyBy('id');

        // Un producto puede haberse dado de baja mientras estaba en el carrito.
        $huerfanos = array_diff(array_keys($this->cart), $productos->keys()->all());

        if ($huerfanos !== []) {
            foreach ($huerfanos as $id) {
                unset($this->cart[$id]);
            }

            $this->persistirCarrito();
        }

        return collect($this->cart)
            ->map(function (array $item, int $id) use ($productos): array {
                $precio = (float) $productos[$id]->precio;

                return [
                    'id' => $id,
                    'nombre' => $productos[$id]->nombre,
                    'precio' => $precio,
                    'cantidad' => $item['cantidad'],
                    'subtotal' => $precio * $item['cantidad'],
                    'notas' => $item['notas'],
                ];
            })
            ->values();
    }

    /**
     * Descarta cualquier cosa que no sea cantidad y notas, y de paso convierte
     * los carritos guardados con el formato anterior, que sí llevaban precio.
     *
     * @param  array<mixed>  $bruto
     * @return array<int, array{cantidad:int, notas:string}>
     */
    private function normalizarCarrito(array $bruto): array
    {
        $limpio = [];

        foreach ($bruto as $id => $item) {
            $id = (int) $id;
            $cantidad = (int) (is_array($item) ? ($item['cantidad'] ?? 0) : 0);

            if ($id <= 0 || $cantidad <= 0) {
                continue;
            }

            $limpio[$id] = [
                'cantidad' => min($cantidad, self::MAX_CANTIDAD),
                'notas' => (string) ($item['notas'] ?? ''),
            ];
        }

        return $limpio;
    }

    private function persistirCarrito(): void
    {
        session()->put($this->claveDeSesion(), $this->cart);
    }

    private function claveDeSesion(): string
    {
        return 'pos_cart_'.$this->negocioId;
    }

    public function render()
    {
        $lineas = $this->lineasDelCarrito();
        $subtotal = (float) $lineas->sum('subtotal');

        // El scope global de negocio filtra por el tenant en sesión.
        $categorias = Categoria::query()
            ->where('activo', true)
            ->orderBy('orden_visualizacion')
            ->get();

        $productos = Producto::query()
            ->select('id', 'nombre', 'precio', 'imagen_path')
            ->where('disponible', true)
            ->buscar($this->search)
            ->when($this->selectedCategoriaId, fn ($q) => $q->where('categoria_id', $this->selectedCategoriaId))
            ->orderBy('nombre')
            ->limit(self::MAX_PRODUCTOS)
            ->get();

        return view('livewire.pos.pos-main', [
            'productos' => $productos,
            'categorias' => $categorias,
            'lineas' => $lineas,
            'subtotal' => $subtotal,
            // De momento el total es igual al subtotal; aquí entrarán impuestos si aplican.
            'total' => $subtotal,
            'limiteAlcanzado' => $productos->count() === self::MAX_PRODUCTOS,
        ])->layout('layouts.app');
    }
}
