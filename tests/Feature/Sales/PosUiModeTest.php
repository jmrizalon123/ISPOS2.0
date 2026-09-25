<?php

namespace Tests\Feature\Sales;

use App\Models\Setting;
use App\Services\SettingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosUiModeTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_retail_store_uses_retail_pos_ui_layout(): void
    {
        ['store' => $store, 'register' => $register, 'cashier' => $cashier] = $this->createPosFixtures();
        $store->update(['store_category' => 'retail']);
        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('POS/Terminal')
                ->where('pos_ui_layout', 'retail')
                ->where('store.pos_ui_layout', 'retail')
                ->where('store.store_category', 'retail'));
    }

    public function test_restaurant_store_uses_restaurant_pos_ui_layout(): void
    {
        ['store' => $store, 'register' => $register, 'cashier' => $cashier] = $this->createPosFixtures();
        $store->update(['store_category' => 'restaurant']);
        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('POS/Terminal')
                ->where('pos_ui_layout', 'restaurant')
                ->where('store.pos_ui_layout', 'restaurant')
                ->where('store.store_category', 'restaurant'));
    }

    public function test_cafe_store_uses_cafe_pos_ui_layout(): void
    {
        ['store' => $store, 'register' => $register, 'cashier' => $cashier] = $this->createPosFixtures();
        $store->update(['store_category' => 'cafe']);
        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('pos_ui_layout', 'cafe'));
    }

    public function test_grocery_store_uses_grocery_pos_ui_layout(): void
    {
        ['store' => $store, 'register' => $register, 'cashier' => $cashier] = $this->createPosFixtures();
        $store->update(['store_category' => 'grocery']);
        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('pos_ui_layout', 'grocery'));
    }

    public function test_store_setting_override_takes_precedence(): void
    {
        ['store' => $store, 'register' => $register, 'cashier' => $cashier] = $this->createPosFixtures();
        $store->update(['store_category' => 'retail']);

        app(SettingService::class)->set('pos.ui_layout', 'pharmacy', Setting::SCOPE_STORE, $store->id);

        $this->startPosSession($cashier, $store, $register);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('pos_ui_layout', 'pharmacy'));
    }
}
