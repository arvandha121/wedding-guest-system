<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pengguna beserta penugasannya pada satu acara (membutuhkan data pivot).
 *
 * @mixin \App\Models\User
 */
class EventMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'name' => $this->name,
            'email' => $this->email,
            'assigned_role' => $this->pivot->assigned_role,
            'status' => $this->status->value,
            'assigned_at' => $this->pivot->created_at?->timezone(config('wgs.display_timezone'))->toIso8601String(),
        ];
    }
}
