<?php

declare(strict_types=1);

namespace App\View\Components\Forms;

use App\Traits\FormTrait;
use Illuminate\View\Component;
use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;

abstract class Addresses extends Component
{
    use FormTrait;

    public bool $readonly;

    public bool $divisionView = false;

    public array $address = [];

    public ?array $regions = [];

    public array $districts = [];

    public ?array $settlements = [];

    public ?array $streets = [];

    public string $class = '';

    /**
     * Create a new component instance.
     */
    public function __construct(
        $address,
        $districts,
        $settlements,
        $streets,
        $class,
        $readonly = false,
        $divisionView = false,
        string $property = 'address'
    ) {
        $this->readonly = $readonly;
        $this->divisionView = $divisionView;

        $this->address = $address;

        try {
            $this->regions = EHealth::address()->getRegions()->getData();
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when searching for regions');

            return;
        }

        $this->districts = $districts;

        $this->settlements = $settlements;

        $this->streets = $streets;

        $this->class = $class;

        $this->dictionaries = dictionary()->basics()->getMultipleFormatted(['SETTLEMENT_TYPE', 'STREET_TYPE'])->toArray();
    }

    abstract public static function getAddressRules(array $address): array;

    abstract public static function getAddressMessages(): array;
}
