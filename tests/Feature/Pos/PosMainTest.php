<?php

namespace Tests\Feature\Pos;

use App\Enums\RolUsuario;
use App\Livewire\Pos\PosMain;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosMainTest extends TestCase
{
    use RefreshDatabase;

    private User $cajero;

    private Producto $productoPropio;

    private Producto $productoAjeno;

    protected function setUp(): void
    {
        parent::setUp();

        // Los datos se crean sin sesión iniciada a propósito: con un usuario
        // autenticado el scope global de negocio ya estaría filtrando.
        $propio = $this->crearNegocio('Donde Mechas', 'donde-mechas');
        $ajeno = $this->crearNegocio('Bar El Tapitas', 'bar-el-tapitas');

        $this->productoPropio = $this->crearProducto($propio, 'Hamburguesa Especial', 17000, '7700000001');
        $this->productoAjeno = $this->crearProducto($ajeno, 'Michelada Ajena', 9000, '7799999999');

        $this->cajero = $this->crearUsuarioEnNegocio($propio, RolUsuario::Cajero, [
            'nombre' => 'Cajero Turno',
            'email' => 'caja@dondemechas.test',
        ]);
    }

    public function test_solo_muestra_productos_del_negocio_en_sesion(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->assertSee('Hamburguesa Especial')
            ->assertDontSee('Michelada Ajena');
    }

    public function test_no_permite_agregar_un_producto_de_otro_negocio(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->productoAjeno->id)
            ->assertSet('cart', []);
    }

    public function test_el_carrito_no_expone_precios_al_navegador(): void
    {
        $componente = Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->productoPropio->id);

        // Solo cantidad y notas viajan al cliente; el precio se queda en el servidor.
        $componente->assertSet('cart', [
            $this->productoPropio->id => ['cantidad' => 1, 'notas' => ''],
        ]);
    }

    public function test_el_total_se_recalcula_desde_la_base_de_datos(): void
    {
        $componente = Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->call('addToCart', $this->productoPropio->id)
            ->assertViewHas('total', 17000.0);

        // Si cambia el precio en la BD, la tirilla debe reflejarlo al re-renderizar
        // en lugar de arrastrar el precio con el que se agregó.
        $this->productoPropio->forceFill(['precio' => 20000])->save();

        $componente->call('$refresh')->assertViewHas('total', 20000.0);
    }

    public function test_el_buscador_encuentra_sin_distinguir_mayusculas(): void
    {
        // Regresión: en PostgreSQL LIKE distingue mayúsculas y "hamb" no
        // encontraba "Hamburguesa Especial".
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->set('search', 'hamb')
            ->assertSee('Hamburguesa Especial');
    }

    public function test_el_buscador_ignora_los_comodines_de_like(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->set('search', '%')
            ->assertDontSee('Hamburguesa Especial');
    }

    public function test_el_lector_de_codigo_de_barras_agrega_y_limpia_el_campo(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->set('search', '7700000001')
            ->call('agregarPorCodigoDeBarras')
            ->assertSet('search', '')
            ->assertSet('cart', [
                $this->productoPropio->id => ['cantidad' => 1, 'notas' => ''],
            ]);
    }

    public function test_el_codigo_de_barras_de_otro_negocio_no_agrega_nada(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->set('search', '7799999999')
            ->call('agregarPorCodigoDeBarras')
            ->assertSet('cart', []);
    }

    public function test_buscar_libera_el_filtro_de_categoria(): void
    {
        Livewire::actingAs($this->cajero)
            ->test(PosMain::class)
            ->set('selectedCategoriaId', 999)
            ->set('search', 'hamb')
            ->assertSet('selectedCategoriaId', null)
            ->assertSee('Hamburguesa Especial');
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

    private function crearProducto(Negocio $negocio, string $nombre, int $precio, string $codigo): Producto
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
            'codigo_barras' => $codigo,
            'precio' => $precio,
            'disponible' => true,
        ]);
    }
}
