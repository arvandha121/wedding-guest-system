<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Guest
 */
class GuestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'name' => $this->name,
            'family_name' => $this->family_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'organization' => $this->organization,
            'notes' => $this->notes,
            'quota' => $this->quota,
            'category' => $this->whenLoaded('category', fn () => $this->category
                ? ['id' => $this->category->ulid, 'name' => $this->category->name]
                : null),
            'created_at' => $this->created_at->timezone(config('wgs.display_timezone'))->toIso8601String(),
        ];
    }
}
