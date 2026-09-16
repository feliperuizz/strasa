<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Login/logout por sessão (guard web padrão do Laravel).
 */
class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Conta desativada: recusa antes de tentar, com mensagem clara. É um
        // sistema interno, então dizer "desativado" para quem sabe o e-mail
        // não expõe nada.
        $conta = \App\Models\User::where('email', $credentials['email'])->first();

        if ($conta && $conta->isDeactivated()) {
            throw ValidationException::withMessages([
                'email' => 'Este acesso foi desativado. Fale com o administrador da sua equipe.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'As credenciais informadas não conferem.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
