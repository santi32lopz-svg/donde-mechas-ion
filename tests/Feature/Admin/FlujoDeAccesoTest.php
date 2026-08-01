<?php

namespace Tests\Feature\Admin;

use App\Enums\RolUsuario;
use App\Livewire\Admin\MiNegocio;
use App\Livewire\Auth\SeleccionNegocio;
use App\Livewire\Auth\SeleccionRol;
use App\Models\Negocio;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FlujoDeAccesoTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $mechas;

    private Negocio $pizzaHouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mechas = $this->crearNegocio('Donde Mechas', 'donde-mechas');
        $this->pizzaHouse = $this->crearNegocio('Pizza House', 'pizza-house');
    }

    public function test_el_login_lleva_a_la_seleccion_de_rol(): void
    {
        // El destino ya no es el POS: primero se elige con qué rol se entra.
        $usuario = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador, [
            'email' => 'admin@dondemechas.test',
            'password' => bcrypt('secret123'),
        ]);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'secret123',
        ])->assertRedirect(route('rol.seleccionar'));
    }

    public function test_el_superadmin_no_recibe_ningun_negocio_por_defecto(): void
    {
        $superadmin = $this->crearSuperadmin();

        $this->actingAs($superadmin);

        $this->assertNull(app(TenantContext::class)->negocioId());
    }

    public function test_el_superadmin_es_enviado_a_elegir_negocio(): void
    {
        $this->actingAs($this->crearSuperadmin())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('negocio.seleccionar'));
    }

    public function test_el_superadmin_ve_todos_los_negocios_para_elegir(): void
    {
        Livewire::actingAs($this->crearSuperadmin())
            ->test(SeleccionNegocio::class)
            ->assertSee('Donde Mechas')
            ->assertSee('Pizza House');
    }

    public function test_al_elegir_negocio_queda_activo_y_se_entra_al_panel(): void
    {
        Livewire::actingAs($this->crearSuperadmin())
            ->test(SeleccionNegocio::class)
            ->call('elegir', $this->pizzaHouse->id)
            ->assertRedirect(route('pos.main'));

        $this->assertSame($this->pizzaHouse->id, session(TenantContext::CLAVE_SESION));
    }

    public function test_no_se_puede_activar_un_negocio_ajeno(): void
    {
        // Un cajero de Donde Mechas no debe poder saltar a Pizza House.
        $cajero = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Cajero);

        Livewire::actingAs($cajero)
            ->test(SeleccionNegocio::class)
            ->call('elegir', $this->pizzaHouse->id);

        $this->assertNotSame($this->pizzaHouse->id, session(TenantContext::CLAVE_SESION));
    }

    public function test_la_ruta_de_activacion_rechaza_negocios_ajenos(): void
    {
        $cajero = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Cajero);

        $this->actingAs($cajero)
            ->get(route('negocio.activar', $this->pizzaHouse))
            ->assertForbidden();
    }

    public function test_un_cajero_con_un_solo_negocio_entra_directo_al_pos(): void
    {
        $cajero = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Cajero);

        Livewire::actingAs($cajero)
            ->test(SeleccionRol::class)
            ->assertRedirect(route('pos.main'));
    }

    public function test_un_usuario_con_dos_roles_ve_la_pantalla_de_seleccion(): void
    {
        $usuario = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador);
        $usuario->negocios()->attach($this->pizzaHouse->id, [
            'rol' => RolUsuario::Cajero->value,
            'activo' => true,
        ]);

        Livewire::actingAs($usuario->refresh())
            ->test(SeleccionRol::class)
            ->assertSee('Administrador')
            ->assertSee('Cajero');
    }

    public function test_crear_un_negocio_lo_deja_disponible_de_inmediato(): void
    {
        $admin = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador);

        Livewire::actingAs($admin)
            ->test(MiNegocio\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Bar El Tapitas')
            ->set('descripcion', 'Gastrobar nocturno')
            ->set('estado', 'activo')
            ->call('guardar')
            ->assertHasNoErrors();

        $creado = Negocio::query()->where('nombre', 'Bar El Tapitas')->first();

        $this->assertNotNull($creado);
        $this->assertSame('bar-el-tapitas', $creado->slug);
        // Quien lo crea queda como administrador para poder trabajarlo.
        $this->assertTrue($admin->refresh()->puedeAccederA($creado->id));
    }

    public function test_dos_negocios_con_el_mismo_nombre_no_chocan_de_slug(): void
    {
        $admin = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador);

        Livewire::actingAs($admin)
            ->test(MiNegocio\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Donde Mechas')
            ->set('estado', 'activo')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('negocios', ['slug' => 'donde-mechas-2']);
    }

    public function test_el_nombre_del_negocio_es_obligatorio(): void
    {
        $admin = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador);

        Livewire::actingAs($admin)
            ->test(MiNegocio\Index::class)
            ->call('abrirFormulario')
            ->set('nombre', '')
            ->call('guardar')
            ->assertHasErrors(['nombre']);
    }

    public function test_un_administrador_solo_ve_sus_negocios_en_mi_negocio(): void
    {
        $admin = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador);

        Livewire::actingAs($admin)
            ->test(MiNegocio\Index::class)
            ->assertSee('Donde Mechas')
            ->assertDontSee('Pizza House');
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

    private function crearSuperadmin(): User
    {
        return User::create([
            'nombre' => 'Super Administrador',
            'email' => 'super@dondemechas.test',
            'password' => 'secret',
            'rol' => RolUsuario::Superadmin->value,
            'activo' => true,
        ]);
    }
}
