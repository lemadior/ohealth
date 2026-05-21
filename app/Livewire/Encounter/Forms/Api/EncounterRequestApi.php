<?php

declare(strict_types=1);

namespace App\Livewire\Encounter\Forms\Api;

class EncounterRequestApi
{
    /**
     * Build an array of parameters for a service request list.
     *
     * @param  string  $requisition  A shared identifier common to all service requests that were authorized more or less simultaneously by a single author, representing the composite or group identifier. Example: AX654-654T.
     * @param  string  $status  The status of the service request. Default: active.
     * @param  int  $page  Page number. Default: 1.
     * @param  int  $pageSize  A limit on the number of objects to be returned, between 1 and 100. Default: 50.
     * @return array
     */
    public static function buildGetServiceRequestList(
        string $requisition,
        string $status = 'active',
        int $page = 1,
        int $pageSize = 50
    ): array {
        return [
            'requisition' => $requisition,
            'status' => $status,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
}
