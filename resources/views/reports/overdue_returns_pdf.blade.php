<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keterlambatan</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .header p { margin: 5px 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Keterlambatan</h1>
        <p>Dicetak pada: {{ now()->format('d-m-Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Anggota</th>
                <th>Judul Buku</th>
                <th>Jatuh Tempo</th>
                <th>Tanggal Kembali</th>
                <th>Hari Terlambat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($overdue_returns as $index => $loan)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $loan->user->name ?? 'N/A' }}</td>
                    <td>{{ $loan->books->pluck('title')->implode(', ') }}</td>
                    <td>{{ \Carbon\Carbon::parse($loan->due_date)->format('d-m-Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($loan->actual_return_date)->format('d-m-Y') }}</td>
                    <td>{{ $loan->days_overdue }} hari</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data keterlambatan ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
