<?php
// tests/Feature/Admin/SubscriptionManagementTest.php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->buyer = User::factory()->create([
            'role' => 'buyer',
            'request_limit' => 1000,
            'subscription_valid_until' => now()->addYear(),
            'is_active' => true,
        ]);
    }

    #[Test]
    public function admin_can_view_subscriptions_list()
    {
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.subscriptions.index'));

        $response->assertStatus(200)
                ->assertSee($this->buyer->name);
    }

    #[Test]
    public function non_admin_cannot_view_subscriptions_list()
    {
        $regularUser = User::factory()->create(['role' => 'buyer']);

        $response = $this->actingAs($regularUser)
                        ->get(route('admin.subscriptions.index'));

        $response->assertStatus(403); // или redirect, зависит от вашей auth логики
    }

    #[Test]
    public function admin_can_edit_subscription()
    {
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.subscriptions.edit', $this->buyer));

        $response->assertStatus(200)
                ->assertSee($this->buyer->email);
    }

    #[Test]
    public function admin_can_update_subscription()
    {
        $newData = [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'request_limit' => 2000,
            'subscription_valid_until' => now()->addYears(2)->format('Y-m-d\TH:i'),
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
                        ->put(route('admin.subscriptions.update', $this->buyer), $newData);

        $response->assertRedirect(route('admin.subscriptions.index'))
                ->assertSessionHas('success');

        $this->buyer->refresh();
        $this->assertEquals('Updated Name', $this->buyer->name);
        $this->assertEquals(2000, $this->buyer->request_limit);
    }

    #[Test]
    public function admin_can_generate_api_key()
    {
        $response = $this->actingAs($this->admin)
                        ->post(route('admin.subscriptions.generate-key', $this->buyer));

        $response->assertRedirect()
                ->assertSessionHas('success')
                ->assertSessionHas('api_key');

        $this->buyer->refresh();
        $this->assertNotNull($this->buyer->api_key);
        $this->assertEquals(64, strlen($this->buyer->api_key));
    }

    #[Test]
    public function admin_can_reset_usage_counter()
    {
        // Устанавливаем начальное значение
        $this->buyer->update(['requests_used' => 500]);

        $response = $this->actingAs($this->admin)
                        ->post(route('admin.subscriptions.reset-usage', $this->buyer));

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->buyer->refresh();
        $this->assertEquals(0, $this->buyer->requests_used);
    }

    #[Test]
    public function admin_can_delete_subscription()
    {
        $response = $this->actingAs($this->admin)
                        ->delete(route('admin.subscriptions.destroy', $this->buyer));

        $response->assertRedirect(route('admin.subscriptions.index'))
                ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $this->buyer->id]);
    }

    #[Test]
    public function cannot_manage_non_buyer_accounts()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($this->admin)
                        ->get(route('admin.subscriptions.edit', $adminUser));

        $response->assertRedirect()
                ->assertSessionHas('error');
    }
}
