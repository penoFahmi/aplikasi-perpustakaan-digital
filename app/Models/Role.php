<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['name', 'display_name', 'level'];

    /**
     * Sebuah Role bisa dimiliki oleh banyak User.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
