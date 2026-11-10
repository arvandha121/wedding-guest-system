<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\GenerateInvitationsRequest;
use App\Http\Requests\Invitation\ListInvitationsRequest;
use App\Http\Requests\Invitation\RevokeInvitationRequest;
use App\Models\Event;
use App\Models\Invitation;
use App\Services\DeliveryService;
use App\Services\InvitationService;
use App\Services\QRCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitations,
        private readonly DeliveryService $delivery,
        private readonly QRCodeService $qr,
    ) {
    }

    public function index(ListInvitationsRequest $request, Event $event): View
    {
        Gate::authorize('manageInvitations', $event);

        $f = $request->validated();

        $items = Invitation::query()
            ->where('event_id', $event->id)
            ->with('guest')
            ->when($f['status'] ?? null, fn ($q, string $s) => $q->where('status', $s))
            ->when($f['search'] ?? null, function ($q, string $search): void {
                $like = '%' . mb_strtolower(addcslashes($search, '%_\\')) . '%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(invitation_code) LIKE ?', [$like])
                    ->orWhereHas('guest', fn ($g) => $g->whereRaw('LOWER(name) LIKE ?', [$like])));
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.invitations.index', [
            'event' => $event, 'invitations' => $items, 'filters' => $f, 'statuses' => InvitationStatus::cases(),
        ]);
    }

    public function store(GenerateInvitationsRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageInvitations', $event);

        $result = $this->invitations->generateMany($event, null, $request->validated('sessions'));

        return $this->back($event, "{$result['created']} undangan dibuat, {$result['skipped']} tamu sudah punya undangan.");
    }

    public function sendAll(Event $event): RedirectResponse
    {
        Gate::authorize('manageInvitations', $event);

        $result = $this->delivery->queueMany($event, null);

        return $this->back($event, "{$result['queued']} email masuk antrean, {$result['skipped']} dilewati (tanpa email).");
    }

    public function resend(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('manageInvitations', $invitation->event);

        $this->delivery->queueEmail($invitation);

        return $this->back($invitation->event, 'Email undangan masuk antrean.');
    }

    public function regenerate(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('manageInvitations', $invitation->event);

        $this->invitations->regenerateQr($invitation);

        return $this->back($invitation->event, 'QR Code baru dibuat. QR lama tidak berlaku lagi.');
    }

    public function revoke(RevokeInvitationRequest $request, Invitation $invitation): RedirectResponse
    {
        Gate::authorize('manageInvitations', $invitation->event);

        $this->invitations->revoke($invitation, $request->user(), $request->validated('reason'));

        return $this->back($invitation->event, 'Undangan berhasil dicabut.');
    }

    public function preview(Invitation $invitation): View
    {
        Gate::authorize('manageInvitations', $invitation->event);
        abort_if($invitation->isRevoked(), 410, 'Undangan sudah dicabut.');

        $invitation->load('event', 'guest', 'sessions');

        return view('invitations.show', [
            'invitation' => $invitation,
            'event' => $invitation->event,
            'qrUrl' => route('admin.invitations.qr', $invitation),
            'downloadUrl' => route('admin.invitations.qr', ['invitation' => $invitation, 'download' => 1]),
            'preview' => true,
        ]);
    }

    public function qr(Request $request, Invitation $invitation): Response
    {
        Gate::authorize('manageInvitations', $invitation->event);
        abort_if($invitation->isRevoked(), 410);

        return response($this->qr->webp($this->invitations->qrToken($invitation)), 200, [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline') . '; filename="qr-' . $invitation->invitation_code . '.webp"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function back(Event $event, string $message): RedirectResponse
    {
        return redirect()->route('admin.events.invitations.index', $event)->with('status', $message);
    }
}
