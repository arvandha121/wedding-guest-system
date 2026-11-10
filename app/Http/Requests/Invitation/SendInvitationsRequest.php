<?php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class SendInvitationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['nullable', 'array', 'min:1', 'max:500'],
            'ids.*' => ['string', 'size:26'],
        ];
    }
}
