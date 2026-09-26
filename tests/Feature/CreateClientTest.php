<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The simplified "new client" flow: pick a kind, type a name and an email — everything else has a sensible default. */
class CreateClientTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->setPlatformRole('admin');
    }

    public function test_the_minimum_is_a_name_a_language_and_an_owner_email(): void
    {
        $this->actingAs($this->admin)->post('/admin/clients', [
            'name' => 'مطعم النور', 'default_locale' => 'ar', 'owner_email' => 'noor@example.com',
        ])->assertRedirect('/admin/clients')->assertSessionHas('created', fn ($c) => $c['slug'] === 'mtaam-alnor' && $c['published'] === false && $c['password']);

        $client = Client::where('slug', 'mtaam-alnor')->firstOrFail(); // Arabic name → a readable Latin address
        $this->assertSame('draft', $client->status);                    // sample content stays hidden until published
        $this->assertSame('owner', User::where('email', 'noor@example.com')->first()->roleFor($client));
        $this->assertTrue($client->sections()->exists());               // blank layout: hero + contact
    }

    public function test_picking_a_kind_sets_the_layout_the_business_type_and_the_brand_color(): void
    {
        $this->actingAs($this->admin)->post('/admin/clients', [
            'name' => 'Café Noor', 'kind' => 'restaurant', 'default_locale' => 'en', 'brand' => '#DC2626',
            'status' => 'active', 'owner_email' => 'cafe@example.com',
        ])->assertSessionHasNoErrors();

        $client = Client::where('slug', 'cafe-noor')->firstOrFail();
        $this->assertSame('Restaurant / café', $client->business_type);
        $this->assertSame('#dc2626', $client->theme['brand']);
        $this->assertTrue($client->sections()->where('type', 'priced_list')->exists()); // the venue layout has a menu
        $this->get('/cafe-noor')->assertOk();
    }

    public function test_a_taken_or_reserved_name_gets_the_next_free_address(): void
    {
        Client::create(['slug' => 'acme', 'name' => 'Acme', 'default_locale' => 'en', 'locales' => ['en']]);
        Client::create(['slug' => 'acme-2', 'name' => 'Acme 2', 'default_locale' => 'en', 'locales' => ['en']])->delete(); // deleted still holds its address

        $base = ['default_locale' => 'en', 'owner_email' => 'o@example.com'];
        $this->actingAs($this->admin)->post('/admin/clients', ['name' => 'ACME'] + $base)->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post('/admin/clients', ['name' => 'Admin'] + $base)->assertSessionHasNoErrors();

        $this->assertTrue(Client::where('slug', 'acme-3')->exists());
        $this->assertTrue(Client::where('slug', 'admin-2')->exists()); // "admin" is a reserved address
    }

    public function test_the_address_helper_suggests_and_checks(): void
    {
        Client::create(['slug' => 'taken', 'name' => 'Taken', 'default_locale' => 'en', 'locales' => ['en']]);

        $this->actingAs($this->admin)->getJson('/admin/clients/slug?name=' . urlencode('صالون ليالي') . '&locale=ar')
            ->assertOk()->assertJson(['slug' => 'salon-lyaly', 'available' => true]);
        $this->actingAs($this->admin)->getJson('/admin/clients/slug?name=Taken')->assertJson(['slug' => 'taken-2']);

        $this->actingAs($this->admin)->getJson('/admin/clients/slug?slug=taken')->assertJson(['available' => false]);
        $this->actingAs($this->admin)->getJson('/admin/clients/slug?slug=login')->assertJson(['available' => false]);
        $this->actingAs($this->admin)->getJson('/admin/clients/slug?slug=Bad Slug')->assertJson(['available' => false]);
        $this->actingAs($this->admin)->getJson('/admin/clients/slug?slug=fresh-one')->assertJson(['available' => true, 'message' => null]);

        $this->actingAs(User::factory()->create())->getJson('/admin/clients/slug?slug=x')->assertForbidden();
    }

    public function test_the_create_page_gets_kinds_with_their_sections_for_the_preview(): void
    {
        $this->actingAs($this->admin)->get('/admin/clients/create')->assertInertia(fn ($p) => $p
            ->component('Admin/Clients/Create')
            ->where('kinds', fn ($k) => collect($k)->pluck('value')->contains('restaurant') && collect($k)->every(fn ($x) => isset($x['icon'], $x['preset'])))
            ->where('presets.venue', fn ($s) => collect($s)->pluck('type')->contains('priced_list'))
            ->has('colors'));
    }
}
