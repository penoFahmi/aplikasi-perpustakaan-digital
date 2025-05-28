<?php

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    use HasUlids;

    protected $table = 'authors';

    protected $fillable = [
        'name',
        'nationality',
        'birthdate',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'nationality' => 'string',
            'birthdate' => 'string',
        ];
    }


    public function bookAuthors(): HasMany
    {
        return $this->hasMany(BookAuthor::class, 'author_id');
    }

}
