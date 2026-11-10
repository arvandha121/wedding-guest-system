<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GuestTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['nama', 'nama_keluarga', 'telepon', 'email', 'kategori', 'kuota', 'alamat', 'instansi', 'catatan'];
    }

    public function array(): array
    {
        return [['Budi Santoso', 'Keluarga Santoso', '081234567890', 'budi@contoh.com', 'Tamu Umum', 2, '', '', '']];
    }
}
