<?php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class RevokeInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:255']];
    }

    public function messages(): array
    {
        return ['reason.required' => 'Alasan pencabutan wajib diisi.'];
    }
}
