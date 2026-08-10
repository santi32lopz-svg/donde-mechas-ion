<?php

namespace App\Support;

use App\Exceptions\OperacionDeCajaNoPermitida;
use App\Models\GastoCaja;
use App\Models\TurnoCaja;
use Illuminate\Support\Facades\DB;

/**
 * Operaciones sobre el turno de caja.
 *
 * Vive fuera de los componentes porque el turno se abre desde la terminal pero
 * también se consultará desde el Panel Operativo y desde el módulo Caja. La
 * lógica es la misma en los tres sitios.
 *
 * Toda operación deja rastro de quién, cuándo y por qué: un movimiento de
 * dinero anónimo no se puede auditar, y el cuadre de caja existe precisamente
 * para encontrar responsables de una diferencia.
 */
class GestionDeTurno
{
    /** Categorías de egreso. Sirven para agrupar en los reportes de caja. */
    public const TIPOS_DE_EGRESO = [
        'domiciliario' => 'Pago a domiciliario',
        'insumos' => 'Compra de insumos',
        'proveedor' => 'Pago a proveedor',
        'otro' => 'Otro',
    ];

    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * Turno abierto del negocio activo, si lo hay.
     */
    public function abierto(): ?TurnoCaja
    {
        return TurnoCaja::query()
            ->where('estado', TurnoCaja::ABIERTO)
            ->latest('fecha_apertura')
            ->first();
    }

    /**
     * Base que se propone al abrir: lo que se contó al cerrar el turno
     * anterior. Evita volver a teclear cada día la misma cifra y los errores
     * que eso arrastra. Es editable antes de confirmar.
     */
    public function baseSugerida(): float
    {
        $ultimoCierre = TurnoCaja::query()
            ->where('estado', TurnoCaja::CERRADO)
            ->whereNotNull('monto_cierre')
            ->latest('fecha_cierre')
            ->value('monto_cierre');

        return (float) ($ultimoCierre ?? 0);
    }

    /**
     * Abre un turno para el negocio activo.
     *
     * @throws OperacionDeCajaNoPermitida
     */
    public function abrir(float $base, ?string $observaciones = null): TurnoCaja
    {
        if ($base < 0) {
            throw OperacionDeCajaNoPermitida::baseNegativa();
        }

        return DB::transaction(function () use ($base, $observaciones) {
            // Se vuelve a comprobar dentro de la transacción: entre la pantalla
            // y el guardado alguien pudo abrir turno desde otra terminal. El
            // índice único parcial es la última red, pero este aviso explica el
            // motivo en lugar de dejar escapar un error de integridad.
            if ($this->abierto() !== null) {
                throw OperacionDeCajaNoPermitida::turnoYaAbierto();
            }

            return TurnoCaja::create([
                'negocio_id' => $this->tenant->negocioId(),
                'user_id' => auth()->id(),
                'monto_apertura' => $base,
                'estado' => TurnoCaja::ABIERTO,
                'fecha_apertura' => now(),
                'observaciones_apertura' => $observaciones ?: null,
            ]);
        });
    }

    /**
     * Registra una salida de dinero del cajón.
     *
     * Puede dejar la caja en negativo y se permite a propósito: el dinero pudo
     * entrar por una vía que el sistema todavía no conoce, y registrar la
     * realidad importa más que impedir una operación válida. Quien llama debe
     * avisar antes de confirmar.
     *
     * @throws OperacionDeCajaNoPermitida
     */
    public function registrarEgreso(string $descripcion, float $monto, string $tipo = 'otro'): GastoCaja
    {
        $turno = $this->abierto();

        if ($turno === null) {
            throw OperacionDeCajaNoPermitida::sinTurnoAbierto();
        }

        if ($monto <= 0) {
            throw OperacionDeCajaNoPermitida::montoInvalido();
        }

        if (! array_key_exists($tipo, self::TIPOS_DE_EGRESO)) {
            $tipo = 'otro';
        }

        return GastoCaja::create([
            'negocio_id' => $this->tenant->negocioId(),
            'turno_caja_id' => $turno->id,
            'user_id' => auth()->id(),
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'monto' => $monto,
        ]);
    }

    /**
     * Comprueba si un egreso dejaría el cajón en negativo, para poder avisar
     * antes de confirmarlo.
     */
    public function dejariaSaldoNegativo(float $monto): bool
    {
        $turno = $this->abierto();

        return $turno !== null && $monto > $turno->efectivoEsperado();
    }
}
