<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class MemberActivityReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $members;

    public function __construct(Collection $members)
    {
        $this->members = $members;
    }

    public function collection()
    {
        return $this->members;
    }

    public function headings(): array
    {
        return [
            'Nama Anggota',
            'Total Pinjaman',
            'Total Denda (Rp)',
        ];
    }

    public function map($member): array
    {
        return [
            $member->name,
            $member->total_loans,
            // Menggunakan properti agregat dari controller
            $member->total_fines ?? 0,
        ];
    }
}
