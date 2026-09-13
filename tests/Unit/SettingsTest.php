<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\SettingsProviderService;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_get_setting(): void
    {
        $service = resolve(SettingsProviderService::class);

        $service->set('test', 'en');

        $setting = $service->get('test');

        $this->assertEquals('en', $setting);
    }

    public function test_get_all_settings(): void
    {
        $service = resolve(SettingsProviderService::class);

        $service->set('test', 'language', 'en');
        $service->set('test', 'key', 'value');

        $settings = $service->all('test');

        $this->assertDatabaseHas((new Setting)->getTable(), ['value' => reset($settings)]);
    }

    public function test_set_one_setting(): void
    {
        $service = resolve(SettingsProviderService::class);

        $service->set('language', 'en');
        $result = $service->get('language');

        $this->assertEquals('en', $result);
    }

    public function test_set_multiple_settings(): void
    {
        $this->markTestSkipped('Not sure why this test is here. Multiple setting retrieval was never supported');

        $service = resolve(SettingsProviderService::class);

        $data = ['language' => 'en', 'timezone' => 'utc'];

        $service->set('test', json_encode($data));
        $result = $service->get();

        $this->assertEquals($data, $result);
    }
}
