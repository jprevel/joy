<?php

namespace App\Http\Middleware;

use App\Constants\AuditConstants;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    private const ALLOWED_IPS = [
        '127.0.0.1',
        '::1',
        'localhost',
    ];
    
    private const HTTP_UNAUTHORIZED = 401;
    
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isAuthorizedIP($request)) {
            $this->logUnauthorizedAccess($request);
            return $this->createUnauthorizedResponse();
        }
        
        $this->logAuthorizedAccess($request);
        return $next($request);
    }
    
    private function isAuthorizedIP(Request $request): bool
    {
        return in_array($request->ip(), self::ALLOWED_IPS, true);
    }
    
    private function logUnauthorizedAccess(Request $request): void
    {
        AuditService::logSecurityEvent(
            'unauthorized_admin_access',
            AuditConstants::SEVERITY_WARNING,
            null,
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ],
            [AuditConstants::TAG_SECURITY, AuditConstants::TAG_ADMIN_ACCESS]
        );
    }
    
    private function logAuthorizedAccess(Request $request): void
    {
        AuditService::log(
            action: 'admin_access',
            newValues: [
                'ip_address' => $request->ip(),
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
            ],
            severity: AuditConstants::SEVERITY_INFO,
            tags: ['admin', 'access']
        );
    }
    
    private function createUnauthorizedResponse(): Response
    {
        return response()->view('errors.401', [
            'message' => 'Admin access denied. Your IP address is not authorized to access the admin panel.'
        ], self::HTTP_UNAUTHORIZED);
    }
}
