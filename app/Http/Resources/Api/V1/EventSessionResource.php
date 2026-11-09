<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\EventSession
 */
class EventSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tz = config('wgs.display_timezone');

        return [
            'id' => $this->ulid,
            'name' => $this->name,
            'start_time' => $this->start_time->timezone($tz)->toIso8601String(),
            'end_time' => $this->end_time->timezone($tz)->toIso8601String(),
            'checkin_opens_at' => $this->checkin_opens_at?->timezone($tz)->toIso8601String(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'sort_order' => $this->sort_order,
        ];
    }
}
