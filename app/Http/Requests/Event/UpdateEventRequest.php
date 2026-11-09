<?php

declare(strict_types=1);

namespace App\Http\Requests\Event;

class UpdateEventRequest extends StoreEventRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'remove_banner' => ['nullable', 'boolean'],
            'remove_couple_photo' => ['nullable', 'boolean'],
        ];
    }
}
