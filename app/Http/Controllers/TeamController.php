<?php

namespace App\Http\Controllers;

use App\Mail\CompanyInvitationMail;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Gestão do time da empresa: lista membros, envia e revoga convites.
 * Somente gestores/admins.
 */
class TeamController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $members = User::where('company_id', $companyId)
            ->withCount([
                'assignedTasks as tasks_total',
                'assignedTasks as tasks_completed' => fn($q) => $q->where('is_published', true)
            ])
            ->orderBy('name')
            ->get();

        return view('team.index', compact('members'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        User::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return back()->with('status', 'Membro adicionado com sucesso!');
    }
    public function updateMember(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($user->company_id === $request->user()->company_id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        $user->update($updateData);

        return back()->with('status', 'Membro atualizado com sucesso!');
    }

    public function removeMember(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($user->company_id === $request->user()->company_id, 403);
        abort_if($user->id === $request->user()->id, 403, 'Você não pode remover a si mesmo.');

        $user->delete();

        return back()->with('status', 'Membro removido da empresa.');
    }
}
