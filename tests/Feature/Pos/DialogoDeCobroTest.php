<?php

namespace Tests\Feature\Pos;

use App\Enums\RolUsuario;
use App\Livewire\Pos\PosMain;
use App\Models\Etiqueta;
use App\Models\Negocio;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\TurnoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DialogoDeCobroTest extends TestCase
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
    // El carrito queda bloqueado
    // ------------------------------------------------------------------

    public function test_no_se_pueden_agregar_productos_durante_el_cobro(): void
    {
        $carritoEsperado = [$this->hamburguesa->id => ['cantidad' => 1, 'notas' => '']];

        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->call('addToCart', $this->hamburguesa->id)
            ->assertSet('cart', $carritoEsperado);
    }

    public function test_no_se_pueden_cambiar_cantidades_durante_el_cobro(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->call('updateQuantity', $this->hamburguesa->id, 'increase')
            ->assertSet('cart', [$this->hamburguesa->id => ['cantidad' => 1, 'notas' => '']]);
    }

    public function test_no_se_pueden_eliminar_productos_durante_el_cobro(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->call('removeFromCart', $this->hamburguesa->id)
            ->assertSet('cart', [$this->hamburguesa->id => ['cantidad' => 1, 'notas' => '']]);
    }

    public function test_no_se_puede_vaciar_el_carrito_durante_el_cobro(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->call('clearCart')
            ->assertSet('cart', [$this->hamburguesa->id => ['cantidad' => 1, 'notas' => '']]);
    }

    public function test_el_lector_de_codigos_tampoco_altera_el_carrito(): void
    {
        // El bloqueo es de servidor: una petición fabricada tampoco pasa.
        $this->hamburguesa->update(['codigo_barras' => '7700000001']);

        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->set('search', '7700000001')
            ->call('agregarPorCodigoDeBarras')
            ->assertSet('cart', [$this->hamburguesa->id => ['cantidad' => 1, 'notas' => '']]);
    }

    public function test_cancelar_devuelve_el_carrito_intacto(): void
    {
        $componente = Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->set('efectivoRecibido', '50000')
            ->call('cerrarCobro');

        $componente
            ->assertSet('cobrando', false)
            ->assertSet('efectivoRecibido', '')
            ->assertSet('cart', [$this->hamburguesa->id => ['cantidad' => 2, 'notas' => '']]);

        // Y tras cancelar el carrito vuelve a admitir cambios.
        $componente->call('addToCart', $this->hamburguesa->id)
            ->assertSet('cart', [$this->hamburguesa->id => ['cantidad' => 3, 'notas' => '']]);
    }

    public function test_no_se_abre_el_cobro_con_el_carrito_vacio(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('abrirCobro')
            ->assertSet('cobrando', false);
    }

    // ------------------------------------------------------------------
    // Efectivo insuficiente
    // ------------------------------------------------------------------

    public function test_no_se_puede_confirmar_con_efectivo_insuficiente(): void
    {
        $this->abrirTurno();

        $componente = Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->set('efectivoRecibido', '10000')
            ->call('confirmarCobro');

        $componente->assertSee('menor que el total')
            ->assertSet('cobrando', true);

        $this->assertSame(0, Pedido::query()->withoutGlobalScope('negocio')->count());
    }

    public function test_el_importe_justo_permite_confirmar(): void
    {
        $this->abrirTurno();

        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->set('efectivoRecibido', '17000')
            ->call('confirmarCobro')
            ->assertSet('cobrando', false);

        $this->assertSame(1, Pedido::query()->withoutGlobalScope('negocio')->count());
    }

    public function test_el_cambio_aparece_en_el_aviso_de_la_venta(): void
    {
        $this->abrirTurno();

        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->set('efectivoRecibido', '20000')
            ->call('confirmarCobro')
            ->assertSee('Cambio: $3.000');
    }

    // ------------------------------------------------------------------
    // Numpad
    // ------------------------------------------------------------------

    public function test_el_numpad_compone_el_importe(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->call('pulsar', '2')
            ->call('pulsar', '0')
            ->call('pulsar', '00')
            ->assertSet('efectivoRecibido', '2000');
    }

    public function test_el_numpad_borra_y_limpia(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->call('pulsar', '123')
            ->call('borrarDigito')
            ->assertSet('efectivoRecibido', '12')
            ->call('limpiarMonto')
            ->assertSet('efectivoRecibido', '');
    }

    public function test_el_atajo_exacto_pone_el_total(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->call('montoExacto')
            ->assertSet('efectivoRecibido', '34000');
    }

    // ------------------------------------------------------------------
    // Métodos de pago
    // ------------------------------------------------------------------

    public function test_los_metodos_electronicos_no_piden_importe(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->set('efectivoRecibido', '5000')
            ->call('seleccionarMetodo', 'tarjeta')
            // Se limpia porque con tarjeta el importe es exacto.
            ->assertSet('efectivoRecibido', '')
            ->call('confirmarCobro')
            ->assertSet('cobrando', false);

        $this->assertSame(
            'tarjeta',
            Pedido::query()->withoutGlobalScope('negocio')->firstOrFail()->metodo_pago
        );
    }

    public function test_un_metodo_desconocido_no_cambia_la_seleccion(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->hamburguesa->id)
            ->call('abrirCobro')
            ->call('seleccionarMetodo', 'bitcoin')
            ->assertSet('metodoPago', 'efectivo');
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
