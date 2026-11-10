<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Exceptions\ApiException;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class InvitationService
{
    public function __construct(private readonly TokenService $tokens)
    {
    }

    /** @param list<int>|null $sessionIds null = semua sesi acara */
    public function generate(Event $event, Guest $guest, ?array $sessionIds = null): Invitation
    {
        $sessionIds ??= $event->sessions()->pluck('event_sessions.id')->all();

        if ($sessionIds === []) {
            throw new ApiException('Tambahkan minimal satu sesi sebelum membuat undangan.', 'EVENT_HAS_NO_SESSIONS', 422);
        }

        try {
            return DB::transaction(function () use ($event, $guest, $sessionIds): Invitation {
                $invitation = new Invitation();
                $invitation->event_id = $event->id;
                $invitation->guest_id = $guest->id;
                $invitation->invitation_code = $this->uniqueCode();
                $this->assignPublicToken($invitation, $this->tokens->publicToken());
                $this->assignQrToken($invitation, $this->tokens->qrToken());
                $invitation->quota = $guest->quota;
                $invitation->status = InvitationStatus::Generated;
                $invitation->generated_at = now();
                $invitation->save();
                $invitation->sessions()->attach($sessionIds);

                return $invitation;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ApiException('Tamu ini sudah memiliki undangan aktif.', 'INVITATION_EXISTS', 409);
        }
    }

    /**
     * Membuat undangan untuk tamu yang belum punya undangan aktif.
     *
     * @param list<string>|null $guestUlids null = semua tamu acara
     * @param list<string>|null $sessionUlids null = semua sesi acara
     * @return array{created: int, skipped: int}
     */
    public function generateMany(Event $event, ?array $guestUlids, ?array $sessionUlids): array
    {
        $sessionIds = $sessionUlids === null
            ? null
            : EventSession::query()->where('event_id', $event->id)->whereIn('ulid', $sessionUlids)->pluck('id')->all();

        $target = Guest::query()->where('event_id', $event->id)->when($guestUlids !== null, fn ($q) => $q->whereIn('ulid', $guestUlids));
        $pending = (clone $target)->whereDoesntHave('invitations', fn ($q) => $q->active())->get();

        foreach ($pending as $guest) {
            $this->generate($event, $guest, $sessionIds);
        }

        return ['created' => $pending->count(), 'skipped' => (clone $target)->count() - $pending->count()];
    }

    public function revoke(Invitation $invitation, User $by, string $reason): Invitation
    {
        if ($invitation->isRevoked()) {
            throw new ApiException('Undangan sudah dicabut.', 'INVITATION_REVOKED', 422);
        }

        $invitation->status = InvitationStatus::Revoked;
        $invitation->revoked_at = now();
        $invitation->revoked_by = $by->id;
        $invitation->revoke_reason = $reason;
        $invitation->save();

        return $invitation;
    }

    /** Token QR lama langsung tidak berlaku; tautan undangan (public token) tetap sama. */
    public function regenerateQr(Invitation $invitation): Invitation
    {
        $this->assertActive($invitation);

        $this->assignQrToken($invitation, $this->tokens->qrToken());
        $invitation->qr_version++;
        $invitation->save();

        return $invitation;
    }

    /** @param list<string> $sessionUlids */
    public function setSessions(Invitation $invitation, array $sessionUlids): Invitation
    {
        $this->assertActive($invitation);

        $ids = EventSession::query()->where('event_id', $invitation->event_id)->whereIn('ulid', $sessionUlids)->pluck('id')->all();

        $invitation->sessions()->sync($ids);

        return $invitation;
    }

    public function findByQrToken(string $token): ?Invitation
    {
        return Invitation::query()->where('qr_token_hash', $this->tokens->hash($token))->first();
    }

    public function findByPublicToken(string $token): ?Invitation
    {
        return Invitation::query()->where('public_token_hash', $this->tokens->hash($token))->first();
    }

    public function qrToken(Invitation $invitation): string
    {
        return $this->tokens->decrypt($invitation->qr_token_enc);
    }

    public function publicToken(Invitation $invitation): string
    {
        return $this->tokens->decrypt($invitation->public_token_enc);
    }

    public function markOpened(Invitation $invitation): void
    {
        if (in_array($invitation->status, [InvitationStatus::Generated, InvitationStatus::Sent, InvitationStatus::Delivered], true)) {
            $invitation->status = InvitationStatus::Opened;
            $invitation->opened_at ??= now();
            $invitation->save();
        }
    }

    public function markSent(Invitation $invitation): void
    {
        if (in_array($invitation->status, [InvitationStatus::Draft, InvitationStatus::Generated], true)) {
            $invitation->status = InvitationStatus::Sent;
            $invitation->sent_at = now();
            $invitation->save();
        }
    }

    private function assertActive(Invitation $invitation): void
    {
        if ($invitation->isRevoked()) {
            throw new ApiException('Undangan sudah dicabut dan tidak dapat diubah.', 'INVITATION_REVOKED', 422);
        }
    }

    private function assignQrToken(Invitation $invitation, string $token): void
    {
        $invitation->qr_token_hash = $this->tokens->hash($token);
        $invitation->qr_token_enc = $this->tokens->encrypt($token);
    }

    private function assignPublicToken(Invitation $invitation, string $token): void
    {
        $invitation->public_token_hash = $this->tokens->hash($token);
        $invitation->public_token_enc = $this->tokens->encrypt($token);
    }

    private function uniqueCode(): string
    {
        do {
            $code = $this->tokens->code();
        } while (Invitation::query()->where('invitation_code', $code)->exists());

        return $code;
    }
}
