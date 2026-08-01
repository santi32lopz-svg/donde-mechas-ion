<?php

namespace Tests\Feature\Admin;

use App\Enums\RolUsuario;
use App\Livewire\Admin\Configuracion;
use App\Livewire\Admin\Usuarios;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsuariosCrudTest extends TestCase
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

        $this->admin = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador, [
            'nombre' => 'Administrador Mechas',
            'email' => 'admin@dondemechas.test',
        ]);
    }

    // ------------------------------------------------------------------
    // Alta
    // ------------------------------------------------------------------

    public function test_anadir_un_correo_nuevo_crea_la_cuenta_y_la_vincula(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->call('abrirFormulario')
            ->set('email', 'nuevo@dondemechas.test')
            ->set('nombre', 'Cajero Nuevo')
            ->set('password', 'contrasena123')
            ->set('rol', RolUsuario::Cajero->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $usuario = User::query()->where('email', 'nuevo@dondemechas.test')->first();

        $this->assertNotNull($usuario);
        $this->assertTrue($usuario->puedeAccederA($this->mechas->id));
        $this->assertSame(RolUsuario::Cajero, $usuario->rolEn($this->mechas->id));
    }

    public function test_anadir_un_correo_existente_lo_vincula_sin_crear_otra_cuenta(): void
    {
        // Alguien que ya trabaja en otro negocio no debería necesitar una cuenta
        // nueva para entrar a este.
        $existente = $this->crearUsuarioEnNegocio($this->ajeno, RolUsuario::Cajero, [
            'nombre' => 'Persona Compartida',
            'email' => 'compartida@plataforma.test',
        ]);

        Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->call('abrirFormulario')
            ->set('email', 'compartida@plataforma.test')
            ->set('rol', RolUsuario::Administrador->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(1, User::query()->where('email', 'compartida@plataforma.test')->count());

        $existente->refresh();
        // Administradora aquí, cajera allí: el rol es por negocio.
        $this->assertSame(RolUsuario::Administrador, $existente->rolEn($this->mechas->id));
        $this->assertSame(RolUsuario::Cajero, $existente->rolEn($this->ajeno->id));
    }

    public function test_no_se_puede_anadir_dos_veces_a_la_misma_persona(): void
    {
        $componente = Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->call('abrirFormulario')
            ->set('email', $this->admin->email)
            ->set('rol', RolUsuario::Cajero->value)
            ->call('guardar');

        $componente->assertSee('ya forma parte de este negocio');
    }

    public function test_una_cuenta_nueva_exige_nombre_y_contrasena(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->call('abrirFormulario')
            ->set('email', 'sindatos@dondemechas.test')
            ->set('rol', RolUsuario::Cajero->value)
            ->call('guardar')
            ->assertHasErrors(['nombre', 'password']);
    }

    // ------------------------------------------------------------------
    // Salvaguardas del último administrador
    // ------------------------------------------------------------------

    public function test_no_se_puede_degradar_al_ultimo_administrador(): void
    {
        // Sin esta salvaguarda el negocio se queda sin nadie que pueda gestionarlo.
        $otroAdmin = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador, [
            'email' => 'otroadmin@dondemechas.test',
        ]);

        // Con dos administradores sí se puede degradar a uno.
        Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->call('abrirFormulario', $otroAdmin->id)
            ->set('rol', RolUsuario::Cajero->value)
            ->call('guardar');

        $this->assertSame(RolUsuario::Cajero, $otroAdmin->refresh()->rolEn($this->mechas->id));

        // Pero al que queda, no.
        $componente = Livewire::actingAs($otroAdmin)
            ->test(Usuarios\Index::class)
            ->call('abrirFormulario', $this->admin->id)
            ->set('rol', RolUsuario::Cajero->value)
            ->call('guardar');

        $componente->assertSee('se quedaría sin nadie que pueda gestionarlo');
        $this->assertSame(RolUsuario::Administrador, $this->admin->refresh()->rolEn($this->mechas->id));
    }

    public function test_no_se_puede_desvincular_al_ultimo_administrador(): void
    {
        $cajero = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Cajero, [
            'email' => 'cajero@dondemechas.test',
        ]);

        $componente = Livewire::actingAs($cajero)
            ->test(Usuarios\Index::class)
            ->call('confirmarDesvinculacion', $this->admin->id)
            ->call('desvincular');

        $componente->assertSee('último administrador');
        $this->assertTrue($this->admin->refresh()->puedeAccederA($this->mechas->id));
    }

    public function test_no_se_puede_suspender_el_propio_acceso(): void
    {
        $componente = Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->call('alternarAcceso', $this->admin->id);

        $componente->assertSee('No puedes suspender tu propio acceso');
    }

    public function test_no_se_puede_desvincular_a_uno_mismo(): void
    {
        $otroAdmin = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador, [
            'email' => 'otro@dondemechas.test',
        ]);

        $componente = Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->call('confirmarDesvinculacion', $this->admin->id)
            ->call('desvincular');

        $componente->assertSee('No puedes desvincularte a ti mismo');
        $this->assertTrue($this->admin->refresh()->puedeAccederA($this->mechas->id));
    }

    // ------------------------------------------------------------------
    // Acceso y aislamiento
    // ------------------------------------------------------------------

    public function test_suspender_el_acceso_impide_entrar_al_negocio(): void
    {
        $cajero = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Cajero, [
            'email' => 'suspendido@dondemechas.test',
        ]);

        Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->call('alternarAcceso', $cajero->id);

        $this->assertFalse($cajero->refresh()->puedeAccederA($this->mechas->id));
    }

    public function test_desvincular_no_borra_la_cuenta(): void
    {
        // La persona puede seguir trabajando en otros negocios.
        $compartida = $this->crearUsuarioEnNegocio($this->ajeno, RolUsuario::Cajero, [
            'email' => 'compartida@plataforma.test',
        ]);
        $compartida->negocios()->attach($this->mechas->id, [
            'rol' => RolUsuario::Cajero->value,
            'activo' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->call('confirmarDesvinculacion', $compartida->id)
            ->call('desvincular');

        $this->assertDatabaseHas('users', ['id' => $compartida->id]);
        $this->assertFalse($compartida->refresh()->puedeAccederA($this->mechas->id));
        $this->assertTrue($compartida->puedeAccederA($this->ajeno->id));
    }

    public function test_solo_se_ven_los_usuarios_del_negocio_activo(): void
    {
        $this->crearUsuarioEnNegocio($this->ajeno, RolUsuario::Cajero, [
            'nombre' => 'Cajero De Pizza House',
            'email' => 'ajeno@pizzahouse.test',
        ]);

        Livewire::actingAs($this->admin)
            ->test(Usuarios\Index::class)
            ->assertSee('Administrador Mechas')
            ->assertDontSee('Cajero De Pizza House');
    }

    // ------------------------------------------------------------------
    // Configuración
    // ------------------------------------------------------------------

    public function test_configuracion_guarda_los_datos_del_negocio(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Configuracion::class)
            ->set('nombre', 'Donde Mechas Centro')
            ->set('nitRut', '900123456-7')
            ->set('telefono', '3001234567')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->mechas->refresh();

        $this->assertSame('Donde Mechas Centro', $this->mechas->nombre);
        $this->assertSame('900123456-7', $this->mechas->nit_rut);
    }

    public function test_renombrar_el_negocio_no_cambia_su_identificador(): void
    {
        // El slug alimenta URLs: renombrar no debe romper enlaces compartidos.
        Livewire::actingAs($this->admin)
            ->test(Configuracion::class)
            ->set('nombre', 'Otro Nombre Completamente Distinto')
            ->call('guardar');

        $this->assertSame('donde-mechas', $this->mechas->refresh()->slug);
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
}
