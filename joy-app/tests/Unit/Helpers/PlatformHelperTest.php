<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\PlatformHelper;
use Illuminate\Support\Facades\Config;

class PlatformHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        Config::set('platforms.available', ['Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog']);
        Config::set('platforms.config', [
            'Facebook' => [
                'display_name' => 'Facebook',
                'icon' => '📘',
                'character_limit' => null,
                'hashtag_limit' => 30,
                'supports_media' => true,
                'media_types' => ['image', 'video', 'gif'],
                'color_classes' => 'bg-blue-600 text-white',
                'light_color_classes' => 'bg-blue-100 text-blue-800',
                'url_pattern' => 'https://facebook.com/',
                'optimal_posting_times' => [9, 13, 15],
            ],
            'Instagram' => [
                'display_name' => 'Instagram',
                'icon' => '📷',
                'character_limit' => 2200,
                'hashtag_limit' => 30,
                'supports_media' => true,
                'media_types' => ['image', 'video', 'story'],
                'color_classes' => 'bg-pink-600 text-white',
                'light_color_classes' => 'bg-pink-100 text-pink-800',
                'url_pattern' => 'https://instagram.com/',
                'optimal_posting_times' => [11, 13, 17],
            ],
            'LinkedIn' => [
                'display_name' => 'LinkedIn',
                'icon' => '💼',
                'character_limit' => 3000,
                'hashtag_limit' => null,
                'supports_media' => true,
                'media_types' => ['image', 'video', 'document'],
                'color_classes' => 'bg-blue-800 text-white',
                'light_color_classes' => 'bg-blue-200 text-blue-900',
                'url_pattern' => 'https://linkedin.com/',
                'optimal_posting_times' => [8, 12, 17],
            ],
        ]);
    }

    /** @test */
    public function it_gets_all_platforms_from_configuration()
    {
        // Act
        $platforms = PlatformHelper::getAllPlatforms();
        
        // Assert
        $this->assertEquals(['Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog'], $platforms);
    }

    /** @test */
    public function it_returns_empty_array_when_no_platforms_configured()
    {
        // Arrange
        Config::set('platforms.available', []);
        
        // Act
        $platforms = PlatformHelper::getAllPlatforms();
        
        // Assert
        $this->assertEquals([], $platforms);
    }

    /** @test */
    public function it_gets_platform_icons_from_configuration()
    {
        // Act & Assert
        $this->assertEquals('📘', PlatformHelper::getIcon('Facebook'));
        $this->assertEquals('📷', PlatformHelper::getIcon('Instagram'));
        $this->assertEquals('💼', PlatformHelper::getIcon('LinkedIn'));
        $this->assertEquals('📄', PlatformHelper::getIcon('UnknownPlatform')); // Default
    }

    /** @test */
    public function it_gets_color_classes_from_configuration()
    {
        // Act & Assert
        $this->assertEquals('bg-blue-600 text-white', PlatformHelper::getColorClasses('Facebook'));
        $this->assertEquals('bg-pink-600 text-white', PlatformHelper::getColorClasses('Instagram'));
        $this->assertEquals('bg-gray-500 text-white', PlatformHelper::getColorClasses('UnknownPlatform')); // Default
    }

    /** @test */
    public function it_extracts_background_color_from_color_classes()
    {
        // Act & Assert
        $this->assertEquals('bg-blue-600', PlatformHelper::getBackgroundColor('Facebook'));
        $this->assertEquals('bg-pink-600', PlatformHelper::getBackgroundColor('Instagram'));
        $this->assertEquals('bg-blue-800', PlatformHelper::getBackgroundColor('LinkedIn'));
    }

    /** @test */
    public function it_gets_light_background_colors()
    {
        // Act & Assert
        $this->assertEquals('bg-blue-100 text-blue-800', PlatformHelper::getLightBackgroundColor('Facebook'));
        $this->assertEquals('bg-pink-100 text-pink-800', PlatformHelper::getLightBackgroundColor('Instagram'));
        $this->assertEquals('bg-gray-100 text-gray-800', PlatformHelper::getLightBackgroundColor('UnknownPlatform')); // Default
    }

    /** @test */
    public function it_gets_display_names_from_configuration()
    {
        // Act & Assert
        $this->assertEquals('Facebook', PlatformHelper::getDisplayName('Facebook'));
        $this->assertEquals('Instagram', PlatformHelper::getDisplayName('Instagram'));
        $this->assertEquals('LinkedIn', PlatformHelper::getDisplayName('LinkedIn'));
        $this->assertEquals('Customplatform', PlatformHelper::getDisplayName('customplatform')); // ucfirst fallback
    }

    /** @test */
    public function it_gets_character_limits_from_configuration()
    {
        // Act & Assert
        $this->assertNull(PlatformHelper::getCharacterLimit('Facebook'));
        $this->assertEquals(2200, PlatformHelper::getCharacterLimit('Instagram'));
        $this->assertEquals(3000, PlatformHelper::getCharacterLimit('LinkedIn'));
        $this->assertNull(PlatformHelper::getCharacterLimit('UnknownPlatform'));
    }

    /** @test */
    public function it_gets_hashtag_limits_from_configuration()
    {
        // Act & Assert
        $this->assertEquals(30, PlatformHelper::getHashtagLimit('Facebook'));
        $this->assertEquals(30, PlatformHelper::getHashtagLimit('Instagram'));
        $this->assertNull(PlatformHelper::getHashtagLimit('LinkedIn'));
        $this->assertNull(PlatformHelper::getHashtagLimit('UnknownPlatform'));
    }

    /** @test */
    public function it_checks_media_support_from_configuration()
    {
        // Act & Assert
        $this->assertTrue(PlatformHelper::supportsMedia('Facebook'));
        $this->assertTrue(PlatformHelper::supportsMedia('Instagram'));
        $this->assertTrue(PlatformHelper::supportsMedia('LinkedIn'));
        $this->assertFalse(PlatformHelper::supportsMedia('UnknownPlatform')); // Default false
    }

    /** @test */
    public function it_gets_supported_media_types_from_configuration()
    {
        // Act & Assert
        $this->assertEquals(['image', 'video', 'gif'], PlatformHelper::getSupportedMediaTypes('Facebook'));
        $this->assertEquals(['image', 'video', 'story'], PlatformHelper::getSupportedMediaTypes('Instagram'));
        $this->assertEquals(['image', 'video', 'document'], PlatformHelper::getSupportedMediaTypes('LinkedIn'));
        $this->assertEquals([], PlatformHelper::getSupportedMediaTypes('UnknownPlatform')); // Default empty array
    }

    /** @test */
    public function it_creates_platform_options_for_forms()
    {
        // Act
        $options = PlatformHelper::getOptions();
        
        // Assert
        $expected = [
            'Facebook' => 'Facebook',
            'Instagram' => 'Instagram',
            'LinkedIn' => 'LinkedIn',
            'Twitter' => 'Twitter',
            'Blog' => 'Blog',
        ];
        
        $this->assertEquals($expected, $options);
    }

    /** @test */
    public function it_validates_platform_existence()
    {
        // Act & Assert
        $this->assertTrue(PlatformHelper::isValidPlatform('Facebook'));
        $this->assertTrue(PlatformHelper::isValidPlatform('Instagram'));
        $this->assertTrue(PlatformHelper::isValidPlatform('LinkedIn'));
        $this->assertTrue(PlatformHelper::isValidPlatform('Twitter'));
        $this->assertTrue(PlatformHelper::isValidPlatform('Blog'));
        $this->assertFalse(PlatformHelper::isValidPlatform('UnknownPlatform'));
        $this->assertFalse(PlatformHelper::isValidPlatform(''));
    }

    /** @test */
    public function it_gets_url_patterns_from_configuration()
    {
        // Act & Assert
        $this->assertEquals('https://facebook.com/', PlatformHelper::getUrlPattern('Facebook'));
        $this->assertEquals('https://instagram.com/', PlatformHelper::getUrlPattern('Instagram'));
        $this->assertEquals('https://linkedin.com/', PlatformHelper::getUrlPattern('LinkedIn'));
        $this->assertNull(PlatformHelper::getUrlPattern('UnknownPlatform'));
    }

    /** @test */
    public function it_gets_optimal_posting_times_from_configuration()
    {
        // Act & Assert
        $this->assertEquals([9, 13, 15], PlatformHelper::getOptimalPostingTimes('Facebook'));
        $this->assertEquals([11, 13, 17], PlatformHelper::getOptimalPostingTimes('Instagram'));
        $this->assertEquals([8, 12, 17], PlatformHelper::getOptimalPostingTimes('LinkedIn'));
        $this->assertEquals([12], PlatformHelper::getOptimalPostingTimes('UnknownPlatform')); // Default noon
    }

    /** @test */
    public function it_uses_constants_correctly()
    {
        // Act & Assert
        $this->assertEquals('Facebook', PlatformHelper::FACEBOOK);
        $this->assertEquals('Instagram', PlatformHelper::INSTAGRAM);
        $this->assertEquals('LinkedIn', PlatformHelper::LINKEDIN);
        $this->assertEquals('Twitter', PlatformHelper::TWITTER);
        $this->assertEquals('Blog', PlatformHelper::BLOG);
    }

    /** @test */
    public function it_handles_missing_configuration_gracefully()
    {
        // Arrange - Remove configuration
        Config::set('platforms.config.TestPlatform', []);
        
        // Act & Assert - Should not throw exceptions, use defaults
        $this->assertEquals('📄', PlatformHelper::getIcon('TestPlatform')); // Default icon
        $this->assertEquals('bg-gray-500 text-white', PlatformHelper::getColorClasses('TestPlatform')); // Default color
        $this->assertNull(PlatformHelper::getCharacterLimit('TestPlatform'));
        $this->assertNull(PlatformHelper::getHashtagLimit('TestPlatform'));
        $this->assertFalse(PlatformHelper::supportsMedia('TestPlatform'));
        $this->assertEquals([], PlatformHelper::getSupportedMediaTypes('TestPlatform'));
        $this->assertNull(PlatformHelper::getUrlPattern('TestPlatform'));
        $this->assertEquals([12], PlatformHelper::getOptimalPostingTimes('TestPlatform'));
    }

    /** @test */
    public function it_handles_partial_configuration()
    {
        // Arrange - Set partial configuration
        Config::set('platforms.config.PartialPlatform', [
            'display_name' => 'Partial Platform',
            'icon' => '🔧',
            // Missing other properties
        ]);
        
        // Act & Assert
        $this->assertEquals('Partial Platform', PlatformHelper::getDisplayName('PartialPlatform'));
        $this->assertEquals('🔧', PlatformHelper::getIcon('PartialPlatform'));
        $this->assertNull(PlatformHelper::getCharacterLimit('PartialPlatform'));
        $this->assertEquals('bg-gray-500 text-white', PlatformHelper::getColorClasses('PartialPlatform')); // Default
    }

    /** @test */
    public function it_returns_empty_options_when_no_platforms_configured()
    {
        // Arrange
        Config::set('platforms.available', []);
        
        // Act
        $options = PlatformHelper::getOptions();
        
        // Assert
        $this->assertEquals([], $options);
    }
}