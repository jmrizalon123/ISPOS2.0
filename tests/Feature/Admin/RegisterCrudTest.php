<?php

namespace Tests\Feature\Admin;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\Company;
use App\Models\Register;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_company_admin_can_create_register_with_extended_fields(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $user->assignRole('Company Admin');

        $payload = [
            'store_id' => $store->id,
            'register_code' => 'REG-A1',
            'register_name' => 'Front Counter',
            'min' => 'MIN-98765',
            'permit_number' => 'PTU-001',
            'terminal_code' => 'T01',
            'terminal_name' => 'Terminal 1',
            'allow_cash_sales' => true,
            'allow_card_sales' => true,
            'allow_gcash_sales' => false,
            'allow_maya_sales' => false,
            'allow_other_payments' => true,
            'allow_discount' => true,
            'allow_void' => false,
            'allow_refund' => false,
            'allow_reprint' => true,
            'allow_price_override' => false,
            'allow_open_drawer' => false,
            'require_cashier_login' => true,
            'require_manager_approval' => false,
            'auto_print_receipt' => true,
            'auto_print_kitchen_order' => false,
            'auto_print_customer_receipt' => false,
            'enable_customer_display' => false,
            'enable_kds' => false,
            'enable_ncs' => false,
            'online_order_enabled' => false,
            'offline_mode_enabled' => true,
            'sync_enabled' => true,
            'status' => 'active',
            'is_active' => true,
        ];

        $this->actingAs($user)
            ->withSession([
                BackofficeContextService::SESSION_KEY => [
                    'scope' => 'company',
                    'company_id' => $company->id,
                    'store_id' => null,
                ],
                BackofficeContextService::ESTABLISHED_SESSION_KEY => true,
            ])
            ->post(route('admin.registers.store'), $payload)
            ->assertRedirect(route('admin.registers.index'));

        $register = Register::query()->where('register_code', 'REG-A1')->first();

        $this->assertNotNull($register);
        $this->assertSame('Front Counter', $register->register_name);
        $this->assertSame('MIN-98765', $register->min);
        $this->assertSame('PTU-001', $register->permit_number);
        $this->assertSame($company->id, $register->company_id);
        $this->assertTrue($register->allow_cash_sales);
        $this->assertFalse($register->allow_gcash_sales);
    }
}
