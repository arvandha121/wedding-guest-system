<?php

declare(strict_types=1);

namespace App\Http\Requests\Event;

use App\Enums\CheckinMode;
use App\Rules\GoogleMapsUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi dilakukan lewat EventPolicy di controller
    }

    public function rules(): array
    {
        $closes = ['nullable', 'date'];

        if ($this->filled('checkin_opens_at')) {
            $closes[] = 'after:checkin_opens_at';
        }

        $image = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'];

        return [
            'name' => ['required', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:200'],
            'groom_name' => ['required', 'string', 'max:100'],
            'bride_name' => ['required', 'string', 'max:100'],
            'groom_family' => ['nullable', 'string', 'max:150'],
            'bride_family' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'event_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'venue_name' => ['required', 'string', 'max:150'],
            'venue_address' => ['required', 'string', 'max:500'],
            'google_maps_url' => ['nullable', 'string', 'max:500', new GoogleMapsUrl()],
            'theme' => ['nullable', Rule::in(config('wgs.themes'))],
            'checkin_mode' => ['nullable', Rule::enum(CheckinMode::class)],
            'allow_manual_checkin' => ['nullable', 'boolean'],
            'checkin_opens_at' => ['nullable', 'date'],
            'checkin_closes_at' => $closes,
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{6,25}$/'],
            'banner' => $image,
            'couple_photo' => $image,
        ];
    }

    public function messages(): array
    {
        return [
            'event_date.date_format' => 'Tanggal acara harus berformat YYYY-MM-DD.',
            'start_time.date_format' => 'Jam mulai harus berformat HH:MM.',
            'end_time.date_format' => 'Jam selesai harus berformat HH:MM.',
            'checkin_closes_at.after' => 'Penutupan check-in harus setelah pembukaan check-in.',
            'contact_phone.regex' => 'Nomor telepon tidak valid.',
            'banner.mimes' => 'Banner harus berformat JPG, PNG, atau WebP.',
            'banner.max' => 'Ukuran banner maksimal 5 MB.',
            'couple_photo.mimes' => 'Foto mempelai harus berformat JPG, PNG, atau WebP.',
            'couple_photo.max' => 'Ukuran foto mempelai maksimal 5 MB.',
        ];
    }
}
