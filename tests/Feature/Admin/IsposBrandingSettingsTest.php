<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Services\IsposBrandingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class IsposBrandingSettingsTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_admin_can_save_ispos_branding_settings(): void
    {
        ['cashier' => $cashier] = $this->createPosFixtures();
        $cashier->givePermissionTo(['settings.view', 'settings.update']);

        $logo = UploadedFile::fake()->image('logo.png', 120, 120);

        $this->actingAs($cashier)
            ->post(route('admin.settings.ispos.update'), [
                'name' => 'RetailOS',
                'tagline' => 'Modern retail for growing teams.',
                'ownership' => 'Acme Retail Group',
                'copyright' => '© 2026 Acme Retail Group',
                'support_email' => 'support@example.com',
                'support_url' => 'https://example.com',
                'logo' => $logo,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $service = app(IsposBrandingService::class);

        $this->assertSame('RetailOS', $service->name());
        $this->assertSame('Modern retail for growing teams.', $service->tagline());
        $this->assertSame('Acme Retail Group', $service->ownership());
        $this->assertSame('© 2026 Acme Retail Group', $service->copyright());
        $this->assertSame('support@example.com', $service->supportEmail());
        $this->assertSame('https://example.com', $service->supportUrl());
        $this->assertNotNull($service->logoUrl());

        $this->assertDatabaseHas('settings', [
            'scope' => Setting::SCOPE_SYSTEM,
            'scope_id' => null,
            'key' => IsposBrandingService::KEY_NAME,
        ]);
    }

    public function test_settings_page_includes_ispos_branding_settings(): void
    {
        ['store' => $store, 'cashier' => $cashier] = $this->createPosFixtures();
        $cashier->givePermissionTo('settings.view');

        $this->actingAs($cashier)
            ->get(route('admin.settings.edit', ['company_id' => $store->company_id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('isposBrandingSettings'));
    }

    public function test_layout_renders_branding_title_and_favicon(): void
    {
        ['cashier' => $cashier] = $this->createPosFixtures();

        app(IsposBrandingService::class)->update(
            ['name' => 'RetailOS'],
            UploadedFile::fake()->image('logo.png'),
        );

        $company = $cashier->company;
        $companyName = $company->display_name ?: $company->name;

        $this->actingAs($cashier)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('RetailOS | '.e($companyName), false)
            ->assertSee('data-app-favicon', false);
    }

    public function test_shared_app_props_include_branding(): void
    {
        ['cashier' => $cashier] = $this->createPosFixtures();

        app(IsposBrandingService::class)->update([
            'name' => 'Shared Brand',
            'tagline' => 'Shared tagline',
        ]);

        $this->actingAs($cashier)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('app.name', 'Shared Brand')
                ->has('app.branding')
                ->where('app.branding.name', 'Shared Brand'));
    }
}
