<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPushSubscriptions;

    public const ROLE_ADMIN = 'admin';      // administrador
    public const ROLE_MEMBER = 'member';    // colaborador

    public const ROLES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_MEMBER => 'Colaborador',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'role',
        'avatar_color',
        'avatar_path',
        'avatar_disk',
        'password',
        'sidebar_client_order',
        'notification_settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'sidebar_client_order' => 'array',
            'notification_settings' => 'array',
        ];
    }

    /* --------------------------------------------------------------------- */
    /* Relações                                                              */
    /* --------------------------------------------------------------------- */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_user');
    }

    public function favoriteProjects()
    {
        return $this->belongsToMany(Project::class, 'project_user_favorites')->withTimestamps();
    }

    /* --------------------------------------------------------------------- */
    /* Helpers                                                               */
    /* --------------------------------------------------------------------- */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMember(): bool
    {
        return in_array($this->role, [self::ROLE_MEMBER, 'colaborador'], true);
    }

    public function isManager(): bool
    {
        return $this->isAdmin();
    }

    public function roleLabel(): string
    {
        if ($this->role === 'colaborador' || $this->role === self::ROLE_MEMBER) {
            return 'Colaborador';
        }

        return self::ROLES[$this->role] ?? $this->role;
    }

    /** Iniciais para o avatar (ex.: "Felipe Ruiz" -> "FR"). */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))
            ->implode('');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Storage::disk($this->avatar_disk ?: 's3')
                ->url($this->avatar_path);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
