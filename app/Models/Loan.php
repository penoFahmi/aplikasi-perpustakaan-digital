<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
     use HasUlids;

    protected $table = 'loans';

    protected $fillable = [
        'user_id',
        'book_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'string',
            'book_id' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

}
