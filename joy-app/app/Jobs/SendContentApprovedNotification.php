<?php

namespace App\Jobs;

use App\Contracts\SlackNotificationServiceContract;
use App\Models\ContentItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendContentApprovedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ContentItem $contentItem
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SlackNotificationServiceContract $slackNotificationService): void
    {
        $slackNotificationService->sendContentApprovedNotification($this->contentItem);
    }
}
