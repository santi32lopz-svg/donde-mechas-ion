<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Insumo;
use App\Models\RecetaProducto;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MenuInicialSeeder extends Seeder
{
    public function run(): void
    {
        // Usar una transacción para garantizar integridad de datos
        DB::transaction(function () {
            
            // -------------------------------------------------------------
            // 0. CREACIÓN DEL NEGOCIO PRINCIPAL (SAAS TENANT)
            // -------------------------------------------------------------
            $negocioId = DB::table('negocios')->insertGetId([
                'nombre'             => 'Donde Mechas',
                'slug'               => 'donde-mechas',
                'nit_rut'            => '123456789-0',
                'telefono'           => '3000000000',
                'direccion'          => 'Bogotá, Colombia',
                'plan'               => 'pro',
                'estado_suscripcion' => 'activo',
                'fecha_vencimiento'  => Carbon::now()->addYear(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // -------------------------------------------------------------
            // 1. USUARIOS INICIALES VINCULADOS AL NEGOCIO
            // -------------------------------------------------------------
            // El vínculo con el negocio vive ahora en la tabla pivote
            // negocio_usuario, no en una columna de users.
            $administrador = User::create([
                'nombre'   => 'Administrador Mechas',
                'email'    => 'admin@dondemechas.com',
                'password' => Hash::make('admin123'),
                'rol'      => RolUsuario::Administrador->value,
                'activo'   => true,
            ]);

            $cajero = User::create([
                'nombre'   => 'Cajero Turno',
                'email'    => 'caja@dondemechas.com',
                'password' => Hash::make('caja123'),
                'rol'      => RolUsuario::Cajero->value,
                'activo'   => true,
            ]);

            $administrador->negocios()->attach($negocioId, [
                'rol'    => RolUsuario::Administrador->value,
                'activo' => true,
            ]);

            $cajero->negocios()->attach($negocioId, [
                'rol'    => RolUsuario::Cajero->value,
                'activo' => true,
            ]);

            // Superadministrador de plataforma: administra el sistema completo y
            // crea negocios nuevos. A propósito SIN vínculos en negocio_usuario,
            // porque alcanza todos los negocios por su rol y debe elegir sobre
            // cuál trabaja en lugar de arrastrar uno asignado.
            User::create([
                'nombre'   => 'Super Administrador',
                'email'    => 'superadmin@dondemechas.com',
                'password' => Hash::make('super123'),
                'rol'      => RolUsuario::Superadmin->value,
                'activo'   => true,
            ]);

            // -------------------------------------------------------------
            // 2. CATEGORÍAS DEL MENÚ
            // -------------------------------------------------------------
            $catHamburguesas = Categoria::create(['negocio_id' => $negocioId, 'nombre' => 'Hamburguesas', 'orden_visualizacion' => 1, 'activo' => true]);
            $catSalchipapas  = Categoria::create(['negocio_id' => $negocioId, 'nombre' => 'Salchipapas', 'orden_visualizacion' => 2, 'activo' => true]);
            $catPerros       = Categoria::create(['negocio_id' => $negocioId, 'nombre' => 'Perros Calientes', 'orden_visualizacion' => 3, 'activo' => true]);
            $catEmpanadas    = Categoria::create(['negocio_id' => $negocioId, 'nombre' => 'Empanadas', 'orden_visualizacion' => 4, 'activo' => true]);
            $catBebidas      = Categoria::create(['negocio_id' => $negocioId, 'nombre' => 'Bebidas', 'orden_visualizacion' => 5, 'activo' => true]);

            // -------------------------------------------------------------
            // 3. INSUMOS CONTABLES (MATERIA PRIMA CON GRAMAJES Y UNIDADES)
            // -------------------------------------------------------------
            $insumosData = [
                // Panes y masas
                'pan_brioche'         => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Pan Brioche', 'unidad_medida' => 'unidades', 'stock_actual' => 100, 'stock_minimo' => 20, 'es_contable' => true]),
                'pan_perro'           => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Pan Perro Vaporizado/Tostado', 'unidad_medida' => 'unidades', 'stock_actual' => 100, 'stock_minimo' => 20, 'es_contable' => true]),
                
                // Proteínas
                'carne_120g'          => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Disco Carne Artesanal (120g)', 'unidad_medida' => 'unidades', 'stock_actual' => 150, 'stock_minimo' => 30, 'es_contable' => true]),
                'tocineta'            => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Tocineta Porcionada', 'unidad_medida' => 'gramos', 'stock_actual' => 5000, 'stock_minimo' => 1000, 'es_contable' => true]),
                'chorizo'             => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Chorizo Parrillero', 'unidad_medida' => 'gramos', 'stock_actual' => 5000, 'stock_minimo' => 1000, 'es_contable' => true]),
                'salchicha_jumbo'     => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Salchicha Americana Jumbo', 'unidad_medida' => 'unidades', 'stock_actual' => 100, 'stock_minimo' => 20, 'es_contable' => true]),
                'salchicha_picada'    => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Salchicha Picada', 'unidad_medida' => 'gramos', 'stock_actual' => 5000, 'stock_minimo' => 1000, 'es_contable' => true]),
                'pollo_desmechado'    => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Pollo Desmechado', 'unidad_medida' => 'gramos', 'stock_actual' => 4000, 'stock_minimo' => 800, 'es_contable' => true]),
                'costilla_bbq'        => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Costilla BBQ Desmechada', 'unidad_medida' => 'gramos', 'stock_actual' => 3000, 'stock_minimo' => 500, 'es_contable' => true]),
                'huevo'               => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Huevo Frito', 'unidad_medida' => 'unidades', 'stock_actual' => 100, 'stock_minimo' => 20, 'es_contable' => true]),
                
                // Base
                'papa_frita'          => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Base Papa Frita', 'unidad_medida' => 'gramos', 'stock_actual' => 15000, 'stock_minimo' => 3000, 'es_contable' => true]),

                // Empanadas preparadas
                'emp_carne_papa'      => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Empanada Papa y Carne (Unidad)', 'unidad_medida' => 'unidades', 'stock_actual' => 80, 'stock_minimo' => 15, 'es_contable' => true]),
                'emp_queso'           => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Empanada Queso (Unidad)', 'unidad_medida' => 'unidades', 'stock_actual' => 80, 'stock_minimo' => 15, 'es_contable' => true]),
                'emp_ranchera'        => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Empanada Ranchera (Unidad)', 'unidad_medida' => 'unidades', 'stock_actual' => 80, 'stock_minimo' => 15, 'es_contable' => true]),
                'emp_pollo_jq'        => Insumo::create(['negocio_id' => $negocioId, 'nombre' => 'Empanada Pollo Jamón Queso (Unidad)', 'unidad_medida' => 'unidades', 'stock_actual' => 80, 'stock_minimo' => 15, 'es_contable' => true]),
            ];

            // -------------------------------------------------------------
            // 4. PRODUCTOS Y SUS RECETAS (DESCUENTO AUTOMÁTICO DE INSUMOS)
            // -------------------------------------------------------------

            // --- A. HAMBURGUESAS ---
            $hSencilla = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catHamburguesas->id, 'nombre' => 'Hamburguesa Sencilla', 'precio' => 12500.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $hSencilla->id, 'insumo_id' => $insumosData['pan_brioche']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $hSencilla->id, 'insumo_id' => $insumosData['carne_120g']->id, 'cantidad_usada' => 1]);

            $hEspecial = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catHamburguesas->id, 'nombre' => 'Hamburguesa Especial', 'precio' => 17000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $hEspecial->id, 'insumo_id' => $insumosData['pan_brioche']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $hEspecial->id, 'insumo_id' => $insumosData['carne_120g']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $hEspecial->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 80]);
            RecetaProducto::create(['producto_id' => $hEspecial->id, 'insumo_id' => $insumosData['huevo']->id, 'cantidad_usada' => 1]);

            $hTrifasica = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catHamburguesas->id, 'nombre' => 'Hamburguesa Trifásica', 'precio' => 22310.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $hTrifasica->id, 'insumo_id' => $insumosData['pan_brioche']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $hTrifasica->id, 'insumo_id' => $insumosData['carne_120g']->id, 'cantidad_usada' => 2]);
            RecetaProducto::create(['producto_id' => $hTrifasica->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 80]);
            RecetaProducto::create(['producto_id' => $hTrifasica->id, 'insumo_id' => $insumosData['huevo']->id, 'cantidad_usada' => 1]);

            // COMBOS HAMBURGUESAS
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catHamburguesas->id, 'nombre' => 'Combo Hamburguesa Sencilla', 'precio' => 18500.00, 'disponible' => true]);
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catHamburguesas->id, 'nombre' => 'Combo Hamburguesa Especial', 'precio' => 23000.00, 'disponible' => true]);
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catHamburguesas->id, 'nombre' => 'Combo Hamburguesa Trifásica', 'precio' => 28310.00, 'disponible' => true]);

            // --- B. SALCHIPAPAS ---
            $sSencilla = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catSalchipapas->id, 'nombre' => 'Salchipapa Sencilla', 'precio' => 12000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $sSencilla->id, 'insumo_id' => $insumosData['papa_frita']->id, 'cantidad_usada' => 200]);
            RecetaProducto::create(['producto_id' => $sSencilla->id, 'insumo_id' => $insumosData['salchicha_picada']->id, 'cantidad_usada' => 62]);
            RecetaProducto::create(['producto_id' => $sSencilla->id, 'insumo_id' => $insumosData['chorizo']->id, 'cantidad_usada' => 42]);

            $sEspecial = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catSalchipapas->id, 'nombre' => 'Salchipapa Especial', 'precio' => 20000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['papa_frita']->id, 'cantidad_usada' => 300]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['salchicha_picada']->id, 'cantidad_usada' => 93]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['chorizo']->id, 'cantidad_usada' => 84]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 80]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['pollo_desmechado']->id, 'cantidad_usada' => 80]);

            $sTrifasica = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catSalchipapas->id, 'nombre' => 'Salchipapa Trifásica', 'precio' => 30000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['papa_frita']->id, 'cantidad_usada' => 400]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['salchicha_picada']->id, 'cantidad_usada' => 114]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['chorizo']->id, 'cantidad_usada' => 126]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 120]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['pollo_desmechado']->id, 'cantidad_usada' => 120]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['costilla_bbq']->id, 'cantidad_usada' => 100]);

            // COMBOS SALCHIPAPAS
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catSalchipapas->id, 'nombre' => 'Combo Salchipapa Sencilla', 'precio' => 14500.00, 'disponible' => true]);
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catSalchipapas->id, 'nombre' => 'Combo Salchipapa Especial', 'precio' => 25000.00, 'disponible' => true]);
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catSalchipapas->id, 'nombre' => 'Combo Salchipapa Trifásica', 'precio' => 37000.00, 'disponible' => true]);

            // --- C. PERROS CALIENTES ---
            $pSencillo = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catPerros->id, 'nombre' => 'Perro Sencillo', 'precio' => 9000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $pSencillo->id, 'insumo_id' => $insumosData['pan_perro']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $pSencillo->id, 'insumo_id' => $insumosData['salchicha_jumbo']->id, 'cantidad_usada' => 1]);

            $pChoriperro = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catPerros->id, 'nombre' => 'Choriperro', 'precio' => 12000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $pChoriperro->id, 'insumo_id' => $insumosData['pan_perro']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $pChoriperro->id, 'insumo_id' => $insumosData['chorizo']->id, 'cantidad_usada' => 100]);

            $pPerraPaisa = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catPerros->id, 'nombre' => 'Perra Paisa', 'precio' => 15000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $pPerraPaisa->id, 'insumo_id' => $insumosData['pan_perro']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $pPerraPaisa->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 150]);

            // COMBOS PERROS
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catPerros->id, 'nombre' => 'Combo Perro Sencillo', 'precio' => 15000.00, 'disponible' => true]);
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catPerros->id, 'nombre' => 'Combo Choriperro', 'precio' => 18000.00, 'disponible' => true]);
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catPerros->id, 'nombre' => 'Combo Perra Paisa', 'precio' => 21000.00, 'disponible' => true]);

            // --- D. EMPANADAS ---
            $emp1 = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catEmpanadas->id, 'nombre' => 'Empanada de Papa y Carne', 'precio' => 3500.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $emp1->id, 'insumo_id' => $insumosData['emp_carne_papa']->id, 'cantidad_usada' => 1]);

            $emp2 = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catEmpanadas->id, 'nombre' => 'Empanada de Queso', 'precio' => 3500.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $emp2->id, 'insumo_id' => $insumosData['emp_queso']->id, 'cantidad_usada' => 1]);

            $emp3 = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catEmpanadas->id, 'nombre' => 'Empanada Ranchera', 'precio' => 3500.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $emp3->id, 'insumo_id' => $insumosData['emp_ranchera']->id, 'cantidad_usada' => 1]);

            $emp4 = Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catEmpanadas->id, 'nombre' => 'Empanada Pollo, Jamón y Queso', 'precio' => 3500.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $emp4->id, 'insumo_id' => $insumosData['emp_pollo_jq']->id, 'cantidad_usada' => 1]);

            // --- E. BEBIDAS ---
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catBebidas->id, 'nombre' => 'Gaseosa Personal 350ml', 'precio' => 4000.00, 'disponible' => true]);
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catBebidas->id, 'nombre' => 'Agua Embotellada', 'precio' => 3000.00, 'disponible' => true]);
            Producto::create(['negocio_id' => $negocioId, 'categoria_id' => $catBebidas->id, 'nombre' => 'Jugo Natural', 'precio' => 5000.00, 'disponible' => true]);

            // --- CÓDIGOS DE BARRAS ---
            // La columna se añadió después de escribirse este seeder, así que
            // todos los productos quedaban en NULL y el lector del POS no tenía
            // nada que encontrar. Se asigna un código determinista: prefijo 770
            // (Colombia) más el id del producto.
            Producto::query()
                ->where('negocio_id', $negocioId)
                ->whereNull('codigo_barras')
                ->get()
                ->each(function (Producto $producto): void {
                    $producto->forceFill([
                        'codigo_barras' => '770'.str_pad((string) $producto->id, 10, '0', STR_PAD_LEFT),
                    ])->save();
                });
        });
    }
}