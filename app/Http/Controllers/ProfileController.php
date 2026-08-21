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
            $disk = 's3'; // Fixado no R2/S3 independente do disco de anexos
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

        if ($request->has('notification_settings')) {
            $settings = $request->input('notification_settings', []);
            $user->notification_settings = [
                'daily_enabled' => !empty($settings['daily_enabled']),
                'daily_time' => $settings['daily_time'] ?? '08:00',
                'publish_enabled' => !empty($settings['publish_enabled']),
                'publish_time' => $settings['publish_time'] ?? '10:00',
                'publish_time_reminder_enabled' => !empty($settings['publish_time_reminder_enabled']),
                'daily_briefing_email_enabled' => !empty($settings['daily_briefing_email_enabled']),
                'theme' => $settings['theme'] ?? 'system',
            ];
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Perfil atualizado com sucesso!');
    }

    public function updateSidebarOrder(Request $request)
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $request->user()->update([
            'sidebar_client_order' => $data['order'],
        ]);

        return response()->json(['status' => 'success']);
    }

    public function subscribeToPush(Request $request)
    {
        $endpoint = $request->endpoint;
        $token = $request->keys['auth'] ?? null;
        $key = $request->keys['p256dh'] ?? null;
        $user = $request->user();
        
        $user->updatePushSubscription($endpoint, $key, $token);
        
        return response()->json(['success' => true], 200);
    }
}
