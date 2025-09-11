<?php

namespace App\Enums;

enum VariantStatus: string
{
    case DRAFT = 'Draft';
    case IN_REVIEW = 'In Review';
    case APPROVED = 'Approved';
    case SCHEDULED = 'Scheduled';
    
    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-gray-500',
            self::IN_REVIEW => 'bg-yellow-500',
            self::APPROVED => 'bg-green-500',
            self::SCHEDULED => 'bg-blue-500',
        };
    }
    
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->value;
        }
        return $options;
    }
}