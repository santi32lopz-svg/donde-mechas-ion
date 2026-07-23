<?php

namespace App\Livewire\Pos;

use Livewire\Component;
use App\Models\Producto;

class PosMain extends Component
{
    // Propiedades del Carrito y Estado
    public array $cart = [];
    public float $subtotal = 0.00;
    public float $total = 0.00;
    
    // Filtros de búsqueda y navegación en el POS
    public string $search = '';
    public ?int $selectedCategoriaId = null;

    public function mount(): void
    {
        // Restaurar el carrito de la sesión si el usuario recarga la página
        $this->cart = session()->get('pos_cart', []);
        $this->calculateTotals();
    }

    /**
     * Agrega un producto al carrito o incrementa su cantidad
     */
    public function addToCart(int $productoId): void
    {
        // Buscar el producto en la BD
        $producto = Producto::find($productoId);

        if (!$producto) {
            // Opcional: Lanzar alerta si no se encuentra
            return;
        }

        $id = $producto->id;

        if (isset($this->cart[$id])) {
            // Si ya existe, simplemente incrementamos la cantidad
            $this->cart[$id]['cantidad']++;
            $this->cart[$id]['subtotal'] = $this->cart[$id]['cantidad'] * $this->cart[$id]['precio'];
        } else {
            // Si es un producto nuevo, lo estructuramos en el array
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

        // Si la cantidad llega a cero o menos, lo eliminamos de la orden
        if ($this->cart[$productoId]['cantidad'] <= 0) {
            $this->removeFromCart($productoId);
            return;
        }

        // Recalcular subtotal del ítem
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
        session()->forget('pos_cart');
        $this->calculateTotals();
    }

    /**
     * Recalcula los totales generales del pedido
     */
    public function calculateTotals(): void
    {
        $this->subtotal = array_sum(array_column($this->cart, 'subtotal'));
        
        // De momento el total es igual al subtotal (puedes añadir impuestos aquí si aplica)
        $this->total = $this->subtotal;
    }

    /**
     * Helper para persistir sesión y recalcular totales
     */
    private function syncAndRecalculate(): void
    {
        session()->put('pos_cart', $this->cart);
        $this->calculateTotals();
    }

    public function render()
    {
        // Consulta de productos activa según búsqueda y categoría activa
        $productos = Producto::query()
            ->when($this->search, fn($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->when($this->selectedCategoriaId, fn($q) => $q->where('categoria_id', $this->selectedCategoriaId))
            ->get();

        return view('livewire.pos.pos-main', [
            'productos' => $productos,
        ])->layout('layouts.app');
    }
}
