<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Aceite de convite: o visitante abre o link com token, define a senha e
 * passa a fazer parte da empresa que o convidou.
 */
class InvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = $this->validInvitation($token);

        return view('auth.accept-invitation', compact('invitation'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->validInvitation($token);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'company_id' => $invitation->company_id,
            'name' => $data['name'],
            'email' => $invitation->email,
            'role' => $invitation->role,
            'avatar_color' => '#0ea5e9',
            'password' => Hash::make($data['password']),
        ]);

        $invitation->update(['accepted_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function validInvitation(string $token): CompanyInvitation
    {
        $invitation = CompanyInvitation::where('token', $token)->firstOrFail();

        abort_if($invitation->isAccepted(), 410, 'Este convite já foi utilizado.');
        abort_if($invitation->isExpired(), 410, 'Este convite expirou.');

        return $invitation;
    }
}
