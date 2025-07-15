<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class OverdueReturnsReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $overdueReturns;

    public function __construct(Collection $overdueReturns)
    {
        $this->overdueReturns = $overdueReturns;
    }

    public function collection()
    {
        return $this->overdueReturns;
    }

    public function headings(): array
    {
        return [
            'Nama Anggota',
            'Judul Buku',
            'Jatuh Tempo',
            'Tanggal Kembali',
            'Hari Terlambat',
        ];
    }

    public function map($loan): array
    {
        return [
            $loan->user->name,
            $loan->books->pluck('title')->implode(', '),
            Carbon::parse($loan->due_date)->format('d-m-Y'),
            Carbon::parse($loan->actual_return_date)->format('d-m-Y'),
            // Properti 'days_overdue' sudah dihitung di controller
            $loan->days_overdue,
        ];
    }
}
