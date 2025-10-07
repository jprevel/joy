<?php

namespace App\DTOs;

use App\Constants\AuditConstants;
use Illuminate\Database\Eloquent\Model;

class AuditLogData
{
    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string> $tags
     * @param array<string, mixed>|null $requestData
     * @param array<string, mixed>|null $responseData
     */
    public function __construct(
        public string $action,
        public ?Model $auditable = null,
        public array $oldValues = [],
        public array $newValues = [],
        public ?int $workspaceId = null,
        public ?int $userId = null,
        public ?string $userType = null,
        public string $severity = AuditConstants::SEVERITY_INFO,
        public array $tags = [],
        public ?array $requestData = null,
        public ?array $responseData = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event' => $this->action,  // Database column is 'event'
            'auditable_type' => $this->auditable ? get_class($this->auditable) : null,
            'auditable_id' => $this->auditable?->id,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'workspace_id' => $this->workspaceId,
            'user_id' => $this->userId,
            'user_type' => $this->userType,
            'severity' => $this->severity,
            'tags' => $this->tags,
            'request_data' => $this->requestData,
            'response_data' => $this->responseData,
        ];
    }
    
    public static function create(string $action): self
    {
        return new self($action);
    }
    
    public function withAuditable(Model $auditable): self
    {
        $this->auditable = $auditable;
        return $this;
    }
    
    public function withWorkspace(int $workspaceId): self
    {
        $this->workspaceId = $workspaceId;
        return $this;
    }
    
    public function withUser(int $userId, string $userType = AuditConstants::USER_TYPE_USER): self
    {
        $this->userId = $userId;
        $this->userType = $userType;
        return $this;
    }
    
    public function withSeverity(string $severity): self
    {
        $this->severity = $severity;
        return $this;
    }
    
    /**
     * @param array<string> $tags
     */
    public function withTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }
    
    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     */
    public function withChanges(array $oldValues, array $newValues): self
    {
        $this->oldValues = $oldValues;
        $this->newValues = $newValues;
        return $this;
    }
    
    /**
     * @param array<string, mixed> $requestData
     */
    public function withRequestData(array $requestData): self
    {
        $this->requestData = $requestData;
        return $this;
    }
    
    /**
     * @param array<string, mixed> $responseData
     */
    public function withResponseData(array $responseData): self
    {
        $this->responseData = $responseData;
        return $this;
    }
}