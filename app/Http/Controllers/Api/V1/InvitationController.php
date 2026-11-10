<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\GenerateInvitationsRequest;
use App\Http\Requests\Invitation\ListInvitationsRequest;
use App\Http\Requests\Invitation\RevokeInvitationRequest;
use App\Http\Requests\Invitation\SendInvitationsRequest;
use App\Http\Requests\Invitation\SetSessionsRequest;
use App\Http\Resources\Api\V1\InvitationResource;
use App\Models\Event;
use App\Models\Invitation;
use App\Services\DeliveryService;
use App\Services\InvitationService;
use App\Services\QRCodeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitations,
        private readonly DeliveryService $delivery,
        private readonly QRCodeService $qr,
    ) {
    }

    public function index(ListInvitationsRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageInvitations', $event);

        $f = $request->validated();

        $items = Invitation::query()
            ->where('event_id', $event->id)
            ->with('guest', 'sessions')
            ->when($f['status'] ?? null, fn ($q, string $s) => $q->where('status', $s))
            ->when($f['search'] ?? null, function ($q, string $search): void {
                $like = '%' . mb_strtolower(addcslashes($search, '%_\\')) . '%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(invitation_code) LIKE ?', [$like])
                    ->orWhereHas('guest', fn ($g) => $g->whereRaw('LOWER(name) LIKE ?', [$like])));
            })
            ->latest('id')
            ->paginate((int) ($f['per_page'] ?? 25))
            ->withQueryString();

        return ApiResponse::paginated($items, InvitationResource::class, 'Daftar undangan berhasil diambil.');
    }

    public function store(GenerateInvitationsRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageInvitations', $event);

        $result = $this->invitations->generateMany(
            $event,
            $request->boolean('all') ? null : $request->validated('guests'),
            $request->validated('sessions'),
        );

        return ApiResponse::success($result, "{$result['created']} undangan dibuat.", 201);
    }

    public function show(Request $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('manageInvitations', $invitation->event);

        return ApiResponse::success($this->present($invitation, $request), 'Detail undangan berhasil diambil.');
    }

    public function sessions(SetSessionsRequest $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('manageInvitations', $invitation->event);

        $this->invitations->setSessions($invitation, $request->validated('sessions'));

        return ApiResponse::success($this->present($invitation, $request), 'Sesi undangan berhasil diperbarui.');
    }

    public function regenerate(Request $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('manageInvitations', $invitation->event);

        $this->invitations->regenerateQr($invitation);

        return ApiResponse::success($this->present($invitation, $request), 'QR Code baru dibuat. QR lama tidak berlaku lagi.');
    }

    public function revoke(RevokeInvitationRequest $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('manageInvitations', $invitation->event);

        $this->invitations->revoke($invitation, $request->user(), $request->validated('reason'));

        return ApiResponse::success($this->present($invitation, $request), 'Undangan berhasil dicabut.');
    }

    public function resend(Request $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('manageInvitations', $invitation->event);
        $request->validate(['channel' => ['required', Rule::in(['email'])]]);

        $log = $this->delivery->queueEmail($invitation);

        return ApiResponse::success(['delivery' => ['status' => $log->status, 'recipient' => $log->recipient]], 'Email undangan masuk antrean.', 202);
    }

    public function sendAll(SendInvitationsRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageInvitations', $event);

        $result = $this->delivery->queueMany($event, $request->validated('ids'));

        return ApiResponse::success($result, "{$result['queued']} email masuk antrean.", 202);
    }

    public function deliveries(Invitation $invitation): JsonResponse
    {
        Gate::authorize('manageInvitations', $invitation->event);

        $items = $invitation->deliveries()->get()->map(fn ($d) => [
            'channel' => $d->channel, 'recipient' => $d->recipient, 'status' => $d->status, 'attempt' => $d->attempt,
            'sent_at' => $d->sent_at?->toIso8601String(), 'error' => $d->error_message,
        ])->all();

        return ApiResponse::success($items, 'Riwayat pengiriman berhasil diambil.');
    }

    public function qr(Invitation $invitation): Response
    {
        Gate::authorize('manageInvitations', $invitation->event);

        return $this->qrResponse($invitation);
    }

    public function qrResponse(Invitation $invitation): Response
    {
        abort_if($invitation->isRevoked(), 410, 'Undangan sudah dicabut.');

        return response($this->qr->webp($this->invitations->qrToken($invitation)), 200, [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => 'inline; filename="qr-' . $invitation->invitation_code . '.webp"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function present(Invitation $invitation, Request $request): array
    {
        return InvitationResource::make($invitation->load('guest', 'sessions'))->resolve($request);
    }
}
