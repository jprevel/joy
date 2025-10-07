<?php

namespace App\Http\Middleware;

use App\Constants\AuditConstants;
use App\Services\AuditService;
use App\DTOs\AuditLogRequest;
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
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect('/login')->with('error', 'Authentication required');
        }

        $user = auth()->user();

        // Check if user has admin role
        if (!$user->hasRole('admin')) {
            // Redirect based on user's actual role
            if ($user->hasRole('agency')) {
                return redirect('/calendar/agency')->with('error', 'Admin access required');
            } elseif ($user->hasRole('client')) {
                return redirect('/calendar/client')->with('error', 'Admin access required');
            } else {
                return redirect('/')->with('error', 'Admin access required');
            }
        }

        // Allow access if coming from calendar/admin route (testing mode)
        if ($this->isTestingModeAdminAccess($request)) {
            $this->logAuthorizedAccess($request);
            return $next($request);
        }

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
    
    private function isTestingModeAdminAccess(Request $request): bool
    {
        // Check if request has testing mode referer (coming from calendar/admin)
        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, '/calendar/admin')) {
            return true;
        }
        
        // Check if user agent indicates testing/development
        $userAgent = $request->userAgent();
        if ($userAgent && (str_contains($userAgent, 'Chrome') || str_contains($userAgent, 'Firefox') || str_contains($userAgent, 'Safari'))) {
            // Allow if coming from localhost
            return in_array($request->ip(), self::ALLOWED_IPS, true);
        }
        
        return false;
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
        try {
            AuditService::log(
                AuditLogRequest::create('admin_access')
                    ->withNewValues([
                        'ip_address' => $request->ip(),
                        'route' => $request->route()?->getName(),
                        'url' => $request->fullUrl(),
                    ])
                    ->withSeverity(AuditConstants::SEVERITY_INFO)
                    ->withTags(['admin', 'access'])
            );
        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::warning('Failed to log admin access: ' . $e->getMessage());
        }
    }
    
    private function createUnauthorizedResponse(): Response
    {
        return response()->view('errors.401', [
            'message' => 'Admin access denied. Your IP address is not authorized to access the admin panel.'
        ], self::HTTP_UNAUTHORIZED);
    }
}
