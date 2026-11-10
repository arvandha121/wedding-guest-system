<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Token QR tidak pernah dikirim lewat API; hanya tautan undangan publik untuk admin.
 *
 * @mixin \App\Models\Invitation
 */
class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tz = config('wgs.display_timezone');
        $iso = fn ($date) => $date?->timezone($tz)->toIso8601String();

        return [
            'id' => $this->ulid,
            'code' => $this->invitation_code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'quota' => $this->quota,
            'qr_version' => $this->qr_version,
            'public_url' => $this->isRevoked() ? null : route('invitation.public', app(InvitationService::class)->publicToken($this->resource)),
            'guest' => $this->whenLoaded('guest', fn () => [
                'id' => $this->guest->ulid, 'name' => $this->guest->name, 'email' => $this->guest->email, 'phone' => $this->guest->phone,
            ]),
            'sessions' => $this->whenLoaded('sessions', fn () => $this->sessions->map(fn ($s) => ['id' => $s->ulid, 'name' => $s->name])->all()),
            'generated_at' => $iso($this->generated_at),
            'sent_at' => $iso($this->sent_at),
            'opened_at' => $iso($this->opened_at),
            'revoked_at' => $iso($this->revoked_at),
            'revoke_reason' => $this->revoke_reason,
        ];
    }
}
