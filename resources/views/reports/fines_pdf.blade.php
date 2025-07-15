<!DOCTYPE html>
<html>
<head>
    <title>Laporan Denda</title>
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
        <h1>Laporan Denda</h1>
        <p>Dicetak pada: {{ now()->format('d-m-Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Anggota</th>
                <th>Judul Buku</th>
                <th>Jumlah Denda (Rp)</th>
                <th>Status Denda</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fines as $index => $loan)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $loan->user->name ?? 'N/A' }}</td>
                    <td>{{ $loan->books->pluck('title')->implode(', ') }}</td>
                    <td>{{ number_format($loan->denda, 0, ',', '.') }}</td>
                    <td>{{ $loan->status_denda }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">Tidak ada data denda ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
