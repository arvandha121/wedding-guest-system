<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resepsionis menerima versi ringkas; field lengkap hanya untuk admin.
 *
 * @mixin \App\Models\Event
 */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tz = config('wgs.display_timezone');
        $isAdmin = (bool) $request->user()?->role->isAdmin();

        return [
            'id' => $this->ulid,
            'name' => $this->name,
            'title' => $this->title,
            'groom_name' => $this->groom_name,
            'bride_name' => $this->bride_name,
            'event_date' => $this->event_date->toDateString(),
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'venue_name' => $this->venue_name,
            'venue_address' => $this->venue_address,
            'google_maps_url' => $this->google_maps_url,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'checkin_mode' => $this->checkin_mode->value,
            'checkin_opens_at' => $this->checkin_opens_at?->timezone($tz)->toIso8601String(),
            'checkin_closes_at' => $this->checkin_closes_at?->timezone($tz)->toIso8601String(),
            'allow_manual_checkin' => $this->allow_manual_checkin,
            $this->mergeWhen($isAdmin, fn (): array => [
                'description' => $this->description,
                'groom_family' => $this->groom_family,
                'bride_family' => $this->bride_family,
                'theme' => $this->theme,
                'banner_url' => $this->bannerUrl(),
                'couple_photo_url' => $this->couplePhotoUrl(),
                'contact_name' => $this->contact_name,
                'contact_phone' => $this->contact_phone,
                'created_at' => $this->created_at->timezone($tz)->toIso8601String(),
                'updated_at' => $this->updated_at->timezone($tz)->toIso8601String(),
            ]),
            'deleted_at' => $this->deleted_at?->timezone($tz)->toIso8601String(),
            'sessions_count' => $this->whenCounted('sessions'),
            'sessions' => EventSessionResource::collection($this->whenLoaded('sessions')),
        ];
    }
}
