<?php

declare(strict_types=1);

namespace App\Exceptions\EHealth;

use RuntimeException;

class EHealthSyncScopeException extends RuntimeException
{
    public function __construct(array $missingScopes)
    {
        parent::__construct('Missing eHealth token scopes for synchronization: ' . implode(', ', $missingScopes), 403);
    }
}
