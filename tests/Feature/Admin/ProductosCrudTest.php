<?php

namespace Tests\Feature\Admin;

use App\Enums\RolUsuario;
use App\Livewire\Admin\Productos;
use App\Livewire\Pos\PosMain;
use App\Models\DetallePedido;
use App\Models\Etiqueta;
use App\Models\Negocio;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductosCrudTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $mechas;

    private Negocio $ajeno;

    private User $admin;

    private Etiqueta $hamburguesas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mechas = $this->crearNegocio('Donde Mechas', 'donde-mechas');
        $this->ajeno = $this->crearNegocio('Pizza House', 'pizza-house');

        $this->hamburguesas = $this->crearEtiqueta($this->mechas, 'Hamburguesas');
        $this->admin = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador);
    }

    public function test_crear_un_producto_lo_asigna_al_negocio_activo(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Hamburguesa Especial')
            ->set('precio', '17000')
            ->set('etiquetaId', $this->hamburguesas->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $producto = Producto::query()->where('nombre', 'Hamburguesa Especial')->first();

        $this->assertNotNull($producto);
        $this->assertSame($this->mechas->id, $producto->negocio_id);
        $this->assertSame($this->hamburguesas->id, $producto->etiqueta_id);
    }

    public function test_el_producto_creado_aparece_en_el_pos(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Combo Trifásico')
            ->set('precio', '28310')
            ->set('etiquetaId', $this->hamburguesas->id)
            ->call('guardar');

        Livewire::actingAs($this->admin)
            ->test(PosMain::class)
            ->assertSee('Combo Trifásico');
    }

    // ------------------------------------------------------------------
    // Diálogo rápido de etiqueta
    // ------------------------------------------------------------------

    public function test_crear_una_etiqueta_desde_el_formulario_no_pierde_lo_escrito(): void
    {
        // El requisito central de la etapa: el usuario está a medias de crear un
        // producto, no hay etiqueta, la crea sin salir y sigue donde estaba.
        $componente = Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Salchipapa Especial')
            ->set('precio', '20000')
            ->set('codigoBarras', '7700000123')
            ->call('abrirModalEtiqueta')
            ->set('nuevaEtiquetaNombre', 'Salchipapas')
            ->call('guardarEtiqueta');

        $nueva = Etiqueta::query()->where('nombre', 'Salchipapas')->firstOrFail();

        $componente
            ->assertSet('mostrandoModalEtiqueta', false)   // el modal se cierra
            ->assertSet('etiquetaId', $nueva->id)          // queda seleccionada
            ->assertSet('nombre', 'Salchipapa Especial')   // y nada se perdió
            ->assertSet('precio', '20000')
            ->assertSet('codigoBarras', '7700000123')
            ->assertSet('mostrandoFormulario', true);
    }

    public function test_se_puede_completar_el_producto_tras_crear_la_etiqueta(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Empanada de Queso')
            ->set('precio', '3500')
            ->call('abrirModalEtiqueta')
            ->set('nuevaEtiquetaNombre', 'Empanadas')
            ->call('guardarEtiqueta')
            ->call('guardar')
            ->assertHasNoErrors();

        $producto = Producto::query()->where('nombre', 'Empanada de Queso')->firstOrFail();

        $this->assertSame('Empanadas', $producto->etiqueta->nombre);
    }

    public function test_la_etiqueta_creada_desde_el_modal_pertenece_al_negocio_activo(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario')
            ->call('abrirModalEtiqueta')
            ->set('nuevaEtiquetaNombre', 'Bebidas')
            ->call('guardarEtiqueta');

        $etiqueta = Etiqueta::query()->where('nombre', 'Bebidas')->firstOrFail();

        $this->assertSame($this->mechas->id, $etiqueta->negocio_id);
    }

    public function test_el_nombre_de_la_etiqueta_rapida_es_obligatorio(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario')
            ->call('abrirModalEtiqueta')
            ->set('nuevaEtiquetaNombre', '')
            ->call('guardarEtiqueta')
            ->assertHasErrors(['nuevaEtiquetaNombre'])
            ->assertSet('mostrandoModalEtiqueta', true);
    }

    // ------------------------------------------------------------------
    // Edición, borrado y disponibilidad
    // ------------------------------------------------------------------

    public function test_editar_cambia_precio_y_etiqueta(): void
    {
        $producto = $this->crearProducto('Hamburguesa Sencilla', 12500);
        $bebidas = $this->crearEtiqueta($this->mechas, 'Bebidas');

        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario', $producto->id)
            ->set('precio', '13500')
            ->set('etiquetaId', $bebidas->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $producto->refresh();

        $this->assertSame('13500.00', $producto->precio);
        $this->assertSame($bebidas->id, $producto->etiqueta_id);
    }

    public function test_alternar_disponibilidad_lo_retira_del_pos(): void
    {
        $producto = $this->crearProducto('Perra Paisa', 15000);

        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('alternarDisponibilidad', $producto->id);

        $this->assertFalse($producto->refresh()->disponible);

        Livewire::actingAs($this->admin)
            ->test(PosMain::class)
            ->assertDontSee('Perra Paisa');
    }

    public function test_se_puede_eliminar_un_producto_sin_ventas(): void
    {
        $producto = $this->crearProducto('Producto Suelto', 5000);

        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('confirmarEliminacion', $producto->id)
            ->call('eliminar');

        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
    }

    public function test_no_se_puede_eliminar_un_producto_ya_vendido(): void
    {
        // detalle_pedidos apunta a productos con NO ACTION: borrarlo lanzaría un
        // error de integridad crudo y además falsearía el histórico de ventas.
        $producto = $this->crearProducto('Hamburguesa Vendida', 17000);

        $pedido = Pedido::create([
            'negocio_id' => $this->mechas->id,
            'user_id' => $this->admin->id,
            'numero_pedido' => 'TEST-0001',
            'estado' => 'completado',
            'metodo_pago' => 'efectivo',
            'monto_total' => 17000,
        ]);

        DetallePedido::create([
            'negocio_id' => $this->mechas->id,
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 17000,
            'subtotal' => 17000,
        ]);

        $componente = Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('confirmarEliminacion', $producto->id)
            ->call('eliminar');

        $this->assertDatabaseHas('productos', ['id' => $producto->id]);
        $componente->assertSee('ya aparece en pedidos registrados');
    }

    // ------------------------------------------------------------------
    // Aislamiento y validación
    // ------------------------------------------------------------------

    public function test_solo_se_ven_productos_del_negocio_activo(): void
    {
        $this->crearProducto('Hamburguesa Especial', 17000);

        $etiquetaAjena = $this->crearEtiqueta($this->ajeno, 'Pizzas');
        Producto::create([
            'negocio_id' => $this->ajeno->id,
            'etiqueta_id' => $etiquetaAjena->id,
            'nombre' => 'Pizza Napolitana',
            'precio' => 30000,
            'disponible' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->assertSee('Hamburguesa Especial')
            ->assertDontSee('Pizza Napolitana');
    }

    public function test_no_se_puede_asignar_una_etiqueta_de_otro_negocio(): void
    {
        // El id llega del navegador, así que se valida contra la consulta con
        // scope y no con un exists a secas sobre la tabla.
        $etiquetaAjena = $this->crearEtiqueta($this->ajeno, 'Pizzas');

        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Producto Infiltrado')
            ->set('precio', '1000')
            ->set('etiquetaId', $etiquetaAjena->id)
            ->call('guardar')
            ->assertHasErrors(['etiquetaId']);

        $this->assertDatabaseMissing('productos', ['nombre' => 'Producto Infiltrado']);
    }

    public function test_el_codigo_de_barras_no_se_repite_dentro_del_negocio(): void
    {
        $this->crearProducto('Primero', 1000, '7700000001');

        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Segundo')
            ->set('precio', '2000')
            ->set('etiquetaId', $this->hamburguesas->id)
            ->set('codigoBarras', '7700000001')
            ->call('guardar')
            ->assertHasErrors(['codigoBarras']);
    }

    public function test_el_buscador_del_listado_filtra_por_nombre(): void
    {
        $this->crearProducto('Hamburguesa Especial', 17000);
        $this->crearProducto('Jugo Natural', 5000);

        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->set('busqueda', 'hamb')
            ->assertSee('Hamburguesa Especial')
            ->assertDontSee('Jugo Natural');
    }

    public function test_el_precio_es_obligatorio(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Productos\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Sin Precio')
            ->set('etiquetaId', $this->hamburguesas->id)
            ->set('precio', '')
            ->call('guardar')
            ->assertHasErrors(['precio']);
    }

    // ------------------------------------------------------------------

    private function crearNegocio(string $nombre, string $slug): Negocio
    {
        return Negocio::create([
            'nombre' => $nombre,
            'slug' => $slug,
            'plan' => 'basico',
            'estado_suscripcion' => 'activo',
        ]);
    }

    private function crearEtiqueta(Negocio $negocio, string $nombre): Etiqueta
    {
        return Etiqueta::create([
            'negocio_id' => $negocio->id,
            'nombre' => $nombre,
            'orden_visualizacion' => 1,
            'activo' => true,
        ]);
    }

    private function crearProducto(string $nombre, int $precio, ?string $codigo = null): Producto
    {
        return Producto::create([
            'negocio_id' => $this->mechas->id,
            'etiqueta_id' => $this->hamburguesas->id,
            'nombre' => $nombre,
            'precio' => $precio,
            'codigo_barras' => $codigo,
            'disponible' => true,
        ]);
    }
}
