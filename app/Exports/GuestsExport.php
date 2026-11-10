<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GuestsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly Event $event)
    {
    }

    public function query(): Builder
    {
        return Guest::query()->where('event_id', $this->event->id)->with('category')->orderBy('name')->orderBy('id');
    }

    public function headings(): array
    {
        return ['nama', 'nama_keluarga', 'telepon', 'email', 'kategori', 'kuota', 'alamat', 'instansi', 'catatan'];
    }

    /** @param Guest $guest */
    public function map($guest): array
    {
        return [
            $guest->name, $guest->family_name, $guest->phone, $guest->email, $guest->category?->name,
            $guest->quota, $guest->address, $guest->organization, $guest->notes,
        ];
    }
}
