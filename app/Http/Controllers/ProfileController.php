<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar_color' => ['nullable', 'string', 'size:7'],
            'avatar' => ['nullable', 'image', 'max:5120'], // 5MB max
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($data['password'] ?? false) {
            $user->password = Hash::make($data['password']);
        }

        if ($data['avatar_color'] ?? false) {
            $user->avatar_color = $data['avatar_color'];
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $disk = config('filesystems.attachments_disk');
            $folder = "company-{$user->company_id}/avatars";
            $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Exclui avatar antigo, se existir
            if ($user->avatar_path && $user->avatar_disk) {
                Storage::disk($user->avatar_disk)->delete($user->avatar_path);
            }

            $path = $file->storeAs($folder, $name, $disk);

            $user->avatar_path = $path;
            $user->avatar_disk = $disk;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Perfil atualizado com sucesso!');
    }
}
