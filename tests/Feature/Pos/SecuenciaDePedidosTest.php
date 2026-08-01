<?php

namespace Tests\Feature\Pos;

use App\Models\Negocio;
use App\Models\Pedido;
use App\Models\User;
use App\Support\SecuenciaDePedidos;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecuenciaDePedidosTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $mechas;

    private Negocio $pizzaHouse;

    private SecuenciaDePedidos $secuencia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mechas = $this->crearNegocio('Donde Mechas', 'donde-mechas', 'DM');
        $this->pizzaHouse = $this->crearNegocio('Pizza House', 'pizza-house', 'PH');
        $this->secuencia = app(SecuenciaDePedidos::class);
    }

    public function test_la_secuencia_empieza_en_uno_y_es_continua(): void
    {
        $this->assertSame(1, $this->secuencia->siguiente($this->mechas->id));
        $this->assertSame(2, $this->secuencia->siguiente($this->mechas->id));
        $this->assertSame(3, $this->secuencia->siguiente($this->mechas->id));
    }

    public function test_cada_negocio_tiene_su_propia_secuencia(): void
    {
        // El motivo de toda la etapa: dos negocios pueden emitir su pedido 1.
        $this->secuencia->siguiente($this->mechas->id);
        $this->secuencia->siguiente($this->mechas->id);

        $this->assertSame(1, $this->secuencia->siguiente($this->pizzaHouse->id));
        $this->assertSame(3, $this->secuencia->siguiente($this->mechas->id));
    }

    public function test_no_entrega_numeros_repetidos(): void
    {
        $numeros = [];

        for ($i = 0; $i < 50; $i++) {
            $numeros[] = $this->secuencia->siguiente($this->mechas->id);
        }

        $this->assertCount(50, array_unique($numeros));
        $this->assertSame(range(1, 50), $numeros);
    }

    public function test_la_secuencia_nunca_se_reinicia_aunque_se_borren_pedidos(): void
    {
        // El contador vive aparte de los pedidos a propósito: borrar o anular
        // una venta no debe hacer que el siguiente número se repita.
        $primero = $this->secuencia->siguiente($this->mechas->id);
        $this->crearPedido($this->mechas, $primero);

        Pedido::query()->withoutGlobalScope('negocio')->delete();

        $this->assertSame(2, $this->secuencia->siguiente($this->mechas->id));
    }

    public function test_dos_negocios_pueden_tener_el_pedido_numero_uno(): void
    {
        $this->crearPedido($this->mechas, 1);
        $this->crearPedido($this->pizzaHouse, 1);

        $this->assertSame(2, Pedido::query()->withoutGlobalScope('negocio')->count());
    }

    public function test_el_mismo_numero_no_se_repite_dentro_de_un_negocio(): void
    {
        $this->crearPedido($this->mechas, 1);

        $this->expectException(QueryException::class);

        $this->crearPedido($this->mechas, 1);
    }

    public function test_el_numero_se_libera_si_la_venta_se_deshace(): void
    {
        // siguiente() se llama dentro de la transacción de la venta, así que un
        // fallo posterior deshace también la reserva y no deja huecos.
        try {
            DB::transaction(function () {
                $this->secuencia->siguiente($this->mechas->id);

                throw new \RuntimeException('la venta falló');
            });
        } catch (\RuntimeException) {
            // esperado
        }

        $this->assertSame(0, $this->secuencia->ultimoEntregado($this->mechas->id));
        $this->assertSame(1, $this->secuencia->siguiente($this->mechas->id));
    }

    public function test_el_numero_se_presenta_con_el_prefijo_del_negocio(): void
    {
        $pedido = $this->crearPedido($this->mechas, 123);

        $this->assertSame('DM-000123', $pedido->numeroFormateado());
    }

    public function test_sin_prefijo_configurado_se_usa_el_por_defecto(): void
    {
        $sinPrefijo = $this->crearNegocio('Sin Prefijo', 'sin-prefijo', null);
        $pedido = $this->crearPedido($sinPrefijo, 7);

        $this->assertSame(
            Negocio::PREFIJO_PEDIDO_POR_DEFECTO.'-000007',
            $pedido->numeroFormateado()
        );
    }

    public function test_el_prefijo_no_altera_el_consecutivo_guardado(): void
    {
        // El prefijo es presentación: cambiarlo no toca ninguna fila.
        $pedido = $this->crearPedido($this->mechas, 42);

        $this->mechas->update(['prefijo_pedido' => 'XX']);

        $this->assertSame(42, $pedido->refresh()->numero_secuencial);
        $this->assertSame('XX-000042', $pedido->fresh()->numeroFormateado());
    }

    private function crearNegocio(string $nombre, string $slug, ?string $prefijo): Negocio
    {
        return Negocio::create([
            'nombre' => $nombre,
            'slug' => $slug,
            'prefijo_pedido' => $prefijo,
            'plan' => 'basico',
            'estado_suscripcion' => 'activo',
        ]);
    }

    private function crearPedido(Negocio $negocio, int $numero): Pedido
    {
        $usuario = $this->crearUsuarioEnNegocio($negocio);

        return app(TenantContext::class)->sinAislamiento(fn () => Pedido::create([
            'negocio_id' => $negocio->id,
            'numero_secuencial' => $numero,
            'user_id' => $usuario->id,
            'estado' => 'completado',
            'metodo_pago' => 'efectivo',
            'monto_total' => 17000,
        ]));
    }
}
