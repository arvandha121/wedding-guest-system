<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSession>
 */
class EventSessionFactory extends Factory
{
    protected $model = EventSession::class;

    public function definition(): array
    {
        $day = now()->addDays(30);

        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['Akad Nikah', 'Resepsi', 'Acara Keluarga']),
            'start_time' => $day->copy()->setTime(9, 0),
            'end_time' => $day->copy()->setTime(11, 0),
            'status' => SessionStatus::Scheduled,
            'sort_order' => 0,
        ];
    }
}
