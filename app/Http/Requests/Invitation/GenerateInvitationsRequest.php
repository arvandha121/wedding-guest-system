<?php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateInvitationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventId = $this->route('event')->id;

        return [
            'all' => ['nullable', 'boolean'],
            'guests' => [Rule::requiredIf(! $this->boolean('all')), 'nullable', 'array', 'min:1', 'max:500'],
            'guests.*' => ['string', 'size:26', Rule::exists('guests', 'ulid')->where('event_id', $eventId)->whereNull('deleted_at')],
            'sessions' => ['nullable', 'array', 'min:1'],
            'sessions.*' => ['string', 'size:26', Rule::exists('event_sessions', 'ulid')->where('event_id', $eventId)],
        ];
    }

    public function messages(): array
    {
        return [
            'guests.required' => 'Pilih tamu atau gunakan opsi semua tamu.',
            'guests.*.exists' => 'Ada tamu yang tidak ditemukan pada acara ini.',
            'sessions.*.exists' => 'Ada sesi yang tidak ditemukan pada acara ini.',
        ];
    }
}
