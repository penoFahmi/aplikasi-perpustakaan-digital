<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class BookInventoryReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $books;

    public function __construct(Collection $books)
    {
        $this->books = $books;
    }

    public function collection()
    {
        return $this->books;
    }

    public function headings(): array
    {
        return [
            'Judul Buku',
            'Stok Total',
            'Stok Dipinjam',
            'Stok Tersedia',
        ];
    }

    public function map($book): array
    {
        return [
            $book->title,
            $book->stock,
            // Properti 'borrowed_count' dihitung di controller
            $book->borrowed_count,
            // Properti 'available_stock' dihitung di controller
            $book->available_stock,
        ];
    }
}
