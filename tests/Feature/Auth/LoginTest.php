<?php

namespace Tests\Feature\Auth;

use App\Enums\RolUsuario;
use App\Models\Negocio;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\MenuInicialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials_and_active_business(): void
    {
        $this->seed(MenuInicialSeeder::class);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@dondemechas.com',
            'password' => 'admin123',
        ]);

        // El login ya no entra al POS: primero se elige con qué rol se trabaja,
        // porque un usuario puede administrar un negocio y ser cajero de otro.
        $response->assertRedirect(route('rol.seleccionar'));
        $this->assertAuthenticated();

        // No se compara contra un id fijo: la secuencia de PostgreSQL avanza
        // entre pruebas y "1" solo acertaba por casualidad.
        $negocio = User::query()
            ->where('email', 'admin@dondemechas.com')
            ->firstOrFail()
            ->negociosDisponibles()
            ->firstOrFail();

        $this->assertEquals($negocio->id, session('tenant_id'));
        $this->assertEquals('Donde Mechas', session('tenant_nombre'));
    }

    public function test_user_can_open_pos_page_and_see_order_content(): void
    {
        $this->seed(MenuInicialSeeder::class);

        $user = User::query()->where('email', 'admin@dondemechas.com')->firstOrFail();

        $response = $this->actingAs($user)->get('/pos');

        $response->assertOk();
        $response->assertSee('Pedido Activo');
        // Se afirma sobre la propiedad enlazada y no sobre el placeholder,
        // que es texto de interfaz y cambia con cualquier ajuste de copy.
        $response->assertSee('wire:model.live.debounce.300ms="search"', false);
    }

    public function test_user_cannot_login_when_business_is_not_active(): void
    {
        $this->seed(MenuInicialSeeder::class);

        $user = User::query()->where('email', 'admin@dondemechas.com')->firstOrFail();
        DB::table('negocios')
            ->whereIn('id', $user->negocios()->pluck('negocios.id'))
            ->update(['estado_suscripcion' => 'suspendido']);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@dondemechas.com',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_user_cannot_login_without_any_business(): void
    {
        // Regla nueva de la plataforma: sin al menos un negocio activo no se
        // entra, aunque las credenciales sean correctas.
        User::create([
            'nombre' => 'Usuario Sin Negocio',
            'email' => 'huerfano@dondemechas.com',
            'password' => Hash::make('secret123'),
            'rol' => RolUsuario::Cajero->value,
            'activo' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'huerfano@dondemechas.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_user_with_two_businesses_gets_one_active_by_default(): void
    {
        $this->seed(MenuInicialSeeder::class);

        $user = User::query()->where('email', 'admin@dondemechas.com')->firstOrFail();
        $segundo = Negocio::create([
            'nombre' => 'Pizza House',
            'slug' => 'pizza-house',
            'plan' => 'basico',
            'estado_suscripcion' => 'activo',
        ]);
        $user->negocios()->attach($segundo->id, [
            'rol' => RolUsuario::Administrador->value,
            'activo' => true,
        ]);

        $this->post('/login', [
            'email' => 'admin@dondemechas.com',
            'password' => 'admin123',
        ])->assertRedirect(route('rol.seleccionar'));

        // Se elige uno por defecto; el selector superior permitirá cambiarlo.
        $this->assertContains(
            session(TenantContext::CLAVE_SESION),
            $user->negocios()->pluck('negocios.id')->all(),
        );
    }
}
