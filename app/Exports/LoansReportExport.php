<?php

namespace App\Exports;

use App\Models\Loan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class LoansReportExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data yang sama seperti di ReportController Anda
        return Loan::with(['user:id,name', 'books:id,title'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Definisikan judul kolom di file Excel
        return [
            'ID Pinjaman',
            'Nama Anggota',
            'Judul Buku',
            'Tanggal Pinjam',
            'Tanggal Jatuh Tempo',
            'Status'
        ];
    }

    /**
     * @var Loan $loan
     * @return array
     */
    public function map($loan): array
    {
        // Petakan setiap baris data ke kolom yang sesuai
        return [
            $loan->id,
            $loan->user->name ?? 'N/A',
            $loan->books->pluck('title')->implode(', '),
            Carbon::parse($loan->created_at)->format('d-m-Y'),
            Carbon::parse($loan->tanggal_kembali)->format('d-m-Y'),
            $loan->status_peminjaman,
        ];
    }
}
