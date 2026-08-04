<?php

namespace App\Livewire\Pos;

use App\Exceptions\OperacionDeCajaNoPermitida;
use App\Support\GestionDeTurno;
use Livewire\Component;

/**
 * Franja de estado de la caja y sus operaciones, en la cabecera del POS.
 *
 * Se monta como hermano de PosMain y no dentro de él: las operaciones de caja
 * no tocan el carrito, así que no hay estado que sincronizar, y al no estar
 * anidado queda fuera del morphing de PosMain, que se dispara en cada tecla del
 * buscador.
 *
 * La franja muestra únicamente estado operativo: si la caja está abierta y con
 * qué base. Nada de efectivo esperado, ventas por método ni conteo de pedidos:
 * son agregaciones que se calcularían en cada render de la pantalla que tiene
 * que ser la más rápida del sistema. Ese detalle vive en el modal de cierre,
 * en el Dashboard y en el Panel Operativo.
 */
class ControlDeCaja extends Component
{
    // --- Apertura ---
    public bool $abriendoCaja = false;

    public string $base = '';

    public string $observacionesApertura = '';

    // --- Egreso ---
    public bool $registrandoEgreso = false;

    public string $descripcionEgreso = '';

    public string $montoEgreso = '';

    public string $tipoEgreso = 'otro';

    public ?string $mensajeExito = null;

    public ?string $mensajeError = null;

    // ------------------------------------------------------------------
    // Apertura de turno
    // ------------------------------------------------------------------

    public function abrirFormularioDeApertura(): void
    {
        $this->limpiarAvisos();
        $this->resetValidation();
        $this->observacionesApertura = '';

        // Se propone lo contado al cerrar el turno anterior; el usuario puede
        // cambiarlo antes de confirmar.
        $this->base = (string) (int) $this->turnos()->baseSugerida();

        $this->abriendoCaja = true;
    }

    public function cancelarApertura(): void
    {
        $this->abriendoCaja = false;
        $this->resetValidation();
    }

    public function abrirCaja(): void
    {
        $datos = $this->validate([
            'base' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'observacionesApertura' => ['nullable', 'string', 'max:500'],
        ], attributes: [
            'base' => 'base de caja',
            'observacionesApertura' => 'observaciones',
        ]);

        try {
            $turno = $this->turnos()->abrir(
                (float) $datos['base'],
                $datos['observacionesApertura'],
            );
        } catch (OperacionDeCajaNoPermitida $e) {
            $this->mensajeError = $e->getMessage();
            $this->abriendoCaja = false;

            return;
        }

        $this->abriendoCaja = false;
        $this->mensajeExito = sprintf(
            'Caja abierta con base de $%s. Ya puedes cobrar en efectivo.',
            number_format((float) $turno->monto_apertura, 0, ',', '.'),
        );
    }

    // ------------------------------------------------------------------
    // Salida de caja
    //
    // Operación distinta del cierre y nunca mezclada con él: sacar dinero
    // durante el turno es un movimiento financiero; cerrar es terminar el turno.
    // ------------------------------------------------------------------

    public function abrirFormularioDeEgreso(): void
    {
        $this->limpiarAvisos();
        $this->resetValidation();
        $this->reset(['descripcionEgreso', 'montoEgreso']);
        $this->tipoEgreso = 'otro';
        $this->registrandoEgreso = true;
    }

    public function cancelarEgreso(): void
    {
        $this->registrandoEgreso = false;
        $this->resetValidation();
    }

    public function registrarEgreso(): void
    {
        $datos = $this->validate([
            'descripcionEgreso' => ['required', 'string', 'min:3', 'max:255'],
            'montoEgreso' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'tipoEgreso' => ['required', 'string'],
        ], attributes: [
            'descripcionEgreso' => 'concepto',
            'montoEgreso' => 'monto',
        ]);

        try {
            $gasto = $this->turnos()->registrarEgreso(
                $datos['descripcionEgreso'],
                (float) $datos['montoEgreso'],
                $datos['tipoEgreso'],
            );
        } catch (OperacionDeCajaNoPermitida $e) {
            $this->mensajeError = $e->getMessage();
            $this->registrandoEgreso = false;

            return;
        }

        $this->registrandoEgreso = false;
        $this->mensajeExito = sprintf(
            'Salida registrada: %s por $%s.',
            $gasto->descripcion,
            number_format((float) $gasto->monto, 0, ',', '.'),
        );
    }

    private function limpiarAvisos(): void
    {
        $this->mensajeExito = null;
        $this->mensajeError = null;
    }

    private function turnos(): GestionDeTurno
    {
        return app(GestionDeTurno::class);
    }

    public function render()
    {
        $turno = $this->turnos()->abierto();

        return view('livewire.pos.control-de-caja', [
            'turno' => $turno,
            // Solo se calcula mientras el formulario de egreso está abierto:
            // fuera de ese momento nadie necesita el saldo en esta pantalla.
            'saldoNegativo' => $this->registrandoEgreso && $this->montoEgreso !== ''
                ? $this->turnos()->dejariaSaldoNegativo((float) $this->montoEgreso)
                : false,
            'tiposDeEgreso' => GestionDeTurno::TIPOS_DE_EGRESO,
        ]);
    }
}
