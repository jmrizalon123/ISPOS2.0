<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_admin_can_upload_an_avatar_when_creating_a_user(): void
    {
        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                ...$this->payload($admin),
                'email' => 'avatar-user@example.com',
                'avatar' => UploadedFile::fake()->image('me.png', 200, 200),
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('email', 'avatar-user@example.com')->firstOrFail();

        $this->assertNotNull($created->avatar);
        Storage::disk('public')->assertExists($created->avatar);
        $this->assertSame(Storage::disk('public')->url($created->avatar), $created->avatar_url);
    }

    public function test_uploading_a_new_avatar_replaces_the_previous_file(): void
    {
        $admin = $this->companyAdmin();
        $user = $this->member($admin, 'replace-avatar@example.com');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user->id), [
                ...$this->payload($admin),
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('first.png'),
            ])
            ->assertRedirect(route('admin.users.index'));

        $firstPath = $user->refresh()->avatar;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user->id), [
                ...$this->payload($admin),
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('second.png'),
            ])
            ->assertRedirect(route('admin.users.index'));

        $secondPath = $user->refresh()->avatar;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_admin_can_remove_an_avatar(): void
    {
        $admin = $this->companyAdmin();
        $user = $this->member($admin, 'remove-avatar@example.com');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user->id), [
                ...$this->payload($admin),
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('me.png'),
            ])
            ->assertRedirect(route('admin.users.index'));

        $path = $user->refresh()->avatar;
        $this->assertNotNull($path);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user->id), [
                ...$this->payload($admin),
                'email' => $user->email,
                'remove_avatar' => true,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertNull($user->refresh()->avatar);
        $this->assertNull($user->avatar_url);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_avatar_must_be_an_image(): void
    {
        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                ...$this->payload($admin),
                'email' => 'bad-avatar@example.com',
                'avatar' => UploadedFile::fake()->create('resume.pdf', 40, 'application/pdf'),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertDatabaseMissing('users', ['email' => 'bad-avatar@example.com']);
    }

    public function test_index_exposes_username_and_avatar_url(): void
    {
        $admin = $this->companyAdmin();
        $this->member($admin, 'listed@example.com', 'listed.user');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->where(
                    'users.data',
                    fn ($rows) => collect($rows)->contains(
                        fn ($row) => $row['username'] === 'listed.user' && array_key_exists('avatar_url', $row),
                    ),
                ),
            );
    }

    protected function companyAdmin(): User
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $admin->assignRole('Company Admin');

        return $admin;
    }

    protected function member(User $admin, string $email, ?string $username = null): User
    {
        return User::factory()->create([
            'company_id' => $admin->company_id,
            'email' => $email,
            'username' => $username,
            'status' => 'active',
        ]);
    }

    /** @return array<string, mixed> */
    protected function payload(User $admin): array
    {
        return [
            'name' => 'Avatar Person',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_id' => $admin->company_id,
            'status' => 'active',
            'roles' => ['Cashier'],
            'store_ids' => [],
        ];
    }
}
