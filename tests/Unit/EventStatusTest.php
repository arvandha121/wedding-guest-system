<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\EventStatus;
use PHPUnit\Framework\TestCase;

class EventStatusTest extends TestCase
{
    public function test_draft_can_only_move_to_published_or_cancelled(): void
    {
        $this->assertTrue(EventStatus::Draft->canTransitionTo(EventStatus::Published));
        $this->assertTrue(EventStatus::Draft->canTransitionTo(EventStatus::Cancelled));
        $this->assertFalse(EventStatus::Draft->canTransitionTo(EventStatus::Ongoing));
        $this->assertFalse(EventStatus::Draft->canTransitionTo(EventStatus::Completed));
    }

    public function test_ongoing_can_complete_but_not_return_to_draft(): void
    {
        $this->assertTrue(EventStatus::Ongoing->canTransitionTo(EventStatus::Completed));
        $this->assertFalse(EventStatus::Ongoing->canTransitionTo(EventStatus::Draft));
    }

    public function test_a_status_cannot_transition_to_itself(): void
    {
        foreach (EventStatus::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status));
        }
    }
}
