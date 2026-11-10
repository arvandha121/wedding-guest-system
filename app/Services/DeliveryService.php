<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventStatus;
use App\Exceptions\ApiException;
use App\Jobs\SendInvitationEmailJob;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\InvitationDeliveryLog;

final class DeliveryService
{
    public function queueEmail(Invitation $invitation): InvitationDeliveryLog
    {
        $invitation->loadMissing('event', 'guest');

        if ($invitation->isRevoked()) {
            throw new ApiException('Undangan sudah dicabut.', 'INVITATION_REVOKED', 422);
        }

        if (in_array($invitation->event->status, [EventStatus::Draft, EventStatus::Cancelled], true)) {
            throw new ApiException('Publikasikan acara terlebih dahulu sebelum mengirim undangan.', 'EVENT_NOT_PUBLISHED', 422);
        }

        if (blank($invitation->guest->email)) {
            throw new ApiException('Tamu ini tidak memiliki alamat email.', 'NO_RECIPIENT', 422);
        }

        $log = new InvitationDeliveryLog();
        $log->invitation_id = $invitation->id;
        $log->channel = 'email';
        $log->recipient = $invitation->guest->email;
        $log->provider = 'smtp';
        $log->status = 'queued';
        $log->save();

        SendInvitationEmailJob::dispatch($log->id);

        return $log;
    }

    /**
     * Kirim email ke undangan berstatus "dibuat" yang tamunya punya email.
     *
     * @param list<string>|null $invitationUlids null = semua yang belum terkirim
     * @return array{queued: int, skipped: int}
     */
    public function queueMany(Event $event, ?array $invitationUlids): array
    {
        if (in_array($event->status, [EventStatus::Draft, EventStatus::Cancelled], true)) {
            throw new ApiException('Publikasikan acara terlebih dahulu sebelum mengirim undangan.', 'EVENT_NOT_PUBLISHED', 422);
        }

        $items = Invitation::query()->where('event_id', $event->id)->active()->with('event', 'guest')
            ->when($invitationUlids !== null, fn ($q) => $q->whereIn('ulid', $invitationUlids), fn ($q) => $q->where('status', 'generated'))
            ->get();

        $queued = 0;

        foreach ($items as $invitation) {
            if (filled($invitation->guest->email)) {
                $this->queueEmail($invitation);
                $queued++;
            }
        }

        return ['queued' => $queued, 'skipped' => $items->count() - $queued];
    }
}
