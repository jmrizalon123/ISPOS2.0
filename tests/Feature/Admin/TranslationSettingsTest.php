<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Services\TranslationConfigService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class TranslationSettingsTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_switch_locale(): void
    {
        ['cashier' => $cashier] = $this->createPosFixtures();

        $this->actingAs($cashier)
            ->from(route('dashboard'))
            ->post(route('locale.update'), ['locale' => 'zh-CN'])
            ->assertRedirect(route('dashboard'));

        $cashier->refresh();

        $this->assertSame('zh-CN', $cashier->preferences['locale'] ?? null);
    }

    public function test_admin_can_save_translation_settings(): void
    {
        ['cashier' => $cashier] = $this->createPosFixtures();
        $cashier->givePermissionTo(['settings.view', 'settings.update']);

        $this->actingAs($cashier)
            ->put(route('admin.settings.translation.update'), [
                'provider' => 'google',
                'google_enabled' => true,
                'google_use_widget' => true,
                'google_api_key' => 'test-api-key',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $service = app(TranslationConfigService::class);

        $this->assertSame('google', $service->provider());
        $this->assertTrue($service->googleEnabled());
        $this->assertTrue($service->googleUseWidget());
        $this->assertTrue($service->hasApiKey());

        $this->assertDatabaseHas('settings', [
            'scope' => Setting::SCOPE_SYSTEM,
            'scope_id' => null,
            'key' => TranslationConfigService::KEY_PROVIDER,
        ]);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        ['cashier' => $cashier] = $this->createPosFixtures();

        $this->actingAs($cashier)
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertStatus(422);
    }

    public function test_settings_page_includes_translation_settings(): void
    {
        ['store' => $store, 'cashier' => $cashier] = $this->createPosFixtures();
        $cashier->givePermissionTo('settings.view');

        $this->actingAs($cashier)
            ->get(route('admin.settings.edit', ['company_id' => $store->company_id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('translationSettings'));
    }
}
