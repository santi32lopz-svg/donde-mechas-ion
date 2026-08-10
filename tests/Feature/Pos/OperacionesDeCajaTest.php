<?php

namespace Tests\Feature\Pos;

use App\Enums\RolUsuario;
use App\Exceptions\OperacionDeCajaNoPermitida;
use App\Livewire\Pos\ControlDeCaja;
use App\Models\GastoCaja;
use App\Models\Negocio;
use App\Models\TurnoCaja;
use App\Models\User;
use App\Support\GestionDeTurno;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperacionesDeCajaTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $mechas;

    private Negocio $pizzaHouse;

    private User $cajero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mechas = $this->crearNegocio('Donde Mechas', 'donde-mechas');
        $this->pizzaHouse = $this->crearNegocio('Pizza House', 'pizza-house');

        $this->cajero = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Cajero);
    }

    // ------------------------------------------------------------------
    // Apertura
    // ------------------------------------------------------------------

    public function test_abrir_caja_crea_el_turno_con_trazabilidad(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeApertura')
            ->set('base', '120000')
            ->set('observacionesApertura', 'Faltaban monedas de $500')
            ->call('abrirCaja')
            ->assertHasNoErrors()
            ->assertSet('abriendoCaja', false);

        $turno = TurnoCaja::query()->withoutGlobalScope('negocio')->firstOrFail();

        // Nunca un movimiento anónimo: usuario, negocio, momento y motivo.
        $this->assertSame($this->cajero->id, $turno->user_id);
        $this->assertSame($this->mechas->id, $turno->negocio_id);
        $this->assertNotNull($turno->fecha_apertura);
        $this->assertSame('Faltaban monedas de $500', $turno->observaciones_apertura);
        $this->assertSame(TurnoCaja::ABIERTO, $turno->estado);
    }

    public function test_la_base_propuesta_es_lo_contado_al_cerrar_el_turno_anterior(): void
    {
        TurnoCaja::create([
            'negocio_id' => $this->mechas->id,
            'user_id' => $this->cajero->id,
            'monto_apertura' => 100000,
            'monto_cierre' => 187500,
            'estado' => TurnoCaja::CERRADO,
            'fecha_apertura' => now()->subDay(),
            'fecha_cierre' => now()->subHours(12),
        ]);

        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeApertura')
            ->assertSet('base', '187500');
    }

    public function test_sin_turno_anterior_la_base_propuesta_es_cero(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeApertura')
            ->assertSet('base', '0');
    }

    public function test_no_se_pueden_abrir_dos_turnos_en_el_mismo_negocio(): void
    {
        $this->abrirTurno($this->mechas);

        $componente = Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeApertura')
            ->set('base', '50000')
            ->call('abrirCaja');

        $componente->assertSee('un solo cajón');
        $this->assertSame(1, TurnoCaja::query()->withoutGlobalScope('negocio')->count());
    }

    public function test_la_base_de_datos_impide_dos_turnos_abiertos(): void
    {
        // Última red: aunque la comprobación de PHP fallara por dos peticiones
        // simultáneas, el índice único parcial lo rechaza.
        $this->abrirTurno($this->mechas);

        $this->expectException(QueryException::class);

        TurnoCaja::create([
            'negocio_id' => $this->mechas->id,
            'user_id' => $this->cajero->id,
            'monto_apertura' => 50000,
            'estado' => TurnoCaja::ABIERTO,
            'fecha_apertura' => now(),
        ]);
    }

    public function test_dos_negocios_pueden_tener_turno_abierto_a_la_vez(): void
    {
        $this->abrirTurno($this->mechas);
        $this->abrirTurno($this->pizzaHouse);

        $this->assertSame(2, TurnoCaja::query()->withoutGlobalScope('negocio')->count());
    }

    public function test_la_base_no_puede_ser_negativa(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeApertura')
            ->set('base', '-5000')
            ->call('abrirCaja')
            ->assertHasErrors(['base']);
    }

    // ------------------------------------------------------------------
    // Salida de caja
    // ------------------------------------------------------------------

    public function test_registrar_un_egreso_deja_trazabilidad_completa(): void
    {
        $turno = $this->abrirTurno($this->mechas);

        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeEgreso')
            ->set('tipoEgreso', 'domiciliario')
            ->set('descripcionEgreso', 'Pago domiciliario turno noche')
            ->set('montoEgreso', '20000')
            ->call('registrarEgreso')
            ->assertHasNoErrors();

        $gasto = GastoCaja::query()->withoutGlobalScope('negocio')->firstOrFail();

        $this->assertSame($this->cajero->id, $gasto->user_id);
        $this->assertSame($this->mechas->id, $gasto->negocio_id);
        $this->assertSame($turno->id, $gasto->turno_caja_id);
        $this->assertSame('domiciliario', $gasto->tipo);
        $this->assertNotNull($gasto->created_at);
    }

    public function test_el_egreso_reduce_el_saldo_esperado(): void
    {
        $turno = $this->abrirTurno($this->mechas, 100000);

        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeEgreso')
            ->set('descripcionEgreso', 'Compra de servilletas')
            ->set('montoEgreso', '15000')
            ->call('registrarEgreso');

        $this->assertSame(85000.0, $turno->refresh()->efectivoEsperado());
    }

    public function test_un_egreso_puede_dejar_la_caja_en_negativo(): void
    {
        // Se permite a propósito: el dinero pudo entrar por una vía que el
        // sistema todavía no conoce. Registrar la realidad importa más.
        $turno = $this->abrirTurno($this->mechas, 10000);

        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeEgreso')
            ->set('descripcionEgreso', 'Pago urgente a proveedor')
            ->set('montoEgreso', '50000')
            ->call('registrarEgreso')
            ->assertHasNoErrors();

        $this->assertSame(-40000.0, $turno->refresh()->efectivoEsperado());
    }

    public function test_se_avisa_antes_de_dejar_la_caja_en_negativo(): void
    {
        $this->abrirTurno($this->mechas, 10000);

        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeEgreso')
            ->set('montoEgreso', '50000')
            ->assertSee('la caja quedará en negativo');
    }

    public function test_no_se_puede_registrar_un_egreso_sin_turno_abierto(): void
    {
        $this->actingAs($this->cajero);

        $this->expectException(OperacionDeCajaNoPermitida::class);

        app(GestionDeTurno::class)->registrarEgreso('Sin caja', 1000);
    }

    public function test_el_monto_del_egreso_debe_ser_positivo(): void
    {
        $this->abrirTurno($this->mechas);

        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeEgreso')
            ->set('descripcionEgreso', 'Prueba')
            ->set('montoEgreso', '0')
            ->call('registrarEgreso')
            ->assertHasErrors(['montoEgreso']);
    }

    // ------------------------------------------------------------------
    // Aislamiento
    // ------------------------------------------------------------------

    public function test_el_turno_de_otro_negocio_no_es_visible(): void
    {
        $this->abrirTurno($this->pizzaHouse);

        $this->actingAs($this->cajero);

        $this->assertNull(app(GestionDeTurno::class)->abierto());
    }

    public function test_la_franja_ofrece_abrir_caja_cuando_no_hay_turno(): void
    {
        // La interfaz dice qué hacer, no solo qué falta.
        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->assertSee('Abrir Caja');
    }

    public function test_la_franja_muestra_el_efectivo_del_cajon(): void
    {
        // No la base con la que se abrió: mientras se vende lo que importa es
        // cuánto dinero hay ahora en el cajón.
        $turno = $this->abrirTurno($this->mechas, 120000);

        Livewire::actingAs($this->cajero)
            ->test(ControlDeCaja::class)
            ->call('abrirFormularioDeEgreso')
            ->set('descripcionEgreso', 'Pago domiciliario')
            ->set('montoEgreso', '20000')
            ->call('registrarEgreso')
            ->assertSee('EFECTIVO EN CAJA')
            ->assertSee('100.000');

        $this->assertSame(100000.0, $turno->refresh()->efectivoEsperado());
    }

    private function crearNegocio(string $nombre, string $slug): Negocio
    {
        return Negocio::create([
            'nombre' => $nombre,
            'slug' => $slug,
            'plan' => 'basico',
            'estado_suscripcion' => 'activo',
        ]);
    }

    private function abrirTurno(Negocio $negocio, float $base = 100000): TurnoCaja
    {
        return app(TenantContext::class)->sinAislamiento(fn () => TurnoCaja::create([
            'negocio_id' => $negocio->id,
            'user_id' => $this->cajero->id,
            'monto_apertura' => $base,
            'estado' => TurnoCaja::ABIERTO,
            'fecha_apertura' => now(),
        ]));
    }
}
