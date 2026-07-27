<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\MenuInicialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $response->assertRedirect('/pos');
        $this->assertAuthenticated();
        $this->assertEquals(1, session('tenant_id'));
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
            ->where('id', $user->negocio_id)
            ->update(['estado_suscripcion' => 'suspendido']);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@dondemechas.com',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }
}
