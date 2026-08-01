<?php

namespace App\Livewire\Pos;

use App\Exceptions\VentaNoRegistrable;
use App\Models\Etiqueta;
use App\Models\Producto;
use App\Support\RegistroDeVenta;
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

    public ?int $etiquetaSeleccionadaId = null;

    /** Avisos del cobro. En propiedades: un flash no se ve sin recargar. */
    public ?string $mensajeExito = null;

    public ?string $mensajeError = null;

    // ------------------------------------------------------------------
    // Diálogo de cobro
    //
    // Vive en este componente y no en uno aparte porque el carrito debe quedar
    // bloqueado mientras está abierto y volver intacto si se cancela. Al no
    // salir nunca de aquí, no hay nada que sincronizar ni que restaurar: la
    // cancelación es simplemente cerrar el diálogo.
    // ------------------------------------------------------------------

    public bool $cobrando = false;

    public string $metodoPago = 'efectivo';

    /** Solo dígitos; se formatea al mostrarlo. */
    public string $efectivoRecibido = '';

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
        if ($this->carritoBloqueado()) {
            return;
        }

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

        if ($this->carritoBloqueado() || $codigo === '') {
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
        if ($this->carritoBloqueado() || ! isset($this->cart[$productoId])) {
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
        if ($this->carritoBloqueado()) {
            return;
        }

        if (isset($this->cart[$productoId])) {
            unset($this->cart[$productoId]);
            $this->persistirCarrito();
        }
    }

    /**
     * Registra la venta del carrito actual.
     *
     * La lógica vive en RegistroDeVenta: la Fase 2 prevé registrar pedidos
     * desde WhatsApp y duplicarla aquí obligaría a mantener dos copias.
     *
     * En esta etapa se cobra en efectivo directamente. El diálogo con métodos
     * de pago y numpad llega en la siguiente.
     */
    public function cobrar(string $metodoPago = 'efectivo'): void
    {
        $this->mensajeError = null;
        $this->mensajeExito = null;

        try {
            $pedido = app(RegistroDeVenta::class)->registrar($this->cart, $metodoPago);
        } catch (VentaNoRegistrable $e) {
            // Regla de negocio, no fallo: el cajero necesita saber qué hacer.
            $this->mensajeError = $e->getMessage();

            return;
        }

        $this->cart = [];
        session()->forget($this->claveDeSesion());

        $cambio = $this->recibido() - (float) $pedido->monto_total;

        $this->cobrando = false;
        $this->efectivoRecibido = '';

        $this->mensajeExito = sprintf(
            'Pedido %s cobrado por $%s.%s',
            $pedido->numeroFormateado(),
            number_format((float) $pedido->monto_total, 0, ',', '.'),
            $cambio > 0 ? ' Cambio: $'.number_format($cambio, 0, ',', '.').'.' : '',
        );
    }

    /**
     * Vacía el carrito por completo (cancelar pedido / limpieza pos-venta).
     */
    public function clearCart(): void
    {
        if ($this->carritoBloqueado()) {
            return;
        }

        $this->cart = [];
        session()->forget($this->claveDeSesion());
    }

    /**
     * Mientras se está cobrando el carrito no admite cambios.
     *
     * La comprobación es de servidor, no solo del diálogo que tapa la pantalla:
     * una petición fabricada a mano tampoco puede alterar lo que se está
     * cobrando.
     */
    private function carritoBloqueado(): bool
    {
        return $this->cobrando;
    }

    // ------------------------------------------------------------------
    // Diálogo de cobro
    // ------------------------------------------------------------------

    public function abrirCobro(): void
    {
        if ($this->cart === []) {
            return;
        }

        $this->mensajeError = null;
        $this->mensajeExito = null;
        $this->metodoPago = 'efectivo';
        $this->efectivoRecibido = '';
        $this->cobrando = true;
    }

    /**
     * Cancelar deja el carrito exactamente como estaba: nunca salió de aquí.
     */
    public function cerrarCobro(): void
    {
        $this->cobrando = false;
        $this->efectivoRecibido = '';
        $this->mensajeError = null;
    }

    public function seleccionarMetodo(string $metodo): void
    {
        if (! in_array($metodo, RegistroDeVenta::metodosDePago(), true)) {
            return;
        }

        $this->metodoPago = $metodo;
        $this->mensajeError = null;

        // El importe recibido solo tiene sentido con efectivo en la mano.
        if (! RegistroDeVenta::requiereTurnoDeCaja($metodo)) {
            $this->efectivoRecibido = '';
        }
    }

    /**
     * Añade dígitos desde el numpad en pantalla. El teclado físico escribe
     * directamente en el campo, así que ambos caminos acaban en la misma
     * propiedad.
     */
    public function pulsar(string $digitos): void
    {
        $limpio = preg_replace('/\D/', '', $digitos) ?? '';

        if ($limpio === '') {
            return;
        }

        // Un tope generoso, solo para que nadie llene la pantalla de ceros.
        $this->efectivoRecibido = substr(ltrim($this->efectivoRecibido.$limpio, '0') ?: '0', 0, 12);
        $this->mensajeError = null;
    }

    public function borrarDigito(): void
    {
        $this->efectivoRecibido = substr($this->efectivoRecibido, 0, -1);
    }

    public function limpiarMonto(): void
    {
        $this->efectivoRecibido = '';
    }

    /**
     * Atajo para el caso más común: el cliente paga justo.
     */
    public function montoExacto(): void
    {
        $this->efectivoRecibido = (string) (int) round($this->totalDelCarrito());
    }

    /**
     * Confirma el cobro con el método y el importe elegidos.
     */
    public function confirmarCobro(): void
    {
        $total = $this->totalDelCarrito();

        if (RegistroDeVenta::requiereTurnoDeCaja($this->metodoPago) && $this->recibido() < $total) {
            // Cobrar de menos descuadra la caja sin dejar rastro de por qué.
            $this->mensajeError = 'El efectivo recibido es menor que el total.';

            return;
        }

        $this->cobrar($this->metodoPago);
    }

    public function recibido(): float
    {
        return (float) ($this->efectivoRecibido === '' ? 0 : $this->efectivoRecibido);
    }

    /**
     * Total vigente del carrito, leído de la base de datos.
     */
    private function totalDelCarrito(): float
    {
        return (float) $this->lineasDelCarrito()->sum('subtotal');
    }

    /**
     * Selecciona o deselecciona una etiqueta.
     */
    public function seleccionarEtiqueta(?int $etiquetaId = null): void
    {
        $this->etiquetaSeleccionadaId = $this->etiquetaSeleccionadaId === $etiquetaId ? null : $etiquetaId;
    }

    /**
     * Al buscar se suelta el filtro de etiqueta: si no, escribir "empanada"
     * con "Hamburguesas" activa no devuelve nada y parece que el buscador falla.
     */
    public function updatedSearch(): void
    {
        if (trim($this->search) !== '') {
            $this->etiquetaSeleccionadaId = null;
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

        // Sin filtrar por disponible: si un producto se oculta del POS mientras
        // el cliente espera, lo más probable es que ya esté servido. Se queda en
        // la tirilla marcado, y al cobrar se deja constancia en la línea.
        $productos = Producto::query()
            ->whereIn('id', array_keys($this->cart))
            ->get()
            ->keyBy('id');

        // Solo se purga lo que ya no existe: sin producto no hay precio.
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
                    'disponible' => (bool) $productos[$id]->disponible,
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
        $etiquetas = Etiqueta::query()
            ->where('activo', true)
            ->orderBy('orden_visualizacion')
            ->get();

        $productos = Producto::query()
            ->select('id', 'nombre', 'precio', 'imagen_path')
            ->where('disponible', true)
            ->buscar($this->search)
            ->when($this->etiquetaSeleccionadaId, fn ($q) => $q->where('etiqueta_id', $this->etiquetaSeleccionadaId))
            ->orderBy('nombre')
            ->limit(self::MAX_PRODUCTOS)
            ->get();

        return view('livewire.pos.pos-main', [
            'productos' => $productos,
            'etiquetas' => $etiquetas,
            'lineas' => $lineas,
            'subtotal' => $subtotal,
            // De momento el total es igual al subtotal; aquí entrarán impuestos si aplican.
            'total' => $subtotal,
            'limiteAlcanzado' => $productos->count() === self::MAX_PRODUCTOS,
            'cambio' => max(0, $this->recibido() - $subtotal),
            'faltante' => max(0, $subtotal - $this->recibido()),
            'hayTurnoAbierto' => app(RegistroDeVenta::class)->turnoAbierto() !== null,
        ])->layout('layouts.app');
    }
}
