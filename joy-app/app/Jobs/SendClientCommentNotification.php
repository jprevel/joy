<?php

namespace App\Jobs;

use App\Contracts\SlackNotificationServiceContract;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendClientCommentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Comment $comment
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SlackNotificationServiceContract $slackNotificationService): void
    {
        $slackNotificationService->sendClientCommentNotification($this->comment);
    }
}
