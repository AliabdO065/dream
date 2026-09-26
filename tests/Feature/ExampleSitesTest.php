<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Sections\Translations;
use Database\Seeders\ExampleSitesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** database/seeders/ExampleSitesSeeder: the "start the demo data over" seeder. */
class ExampleSitesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (Client::withTrashed()->pluck('id') as $id) {
            File::deleteDirectory(public_path("uploads/clients/{$id}")); // generated pictures
        }
        parent::tearDown();
    }

    public function test_it_resets_everything_to_one_example_site_per_business_kind(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_super_admin' => true])->save();
        $old = Client::create(['slug' => 'old-one', 'name' => 'Old', 'default_locale' => 'en', 'locales' => ['en']]);
        $oldOwner = User::factory()->create();
        $old->users()->attach($oldOwner->id, ['role' => 'owner']);

        $this->seed(ExampleSitesSeeder::class);

        // wiped: old clients and non-admin users; the super admin stays
        $this->assertNull(Client::withTrashed()->where('slug', 'old-one')->first());
        $this->assertNull(User::find($oldOwner->id));
        $this->assertTrue($admin->fresh()->is_super_admin);

        // one platform assistant
        $this->assertSame(['assistant@erp.test'], User::where('is_assistant', true)->pluck('email')->all());

        // the platform site + one site per business kind, each with exactly one owner and every section type
        $kinds = config('platform.business_kinds');
        $this->assertSame(count($kinds) + 1, Client::count());
        $allTypes = array_keys(Client::where('slug', 'erp')->first()->allowedSectionTypes());
        $this->assertCount(15, $allTypes);

        foreach (Client::where('slug', '!=', 'erp')->get() as $c) {
            $this->assertContains($c->business_type, array_column($kinds, 'label'));
            $this->assertSame(['owner'], $c->users->pluck('pivot.role')->all(), $c->slug);
            $this->assertEqualsCanonicalizing($allTypes, $c->sections()->where('is_enabled', true)->pluck('type')->unique()->all(), $c->slug);
            $this->get('/' . $c->slug)->assertOk()->assertSee($c->name);
        }

        // "/" uses every type too (some hidden until real facts are filled in) and is fully offered in English
        $home = Client::where('slug', 'erp')->first();
        $this->assertEqualsCanonicalizing($allTypes, $home->sections()->pluck('type')->unique()->all());
        $this->assertSame(['ar', 'en'], Translations::publicLocales($home));
        $this->get('/?lang=en')->assertOk()->assertSee('Your professional website')->assertSee('/powerfit-gym', false);
    }
}
