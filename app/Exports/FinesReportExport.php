<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class FinesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $fines;

    public function __construct(Collection $fines)
    {
        $this->fines = $fines;
    }

    public function collection()
    {
        return $this->fines;
    }

    public function headings(): array
    {
        return [
            'Nama Anggota',
            'Judul Buku',
            'Jumlah Denda (Rp)',
            'Status Denda',
        ];
    }

    public function map($loan): array
    {
        return [
            $loan->user->name,
            $loan->books->pluck('title')->implode(', '),
            $loan->denda,
            $loan->status_denda,
        ];
    }
}
