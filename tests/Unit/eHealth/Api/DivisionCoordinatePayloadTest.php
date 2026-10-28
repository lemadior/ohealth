<?php

declare(strict_types=1);

namespace Tests\Unit\eHealth\Api;

use App\Classes\eHealth\Api\Division;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DivisionCoordinatePayloadTest extends TestCase
{
    public static function coordinates(): array
    {
        return [
            'long decimals' => ['89.99999999', '-179.99999999', '89.99999999', '-179.99999999'],
            '14 decimal places' => ['30.99999999999999', '50.99999999999999', '30.99999999999999', '50.99999999999999'],
            '15 decimal places' => ['30.999999999999999', '50.999999999999999', '30.99999999999999', '50.99999999999999'],
            'reported coordinates' => ['30.1234567890123456789', '50.324231234567890123456789', '30.12345678901234', '50.32423123456789'],
            'beyond float precision' => ['89.999999999999999999', '-179.999999999999999999', '89.99999999999999', '-179.99999999999999'],
            'commas and trailing zeros' => ['50,45000000000000000', '30,52000000000000000', '50.45000000000000', '30.52000000000000'],
            'scientific notation' => ['8.999999999999999999e1', '-1.799999999999999999e2', '89.99999999999999', '-179.99999999999999'],
            'small scientific notation' => ['1.234567890123456789e-10', '-1.234567890123456789e-10', '0.00000000012345', '-0.00000000012345'],
            'leading zeros and plus' => ['+050.450000000', '-030.520000000', '50.450000000', '-30.520000000'],
            'fraction only' => [',1234567890', '-,0000000000190', '0.1234567890', '-0.0000000000190'],
            'trailing separator' => ['30.', '-50.', '30', '-50'],
            'zeros' => ['0,0000', '-0.0000', '0.0000', '-0.0000'],
            'numeric values' => [50.45, 30.52, '50.45', '30.52'],
        ];
    }

    #[DataProvider('coordinates')]
    public function test_create_and_update_send_at_most_14_decimal_places_without_rounding(
        string|float $latitude,
        string|float $longitude,
        string $expectedLatitude,
        string $expectedLongitude
    ): void {
        config(['ehealth.api.domain' => 'https://ehealth.example']);
        $expected = '{"name":"89.999999999999999999","external_id":"00123","location":{"latitude":'
            . $expectedLatitude . ',"longitude":' . $expectedLongitude . '}}';

        Http::fake(function (Request $request) use ($expected) {
            $this->assertSame($expected, $request->body());
            $this->assertTrue(json_validate($request->body()));
            $this->assertContains('application/json', $request->header('Content-Type'));

            return Http::response(['data' => []]);
        });
        $factory = Http::getFacadeRoot();
        $api = new Division($factory);
        $api->stub((function () {
            return $this->stubCallbacks;
        })->call($factory));
        $api->preventStrayRequests();
        $data = [
            'name' => '89.999999999999999999',
            'external_id' => '00123',
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ];

        $api->create($data);
        $api->update('division-uuid', $data);
        $api->update(null, $data);

        Http::assertSentCount(3);
    }
}
