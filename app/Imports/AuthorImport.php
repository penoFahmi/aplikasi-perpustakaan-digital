<?php

// app/Imports/BukuImport.php
namespace App\Imports;

use App\Models\Author;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Penting!

class AuthorImport implements ToModel, WithHeadingRow
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
        return new Author([
            'name'            => $row['name'],
            'nationality'     => $row['nationality'],
            'birthdate'       => $row['birthdate'],
        ]);
    }
}
