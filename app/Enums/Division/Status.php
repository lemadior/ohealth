<?php

declare(strict_types=1);

namespace App\Enums\Division;

use App\Traits\EnumUtils;

/**
 * see: https://e-health-ua.atlassian.net/wiki/spaces/ESOZ/pages/17940185105/DIVISION_STATUS
 */
enum Status: string
{
    use EnumUtils;

    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('forms.status.active'),
            self::INACTIVE => __('forms.status.non_active')
        };
    }
}
