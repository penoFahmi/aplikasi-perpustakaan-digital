<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Loan extends Model
{
    use HasUlids;

    protected $table = 'loans';

    protected $fillable = [
        'user_id',
        // 'book_id',
        'tanggal_kembali',
        'denda',
        'status_peminjaman',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'string',
            // 'book_id' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'loan_details', 'loan_id', 'book_id')
                    ->withPivot('status_buku')
                    ->withTimestamps();
    }

}
