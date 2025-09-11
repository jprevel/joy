<?php

namespace App\DTOs;

use App\Constants\AuditConstants;
use Illuminate\Database\Eloquent\Model;

class AuditLogRequest
{
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

    public static function create(string $action): self
    {
        return new self($action);
    }

    public function withAuditable(Model $auditable): self
    {
        $this->auditable = $auditable;
        return $this;
    }

    public function withChanges(array $oldValues, array $newValues): self
    {
        $this->oldValues = $oldValues;
        $this->newValues = $newValues;
        return $this;
    }

    public function withOldValues(array $oldValues): self
    {
        $this->oldValues = $oldValues;
        return $this;
    }

    public function withNewValues(array $newValues): self
    {
        $this->newValues = $newValues;
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

    public function withTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function withRequestData(array $requestData): self
    {
        $this->requestData = $requestData;
        return $this;
    }

    public function withResponseData(array $responseData): self
    {
        $this->responseData = $responseData;
        return $this;
    }

    public function toAuditLogData(): AuditLogData
    {
        return new AuditLogData(
            action: $this->action,
            auditable: $this->auditable,
            oldValues: $this->oldValues,
            newValues: $this->newValues,
            workspaceId: $this->workspaceId,
            userId: $this->userId,
            userType: $this->userType,
            severity: $this->severity,
            tags: $this->tags,
            requestData: $this->requestData,
            responseData: $this->responseData
        );
    }
}