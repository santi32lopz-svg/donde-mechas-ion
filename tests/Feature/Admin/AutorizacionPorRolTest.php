<?php

namespace Tests\Feature\Admin;

use App\Enums\RolUsuario;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AutorizacionPorRolTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $mechas;

    private User $administrador;

    private User $cajero;

    /**
     * Todas las rutas administrativas. Se recorren enteras a propósito: basta
     * con olvidar el middleware en una para reabrir el agujero.
     *
     * @return array<int, string>
     */
    public static function rutasAdministrativas(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.mi-negocio'],
            ['admin.etiquetas'],
            ['admin.productos'],
            ['admin.usuarios'],
            ['admin.configuracion'],
            ['admin.pedidos'],
            ['admin.caja'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login');

        $this->mechas = Negocio::create([
            'nombre' => 'Donde Mechas',
            'slug' => 'donde-mechas',
            'plan' => 'basico',
            'estado_suscripcion' => 'activo',
        ]);

        $this->administrador = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Administrador, [
            'email' => 'admin@dondemechas.test',
        ]);

        $this->cajero = $this->crearUsuarioEnNegocio($this->mechas, RolUsuario::Cajero, [
            'email' => 'caja@dondemechas.test',
        ]);
    }

    #[DataProvider('rutasAdministrativas')]
    public function test_un_cajero_no_alcanza_las_rutas_administrativas(string $ruta): void
    {
        // El escenario real: escribir la URL a mano en la barra de direcciones.
        $this->actingAs($this->cajero)
            ->get(route($ruta))
            ->assertRedirect(route('pos.main'));
    }

    #[DataProvider('rutasAdministrativas')]
    public function test_un_administrador_alcanza_las_rutas_administrativas(string $ruta): void
    {
        $this->actingAs($this->administrador)
            ->get(route($ruta))
            ->assertOk();
    }

    public function test_el_superadministrador_alcanza_la_administracion(): void
    {
        $superadmin = User::create([
            'nombre' => 'Super Administrador',
            'email' => 'super@dondemechas.test',
            'password' => 'secret',
            'rol' => RolUsuario::Superadmin->value,
            'activo' => true,
        ]);

        // No tiene vínculo en el pivote: alcanza los negocios por su rol.
        $this->actingAs($superadmin);
        session()->put(\App\Support\TenantContext::CLAVE_SESION, $this->mechas->id);

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_el_cajero_si_puede_usar_la_terminal(): void
    {
        // La restricción no debe dejarle sin poder trabajar.
        $this->actingAs($this->cajero)->get(route('pos.main'))->assertOk();
    }

    public function test_el_rol_de_otro_negocio_no_da_acceso(): void
    {
        // Administradora en Pizza House, cajera en Donde Mechas: al trabajar
        // sobre Donde Mechas manda el rol de ESTE negocio.
        $pizza = Negocio::create([
            'nombre' => 'Pizza House',
            'slug' => 'pizza-house',
            'plan' => 'basico',
            'estado_suscripcion' => 'activo',
        ]);

        $this->cajero->negocios()->attach($pizza->id, [
            'rol' => RolUsuario::Administrador->value,
            'activo' => true,
        ]);

        $this->actingAs($this->cajero->refresh());
        session()->put(\App\Support\TenantContext::CLAVE_SESION, $this->mechas->id);

        $this->get(route('admin.productos'))->assertRedirect(route('pos.main'));
    }

    // ------------------------------------------------------------------
    // Límite de intentos de login
    // ------------------------------------------------------------------

    public function test_el_login_se_bloquea_tras_varios_intentos_fallidos(): void
    {
        for ($intento = 1; $intento <= 5; $intento++) {
            $this->post('/login', [
                'email' => 'admin@dondemechas.test',
                'password' => 'contrasena-incorrecta',
            ])->assertSessionHasErrors('email');
        }

        // El sexto ya no llega a comprobar credenciales.
        $this->post('/login', [
            'email' => 'admin@dondemechas.test',
            'password' => 'contrasena-incorrecta',
        ])->assertSessionHasErrors(['email' => 'Demasiados intentos fallidos. Espera un minuto antes de volver a intentarlo.']);

        $this->assertGuest();
    }

    public function test_el_bloqueo_no_afecta_a_otra_cuenta_del_mismo_local(): void
    {
        // La clave combina correo e IP: atacar una cuenta no debe dejar fuera a
        // todo el local, que suele salir por una única IP.
        $this->administrador->forceFill(['password' => Hash::make('secreto123')])->save();

        for ($intento = 1; $intento <= 6; $intento++) {
            $this->post('/login', [
                'email' => 'caja@dondemechas.test',
                'password' => 'contrasena-incorrecta',
            ]);
        }

        $this->post('/login', [
            'email' => 'admin@dondemechas.test',
            'password' => 'secreto123',
        ])->assertRedirect(route('rol.seleccionar'));

        $this->assertAuthenticated();
    }
}
