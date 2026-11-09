<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Support\Arr;

final class SessionService
{
    public function create(Event $event, array $data): EventSession
    {
        $session = new EventSession($this->attributes($data));
        $session->event_id = $event->id;
        $session->status = SessionStatus::Scheduled;
        $session->sort_order = min(250, ((int) $event->sessions()->max('sort_order')) + ($event->sessions()->exists() ? 1 : 0));
        $session->save();

        return $session->refresh();
    }

    public function update(EventSession $session, array $data): EventSession
    {
        $session->fill($this->attributes($data));
        $session->save();

        return $session->refresh();
    }

    /**
     * Penghapusan sesi akan dibatasi pada phase berikutnya bila sudah ada
     * undangan atau kehadiran yang terkait (tabelnya belum ada di Phase 2).
     */
    public function delete(EventSession $session): void
    {
        $session->delete();
    }

    private function attributes(array $data): array
    {
        $attributes = Arr::only($data, ['name', 'status', 'sort_order']);

        foreach (['start_time', 'end_time', 'checkin_opens_at'] as $key) {
            if (array_key_exists($key, $data)) {
                $attributes[$key] = EventService::toUtc($data[$key]);
            }
        }

        return $attributes;
    }
}
