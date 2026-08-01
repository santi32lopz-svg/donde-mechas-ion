<?php

namespace Tests\Feature\Admin;

use App\Enums\RolUsuario;
use App\Livewire\Admin\Etiquetas;
use App\Livewire\Pos\PosMain;
use App\Models\Etiqueta;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EtiquetasCrudTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $mechas;

    private Negocio $ajeno;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mechas = $this->crearNegocio('Donde Mechas', 'donde-mechas');
        $this->ajeno = $this->crearNegocio('Pizza House', 'pizza-house');

        $this->admin = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador);
    }

    public function test_crear_una_etiqueta_la_asigna_al_negocio_activo(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Etiquetas\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Hamburguesas')
            ->set('ordenVisualizacion', 1)
            ->call('guardar')
            ->assertHasNoErrors();

        $etiqueta = Etiqueta::query()->where('nombre', 'Hamburguesas')->first();

        $this->assertNotNull($etiqueta);
        // El negocio no se pasa a mano: lo pone el scope global.
        $this->assertSame($this->mechas->id, $etiqueta->negocio_id);
    }

    public function test_la_etiqueta_creada_aparece_de_inmediato_en_el_pos(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Etiquetas\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Bebidas Frías')
            ->call('guardar');

        Livewire::actingAs($this->admin)
            ->test(PosMain::class)
            ->assertSee('Bebidas Frías');
    }

    public function test_una_etiqueta_oculta_no_sale_en_el_pos(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Etiquetas\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Temporada Navidad')
            ->set('activo', false)
            ->call('guardar');

        Livewire::actingAs($this->admin)
            ->test(PosMain::class)
            ->assertDontSee('Temporada Navidad');
    }

    public function test_editar_una_etiqueta_actualiza_su_nombre(): void
    {
        $etiqueta = $this->crearEtiqueta($this->mechas, 'Perros');

        Livewire::actingAs($this->admin)
            ->test(Etiquetas\Index::class)
            ->call('abrirFormulario', $etiqueta->id)
            ->set('nombre', 'Perros Calientes')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('Perros Calientes', $etiqueta->refresh()->nombre);
    }

    public function test_se_puede_eliminar_una_etiqueta_sin_productos(): void
    {
        $etiqueta = $this->crearEtiqueta($this->mechas, 'Vacía');

        Livewire::actingAs($this->admin)
            ->test(Etiquetas\Index::class)
            ->call('confirmarEliminacion', $etiqueta->id)
            ->call('eliminar');

        $this->assertDatabaseMissing('etiquetas', ['id' => $etiqueta->id]);
    }

    public function test_no_se_puede_eliminar_una_etiqueta_con_productos(): void
    {
        // El caso que motivó cambiar la clave foránea de CASCADE a RESTRICT:
        // antes, borrar la etiqueta se llevaba por delante sus productos.
        $etiqueta = $this->crearEtiqueta($this->mechas, 'Hamburguesas');

        Producto::create([
            'negocio_id' => $this->mechas->id,
            'etiqueta_id' => $etiqueta->id,
            'nombre' => 'Hamburguesa Sencilla',
            'precio' => 12500,
            'disponible' => true,
        ]);

        $componente = Livewire::actingAs($this->admin)
            ->test(Etiquetas\Index::class)
            ->call('confirmarEliminacion', $etiqueta->id)
            ->call('eliminar');

        $this->assertDatabaseHas('etiquetas', ['id' => $etiqueta->id]);
        $this->assertDatabaseHas('productos', ['nombre' => 'Hamburguesa Sencilla']);
        $componente->assertSee('1 producto asociado');
    }

    public function test_solo_se_ven_las_etiquetas_del_negocio_activo(): void
    {
        $this->crearEtiqueta($this->mechas, 'Hamburguesas');
        $this->crearEtiqueta($this->ajeno, 'Pizzas Napolitanas');

        Livewire::actingAs($this->admin)
            ->test(Etiquetas\Index::class)
            ->assertSee('Hamburguesas')
            ->assertDontSee('Pizzas Napolitanas');
    }

    public function test_no_se_puede_editar_una_etiqueta_de_otro_negocio(): void
    {
        $ajena = $this->crearEtiqueta($this->ajeno, 'Pizzas Napolitanas');

        Livewire::actingAs($this->admin)
            ->test(Etiquetas\Index::class)
            ->call('abrirFormulario', $ajena->id)
            // El scope global impide encontrarla, así que el formulario no se abre.
            ->assertSet('mostrandoFormulario', false)
            ->assertSet('etiquetaEnEdicion', null);
    }

    public function test_el_nombre_es_obligatorio(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Etiquetas\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', '')
            ->call('guardar')
            ->assertHasErrors(['nombre']);
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

    private function crearEtiqueta(Negocio $negocio, string $nombre): Etiqueta
    {
        return Etiqueta::create([
            'negocio_id' => $negocio->id,
            'nombre' => $nombre,
            'orden_visualizacion' => 1,
            'activo' => true,
        ]);
    }
}
