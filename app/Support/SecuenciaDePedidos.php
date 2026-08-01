<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Entrega el siguiente número de pedido de un negocio.
 *
 * El problema que resuelve: dos cajeros cobrando a la vez. Calcular el
 * consecutivo con MAX(numero) + 1 hace que ambos lean el mismo máximo y uno de
 * los dos choque contra el índice único.
 *
 * Aquí cada negocio tiene una fila propia que se bloquea con lockForUpdate
 * mientras se incrementa. El segundo cajero espera unos milisegundos a que el
 * primero termine, y solo se serializan las peticiones de número: el resto de
 * la aplicación sigue igual. Como el bloqueo es por fila, dos negocios
 * distintos no se estorban.
 *
 * El contador nunca se reinicia. Es lo que permite rastrear un pedido antiguo
 * sin ambigüedad y evita que auditar dos ejercicios sea un rompecabezas.
 */
class SecuenciaDePedidos
{
    /**
     * Reserva y devuelve el siguiente consecutivo del negocio.
     *
     * Debe llamarse dentro de la transacción de la venta: si la venta se
     * deshace, el número se libera con ella y no deja huecos.
     */
    public function siguiente(int $negocioId): int
    {
        return DB::transaction(function () use ($negocioId) {
            $this->asegurarFila($negocioId);

            $fila = DB::table('secuencias_pedido')
                ->where('negocio_id', $negocioId)
                ->lockForUpdate()
                ->first();

            $siguiente = (int) $fila->ultimo_numero + 1;

            DB::table('secuencias_pedido')
                ->where('negocio_id', $negocioId)
                ->update([
                    'ultimo_numero' => $siguiente,
                    'updated_at' => now(),
                ]);

            return $siguiente;
        });
    }

    /**
     * Consulta el último número entregado sin reservar ninguno.
     */
    public function ultimoEntregado(int $negocioId): int
    {
        return (int) DB::table('secuencias_pedido')
            ->where('negocio_id', $negocioId)
            ->value('ultimo_numero');
    }

    /**
     * Crea la fila si el negocio todavía no tiene secuencia.
     *
     * insertOrIgnore se apoya en el índice único de negocio_id: si dos
     * peticiones simultáneas intentan crearla, una gana y la otra no falla.
     * Hacerlo con un "si no existe, inserta" sí tendría condición de carrera.
     */
    private function asegurarFila(int $negocioId): void
    {
        DB::table('secuencias_pedido')->insertOrIgnore([
            'negocio_id' => $negocioId,
            'ultimo_numero' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
