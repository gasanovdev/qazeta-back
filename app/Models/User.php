<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'password',
        'role',
        'avatar',
        'interest_ids',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'interest_ids' => 'array',
    ];

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function subscribedBranches(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'branch_subscriptions',
            'user_id',
            'branch_id'
        )->withTimestamps();
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'branch_subscriptions',
            'branch_id',
            'user_id'
        )->withTimestamps();
    }

    public function savedNews(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'saved_news')->withTimestamps();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar);
    }
}
