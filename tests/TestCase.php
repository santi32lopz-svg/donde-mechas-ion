<?php

namespace Tests;

use App\Enums\RolUsuario;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea un usuario ya vinculado a un negocio.
     *
     * Desde que la relación vive en la tabla pivote, montar un usuario de
     * prueba son dos pasos en lugar de uno; esto evita repetirlos en cada test.
     */
    protected function crearUsuarioEnNegocio(
        Negocio $negocio,
        RolUsuario $rol = RolUsuario::Cajero,
        array $atributos = [],
    ): User {
        $usuario = User::create(array_merge([
            'nombre' => 'Usuario de prueba',
            'email' => 'usuario'.uniqid().'@dondemechas.test',
            'password' => 'secret',
            'rol' => $rol->value,
            'activo' => true,
        ], $atributos));

        $usuario->negocios()->attach($negocio->id, [
            'rol' => $rol->value,
            'activo' => true,
        ]);

        return $usuario->refresh();
    }
}
