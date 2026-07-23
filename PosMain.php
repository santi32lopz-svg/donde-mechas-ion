<?php

namespace App\Livewire\Pos;

use Livewire\Component;
use App\Models\Producto;
use App\Models\Categoria;

class PosMain extends Component
{
    // Propiedades del Carrito y Estado
    public array $cart = [];
    public float $subtotal = 0.00;
    public float $total = 0.00;

    // Filtros de búsqueda y navegación en el POS
    public string $search = '';
    public ?int $selectedCategoriaId = null;

    // Negocio activo en sesión (Multi-Tenant / Touch POS)
    public ?int $negocioId = null;

    public function mount(): void
    {
        $this->negocioId = auth()->user()->negocio_id;

        // Restaurar el carrito de la sesión (aislado por negocio activo)
        $this->cart = session()->get('pos_cart_' . $this->negocioId, []);
        $this->calculateTotals();
    }

    /**
     * Agrega un producto al carrito o incrementa su cantidad
     */
    public function addToCart(int $productoId): void
    {
        // Se valida que el producto pertenezca al negocio activo en sesión
        $producto = Producto::where('id', $productoId)
            ->where('negocio_id', $this->negocioId)
            ->first();

        if (!$producto) {
            return;
        }

        $id = $producto->id;

        if (isset($this->cart[$id])) {
            $this->cart[$id]['cantidad']++;
            $this->cart[$id]['subtotal'] = $this->cart[$id]['cantidad'] * $this->cart[$id]['precio'];
        } else {
            $this->cart[$id] = [
                'id'       => $producto->id,
                'nombre'   => $producto->nombre,
                'precio'   => (float) $producto->precio,
                'cantidad' => 1,
                'subtotal' => (float) $producto->precio,
                'notas'    => '',
            ];
        }

        $this->syncAndRecalculate();
    }

    /**
     * Incrementa o decrementa la cantidad de un ítem existente
     */
    public function updateQuantity(int $productoId, string $action): void
    {
        if (!isset($this->cart[$productoId])) {
            return;
        }

        if ($action === 'increase') {
            $this->cart[$productoId]['cantidad']++;
        } elseif ($action === 'decrease') {
            $this->cart[$productoId]['cantidad']--;
        }

        if ($this->cart[$productoId]['cantidad'] <= 0) {
            $this->removeFromCart($productoId);
            return;
        }

        $this->cart[$productoId]['subtotal'] = $this->cart[$productoId]['cantidad'] * $this->cart[$productoId]['precio'];

        $this->syncAndRecalculate();
    }

    /**
     * Elimina un ítem por completo del carrito
     */
    public function removeFromCart(int $productoId): void
    {
        if (isset($this->cart[$productoId])) {
            unset($this->cart[$productoId]);
            $this->syncAndRecalculate();
        }
    }

    /**
     * Vacía el carrito por completo (Cancelar pedido / Limpieza pos-venta)
     */
    public function clearCart(): void
    {
        $this->cart = [];
        session()->forget('pos_cart_' . $this->negocioId);
        $this->calculateTotals();
    }

    /**
     * Recalcula los totales generales del pedido
     */
    public function calculateTotals(): void
    {
        $this->subtotal = array_sum(array_column($this->cart, 'subtotal'));
        $this->total = $this->subtotal;
    }

    /**
     * Helper para persistir sesión y recalcular totales
     */
    private function syncAndRecalculate(): void
    {
        session()->put('pos_cart_' . $this->negocioId, $this->cart);
        $this->calculateTotals();
    }

    /**
     * Helper para seleccionar/deseleccionar categoría
     */
    public function selectCategoria(?int $categoriaId = null): void
    {
        if ($this->selectedCategoriaId === $categoriaId) {
            $this->selectedCategoriaId = null;
        } else {
            $this->selectedCategoriaId = $categoriaId;
        }
    }

    public function render()
    {
        // Categorías ACTIVAS del negocio en sesión, ordenadas para su visualización en tiles
        $categorias = Categoria::where('negocio_id', $this->negocioId)
            ->where('activo', true)
            ->orderBy('orden_visualizacion')
            ->get();

        // Productos del negocio activo. Filtro en tiempo real por nombre o código de barras (Livewire)
        // y/o por categoría seleccionada.
        $productos = Producto::query()
            ->where('negocio_id', $this->negocioId)
            ->where('disponible', true)
            ->when($this->search, function ($q) {
                $term = $this->search;
                $q->where(function ($sub) use ($term) {
                    $sub->where('nombre', 'like', "%{$term}%")
                        ->orWhere('codigo_barras', 'like', "%{$term}%");
                });
            })
            ->when($this->selectedCategoriaId, fn ($q) => $q->where('categoria_id', $this->selectedCategoriaId))
            ->orderBy('nombre')
            ->get();

        return view('livewire.pos.pos-main', [
            'productos'  => $productos,
            'categorias' => $categorias,
        ])->layout('layouts.app');
    }
}
