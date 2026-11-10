<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\InvitationMail;
use App\Models\InvitationDeliveryLog;
use App\Services\InvitationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendInvitationEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(public int $logId)
    {
    }

    public function handle(InvitationService $invitations): void
    {
        $log = InvitationDeliveryLog::query()->with('invitation.event', 'invitation.guest')->findOrFail($this->logId);

        if ($log->status === 'sent' || $log->invitation->isRevoked()) {
            return;
        }

        $log->attempt++;
        $log->save();

        $url = route('invitation.public', $invitations->publicToken($log->invitation));
        Mail::to($log->recipient)->send(new InvitationMail($log->invitation, $url));

        $log->status = 'sent';
        $log->sent_at = now();
        $log->error_message = null;
        $log->save();

        $invitations->markSent($log->invitation);
    }

    public function failed(Throwable $e): void
    {
        InvitationDeliveryLog::query()->whereKey($this->logId)->update([
            'status' => 'failed', 'failed_at' => now(), 'error_message' => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
