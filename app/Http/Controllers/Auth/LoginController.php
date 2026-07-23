<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Intentamos autenticar al usuario con las credenciales proporcionadas
        if (Auth::attempt(
            $credentials, 
            $request->boolean('remember') // Permite recordar al usuario si se selecciona la opción "remember me"
            )) {
            $request->session()->regenerate(); // Regeneramos la sesión para prevenir ataques de fijación de sesión

            $user = Auth::user(); // Obtenemos el usuario autenticado

            // Verificación Multi-Tenant: Validar que el negocio del usuario exista y esté activo
            if (!$user->negocio || $user->negocio->estado_suscripcion !== 'activo') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'El negocio asociado a esta cuenta no está activo o no existe.',
                ]);
            }

            // Guardamos la información del Tenant en la sesión global
            session([
                'tenant_id' => $user->negocio_id,
                'tenant_nombre' => $user->negocio->nombre,
            ]);

            return redirect()->intended(route('pos.terminal'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}