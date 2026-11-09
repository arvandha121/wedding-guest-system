<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CheckinMode;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $groom = fake()->firstNameMale();
        $bride = fake()->firstNameFemale();

        return [
            'name' => "Pernikahan {$groom} & {$bride}",
            'title' => "The Wedding of {$groom} & {$bride}",
            'groom_name' => $groom,
            'bride_name' => $bride,
            'event_date' => now()->addDays(30)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '13:00',
            'venue_name' => 'Gedung ' . fake()->lastName(),
            'venue_address' => fake()->address(),
            'google_maps_url' => 'https://maps.app.goo.gl/' . Str::random(10),
            'theme' => 'classic',
            'status' => EventStatus::Draft,
            'checkin_mode' => CheckinMode::Single,
            'allow_manual_checkin' => true,
            'created_by' => User::factory()->superAdmin(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => EventStatus::Published]);
    }

    public function ongoing(): static
    {
        return $this->state(fn (): array => ['status' => EventStatus::Ongoing]);
    }
}
