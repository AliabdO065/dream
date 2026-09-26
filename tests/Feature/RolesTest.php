<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Modules\Forms\Submission;
use App\Sections\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Owner vs editor vs super admin inside a client's dashboard — see App\Support\Access. */
class RolesTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = Client::create(['slug' => 'acme', 'name' => 'Acme', 'default_locale' => 'en', 'locales' => ['en'], 'status' => 'active']);
    }

    private function member(string $role): User
    {
        $u = User::factory()->create();
        $this->client->users()->attach($u->id, ['role' => $role]);

        return $u;
    }

    private function superAdmin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_super_admin' => true])->save();

        return $u;
    }

    private function lead(): Submission
    {
        return Submission::create(['client_id' => $this->client->id, 'payload' => ['name' => 'Visitor', 'phone' => '123']]);
    }

    public function test_an_editor_works_on_content_but_cannot_delete_or_touch_the_profile_or_team(): void
    {
        $editor = $this->member('editor');
        $s = app(Builder::class)->add($this->client, 'text', 'About');
        $lead = $this->lead();

        // allowed: the day-to-day work
        $this->actingAs($editor)->get('/manage/acme')->assertOk();
        $this->actingAs($editor)->get('/manage/acme/sections')->assertOk();
        $this->actingAs($editor)->post("/manage/acme/sections/{$s->id}/toggle")->assertRedirect();
        $this->actingAs($editor)->get('/manage/acme/leads')->assertOk();
        $this->actingAs($editor)->get("/manage/acme/leads/{$lead->id}")->assertOk();

        // not allowed
        $this->actingAs($editor)->delete("/manage/acme/sections/{$s->id}")->assertForbidden();
        $this->actingAs($editor)->delete("/manage/acme/leads/{$lead->id}")->assertForbidden();
        $this->actingAs($editor)->get('/manage/acme/profile')->assertForbidden();
        $this->actingAs($editor)->put('/manage/acme/profile', ['name' => 'Hacked', 'timezone' => 'UTC', 'default_locale' => 'en',
            'theme_brand' => '#000000', 'theme_accent' => '#000000', 'theme_font' => 'sans'])->assertForbidden();
        $this->actingAs($editor)->get('/manage/acme/team')->assertForbidden();
        $this->actingAs($editor)->post('/manage/acme/team', ['email' => 'x@example.com'])->assertForbidden();

        $this->assertNotNull($s->fresh());
        $this->assertNotNull(Submission::find($lead->id));
        $this->assertSame('Acme', $this->client->fresh()->name);
        $this->assertFalse(User::where('email', 'x@example.com')->exists());
    }

    public function test_an_owner_and_a_super_admin_can_do_everything(): void
    {
        foreach ([$this->member('owner'), $this->superAdmin()] as $user) {
            $s = app(Builder::class)->add($this->client, 'text', 'About');
            $lead = $this->lead();

            $this->actingAs($user)->get('/manage/acme/profile')->assertOk();
            $this->actingAs($user)->get('/manage/acme/team')->assertOk();
            $this->actingAs($user)->delete("/manage/acme/sections/{$s->id}")->assertRedirect();
            $this->actingAs($user)->delete("/manage/acme/leads/{$lead->id}")->assertRedirect();

            $this->assertNull($s->fresh());
            $this->assertNull(Submission::find($lead->id));
        }
    }

    public function test_the_sidebar_and_the_ui_abilities_follow_the_role(): void
    {
        $routes = fn ($res) => array_column($res->viewData('page')['props']['menu'], 'route');

        $editor = $this->actingAs($this->member('editor'))->get('/manage/acme');
        $this->assertNotContains('manage.profile.edit', $routes($editor));
        $this->assertNotContains('manage.team.index', $routes($editor));
        $this->assertSame(['sections.delete' => false, 'leads.delete' => false, 'profile.edit' => false, 'team.manage' => false],
            $editor->viewData('page')['props']['auth']['membership']['can']);

        $owner = $this->actingAs($this->member('owner'))->get('/manage/acme');
        $this->assertContains('manage.profile.edit', $routes($owner));
        $this->assertContains('manage.team.index', $routes($owner));
        $this->assertNotContains(false, $owner->viewData('page')['props']['auth']['membership']['can']);
    }

    public function test_an_owner_adds_and_removes_editors_but_never_owners(): void
    {
        $owner = $this->member('owner');
        $coOwner = $this->member('owner');

        // a brand-new person becomes an editor with a one-time password
        $this->actingAs($owner)->post('/manage/acme/team', ['email' => 'new@example.com', 'name' => 'New'])
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Temporary password'));
        $new = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('editor', $new->roleFor($this->client));
        $this->assertFalse((bool) $new->is_super_admin);

        // someone already on the team is not added again (and an owner is never demoted this way)
        $this->actingAs($owner)->post('/manage/acme/team', ['email' => $coOwner->email])->assertSessionHasErrors('email');
        $this->assertSame('owner', $coOwner->roleFor($this->client));

        // owners can't be removed from here; editors can
        $this->actingAs($owner)->delete("/manage/acme/team/{$coOwner->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/manage/acme/team/{$owner->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/manage/acme/team/{$new->id}")->assertRedirect();
        $this->assertNull($new->roleFor($this->client));
        $this->assertNotNull(User::find($new->id)); // the account itself stays
    }

    // ------------------------------------------------------------ platform: admin vs assistant

    private function assistant(): User
    {
        $u = User::factory()->create();
        $u->setPlatformRole('assistant');

        return $u;
    }

    public function test_an_assistant_does_the_client_work_but_not_the_irreversible_or_staff_parts(): void
    {
        $assistant = $this->assistant();
        $base = ['name' => 'Acme', 'slug' => 'acme', 'status' => 'active'];

        // allowed: the admin area, creating/editing clients, their members, and working on any client's site as an owner
        $this->actingAs($assistant)->get('/home')->assertRedirect('/admin');
        $this->actingAs($assistant)->get('/admin')->assertOk();
        $this->actingAs($assistant)->get('/admin/clients')->assertOk();
        $this->actingAs($assistant)->post('/admin/clients', ['name' => 'New', 'slug' => 'new-co', 'preset' => 'blank',
            'default_locale' => 'en', 'status' => 'active', 'owner_email' => 'boss@example.com'])->assertRedirect('/admin/clients');
        $this->actingAs($assistant)->put('/admin/clients/acme', ['name' => 'Acme 2'] + $base)->assertSessionHasNoErrors();
        $this->actingAs($assistant)->post('/admin/clients/acme/members', ['email' => 'ed@example.com', 'role' => 'editor'])->assertSessionHasNoErrors();
        $this->actingAs($assistant)->get('/manage/acme/profile')->assertOk();
        $s = app(Builder::class)->add($this->client, 'text', 'About');
        $this->actingAs($assistant)->delete("/manage/acme/sections/{$s->id}")->assertRedirect();

        // not allowed: deleting or suspending a client, and the platform team
        $this->actingAs($assistant)->delete('/admin/clients/acme')->assertForbidden();
        $this->actingAs($assistant)->put('/admin/clients/acme', ['status' => 'suspended'] + $base)->assertSessionHasErrors('status');
        $this->actingAs($assistant)->get('/admin/admins')->assertForbidden();
        $this->actingAs($assistant)->post('/admin/admins', ['email' => 'me2@example.com', 'role' => 'admin'])->assertForbidden();
        $this->actingAs($assistant)->put("/admin/admins/{$assistant->id}/role", ['role' => 'admin'])->assertForbidden();

        $this->assertNotNull($this->client->fresh());
        $this->assertSame('active', $this->client->fresh()->status);
        $this->assertFalse($assistant->fresh()->is_super_admin);
    }

    public function test_an_assistant_cannot_lift_a_suspension_but_an_admin_can(): void
    {
        $this->client->update(['status' => 'suspended']);
        $base = ['name' => 'Acme', 'slug' => 'acme'];

        $this->actingAs($this->assistant())->put('/admin/clients/acme', ['status' => 'active'] + $base)->assertSessionHasErrors('status');
        $this->assertSame('suspended', $this->client->fresh()->status);

        $this->actingAs($this->superAdmin())->put('/admin/clients/acme', ['status' => 'active'] + $base)->assertSessionHasNoErrors();
        $this->assertSame('active', $this->client->fresh()->status);
    }

    public function test_an_admin_manages_the_platform_team_and_a_person_is_never_both_roles(): void
    {
        $admin = $this->superAdmin();
        $existing = User::factory()->create();

        $this->actingAs($admin)->post('/admin/admins', ['email' => $existing->email, 'role' => 'assistant'])->assertSessionHasNoErrors();
        $this->assertSame('assistant', $existing->fresh()->platformRole());

        $this->actingAs($admin)->put("/admin/admins/{$existing->id}/role", ['role' => 'admin']);
        $this->assertTrue($existing->fresh()->is_super_admin);
        $this->assertFalse($existing->fresh()->is_assistant);

        $this->actingAs($admin)->put("/admin/admins/{$existing->id}/role", ['role' => null]);
        $this->assertNull($existing->fresh()->platformRole());
        $this->actingAs($existing->fresh())->get('/admin')->assertForbidden();
    }

    public function test_the_layout_knows_admin_from_assistant(): void
    {
        $this->actingAs($this->assistant())->get('/admin')->assertInertia(fn ($p) => $p
            ->where('auth.user.is_staff', true)->where('auth.user.is_super_admin', false)->where('auth.user.platform_role', 'assistant'));
        $this->actingAs($this->superAdmin())->get('/admin')->assertInertia(fn ($p) => $p
            ->where('auth.user.is_staff', true)->where('auth.user.is_super_admin', true)->where('auth.user.platform_role', 'admin'));
    }

    public function test_the_platform_team_page_lists_each_persons_client_roles(): void
    {
        $person = $this->member('owner');
        $other = Client::create(['slug' => 'other', 'name' => 'Beta', 'default_locale' => 'en', 'locales' => ['en'], 'status' => 'active']);
        $other->users()->attach($person->id, ['role' => 'editor']);

        $this->actingAs($this->superAdmin())->get('/admin/admins')->assertInertia(fn ($p) => $p
            ->where('users.data', fn ($users) => collect($users)->firstWhere('id', $person->id)['memberships'] === [
                ['id' => $this->client->id, 'slug' => 'acme', 'name' => 'Acme', 'role' => 'owner'],
                ['id' => $other->id, 'slug' => 'other', 'name' => 'Beta', 'role' => 'editor'],
            ]));
    }

    public function test_the_platform_team_page_is_grouped_by_site_with_a_tab_per_site(): void
    {
        $other = Client::create(['slug' => 'other', 'name' => 'Beta', 'default_locale' => 'en', 'locales' => ['en'], 'status' => 'active']);
        $acmeEditor = $this->member('editor');
        $acmeOwner = $this->member('owner');
        $betaOwner = User::factory()->create(['name' => 'Aaron']);
        $other->users()->attach($betaOwner->id, ['role' => 'owner']);
        $other->users()->attach($acmeEditor->id, ['role' => 'owner']); // an Acme editor who owns Beta
        $loner = User::factory()->create(['name' => 'Aa no client']);
        $admin = $this->superAdmin();
        $ids = fn (array $expected) => fn ($users) => collect($users)->pluck('id')->all() === $expected;

        // Everyone: staff, then Acme (owner before editor), then Beta, then people with no site
        $this->actingAs($admin)->get('/admin/admins')->assertInertia(fn ($p) => $p
            ->where('tab', 'all')
            ->where('users.data', $ids([$admin->id, $acmeOwner->id, $acmeEditor->id, $betaOwner->id, $loner->id]))
            ->where('sites', [['slug' => 'acme', 'name' => 'Acme', 'logo' => null, 'count' => 2], ['slug' => 'other', 'name' => 'Beta', 'logo' => null, 'count' => 2]]));

        // One site's tab: only its people, ordered by their role THERE (both are owners of Beta → by name)
        $this->actingAs($admin)->get('/admin/admins?site=other')->assertInertia(fn ($p) => $p
            ->where('tab', 'other')
            ->where('users.data', $ids(collect([$betaOwner, $acmeEditor])->sortBy('name')->pluck('id')->values()->all())));

        $this->actingAs($admin)->get('/admin/admins?site=staff')->assertInertia(fn ($p) => $p
            ->where('tab', 'staff')->where('users.data', $ids([$admin->id])));

        $this->actingAs($admin)->get('/admin/admins?site=nope')->assertInertia(fn ($p) => $p->where('tab', 'all'));
    }

    public function test_only_a_super_admin_can_give_someone_a_new_password_shown_once(): void
    {
        $owner = $this->member('owner');
        $oldHash = $owner->password;

        $this->actingAs($this->assistant())->post("/admin/admins/{$owner->id}/password")->assertForbidden();
        $this->actingAs($owner)->post("/admin/admins/{$owner->id}/password")->assertForbidden();
        $this->assertSame($oldHash, $owner->fresh()->password);

        $admin = $this->superAdmin();
        $this->actingAs($admin)->from('/admin/admins')->post("/admin/admins/{$owner->id}/password")->assertRedirect('/admin/admins');
        $shown = session('password');
        $this->assertSame($owner->email, $shown['email']);
        $this->assertTrue(Hash::check($shown['password'], $owner->fresh()->password)); // the shown one is the one that works
        $this->assertStringNotContainsString($shown['password'], json_encode($owner->fresh()->getAttributes())); // stored only hashed

        // shown once: on the page the redirect lands on, and never again after that
        $this->actingAs($admin)->get('/admin/admins')->assertInertia(fn ($p) => $p->where('flash.password.email', $owner->email));
        $this->actingAs($admin)->get('/admin/admins')->assertInertia(fn ($p) => $p->where('flash.password', null));
    }

    public function test_an_owner_cannot_manage_the_team_of_another_client(): void
    {
        $owner = $this->member('owner');
        $other = Client::create(['slug' => 'other', 'name' => 'Other', 'default_locale' => 'en', 'locales' => ['en'], 'status' => 'active']);
        $theirEditor = User::factory()->create();
        $other->users()->attach($theirEditor->id, ['role' => 'editor']);

        $this->actingAs($owner)->get('/manage/other/team')->assertForbidden();
        $this->actingAs($owner)->delete("/manage/acme/team/{$theirEditor->id}")->assertNotFound();
        $this->assertSame('editor', $theirEditor->roleFor($other));
    }
}
