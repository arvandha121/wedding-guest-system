<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Services\InvitationService;
use App\Services\QRCodeService;
use Illuminate\Http\Response;

/** Halaman undangan untuk tamu (tanpa login). Token tidak dikenal selalu 404. */
final class InvitationController extends Controller
{
    private const HEADERS = [
        'Cache-Control' => 'private, no-store',
        'Referrer-Policy' => 'no-referrer',
        'X-Robots-Tag' => 'noindex, nofollow',
    ];

    public function __construct(
        private readonly InvitationService $invitations,
        private readonly QRCodeService $qr,
    ) {}

    public function show(string $token): Response
    {
        $invitation = $this->resolve($token);
        $invitation->load('event', 'guest', 'sessions');

        if ($invitation->isRevoked()) {
            return response()->view('invitations.revoked', ['event' => $invitation->event], 410)->withHeaders(self::HEADERS);
        }

        $this->invitations->markOpened($invitation);

        return response()->view('invitations.show', [
            'invitation' => $invitation,
            'event' => $invitation->event,
            'qrUrl' => route('invitation.public.qr', $token),
            'downloadUrl' => route('invitation.public.qr', ['token' => $token, 'download' => 1]),
            'preview' => false,
        ])->withHeaders(self::HEADERS);
    }

    public function qr(string $token): Response
    {
        $invitation = $this->resolve($token);
        abort_if($invitation->isRevoked(), 410);

        $disposition = request()->boolean('download') ? 'attachment' : 'inline';

        return response($this->qr->webp($this->invitations->qrToken($invitation)), 200, self::HEADERS + [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => $disposition . '; filename="qr-undangan.webp"',
        ]);
    }

    private function resolve(string $token): Invitation
    {
        $invitation = $this->invitations->findByPublicToken($token);

        abort_if(
            $invitation === null
                || in_array($invitation->event->status, [EventStatus::Draft, EventStatus::Cancelled], true),
            404,
        );

        return $invitation;
    }
}
