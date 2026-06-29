<?php

declare(strict_types=1);

namespace App\Enums\Preperson;

use App\Traits\EnumUtils;

enum Status: string
{
    use EnumUtils;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DRAFT = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('forms.status.active'),
            self::INACTIVE => __('forms.status.non_active'),
            self::DRAFT => __('forms.status.draft')
        };
    }
}
