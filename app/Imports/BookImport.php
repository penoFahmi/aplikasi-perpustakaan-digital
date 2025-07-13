<?php

// app/Imports/BukuImport.php
namespace App\Imports;

use App\Models\Book;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Penting!

class BookImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // 'judul', 'isbn', 'penerbit', dst. harus sama persis
        // dengan nama header di file Excel Anda.
        return new Book([
            'title'           => $row['judul'],
            'isbn'            => $row['isbn'],
            'publisher'       => $row['penerbit'],
            'year_published'  => $row['tahun_terbit'],
            'stock'           => $row['stok'],
        ]);
    }
}
