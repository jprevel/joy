<?php

namespace App\Services\Audit;

use App\Constants\AuditConstants;
use App\DTOs\AuditLogData;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function log(AuditLogData $data): AuditLog
    {
        $this->enrichDataWithContext($data);
        $this->enrichDataWithRequest($data);
        
        return AuditLog::create(array_filter($data->toArray()));
    }
    
    private function enrichDataWithContext(AuditLogData $data): void
    {
        $this->detectWorkspace($data);
        $this->detectUser($data);
    }
    
    private function detectWorkspace(AuditLogData $data): void
    {
        if ($data->workspaceId || !$data->auditable) {
            return;
        }

        if (method_exists($data->auditable, 'workspace')) {
            $data->workspaceId = $data->auditable->workspace_id ?? $data->auditable->workspace()->first()?->id;
        } elseif (isset($data->auditable->workspace_id)) {
            $data->workspaceId = $data->auditable->workspace_id;
        }
    }
    
    private function detectUser(AuditLogData $data): void
    {
        if ($data->userId) {
            return;
        }

        if (Auth::check()) {
            $data->userId = Auth::id();
            $data->userType = $data->userType ?? AuditConstants::USER_TYPE_USER;
        } elseif (Request::hasHeader('magic-link-id')) {
            $data->userId = Request::header('magic-link-id');
            $data->userType = AuditConstants::USER_TYPE_MAGIC_LINK;
        }
    }
    
    private function enrichDataWithRequest(AuditLogData $data): void
    {
        if ($data->requestData) {
            return;
        }
        
        $request = request();
        $data->requestData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
        
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $inputData = $request->except(['password', 'password_confirmation', '_token', 'api_key', 'api_token']);
            if (!empty($inputData)) {
                $data->requestData['input'] = $this->truncateData($inputData, AuditConstants::MAX_REQUEST_SIZE);
            }
        }
    }
    
    private function truncateData(array $data, int $maxSize): array
    {
        $json = json_encode($data);
        if (strlen($json) > $maxSize) {
            return ['_truncated' => true, '_original_size' => strlen($json)];
        }
        return $data;
    }
}