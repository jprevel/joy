<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Platforms
    |--------------------------------------------------------------------------
    |
    | This array contains all the social media platforms and content types
    | supported by the content management system.
    |
    */

    'available' => [
        'Facebook',
        'Instagram',
        'LinkedIn',
        'Twitter',
        'Blog',
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each platform including character limits,
    | hashtag limits, and supported media types.
    |
    */

    'config' => [
        'Facebook' => [
            'display_name' => 'Facebook',
            'icon' => '📘',
            'character_limit' => null, // Flexible limit
            'hashtag_limit' => 30,
            'supports_media' => true,
            'media_types' => ['image', 'video', 'gif'],
            'color_classes' => 'bg-blue-600 text-white',
            'light_color_classes' => 'bg-blue-100 text-blue-800',
            'url_pattern' => 'https://facebook.com/',
            'optimal_posting_times' => [9, 13, 15], // Hours in 24h format
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
        
        'Twitter' => [
            'display_name' => 'Twitter',
            'icon' => '🐦',
            'character_limit' => 280,
            'hashtag_limit' => null,
            'supports_media' => true,
            'media_types' => ['image', 'video', 'gif'],
            'color_classes' => 'bg-sky-500 text-white',
            'light_color_classes' => 'bg-sky-100 text-sky-800',
            'url_pattern' => 'https://twitter.com/',
            'optimal_posting_times' => [9, 12, 15, 18],
        ],
        
        'Blog' => [
            'display_name' => 'Blog',
            'icon' => '📝',
            'character_limit' => null,
            'hashtag_limit' => null,
            'supports_media' => true,
            'media_types' => ['image', 'video', 'document', 'embed'],
            'color_classes' => 'bg-gray-700 text-white',
            'light_color_classes' => 'bg-gray-100 text-gray-800',
            'url_pattern' => null,
            'optimal_posting_times' => [9, 14],
        ],
    ],
];