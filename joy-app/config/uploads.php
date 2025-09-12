<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for file uploads including size limits, allowed types,
    | and storage paths.
    |
    */

    'max_image_size' => env('MAX_IMAGE_SIZE', 10240), // 10MB in kilobytes
    
    'allowed_image_extensions' => [
        'jpg',
        'jpeg', 
        'png',
        'gif',
        'webp',
    ],
    
    'content_images_path' => 'content-images',
    
    'max_file_size' => env('MAX_FILE_SIZE', 51200), // 50MB in kilobytes
    
    'allowed_document_extensions' => [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'txt',
    ],
];