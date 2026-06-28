<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * @return HasOne<Candidate, $this>
     */
    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    /**
     * Build a query for permissions granted through this user's roles.
     *
     * @return Builder<Permission>
     */
    public function permissions(): Builder
    {
        return Permission::query()->whereHas(
            'roles.users',
            fn (Builder $query) => $query->where('users.id', $this->getKey()),
        );
    }

    public function hasRole(string $roleSlug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $roleSlug);
        }

        return $this->roles()->where('roles.slug', $roleSlug)->exists();
    }

    /**
     * @param  array<int, string>  $roleSlugs
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        $roleSlugs = array_values(array_unique(array_filter(
            $roleSlugs,
            fn (mixed $slug): bool => is_string($slug) && $slug !== '',
        )));

        if ($roleSlugs === []) {
            return false;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(
                fn (Role $role): bool => in_array($role->slug, $roleSlugs, true),
            );
        }

        return $this->roles()->whereIn('roles.slug', $roleSlugs)->exists();
    }

    public function hasPermissionTo(string $permissionSlug): bool
    {
        return $this->isSuperAdmin()
            || $this->permissions()->where('permissions.slug', $permissionSlug)->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
}
