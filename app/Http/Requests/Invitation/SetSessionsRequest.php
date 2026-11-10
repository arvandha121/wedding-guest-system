<?php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sessions' => ['required', 'array', 'min:1'],
            'sessions.*' => ['string', 'size:26', Rule::exists('event_sessions', 'ulid')->where('event_id', $this->route('invitation')->event_id)],
        ];
    }
}
