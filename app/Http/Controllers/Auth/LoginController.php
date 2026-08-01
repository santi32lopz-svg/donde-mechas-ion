<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
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

            // Verificación Multi-Tenant: el usuario debe tener al menos un
            // negocio activo. Con la tabla pivote puede pertenecer a varios, así
            // que ya no basta con mirar un único negocio asociado.
            $negocio = $user->negociosDisponibles()->orderBy('negocios.id')->first();

            if ($negocio === null) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Esta cuenta no tiene ningún negocio activo asignado.',
                ]);
            }

            // Se deja un negocio activo por defecto; el selector de la barra
            // superior permitirá cambiarlo si el usuario tiene varios.
            app(TenantContext::class)->cambiarA($negocio->id);

            session([
                'tenant_id' => $negocio->id,
                'tenant_nombre' => $negocio->nombre,
            ]);

            return redirect()->intended(route('pos.main'));
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