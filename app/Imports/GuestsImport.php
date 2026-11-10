<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Hanya dipakai untuk membaca baris; pemrosesan ada di GuestImportService. */
class GuestsImport implements ToArray, WithHeadingRow
{
    public function array(array $array): void
    {
    }
}
