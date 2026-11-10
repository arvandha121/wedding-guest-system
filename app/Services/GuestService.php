<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

final class GuestService
{
    public const FIELDS = ['name', 'family_name', 'phone', 'email', 'address', 'organization', 'notes', 'quota'];

    public function paginate(Event $event, array $f): LengthAwarePaginator
    {
        $sort = in_array($f['sort'] ?? null, ['name', 'created_at'], true) ? $f['sort'] : 'name';
        $direction = ($f['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return Guest::query()
            ->where('event_id', $event->id)
            ->with('category')
            ->when($f['category'] ?? null, fn ($q, string $c) => $q->whereHas('category', fn ($w) => $w->where('ulid', $c)))
            ->when($f['search'] ?? null, function ($q, string $search): void {
                $like = '%' . mb_strtolower(addcslashes($search, '%_\\')) . '%';
                $digits = (string) preg_replace('/\D+/', '', $search);
                $digits = str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;

                $q->where(function ($w) use ($like, $digits): void {
                    $w->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(family_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like]);

                    if (strlen($digits) >= 4) {
                        $w->orWhere('phone_normalized', 'like', "%{$digits}%");
                    }
                });
            })
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate((int) ($f['per_page'] ?? 25))
            ->withQueryString();
    }

    /** @return array{guest: Guest, duplicates: Collection} */
    public function create(Event $event, array $data): array
    {
        $guest = new Guest(Arr::only($data, self::FIELDS));
        $guest->quota = $data['quota'] ?? 1;
        $guest->event_id = $event->id;
        $guest->category_id = $this->categoryId($event->id, $data['category'] ?? null);
        $guest->save();

        return ['guest' => $guest->refresh()->load('category'), 'duplicates' => $this->duplicates($guest)];
    }

    public function update(Guest $guest, array $data): Guest
    {
        $guest->fill(Arr::only($data, self::FIELDS));
        $guest->quota = $data['quota'] ?? $guest->quota;

        if (array_key_exists('category', $data)) {
            $guest->category_id = $this->categoryId($guest->event_id, $data['category']);
        }

        $guest->save();

        return $guest->refresh()->load('category');
    }

    public function delete(Guest $guest): void
    {
        $guest->delete();
    }

    /** @param list<string> $ulids */
    public function bulkDelete(Event $event, array $ulids): int
    {
        return Guest::query()->where('event_id', $event->id)->whereIn('ulid', $ulids)->get()->each->delete()->count();
    }

    /** @param list<string> $ulids */
    public function bulkCategory(Event $event, array $ulids, ?string $categoryUlid): int
    {
        return Guest::query()
            ->where('event_id', $event->id)
            ->whereIn('ulid', $ulids)
            ->update(['category_id' => $this->categoryId($event->id, $categoryUlid)]);
    }

    /** Tamu lain di acara yang sama dengan nama (ternormalisasi) dan telepon yang sama. */
    public function duplicates(Guest $guest): Collection
    {
        return Guest::query()
            ->where('event_id', $guest->event_id)
            ->where('id', '!=', $guest->id)
            ->where('name_normalized', $guest->name_normalized)
            ->when(
                $guest->phone_normalized,
                fn ($q, string $phone) => $q->where('phone_normalized', $phone),
                fn ($q) => $q->whereNull('phone_normalized'),
            )
            ->get(['id', 'ulid', 'name']);
    }

    private function categoryId(int $eventId, ?string $ulid): ?int
    {
        return $ulid === null
            ? null
            : GuestCategory::query()->where('event_id', $eventId)->where('ulid', $ulid)->firstOrFail()->id;
    }
}
