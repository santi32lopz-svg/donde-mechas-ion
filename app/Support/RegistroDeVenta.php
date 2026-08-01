<?php

namespace App\Support;

use App\Exceptions\VentaNoRegistrable;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\TurnoCaja;
use Illuminate\Support\Facades\DB;

/**
 * Convierte un carrito en una venta registrada.
 *
 * Vive fuera del componente Livewire a propósito. La Fase 2 del proyecto prevé
 * registrar pedidos desde un bot de WhatsApp, y si esta lógica estuviera dentro
 * de PosMain habría que duplicarla. Aquí es también donde encajará el descuento
 * de inventario por receta, sin volver a tocar la terminal.
 *
 * Todo ocurre dentro de una única transacción: si algo falla no queda ni el
 * pedido a medias ni un número de la secuencia consumido.
 */
class RegistroDeVenta
{
    /** Métodos que mueven dinero físico y por tanto exigen turno abierto. */
    private const REQUIEREN_CAJA = ['efectivo'];

    private const METODOS_VALIDOS = ['efectivo', 'nequi', 'daviplata', 'tarjeta'];

    public function __construct(
        private readonly SecuenciaDePedidos $secuencia,
        private readonly TenantContext $tenant,
    ) {}

    /**
     * Registra la venta y devuelve el pedido creado.
     *
     * @param  array<int, array{cantidad:int, notas:string}>  $carrito
     *
     * @throws VentaNoRegistrable
     */
    public function registrar(array $carrito, string $metodoPago, ?string $notas = null): Pedido
    {
        if ($carrito === []) {
            throw VentaNoRegistrable::carritoVacio();
        }

        if (! in_array($metodoPago, self::METODOS_VALIDOS, true)) {
            throw VentaNoRegistrable::metodoDePagoInvalido($metodoPago);
        }

        $turno = $this->turnoAbierto();

        // El efectivo entra en el cajón, así que sin turno el cuadre nunca
        // cerraría. Los métodos electrónicos no lo tocan y pueden cobrarse sin
        // caja abierta, que es lo que necesita un negocio pequeño.
        if ($turno === null && in_array($metodoPago, self::REQUIEREN_CAJA, true)) {
            throw VentaNoRegistrable::sinTurnoDeCaja();
        }

        $negocioId = $this->tenant->negocioId();

        return DB::transaction(function () use ($carrito, $metodoPago, $notas, $turno, $negocioId) {
            $lineas = $this->resolverLineas($carrito);

            if ($lineas === []) {
                throw VentaNoRegistrable::carritoVacio();
            }

            $pedido = Pedido::create([
                'negocio_id' => $negocioId,
                // Dentro de la transacción: si la venta se deshace, el número
                // se libera con ella y no deja huecos en la numeración.
                'numero_secuencial' => $this->secuencia->siguiente($negocioId),
                'user_id' => auth()->id(),
                'turno_caja_id' => $turno?->id,
                'estado' => 'completado',
                'metodo_pago' => $metodoPago,
                'monto_total' => array_sum(array_column($lineas, 'subtotal')),
                'notas' => $notas,
            ]);

            $pedido->detalles()->createMany($lineas);

            return $pedido;
        });
    }

    /**
     * Construye las líneas leyendo precio y nombre de la base de datos.
     *
     * Nunca se toma el precio de lo que muestra la pantalla: viaja por el
     * navegador y es editable. Esta relectura es la que hace que el importe
     * cobrado sea el real aunque alguien manipule el cliente.
     *
     * El nombre se copia a la línea porque renombrar un producto después no
     * debe reescribir el histórico de ventas.
     *
     * @param  array<int, array{cantidad:int, notas:string}>  $carrito
     * @return array<int, array<string, mixed>>
     */
    private function resolverLineas(array $carrito): array
    {
        // Sin filtrar por disponible: un producto que dejó de estarlo mientras
        // el cliente esperaba ya está servido, y bloquear la venta lo castigaría
        // a él. Se cobra y se deja constancia en la línea.
        $productos = Producto::query()
            ->whereIn('id', array_keys($carrito))
            ->get()
            ->keyBy('id');

        $lineas = [];

        foreach ($carrito as $productoId => $item) {
            $producto = $productos->get($productoId);

            // Un producto borrado sí desaparece: no hay precio con el que cobrar.
            if ($producto === null) {
                continue;
            }

            $precio = (float) $producto->precio;
            $cantidad = (int) $item['cantidad'];

            $lineas[] = [
                'negocio_id' => $producto->negocio_id,
                'producto_id' => $producto->id,
                'nombre_producto' => $producto->nombre,
                'vendido_sin_disponibilidad' => ! $producto->disponible,
                'precio_unitario' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => $precio * $cantidad,
            ];
        }

        return $lineas;
    }

    /**
     * Turno de caja abierto del negocio activo, si lo hay.
     */
    public function turnoAbierto(): ?TurnoCaja
    {
        return TurnoCaja::query()
            ->where('estado', TurnoCaja::ABIERTO)
            ->latest('fecha_apertura')
            ->first();
    }

    /**
     * @return array<int, string>
     */
    public static function metodosDePago(): array
    {
        return self::METODOS_VALIDOS;
    }

    public static function requiereTurnoDeCaja(string $metodoPago): bool
    {
        return in_array($metodoPago, self::REQUIEREN_CAJA, true);
    }
}
