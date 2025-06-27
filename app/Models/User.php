<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
   use HasApiTokens, HasFactory, Notifiable, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'membership_date',
        'role_id',
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
            'name'=>'string',
            'email'=>'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'membership_date' => 'date',
        ];
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

   public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $roleName): bool
    {
        // Pastikan relasi 'role' tidak null sebelum mengakses properti 'name'
        return $this->role && $this->role->name === $roleName;
    }

    // PASTIKAN FUNGSI INI ADA DAN TERSIMPAN
    public function isAdmin(): bool
    {
        // Pengecekan bisa berdasarkan nama atau level, pastikan 'admin' ada di tabel roles Anda
        return $this->role->name === 'admin';
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'user_id');
    }
}
