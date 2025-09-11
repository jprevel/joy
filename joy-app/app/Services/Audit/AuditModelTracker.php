<?php

namespace App\Services\Audit;

use App\Constants\AuditConstants;
use App\DTOs\AuditLogData;
use App\Models\AuditLog;
use App\Models\MagicLink;
use Illuminate\Database\Eloquent\Model;

class AuditModelTracker
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}
    
    public function logModelCreated(Model $model, array $tags = []): AuditLog
    {
        $data = AuditLogData::create('model_created')
            ->withAuditable($model)
            ->withChanges([], $model->toArray())
            ->withTags(array_merge(['model', 'created'], $tags));
            
        return $this->auditLogger->log($data);
    }
    
    public function logModelUpdated(Model $model, array $oldValues = [], array $tags = []): AuditLog
    {
        $data = AuditLogData::create('model_updated')
            ->withAuditable($model)
            ->withChanges($oldValues, $model->toArray())
            ->withTags(array_merge(['model', 'updated'], $tags));
            
        return $this->auditLogger->log($data);
    }
    
    public function logModelDeleted(Model $model, array $tags = []): AuditLog
    {
        $data = AuditLogData::create('model_deleted')
            ->withAuditable($model)
            ->withChanges($model->toArray(), [])
            ->withTags(array_merge(['model', 'deleted'], $tags));
            
        return $this->auditLogger->log($data);
    }
    
    public function logMagicLinkAccessed(MagicLink $magicLink, array $tags = []): AuditLog
    {
        $data = AuditLogData::create('magic_link_accessed')
            ->withAuditable($magicLink)
            ->withWorkspace($magicLink->workspace_id)
            ->withUser($magicLink->id, AuditConstants::USER_TYPE_MAGIC_LINK)
            ->withTags(array_merge(['magic_link', 'access'], $tags));
            
        return $this->auditLogger->log($data);
    }
    
    public function logCommentCreated(Model $comment, Model $variant, array $tags = []): AuditLog
    {
        $data = AuditLogData::create('comment_created')
            ->withAuditable($comment)
            ->withChanges([], $comment->toArray())
            ->withTags(array_merge(['comment', 'created'], $tags))
            ->withResponseData([
                'variant_id' => $variant->id,
                'variant_platform' => $variant->platform,
            ]);
            
        return $this->auditLogger->log($data);
    }
}