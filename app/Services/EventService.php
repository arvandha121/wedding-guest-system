<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventStatus;
use App\Exceptions\ApiException;
use App\Models\Event;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

final class EventService
{
    private const SORTABLE = ['event_date', 'name', 'status', 'created_at'];

    public function __construct(private readonly ImageService $images)
    {
    }

    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'event_date';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Event::query()
            ->accessibleBy($user)
            ->withCount('sessions')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $like = '%' . mb_strtolower(addcslashes($search, '%_\\')) . '%';
                $query->where(function ($inner) use ($like): void {
                    foreach (['name', 'title', 'groom_name', 'bride_name'] as $column) {
                        $inner->orWhereRaw("LOWER({$column}) LIKE ?", [$like]);
                    }
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('event_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('event_date', '<=', $date))
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    public function create(User $actor, array $data, ?UploadedFile $banner = null, ?UploadedFile $photo = null): Event
    {
        $stored = [];

        try {
            $event = DB::transaction(function () use ($actor, $data, $banner, $photo, &$stored): Event {
                $event = new Event($this->attributes($data));
                $event->status = EventStatus::Draft;
                $event->created_by = $actor->id;

                if ($banner !== null) {
                    $event->banner_path = $stored[] = $this->images->storeWebp($banner, 'events/banners');
                }

                if ($photo !== null) {
                    $event->couple_photo_path = $stored[] = $this->images->storeWebp($photo, 'events/photos', 1600);
                }

                $event->save();

                return $event;
            });
        } catch (Throwable $e) {
            array_map($this->images->delete(...), $stored);
            throw $e;
        }

        return $event->refresh();
    }

    public function update(
        Event $event,
        array $data,
        ?UploadedFile $banner = null,
        ?UploadedFile $photo = null,
        bool $removeBanner = false,
        bool $removePhoto = false,
    ): Event {
        $stored = [];
        $obsolete = [];

        try {
            DB::transaction(function () use ($event, $data, $banner, $photo, $removeBanner, $removePhoto, &$stored, &$obsolete): void {
                $event->fill($this->attributes($data));

                if ($banner !== null || $removeBanner) {
                    $obsolete[] = $event->banner_path;
                    $event->banner_path = $banner !== null
                        ? ($stored[] = $this->images->storeWebp($banner, 'events/banners'))
                        : null;
                }

                if ($photo !== null || $removePhoto) {
                    $obsolete[] = $event->couple_photo_path;
                    $event->couple_photo_path = $photo !== null
                        ? ($stored[] = $this->images->storeWebp($photo, 'events/photos', 1600))
                        : null;
                }

                $event->save();
            });
        } catch (Throwable $e) {
            array_map($this->images->delete(...), $stored);
            throw $e;
        }

        // File lama baru dihapus setelah penyimpanan berhasil.
        array_map($this->images->delete(...), $obsolete);

        return $event->refresh();
    }

    public function changeStatus(Event $event, EventStatus $target): Event
    {
        if ($event->status === $target || ! $event->status->canTransitionTo($target)) {
            throw new ApiException(
                "Status acara tidak dapat diubah dari \"{$event->status->label()}\" ke \"{$target->label()}\".",
                'INVALID_STATUS_TRANSITION',
                422,
            );
        }

        if ($target === EventStatus::Published && ! $event->sessions()->exists()) {
            throw new ApiException(
                'Tambahkan minimal satu sesi sebelum mempublikasikan acara.',
                'EVENT_NOT_READY',
                422,
            );
        }

        $event->status = $target;
        $event->save();

        return $event;
    }

    public function archive(Event $event): void
    {
        $event->delete();
    }

    public function restore(Event $event): Event
    {
        $event->restore();

        return $event;
    }

    private function attributes(array $data): array
    {
        $attributes = Arr::only($data, [
            'name', 'title', 'groom_name', 'bride_name', 'groom_family', 'bride_family',
            'description', 'event_date', 'start_time', 'end_time', 'venue_name', 'venue_address',
            'google_maps_url', 'theme', 'checkin_mode', 'allow_manual_checkin',
            'contact_name', 'contact_phone',
        ]);

        foreach (['checkin_opens_at', 'checkin_closes_at'] as $key) {
            if (array_key_exists($key, $data)) {
                $attributes[$key] = self::toUtc($data[$key]);
            }
        }

        return $attributes;
    }

    /** Input tanpa zona waktu dianggap waktu setempat (display_timezone), disimpan UTC. */
    public static function toUtc(?string $value): ?CarbonImmutable
    {
        return filled($value)
            ? CarbonImmutable::parse($value, config('wgs.display_timezone'))->utc()
            : null;
    }
}
