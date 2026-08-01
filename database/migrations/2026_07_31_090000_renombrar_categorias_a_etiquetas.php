<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Renombra categorias a etiquetas en toda la base de datos.
 *
 * El concepto es el mismo —agrupar productos para el POS— pero el proyecto usa
 * ya "etiqueta" en el panel y en la documentación. Mantener los dos nombres
 * obliga a cada desarrollador nuevo a aprender la equivalencia.
 *
 * Aprovecha el cambio para corregir un problema serio: la clave foránea era
 * ON DELETE CASCADE, de modo que borrar una categoría borraba en silencio
 * todos sus productos. Con el CRUD de etiquetas eso habría sido catastrófico,
 * así que pasa a RESTRICT y el borrado exige reasignar antes.
 *
 * No se pierde información: se renombra, no se recrea. Aun así se cuentan las
 * filas y los vínculos antes y después, y cualquier discrepancia aborta la
 * migración. PostgreSQL ejecuta esto dentro de una transacción, así que un
 * fallo deja la base exactamente como estaba.
 */
return new class extends Migration
{
    public function up(): void
    {
        $antes = $this->fotografiar('categorias', 'productos', 'categoria_id');

        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign('productos_categoria_id_foreign');
        });

        Schema::rename('categorias', 'etiquetas');

        Schema::table('productos', function (Blueprint $table) {
            $table->renameColumn('categoria_id', 'etiqueta_id');
        });

        Schema::table('productos', function (Blueprint $table) {
            // RESTRICT en lugar de CASCADE: una etiqueta con productos no debe
            // poder borrarse, y mucho menos arrastrarlos consigo.
            $table->foreign('etiqueta_id', 'productos_etiqueta_id_foreign')
                ->references('id')
                ->on('etiquetas')
                ->restrictOnDelete();
        });

        $this->renombrarIndices([
            'categorias_pkey' => 'etiquetas_pkey',
            'categorias_negocio_activo_idx' => 'etiquetas_negocio_activo_idx',
            'productos_negocio_categoria_idx' => 'productos_negocio_etiqueta_idx',
        ]);

        $this->renombrarRestriccion('etiquetas', 'categorias_negocio_id_foreign', 'etiquetas_negocio_id_foreign');

        $despues = $this->fotografiar('etiquetas', 'productos', 'etiqueta_id');
        $this->compararFotografias($antes, $despues);
    }

    public function down(): void
    {
        $antes = $this->fotografiar('etiquetas', 'productos', 'etiqueta_id');

        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign('productos_etiqueta_id_foreign');
        });

        Schema::rename('etiquetas', 'categorias');

        Schema::table('productos', function (Blueprint $table) {
            $table->renameColumn('etiqueta_id', 'categoria_id');
        });

        Schema::table('productos', function (Blueprint $table) {
            // Se restaura el comportamiento original, incluido el CASCADE.
            $table->foreign('categoria_id', 'productos_categoria_id_foreign')
                ->references('id')
                ->on('categorias')
                ->cascadeOnDelete();
        });

        $this->renombrarIndices([
            'etiquetas_pkey' => 'categorias_pkey',
            'etiquetas_negocio_activo_idx' => 'categorias_negocio_activo_idx',
            'productos_negocio_etiqueta_idx' => 'productos_negocio_categoria_idx',
        ]);

        $this->renombrarRestriccion('categorias', 'etiquetas_negocio_id_foreign', 'categorias_negocio_id_foreign');

        $despues = $this->fotografiar('categorias', 'productos', 'categoria_id');
        $this->compararFotografias($antes, $despues);
    }

    /**
     * Cuenta lo que no puede cambiar al renombrar.
     *
     * @return array{grupos:int, productos:int, vinculados:int}
     */
    private function fotografiar(string $tablaGrupos, string $tablaProductos, string $columna): array
    {
        return [
            'grupos' => DB::table($tablaGrupos)->count(),
            'productos' => DB::table($tablaProductos)->count(),
            'vinculados' => DB::table($tablaProductos.' as p')
                ->join($tablaGrupos.' as g', 'g.id', '=', 'p.'.$columna)
                ->count(),
        ];
    }

    /**
     * @param  array{grupos:int, productos:int, vinculados:int}  $antes
     * @param  array{grupos:int, productos:int, vinculados:int}  $despues
     */
    private function compararFotografias(array $antes, array $despues): void
    {
        if ($antes === $despues) {
            return;
        }

        throw new RuntimeException(
            'Migración abortada para no perder datos. Antes: '.json_encode($antes).
            ' / Después: '.json_encode($despues).'. No se ha confirmado ningún cambio.'
        );
    }

    /**
     * @param  array<string, string>  $mapa
     */
    private function renombrarIndices(array $mapa): void
    {
        if (! $this->esPostgres()) {
            return;
        }

        foreach ($mapa as $origen => $destino) {
            DB::statement("ALTER INDEX IF EXISTS {$origen} RENAME TO {$destino}");
        }
    }

    private function renombrarRestriccion(string $tabla, string $origen, string $destino): void
    {
        if (! $this->esPostgres()) {
            return;
        }

        $existe = DB::selectOne('SELECT 1 FROM pg_constraint WHERE conname = ?', [$origen]);

        if ($existe !== null) {
            DB::statement("ALTER TABLE {$tabla} RENAME CONSTRAINT {$origen} TO {$destino}");
        }
    }

    private function esPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
