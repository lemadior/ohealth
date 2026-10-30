<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Queue\SerializesModels;

trait SerializesSyncModels
{
    use SerializesModels {
        getSerializedPropertyValue as private serializeModelProperty;
        getRestoredPropertyValue as private restoreModelProperty;
    }

    protected function getSerializedPropertyValue($value, $withRelations = true)
    {
        // Every saved step carries the user. Rehydrating their permissions for each step
        // multiplies memory use; permissions are loaded under the job's team when needed.
        return $this->serializeModelProperty($value, $value instanceof User ? false : $withRelations);
    }

    protected function getRestoredPropertyValue($value)
    {
        // Also handle payloads already queued with user relationships before this change.
        if ($value instanceof ModelIdentifier && is_string($value->class) && is_a($value->class, User::class, true)) {
            $value = clone $value;
            $value->relations = [];
        }

        return $this->restoreModelProperty($value);
    }
}
