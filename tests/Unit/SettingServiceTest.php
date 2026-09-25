<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_settings_by_hierarchy(): void
    {
        $service = app(SettingService::class);

        $service->set('receipt_footer', 'System default', Setting::SCOPE_SYSTEM, null);
        $service->set('receipt_footer', 'Company override', Setting::SCOPE_COMPANY, '01JCOMPANY0000000000000001');
        $service->set('receipt_footer', 'Store override', Setting::SCOPE_STORE, '01JSTORE0000000000000000001');

        $this->assertSame(
            'Store override',
            $service->get('receipt_footer', null, '01JCOMPANY0000000000000001', '01JSTORE0000000000000000001'),
        );
        $this->assertSame(
            'Company override',
            $service->get('receipt_footer', null, '01JCOMPANY0000000000000001'),
        );
        $this->assertSame('System default', $service->get('receipt_footer'));
    }
}
