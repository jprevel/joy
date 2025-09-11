<?php

namespace App\Helpers;

class PlatformHelper
{
    public const FACEBOOK = 'facebook';
    public const INSTAGRAM = 'instagram';
    public const LINKEDIN = 'linkedin';
    public const BLOG = 'blog';
    
    public const PLATFORMS = [
        self::FACEBOOK,
        self::INSTAGRAM,
        self::LINKEDIN,
        self::BLOG,
    ];

    /**
     * Get the icon for a given platform
     */
    public static function getIcon(string $platform): string
    {
        return match ($platform) {
            self::FACEBOOK => '📘',
            self::INSTAGRAM => '📷',
            self::LINKEDIN => '💼',
            self::BLOG => '📝',
            default => '📄'
        };
    }

    /**
     * Get the Tailwind CSS color classes for a given platform
     */
    public static function getColorClasses(string $platform): string
    {
        return match ($platform) {
            self::FACEBOOK => 'bg-blue-600 text-white',
            self::INSTAGRAM => 'bg-pink-600 text-white',
            self::LINKEDIN => 'bg-blue-800 text-white',
            self::BLOG => 'bg-gray-700 text-white',
            default => 'bg-gray-500 text-white'
        };
    }

    /**
     * Get the background color class only
     */
    public static function getBackgroundColor(string $platform): string
    {
        return match ($platform) {
            self::FACEBOOK => 'bg-blue-600',
            self::INSTAGRAM => 'bg-pink-600',
            self::LINKEDIN => 'bg-blue-800',
            self::BLOG => 'bg-gray-700',
            default => 'bg-gray-500'
        };
    }

    /**
     * Get a light background color for badges/pills
     */
    public static function getLightBackgroundColor(string $platform): string
    {
        return match ($platform) {
            self::FACEBOOK => 'bg-blue-100 text-blue-800',
            self::INSTAGRAM => 'bg-pink-100 text-pink-800',
            self::LINKEDIN => 'bg-blue-200 text-blue-900',
            self::BLOG => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Get the display name for a platform
     */
    public static function getDisplayName(string $platform): string
    {
        return match ($platform) {
            self::FACEBOOK => 'Facebook',
            self::INSTAGRAM => 'Instagram',
            self::LINKEDIN => 'LinkedIn',
            self::BLOG => 'Blog',
            default => ucfirst($platform)
        };
    }

    /**
     * Get platform-specific character limits for content
     */
    public static function getCharacterLimit(string $platform): ?int
    {
        return match ($platform) {
            self::FACEBOOK => null, // Facebook has flexible limits
            self::INSTAGRAM => 2200,
            self::LINKEDIN => 3000,
            self::BLOG => null, // No limit for blog posts
            default => null
        };
    }

    /**
     * Get platform-specific hashtag limits
     */
    public static function getHashtagLimit(string $platform): ?int
    {
        return match ($platform) {
            self::FACEBOOK => 30,
            self::INSTAGRAM => 30,
            self::LINKEDIN => null, // No specific limit
            self::BLOG => null,
            default => null
        };
    }

    /**
     * Check if a platform supports media uploads
     */
    public static function supportsMedia(string $platform): bool
    {
        return match ($platform) {
            self::FACEBOOK => true,
            self::INSTAGRAM => true,
            self::LINKEDIN => true,
            self::BLOG => true,
            default => false
        };
    }

    /**
     * Get supported media types for a platform
     */
    public static function getSupportedMediaTypes(string $platform): array
    {
        return match ($platform) {
            self::FACEBOOK => ['image', 'video', 'gif'],
            self::INSTAGRAM => ['image', 'video', 'story'],
            self::LINKEDIN => ['image', 'video', 'document'],
            self::BLOG => ['image', 'video', 'document', 'embed'],
            default => []
        };
    }

    /**
     * Get all platforms as options for forms
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::PLATFORMS as $platform) {
            $options[$platform] = self::getDisplayName($platform);
        }
        return $options;
    }

    /**
     * Validate if a platform is supported
     */
    public static function isValidPlatform(string $platform): bool
    {
        return in_array($platform, self::PLATFORMS, true);
    }

    /**
     * Get platform-specific URL patterns for external links
     */
    public static function getUrlPattern(string $platform): ?string
    {
        return match ($platform) {
            self::FACEBOOK => 'https://facebook.com/',
            self::INSTAGRAM => 'https://instagram.com/',
            self::LINKEDIN => 'https://linkedin.com/',
            default => null
        };
    }

    /**
     * Get optimal posting times for each platform (in hours, 24h format)
     */
    public static function getOptimalPostingTimes(string $platform): array
    {
        return match ($platform) {
            self::FACEBOOK => [9, 13, 15], // 9 AM, 1 PM, 3 PM
            self::INSTAGRAM => [11, 13, 17], // 11 AM, 1 PM, 5 PM
            self::LINKEDIN => [8, 12, 17], // 8 AM, 12 PM, 5 PM (business hours)
            self::BLOG => [9, 14], // 9 AM, 2 PM
            default => [12] // Noon as default
        };
    }
}