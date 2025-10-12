<?php

namespace App\Observers;

use App\Jobs\SendClientCommentNotification;
use App\Models\Comment;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     *
     * Only dispatch notification if comment is from client (not internal)
     */
    public function created(Comment $comment): void
    {
        // Check if comment is from client
        if (!$comment->isFromClient()) {
            return;
        }

        // Check if client has Slack integration
        if (!$comment->contentItem?->client?->hasSlackIntegration()) {
            return;
        }

        // Dispatch notification job
        SendClientCommentNotification::dispatch($comment);
    }
}
