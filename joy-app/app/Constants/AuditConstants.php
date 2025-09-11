<?php

namespace App\Constants;

class AuditConstants
{
    public const DEFAULT_RETENTION_DAYS = 90;
    public const MIN_CLEANUP_DAYS = 30;
    public const EXPORT_LIMIT = 10000;
    public const PAGINATION_LIMIT = 50;
    public const MAX_REQUEST_SIZE = 65535;
    public const MAX_RESPONSE_SIZE = 65535;
    
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';
    
    public const USER_TYPE_USER = 'user';
    public const USER_TYPE_MAGIC_LINK = 'magic_link';
    public const USER_TYPE_SYSTEM = 'system';
    
    public const TAG_SECURITY = 'security';
    public const TAG_ADMIN_ACCESS = 'admin_access';
    public const TAG_EXPORT = 'export';
    public const TAG_CLEANUP = 'cleanup';
    public const TAG_SYNC = 'sync';
}