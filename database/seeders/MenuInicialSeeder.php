<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Insumo;
use App\Models\RecetaProducto;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class MenuInicialSeeder extends Seeder
{
    public function run(): void
    {
        // Usar una transacción para garantizar integridad de datos
        DB::transaction(function () {
            
            // -------------------------------------------------------------
            // 1. USUARIOS INICIALES (ADMINISTRADOR Y CAJERO)
            // -------------------------------------------------------------
            User::create([
                'nombre'   => 'Administrador Mechas',
                'email'    => 'admin@dondemechas.com',
                'password' => Hash::make('admin123'),
                'rol'      => 'administrador',
                'activo'   => true,
            ]);

            User::create([
                'nombre'   => 'Cajero Turno',
                'email'    => 'caja@dondemechas.com',
                'password' => Hash::make('caja123'),
                'rol'      => 'cajero',
                'activo'   => true,
            ]);

            // -------------------------------------------------------------
            // 2. CATEGORÍAS DEL MENÚ
            // -------------------------------------------------------------
            $catHamburguesas = Categoria::create(['nombre' => 'Hamburguesas', 'orden_visualizacion' => 1, 'activo' => true]);
            $catSalchipapas  = Categoria::create(['nombre' => 'Salchipapas', 'orden_visualizacion' => 2, 'activo' => true]);
            $catPerros       = Categoria::create(['nombre' => 'Perros Calientes', 'orden_visualizacion' => 3, 'activo' => true]);
            $catEmpanadas    = Categoria::create(['nombre' => 'Empanadas', 'orden_visualizacion' => 4, 'activo' => true]);
            $catBebidas      = Categoria::create(['nombre' => 'Bebidas', 'orden_visualizacion' => 5, 'activo' => true]);

            // -------------------------------------------------------------
            // 3. INSUMOS CONTABLES (MATERIA PRIMA CON GRAMAJES Y UNIDADES)
            // -------------------------------------------------------------
            $insumosData = [
                // Panes y masas
                'pan_brioche'         => Insumo::create(['nombre' => 'Pan Brioche', 'unidad_medida' => 'unidades', 'stock_actual' => 100, 'stock_minimo' => 20, 'es_contable' => true]),
                'pan_perro'           => Insumo::create(['nombre' => 'Pan Perro Vaporizado/Tostado', 'unidad_medida' => 'unidades', 'stock_actual' => 100, 'stock_minimo' => 20, 'es_contable' => true]),
                
                // Proteínas (Gramos y unidades)
                'carne_120g'          => Insumo::create(['nombre' => 'Disco Carne Artesanal (120g)', 'unidad_medida' => 'unidades', 'stock_actual' => 150, 'stock_minimo' => 30, 'es_contable' => true]),
                'tocineta'            => Insumo::create(['nombre' => 'Tocineta Porcionada', 'unidad_medida' => 'gramos', 'stock_actual' => 5000, 'stock_minimo' => 1000, 'es_contable' => true]),
                'chorizo'             => Insumo::create(['nombre' => 'Chorizo Parrillero', 'unidad_medida' => 'gramos', 'stock_actual' => 5000, 'stock_minimo' => 1000, 'es_contable' => true]),
                'salchicha_jumbo'     => Insumo::create(['nombre' => 'Salchicha Americana Jumbo', 'unidad_medida' => 'unidades', 'stock_actual' => 100, 'stock_minimo' => 20, 'es_contable' => true]),
                'salchicha_picada'    => Insumo::create(['nombre' => 'Salchicha Picada', 'unidad_medida' => 'gramos', 'stock_actual' => 5000, 'stock_minimo' => 1000, 'es_contable' => true]),
                'pollo_desmechado'    => Insumo::create(['nombre' => 'Pollo Desmechado', 'unidad_medida' => 'gramos', 'stock_actual' => 4000, 'stock_minimo' => 800, 'es_contable' => true]),
                'costilla_bbq'        => Insumo::create(['nombre' => 'Costilla BBQ Desmechada', 'unidad_medida' => 'gramos', 'stock_actual' => 3000, 'stock_minimo' => 500, 'es_contable' => true]),
                'huevo'               => Insumo::create(['nombre' => 'Huevo Frito', 'unidad_medida' => 'unidades', 'stock_actual' => 100, 'stock_minimo' => 20, 'es_contable' => true]),
                
                // Base
                'papa_frita'          => Insumo::create(['nombre' => 'Base Papa Frita', 'unidad_medida' => 'gramos', 'stock_actual' => 15000, 'stock_minimo' => 3000, 'es_contable' => true]),

                // Empanadas preparadas (por unidades)
                'emp_carne_papa'      => Insumo::create(['nombre' => 'Empanada Papa y Carne (Unidad)', 'unidad_medida' => 'unidades', 'stock_actual' => 80, 'stock_minimo' => 15, 'es_contable' => true]),
                'emp_queso'           => Insumo::create(['nombre' => 'Empanada Queso (Unidad)', 'unidad_medida' => 'unidades', 'stock_actual' => 80, 'stock_minimo' => 15, 'es_contable' => true]),
                'emp_ranchera'        => Insumo::create(['nombre' => 'Empanada Ranchera (Unidad)', 'unidad_medida' => 'unidades', 'stock_actual' => 80, 'stock_minimo' => 15, 'es_contable' => true]),
                'emp_pollo_jq'        => Insumo::create(['nombre' => 'Empanada Pollo Jamón Queso (Unidad)', 'unidad_medida' => 'unidades', 'stock_actual' => 80, 'stock_minimo' => 15, 'es_contable' => true]),
            ];

            // -------------------------------------------------------------
            // 4. PRODUCTOS Y SUS RECETAS (DESCUENTO AUTOMÁTICO DE INSUMOS)
            // -------------------------------------------------------------

            // --- A. HAMBURGUESAS ---
            $hSencilla = Producto::create(['categoria_id' => $catHamburguesas->id, 'nombre' => 'Hamburguesa Sencilla', 'precio' => 12500.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $hSencilla->id, 'insumo_id' => $insumosData['pan_brioche']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $hSencilla->id, 'insumo_id' => $insumosData['carne_120g']->id, 'cantidad_usada' => 1]);

            $hEspecial = Producto::create(['categoria_id' => $catHamburguesas->id, 'nombre' => 'Hamburguesa Especial', 'precio' => 17000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $hEspecial->id, 'insumo_id' => $insumosData['pan_brioche']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $hEspecial->id, 'insumo_id' => $insumosData['carne_120g']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $hEspecial->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 80]); // 80g
            RecetaProducto::create(['producto_id' => $hEspecial->id, 'insumo_id' => $insumosData['huevo']->id, 'cantidad_usada' => 1]);

            $hTrifasica = Producto::create(['categoria_id' => $catHamburguesas->id, 'nombre' => 'Hamburguesa Trifásica', 'precio' => 22310.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $hTrifasica->id, 'insumo_id' => $insumosData['pan_brioche']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $hTrifasica->id, 'insumo_id' => $insumosData['carne_120g']->id, 'cantidad_usada' => 2]); // Doble carne
            RecetaProducto::create(['producto_id' => $hTrifasica->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 80]);
            RecetaProducto::create(['producto_id' => $hTrifasica->id, 'insumo_id' => $insumosData['huevo']->id, 'cantidad_usada' => 1]);

            // COMBOS HAMBURGUESAS
            Producto::create(['categoria_id' => $catHamburguesas->id, 'nombre' => 'Combo Hamburguesa Sencilla', 'precio' => 18500.00, 'disponible' => true]);
            Producto::create(['categoria_id' => $catHamburguesas->id, 'nombre' => 'Combo Hamburguesa Especial', 'precio' => 23000.00, 'disponible' => true]);
            Producto::create(['categoria_id' => $catHamburguesas->id, 'nombre' => 'Combo Hamburguesa Trifásica', 'precio' => 28310.00, 'disponible' => true]);


            // --- B. SALCHIPAPAS ---
            $sSencilla = Producto::create(['categoria_id' => $catSalchipapas->id, 'nombre' => 'Salchipapa Sencilla', 'precio' => 12000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $sSencilla->id, 'insumo_id' => $insumosData['papa_frita']->id, 'cantidad_usada' => 200]);
            RecetaProducto::create(['producto_id' => $sSencilla->id, 'insumo_id' => $insumosData['salchicha_picada']->id, 'cantidad_usada' => 62]);
            RecetaProducto::create(['producto_id' => $sSencilla->id, 'insumo_id' => $insumosData['chorizo']->id, 'cantidad_usada' => 42]);

            $sEspecial = Producto::create(['categoria_id' => $catSalchipapas->id, 'nombre' => 'Salchipapa Especial', 'precio' => 20000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['papa_frita']->id, 'cantidad_usada' => 300]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['salchicha_picada']->id, 'cantidad_usada' => 93]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['chorizo']->id, 'cantidad_usada' => 84]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 80]);
            RecetaProducto::create(['producto_id' => $sEspecial->id, 'insumo_id' => $insumosData['pollo_desmechado']->id, 'cantidad_usada' => 80]);

            $sTrifasica = Producto::create(['categoria_id' => $catSalchipapas->id, 'nombre' => 'Salchipapa Trifásica', 'precio' => 30000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['papa_frita']->id, 'cantidad_usada' => 400]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['salchicha_picada']->id, 'cantidad_usada' => 114]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['chorizo']->id, 'cantidad_usada' => 126]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 120]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['pollo_desmechado']->id, 'cantidad_usada' => 120]);
            RecetaProducto::create(['producto_id' => $sTrifasica->id, 'insumo_id' => $insumosData['costilla_bbq']->id, 'cantidad_usada' => 100]);

            // COMBOS SALCHIPAPAS
            Producto::create(['categoria_id' => $catSalchipapas->id, 'nombre' => 'Combo Salchipapa Sencilla', 'precio' => 14500.00, 'disponible' => true]);
            Producto::create(['categoria_id' => $catSalchipapas->id, 'nombre' => 'Combo Salchipapa Especial', 'precio' => 25000.00, 'disponible' => true]);
            Producto::create(['categoria_id' => $catSalchipapas->id, 'nombre' => 'Combo Salchipapa Trifásica', 'precio' => 37000.00, 'disponible' => true]);


            // --- C. PERROS CALIENTES ---
            $pSencillo = Producto::create(['categoria_id' => $catPerros->id, 'nombre' => 'Perro Sencillo', 'precio' => 9000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $pSencillo->id, 'insumo_id' => $insumosData['pan_perro']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $pSencillo->id, 'insumo_id' => $insumosData['salchicha_jumbo']->id, 'cantidad_usada' => 1]);

            $pChoriperro = Producto::create(['categoria_id' => $catPerros->id, 'nombre' => 'Choriperro', 'precio' => 12000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $pChoriperro->id, 'insumo_id' => $insumosData['pan_perro']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $pChoriperro->id, 'insumo_id' => $insumosData['chorizo']->id, 'cantidad_usada' => 100]);

            $pPerraPaisa = Producto::create(['categoria_id' => $catPerros->id, 'nombre' => 'Perra Paisa', 'precio' => 15000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $pPerraPaisa->id, 'insumo_id' => $insumosData['pan_perro']->id, 'cantidad_usada' => 1]);
            RecetaProducto::create(['producto_id' => $pPerraPaisa->id, 'insumo_id' => $insumosData['tocineta']->id, 'cantidad_usada' => 150]); // 150g tocineta sin salchicha

            // COMBOS PERROS
            Producto::create(['categoria_id' => $catPerros->id, 'nombre' => 'Combo Perro Sencillo', 'precio' => 15000.00, 'disponible' => true]);
            Producto::create(['categoria_id' => $catPerros->id, 'nombre' => 'Combo Choriperro', 'precio' => 18000.00, 'disponible' => true]);
            Producto::create(['categoria_id' => $catPerros->id, 'nombre' => 'Combo Perra Paisa', 'precio' => 21000.00, 'disponible' => true]);


            // --- D. EMPANADAS ---
            $emp1 = Producto::create(['categoria_id' => $catEmpanadas->id, 'nombre' => 'Empanada de Papa y Carne', 'precio' => 3000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $emp1->id, 'insumo_id' => $insumosData['emp_carne_papa']->id, 'cantidad_usada' => 1]);

            $emp2 = Producto::create(['categoria_id' => $catEmpanadas->id, 'nombre' => 'Empanada de Queso', 'precio' => 3000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $emp2->id, 'insumo_id' => $insumosData['emp_queso']->id, 'cantidad_usada' => 1]);

            $emp3 = Producto::create(['categoria_id' => $catEmpanadas->id, 'nombre' => 'Empanada Ranchera', 'precio' => 4000.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $emp3->id, 'insumo_id' => $insumosData['emp_ranchera']->id, 'cantidad_usada' => 1]);

            $emp4 = Producto::create(['categoria_id' => $catEmpanadas->id, 'nombre' => 'Empanada Pollo, Jamón y Queso', 'precio' => 3500.00, 'disponible' => true]);
            RecetaProducto::create(['producto_id' => $emp4->id, 'insumo_id' => $insumosData['emp_pollo_jq']->id, 'cantidad_usada' => 1]);


            // --- E. BEBIDAS ---
            Producto::create(['categoria_id' => $catBebidas->id, 'nombre' => 'Gaseosa Personal 350ml', 'precio' => 4000.00, 'disponible' => true]);
            Producto::create(['categoria_id' => $catBebidas->id, 'nombre' => 'Agua Embotellada', 'precio' => 3000.00, 'disponible' => true]);
            Producto::create(['categoria_id' => $catBebidas->id, 'nombre' => 'Jugo Natural', 'precio' => 5000.00, 'disponible' => true]);
        });
    }
}