<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Imports\GuestsImport;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Support\Normalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

final class GuestImportService
{
    public const MAX_ROWS = 2000;

    /**
     * Baris yang valid diimpor; baris salah dilaporkan (nomor baris mengikuti file, header = baris 1);
     * baris yang sama persis dengan tamu yang sudah ada dilewati. $dryRun = hanya pratinjau.
     *
     * @return array{dry_run: bool, total: int, valid: int, imported: int, skipped: list<array>, errors: list<array>}
     */
    public function import(Event $event, UploadedFile $file, bool $dryRun = false): array
    {
        $rows = Excel::toArray(new GuestsImport(), $file)[0] ?? [];

        if (count($rows) > self::MAX_ROWS) {
            throw new ApiException('Maksimal ' . self::MAX_ROWS . ' baris per file.', 'IMPORT_TOO_LARGE', 422);
        }

        $categories = GuestCategory::query()->where('event_id', $event->id)->get()->keyBy(fn ($c) => mb_strtolower($c->name));
        $known = Guest::query()->where('event_id', $event->id)->get(['name_normalized', 'phone_normalized'])
            ->mapWithKeys(fn ($g) => [$g->name_normalized . '|' . $g->phone_normalized => true])->all();

        $total = 0;
        $valid = [];
        $errors = [];
        $skipped = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $row = array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : null, $row);

            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $total++;
            $validator = Validator::make($row, [
                'nama' => ['required', 'string', 'max:150'],
                'nama_keluarga' => ['nullable', 'string', 'max:150'],
                'telepon' => ['nullable', 'regex:/^[0-9+\-\s()]{6,25}$/'],
                'email' => ['nullable', 'email', 'max:190'],
                'kuota' => ['nullable', 'integer', 'between:1,100'],
                'alamat' => ['nullable', 'string', 'max:500'],
                'instansi' => ['nullable', 'string', 'max:150'],
                'catatan' => ['nullable', 'string', 'max:1000'],
            ]);

            $messages = $validator->fails() ? collect($validator->errors()->all()) : collect();
            $category = null;

            if (filled($row['kategori'] ?? null)) {
                $category = $categories->get(mb_strtolower($row['kategori']));
                if ($category === null) {
                    $messages->push("Kategori \"{$row['kategori']}\" tidak ditemukan.");
                }
            }

            if ($messages->isNotEmpty()) {
                $errors[] = ['row' => $line, 'messages' => $messages->all()];
                continue;
            }

            $key = Normalizer::name($row['nama']) . '|' . Normalizer::phone($row['telepon'] ?? null);

            if (isset($known[$key])) {
                $skipped[] = ['row' => $line, 'reason' => 'Tamu dengan nama dan telepon yang sama sudah ada.'];
                continue;
            }

            $known[$key] = true;
            $valid[] = [$row, $category];
        }

        if (! $dryRun && $valid !== []) {
            DB::transaction(function () use ($event, $valid): void {
                foreach ($valid as [$row, $category]) {
                    $guest = new Guest([
                        'name' => $row['nama'], 'family_name' => $row['nama_keluarga'] ?? null, 'phone' => $row['telepon'] ?? null,
                        'email' => $row['email'] ?? null, 'address' => $row['alamat'] ?? null,
                        'organization' => $row['instansi'] ?? null, 'notes' => $row['catatan'] ?? null,
                        'quota' => filled($row['kuota'] ?? null) ? (int) $row['kuota'] : 1,
                    ]);
                    $guest->event_id = $event->id;
                    $guest->category_id = $category?->id;
                    $guest->save();
                }
            });
        }

        return [
            'dry_run' => $dryRun,
            'total' => $total,
            'valid' => count($valid),
            'imported' => $dryRun ? 0 : count($valid),
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
