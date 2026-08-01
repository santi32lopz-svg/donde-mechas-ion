<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocioPropio;

    private Negocio $negocioAjeno;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        // Sin sesión iniciada a propósito: así el scope global no filtra y se
        // pueden sembrar los dos negocios.
        $this->negocioPropio = $this->crearNegocio('Donde Mechas', 'donde-mechas');
        $this->negocioAjeno = $this->crearNegocio('Pizza House', 'pizza-house');

        $this->crearProducto($this->negocioPropio, 'Hamburguesa Especial');
        $this->crearProducto($this->negocioAjeno, 'Pizza Napolitana');

        $this->usuario = $this->crearUsuarioEnNegocio(
            $this->negocioPropio,
            RolUsuario::Cajero,
            ['nombre' => 'Cajero Turno', 'email' => 'caja@dondemechas.test'],
        );
    }

    public function test_sin_sesion_no_se_aplica_aislamiento(): void
    {
        // Es lo que permite que seeders y migraciones funcionen.
        $this->assertFalse(app(TenantContext::class)->aplicaAislamiento());
        $this->assertSame(2, Producto::query()->count());
    }

    public function test_con_usuario_autenticado_solo_ve_su_negocio(): void
    {
        $this->actingAs($this->usuario);

        $this->assertTrue(app(TenantContext::class)->aplicaAislamiento());
        $this->assertSame($this->negocioPropio->id, app(TenantContext::class)->negocioId());
        $this->assertSame(1, Producto::query()->count());
    }

    public function test_un_negocio_no_accesible_en_sesion_se_ignora(): void
    {
        // Escenario de manipulación: alguien fuerza el id de otro negocio en la
        // sesión. Debe caer al negocio por defecto, nunca abrir el ajeno.
        $this->actingAs($this->usuario);
        session()->put(TenantContext::CLAVE_SESION, $this->negocioAjeno->id);

        $this->assertSame($this->negocioPropio->id, app(TenantContext::class)->negocioId());
        $this->assertSame('Hamburguesa Especial', Producto::query()->first()->nombre);
    }

    public function test_usar_fija_el_negocio_activo_de_la_peticion(): void
    {
        $tenant = app(TenantContext::class);
        $tenant->usar($this->negocioAjeno->id);

        $this->assertSame($this->negocioAjeno->id, $tenant->negocioId());
        $this->assertSame('Pizza Napolitana', Producto::query()->first()->nombre);
    }

    public function test_sin_negocio_activo_no_devuelve_nada(): void
    {
        // Falla cerrado: antes que mostrar el catálogo de todos los negocios,
        // se prefiere una pantalla vacía.
        $tenant = app(TenantContext::class);
        $tenant->usar(null);

        $this->assertTrue($tenant->aplicaAislamiento());
        $this->assertSame(0, Producto::query()->count());
    }

    public function test_sin_aislamiento_permite_consultas_de_plataforma(): void
    {
        $this->actingAs($this->usuario);

        $total = app(TenantContext::class)->sinAislamiento(
            fn () => Producto::query()->count()
        );

        $this->assertSame(2, $total);
        // Y el aislamiento vuelve a estar activo al salir del bloque.
        $this->assertSame(1, Producto::query()->count());
    }

    public function test_los_registros_nuevos_heredan_el_negocio_activo(): void
    {
        $tenant = app(TenantContext::class);
        $tenant->usar($this->negocioAjeno->id);

        $categoria = Categoria::create([
            'nombre' => 'Pizzas',
            'orden_visualizacion' => 1,
            'activo' => true,
        ]);

        $this->assertSame($this->negocioAjeno->id, $categoria->negocio_id);
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

    private function crearProducto(Negocio $negocio, string $nombre): Producto
    {
        $categoria = Categoria::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Categoría de '.$negocio->slug,
            'orden_visualizacion' => 1,
            'activo' => true,
        ]);

        return Producto::create([
            'negocio_id' => $negocio->id,
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => 17000,
            'disponible' => true,
        ]);
    }
}
