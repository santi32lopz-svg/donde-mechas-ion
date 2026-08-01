<?php

namespace Tests\Feature\Pos;

use App\Enums\RolUsuario;
use App\Exceptions\VentaNoRegistrable;
use App\Livewire\Pos\PosMain;
use App\Models\Etiqueta;
use App\Models\Negocio;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\TurnoCaja;
use App\Models\User;
use App\Support\RegistroDeVenta;
use App\Support\SecuenciaDePedidos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistroDeVentaTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $mechas;

    private User $cajero;

    private Producto $hamburguesa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mechas = Negocio::create([
            'nombre' => 'Donde Mechas',
            'slug' => 'donde-mechas',
            'prefijo_pedido' => 'DM',
            'plan' => 'basico',
            'estado_suscripcion' => 'activo',
        ]);

        $etiqueta = Etiqueta::create([
            'negocio_id' => $this->mechas->id,
            'nombre' => 'Hamburguesas',
            'orden_visualizacion' => 1,
            'activo' => true,
        ]);

        $this->hamburguesa = Producto::create([
            'negocio_id' => $this->mechas->id,
            'etiqueta_id' => $etiqueta->id,
            'nombre' => 'Hamburguesa Especial',
            'precio' => 17000,
            'disponible' => true,
        ]);

        $this->cajero = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Cajero);
    }

    // ------------------------------------------------------------------
    // Registro
    // ------------------------------------------------------------------

    public function test_cobrar_registra_el_pedido_con_sus_lineas(): void
    {
        $this->abrirTurno();

        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('cobrar');

        $pedido = Pedido::query()->withoutGlobalScope('negocio')->firstOrFail();

        $this->assertSame($this->mechas->id, $pedido->negocio_id);
        $this->assertSame(1, $pedido->numero_secuencial);
        $this->assertSame('34000.00', $pedido->monto_total);
        $this->assertSame($this->cajero->id, $pedido->user_id);
        $this->assertSame(1, $pedido->detalles()->count());
        $this->assertSame(2, $pedido->detalles()->first()->cantidad);
    }

    public function test_el_carrito_queda_vacio_tras_cobrar(): void
    {
        $this->abrirTurno();

        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('cobrar')
            ->assertSet('cart', []);
    }

    public function test_la_linea_guarda_el_nombre_vendido(): void
    {
        // Renombrar el producto después no debe reescribir el histórico.
        $this->abrirTurno();

        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('cobrar');

        $this->hamburguesa->update(['nombre' => 'Hamburguesa Renombrada']);

        $detalle = Pedido::query()->withoutGlobalScope('negocio')->firstOrFail()->detalles()->first();

        $this->assertSame('Hamburguesa Especial', $detalle->nombre_producto);
    }

    public function test_el_precio_se_relee_de_la_base_no_del_navegador(): void
    {
        $this->abrirTurno();

        $componente = Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id);

        // Cambia el precio entre agregar y cobrar: debe cobrarse el vigente.
        $this->hamburguesa->update(['precio' => 25000]);

        $componente->call('cobrar');

        $this->assertSame(
            '25000.00',
            Pedido::query()->withoutGlobalScope('negocio')->firstOrFail()->monto_total
        );
    }

    public function test_no_se_puede_cobrar_un_carrito_vacio(): void
    {
        $this->abrirTurno();

        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('cobrar');

        $this->assertSame(0, Pedido::query()->withoutGlobalScope('negocio')->count());
    }

    // ------------------------------------------------------------------
    // Turno de caja
    // ------------------------------------------------------------------

    public function test_el_efectivo_exige_turno_de_caja_abierto(): void
    {
        $componente = Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('cobrar');

        $componente->assertSee('turno de caja abierto');
        $this->assertSame(0, Pedido::query()->withoutGlobalScope('negocio')->count());
        // El carrito se conserva para poder cobrar tras abrir la caja.
        $componente->assertSet('cart', [$this->hamburguesa->id => ['cantidad' => 1, 'notas' => '']]);
    }

    public function test_los_metodos_electronicos_no_exigen_turno(): void
    {
        // Un negocio pequeño que solo cobra por transferencia no debería estar
        // obligado a abrir caja.
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('cobrar', 'nequi');

        $pedido = Pedido::query()->withoutGlobalScope('negocio')->firstOrFail();

        $this->assertSame('nequi', $pedido->metodo_pago);
        $this->assertNull($pedido->turno_caja_id);
    }

    public function test_la_venta_en_efectivo_queda_ligada_al_turno(): void
    {
        $turno = $this->abrirTurno();

        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('cobrar');

        $pedido = Pedido::query()->withoutGlobalScope('negocio')->firstOrFail();

        $this->assertSame($turno->id, $pedido->turno_caja_id);
        // Y el saldo esperado del cajón lo refleja.
        $this->assertSame(117000.0, $turno->refresh()->saldoEsperado());
    }

    // ------------------------------------------------------------------
    // Producto que deja de estar disponible
    // ------------------------------------------------------------------

    public function test_un_producto_no_disponible_sigue_en_el_carrito(): void
    {
        // Antes se purgaba en silencio. Ahora se queda: lo más probable es que
        // el cliente ya lo tenga servido.
        $componente = Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id);

        $this->hamburguesa->update(['disponible' => false]);

        $componente->call('$refresh')
            ->assertSee('Ya no disponible')
            ->assertSet('cart', [$this->hamburguesa->id => ['cantidad' => 1, 'notas' => '']]);
    }

    public function test_se_puede_cobrar_un_producto_que_dejo_de_estar_disponible(): void
    {
        $this->abrirTurno();

        $componente = Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id);

        $this->hamburguesa->update(['disponible' => false]);

        $componente->call('cobrar');

        $detalle = Pedido::query()->withoutGlobalScope('negocio')->firstOrFail()->detalles()->first();

        $this->assertNotNull($detalle);
        // Y queda constancia para auditoría.
        $this->assertTrue((bool) $detalle->vendido_sin_disponibilidad);
    }

    public function test_un_producto_borrado_si_desaparece_del_carrito(): void
    {
        // Sin producto no hay precio con el que cobrar.
        $componente = Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id);

        $this->hamburguesa->delete();

        $componente->call('$refresh')->assertSet('cart', []);
    }

    // ------------------------------------------------------------------
    // Numeración y aislamiento
    // ------------------------------------------------------------------

    public function test_los_pedidos_usan_la_secuencia_del_negocio(): void
    {
        $this->abrirTurno();

        foreach ([1, 2, 3] as $esperado) {
            Livewire::actingAs($this->cajero)
                ->test(PosMain::class)
                ->call('addToCart', $this->hamburguesa->id)
                ->call('cobrar');
        }

        $numeros = Pedido::query()->withoutGlobalScope('negocio')
            ->orderBy('numero_secuencial')
            ->pluck('numero_secuencial')
            ->all();

        $this->assertSame([1, 2, 3], $numeros);
        $this->assertSame(3, app(SecuenciaDePedidos::class)->ultimoEntregado($this->mechas->id));
    }

    public function test_una_venta_fallida_no_consume_numero(): void
    {
        // Sin turno abierto el cobro en efectivo se rechaza antes de reservar.
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('cobrar');

        $this->assertSame(0, app(SecuenciaDePedidos::class)->ultimoEntregado($this->mechas->id));
    }

    public function test_un_metodo_de_pago_invalido_se_rechaza(): void
    {
        $this->expectException(VentaNoRegistrable::class);

        $this->actingAs($this->cajero);
        app(RegistroDeVenta::class)->registrar(
            [$this->hamburguesa->id => ['cantidad' => 1, 'notas' => '']],
            'bitcoin',
        );
    }

    private function abrirTurno(): TurnoCaja
    {
        return TurnoCaja::create([
            'negocio_id' => $this->mechas->id,
            'user_id' => $this->cajero->id,
            'monto_apertura' => 100000,
            'estado' => TurnoCaja::ABIERTO,
            'fecha_apertura' => now(),
        ]);
    }
}
