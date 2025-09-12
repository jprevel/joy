<?php

namespace App\Helpers;

class PlatformHelper
{
    public const FACEBOOK = 'Facebook';
    public const INSTAGRAM = 'Instagram';
    public const LINKEDIN = 'LinkedIn';
    public const TWITTER = 'Twitter';
    public const BLOG = 'Blog';

    /**
     * Get all available platforms
     */
    public static function getAllPlatforms(): array
    {
        return config('platforms.available', []);
    }

    /**
     * Get the icon for a given platform
     */
    public static function getIcon(string $platform): string
    {
        return config("platforms.config.{$platform}.icon", '📄');
    }

    /**
     * Get the Tailwind CSS color classes for a given platform
     */
    public static function getColorClasses(string $platform): string
    {
        return config("platforms.config.{$platform}.color_classes", 'bg-gray-500 text-white');
    }

    /**
     * Get the background color class only
     */
    public static function getBackgroundColor(string $platform): string
    {
        $colorClasses = config("platforms.config.{$platform}.color_classes", 'bg-gray-500 text-white');
        return explode(' ', $colorClasses)[0]; // Return only the background color class
    }

    /**
     * Get a light background color for badges/pills
     */
    public static function getLightBackgroundColor(string $platform): string
    {
        return config("platforms.config.{$platform}.light_color_classes", 'bg-gray-100 text-gray-800');
    }

    /**
     * Get the display name for a platform
     */
    public static function getDisplayName(string $platform): string
    {
        return config("platforms.config.{$platform}.display_name", ucfirst($platform));
    }

    /**
     * Get platform-specific character limits for content
     */
    public static function getCharacterLimit(string $platform): ?int
    {
        return config("platforms.config.{$platform}.character_limit");
    }

    /**
     * Get platform-specific hashtag limits
     */
    public static function getHashtagLimit(string $platform): ?int
    {
        return config("platforms.config.{$platform}.hashtag_limit");
    }

    /**
     * Check if a platform supports media uploads
     */
    public static function supportsMedia(string $platform): bool
    {
        return config("platforms.config.{$platform}.supports_media", false);
    }

    /**
     * Get supported media types for a platform
     */
    public static function getSupportedMediaTypes(string $platform): array
    {
        return config("platforms.config.{$platform}.media_types", []);
    }

    /**
     * Get all platforms as options for forms
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getAllPlatforms() as $platform) {
            $options[$platform] = self::getDisplayName($platform);
        }
        return $options;
    }

    /**
     * Validate if a platform is supported
     */
    public static function isValidPlatform(string $platform): bool
    {
        return in_array($platform, self::getAllPlatforms(), true);
    }

    /**
     * Get platform-specific URL patterns for external links
     */
    public static function getUrlPattern(string $platform): ?string
    {
        return config("platforms.config.{$platform}.url_pattern");
    }

    /**
     * Get optimal posting times for each platform (in hours, 24h format)
     */
    public static function getOptimalPostingTimes(string $platform): array
    {
        return config("platforms.config.{$platform}.optimal_posting_times", [12]);
    }
}