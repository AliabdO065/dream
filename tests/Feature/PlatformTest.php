<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Section;
use App\Models\User;
use App\Modules\Forms\NewLeadMail;
use App\Modules\Forms\Submission;
use App\Sections\Builder;
use App\Support\CurrentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private function client(string $slug = 'acme', array $attrs = []): Client
    {
        return Client::create(array_merge([
            'slug' => $slug, 'name' => ucfirst($slug), 'default_locale' => 'en', 'locales' => ['en'], 'status' => 'active',
        ], $attrs));
    }

    private function superAdmin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_super_admin' => true])->save();

        return $u;
    }

    private function member(Client $c, string $role = 'owner'): User
    {
        $u = User::factory()->create();
        $c->users()->attach($u->id, ['role' => $role]);

        return $u;
    }

    private function editor(Client $c, array $permissions): User
    {
        $u = User::factory()->create();
        $c->users()->attach($u->id, ['role' => 'editor', 'permissions' => User::encodePermissions($permissions)]);

        return $u;
    }

    private function add(Client $c, string $type, string $name, array $plain = [], array $config = []): Section
    {
        return app(Builder::class)->add($c, $type, $name, $plain, $config);
    }

    // ------------------------------------------------------------ public site

    public function test_public_site_renders_enabled_sections_in_order_and_hides_disabled(): void
    {
        $c = $this->client();
        $this->add($c, 'hero', 'Hero', ['headline' => 'First block']);
        $this->add($c, 'text', 'About', ['title' => 'Second block']);
        $hidden = $this->add($c, 'faq', 'FAQ', ['title' => 'Hidden block']);
        $hidden->update(['is_enabled' => false]);

        $this->get('/acme')->assertOk()
            ->assertSeeInOrder(['First block', 'Second block'])
            ->assertDontSee('Hidden block');
    }

    public function test_draft_suspended_and_unknown_clients_are_404(): void
    {
        $this->client('drafty', ['status' => 'draft']);
        $this->client('paused', ['status' => 'suspended']);

        $this->get('/drafty')->assertNotFound();
        $this->get('/paused')->assertNotFound();
        $this->get('/nobody')->assertNotFound();
    }

    public function test_unknown_section_type_is_skipped_not_fatal(): void
    {
        $c = $this->client();
        $this->add($c, 'hero', 'Hero', ['headline' => 'Still standing']);
        Section::create(['client_id' => $c->id, 'type' => 'removed_type', 'name' => 'Gone', 'position' => 99, 'content' => [], 'config' => []]);

        $this->get('/acme')->assertOk()->assertSee('Still standing');
    }

    public function test_content_is_escaped_and_unsafe_links_are_dropped(): void
    {
        $c = $this->client();
        $owner = $this->member($c);

        $this->actingAs($owner)->post("/manage/acme/sections", [
            'type' => 'cta', 'name' => 'Call',
            'content' => [
                'title' => ['en' => '<script>alert(1)</script>'],
                'button_label' => ['en' => 'Go'],
                'button_url' => ['en' => 'javascript:alert(1)'],
                'evil_extra_key' => 'x',
            ],
            'config' => ['background' => 'light'],
        ])->assertRedirect();

        $section = $c->sections()->first();
        $this->assertArrayNotHasKey('evil_extra_key', $section->content);
        $this->assertSame('', $section->content['button_url']);

        $this->get('/acme')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_a_language_is_only_served_once_the_whole_page_is_translated(): void
    {
        $c = $this->client('multi', ['default_locale' => 'de', 'locales' => ['de', 'en']]);
        $s = $this->add($c, 'text', 'About', ['title' => 'Über uns', 'body' => 'Text']);
        app(Builder::class)->translate($s, 'en', ['title' => 'About us']);

        // half translated: English is neither offered nor served — no page that's half German, half English
        $this->get('/multi?lang=en')->assertSee('Über uns')->assertDontSee('About us')->assertDontSee('?lang=en', false);

        app(Builder::class)->translate($s->fresh(), 'en', ['body' => 'Our story']);
        $this->get('/multi?lang=en')->assertSee('About us')->assertSee('Our story');
        $this->get('/multi')->assertSee('Über uns')->assertSee('?lang=en', false); // now in the language menu
        $this->get('/multi?lang=xx')->assertSee('Über uns'); // unsupported language -> default
    }

    public function test_cached_page_updates_after_a_section_changes(): void
    {
        $c = $this->client();
        $s = $this->add($c, 'text', 'About', ['title' => 'Old heading']);
        $this->get('/acme')->assertSee('Old heading');

        $content = $s->content;
        $content['title'] = ['en' => 'New heading'];
        $s->update(['content' => $content]);

        $this->get('/acme')->assertSee('New heading')->assertDontSee('Old heading');
    }

    // ------------------------------------------------------------ slugs

    public function test_slug_rules_reserved_invalid_and_duplicate(): void
    {
        $admin = $this->superAdmin();
        $this->client('taken');
        $base = ['name' => 'X', 'preset' => 'blank', 'default_locale' => 'en', 'status' => 'active', 'owner_email' => 'o@example.com'];

        foreach (['admin', 'login', 'Bad Slug', 'UPPER', 'taken', 'a_b'] as $bad) {
            $this->actingAs($admin)->post('/admin/clients', $base + ['slug' => $bad])->assertSessionHasErrors('slug');
        }
        $this->assertSame(1, Client::count());
    }

    // ------------------------------------------------------------ tenant isolation & access

    public function test_a_member_cannot_touch_another_clients_dashboard(): void
    {
        $a = $this->client('alpha');
        $b = $this->client('beta');
        $userA = $this->member($a);
        $sectionB = $this->add($b, 'text', 'Secret', ['title' => 'Beta only']);

        $this->actingAs($userA)->get('/manage/alpha/sections')->assertOk();
        $this->actingAs($userA)->get('/manage/beta/sections')->assertForbidden();
        $this->actingAs($userA)->put("/manage/beta/sections/{$sectionB->id}", ['name' => 'hacked'])->assertForbidden();
        $this->assertSame('Secret', $sectionB->fresh()->name);
    }

    public function test_a_section_id_from_another_client_is_not_reachable_through_my_client(): void
    {
        $a = $this->client('alpha');
        $b = $this->client('beta');
        $userA = $this->member($a);
        $sectionB = $this->add($b, 'text', 'Secret');

        $this->actingAs($userA)->get("/manage/alpha/sections/{$sectionB->id}/edit")->assertNotFound();
        $this->actingAs($userA)->delete("/manage/alpha/sections/{$sectionB->id}")->assertNotFound();
        $this->assertNotNull(Section::find($sectionB->id));
    }

    public function test_tenant_scope_limits_queries_once_a_client_is_current(): void
    {
        $a = $this->client('alpha');
        $b = $this->client('beta');
        $this->add($a, 'text', 'A');
        $this->add($b, 'text', 'B');

        $this->assertSame(2, Section::count());
        app(CurrentClient::class)->set($a);
        $this->assertSame(['A'], Section::pluck('name')->all());
        $this->assertSame($a->id, Section::create(['type' => 'text', 'name' => 'Auto'])->client_id);
    }

    public function test_images_cannot_point_at_another_clients_files(): void
    {
        Storage::fake('uploads');
        $a = $this->client('alpha');
        $b = $this->client('beta');
        $owner = $this->member($a);

        $this->actingAs($owner)->post('/manage/alpha/sections', [
            'type' => 'hero', 'name' => 'Hero',
            'content' => ['headline' => ['en' => 'Hi'], 'image' => "clients/{$b->id}/stolen.png"],
        ])->assertRedirect();

        $this->assertSame('', $a->sections()->first()->content['image']);
    }

    public function test_image_upload_is_stored_in_the_clients_folder_and_bad_types_are_rejected(): void
    {
        Storage::fake('uploads');
        $c = $this->client();
        $owner = $this->member($c);

        $s = $this->add($c, 'hero', 'Hero', ['headline' => 'Hi']);
        $url = "/manage/acme/sections/{$s->id}";

        $good = UploadedFile::fake()->image('photo.png', 40, 40);
        $this->actingAs($owner)->post($url, [
            '_method' => 'PUT', 'name' => 'Hero',
            'content' => ['headline' => ['en' => 'Hi']], 'uploads' => ['image' => $good],
        ])->assertRedirect();

        $path = $s->fresh()->content['image'];
        $this->assertStringStartsWith("clients/{$c->id}/", $path);
        Storage::disk('uploads')->assertExists($path);

        $svg = UploadedFile::fake()->create('evil.svg', 5, 'image/svg+xml');
        $this->actingAs($owner)->post($url, [
            '_method' => 'PUT', 'name' => 'Hero',
            'content' => ['headline' => ['en' => 'Hi']], 'uploads' => ['image' => $svg],
        ])->assertSessionHasErrors('content');
        $this->assertSame($path, $s->fresh()->content['image']); // unchanged
    }

    public function test_a_failed_save_leaves_no_uploaded_files_behind(): void
    {
        Storage::fake('uploads');
        $c = $this->client();
        $owner = $this->member($c);
        $existing = "clients/{$c->id}/existing.png";
        $s = $this->sectionWithImages($c, [$existing]);
        $before = $s->fresh()->content;
        $svg = fn () => UploadedFile::fake()->create('evil.svg', 5, 'image/svg+xml');
        $png = fn () => UploadedFile::fake()->image('ok.png', 30, 30);

        // UPDATE: row 1 is a valid image (stored first), row 2 is invalid -> the whole save fails
        $this->actingAs($owner)->post("/manage/acme/sections/{$s->id}", [
            '_method' => 'PUT', 'name' => 'Gallery',
            'content' => ['images' => [['image' => ''], ['image' => '']]], 'uploads' => ['images' => [['image' => $png()], ['image' => $svg()]]],
        ])->assertSessionHasErrors('content');
        $this->assertSame([$existing], Storage::disk('uploads')->allFiles()); // nothing new left behind
        $this->assertSame($before, $s->fresh()->content);                      // and nothing changed

        // CREATE: same failure, no section and no files
        $this->post('/manage/acme/sections', [
            'type' => 'gallery', 'name' => 'New gallery',
            'content' => ['images' => [['image' => ''], ['image' => ''], ['image' => '']]], 'uploads' => ['images' => [['image' => $png()], ['image' => $png()], ['image' => $svg()]]],
        ])->assertSessionHasErrors('content');
        $this->assertSame([$existing], Storage::disk('uploads')->allFiles());
        $this->assertSame(1, $c->sections()->count());
    }

    public function test_a_database_failure_after_an_upload_also_removes_the_file(): void
    {
        Storage::fake('uploads');
        $c = $this->client();
        $owner = $this->member($c);
        Section::creating(fn () => throw new \RuntimeException('database went away'));

        $this->actingAs($owner)->post('/manage/acme/sections', [
            'type' => 'gallery', 'name' => 'G', 'content' => ['images' => [['image' => '']]], 'uploads' => ['images' => [['image' => UploadedFile::fake()->image('a.png', 30, 30)]]],
        ])->assertStatus(500);

        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_a_failed_save_never_touches_files_from_an_earlier_successful_one(): void
    {
        Storage::fake('uploads');
        $c = $this->client();
        $owner = $this->member($c);

        $this->actingAs($owner)->post('/manage/acme/sections', [
            'type' => 'gallery', 'name' => 'Good', 'content' => ['images' => [['image' => '']]], 'uploads' => ['images' => [['image' => UploadedFile::fake()->image('a.png', 30, 30)]]],
        ])->assertRedirect();
        $kept = Storage::disk('uploads')->allFiles();
        $this->assertCount(1, $kept);

        $this->post('/manage/acme/sections', [
            'type' => 'gallery', 'name' => 'Bad', 'content' => ['images' => [['image' => '']]], 'uploads' => ['images' => [['image' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')]]],
        ])->assertSessionHasErrors('content');

        $this->assertSame($kept, Storage::disk('uploads')->allFiles());
    }

    // ------------------------------------------------------------ admin

    public function test_guests_and_non_admins_cannot_reach_the_platform_admin(): void
    {
        $this->get('/admin/clients')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/clients')->assertForbidden();
    }

    public function test_super_admin_can_open_any_clients_dashboard(): void
    {
        $this->client('alpha');
        $this->actingAs($this->superAdmin())->get('/manage/alpha/sections')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Manage/Sections/Index')->where('client.slug', 'alpha')->where('auth.user.is_super_admin', true));
    }

    public function test_the_last_super_admin_cannot_be_demoted(): void
    {
        $admin = $this->superAdmin();
        foreach ([null, 'assistant'] as $role) { // neither removing nor downgrading the last admin
            $this->actingAs($admin)->put("/admin/admins/{$admin->id}/role", ['role' => $role])->assertSessionHasErrors('admin');
            $this->assertTrue($admin->fresh()->is_super_admin);
        }

        $second = $this->superAdmin();
        $this->actingAs($admin)->put("/admin/admins/{$second->id}/role", ['role' => null]);
        $this->assertFalse($second->fresh()->is_super_admin);
    }

    public function test_promoting_a_user_creates_or_upgrades_the_account(): void
    {
        $admin = $this->superAdmin();
        $existing = User::factory()->create();

        $this->actingAs($admin)->post('/admin/admins', ['email' => $existing->email]);
        $this->assertTrue($existing->fresh()->is_super_admin);

        $this->actingAs($admin)->post('/admin/admins', ['email' => 'new@example.com', 'name' => 'New']);
        $this->assertTrue(User::where('email', 'new@example.com')->first()->is_super_admin);
    }

    public function test_creating_a_client_creates_owner_and_preset_sections(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post('/admin/clients', [
            'name' => 'Fresh Bakery', 'slug' => 'fresh-bakery', 'business_type' => 'Bakery', 'preset' => 'service',
            'default_locale' => 'de', 'status' => 'active', 'owner_email' => 'baker@example.com',
        ])->assertRedirect('/admin/clients')->assertSessionHas('created');

        $client = Client::where('slug', 'fresh-bakery')->firstOrFail();
        $this->assertSame(['de'], $client->locales);
        $this->assertTrue($client->sections()->where('type', 'contact_form')->exists());
        $this->assertTrue($client->users()->where('email', 'baker@example.com')->exists());
        $this->get('/fresh-bakery')->assertOk();
    }

    public function test_reordering_sections_changes_the_public_page(): void
    {
        $c = $this->client();
        $first = $this->add($c, 'text', 'One', ['title' => 'AAA']);
        $this->add($c, 'text', 'Two', ['title' => 'BBB']);
        $this->get('/acme')->assertSeeInOrder(['AAA', 'BBB']);

        $this->actingAs($this->member($c))->post("/manage/acme/sections/{$first->id}/move/down")->assertRedirect();
        $this->get('/acme')->assertSeeInOrder(['BBB', 'AAA']);
    }

    // ------------------------------------------------------------ modules & features

    public function test_contact_form_stores_submission_and_honeypot_is_ignored(): void
    {
        $c = $this->client();
        $form = $this->add($c, 'contact_form', 'Form');

        $this->post("/acme/submit/{$form->id}", ['name' => 'Ann', 'phone' => '123'])->assertRedirect();
        $this->assertSame(1, Submission::count());
        $this->assertSame($c->id, Submission::first()->client_id);

        $this->post("/acme/submit/{$form->id}", ['name' => 'Bot', 'email' => 'b@example.com', 'website' => 'http://spam'])->assertRedirect();
        $this->assertSame(1, Submission::count());

        $this->post("/acme/submit/{$form->id}", ['name' => 'NoContact'])->assertStatus(422);
        $this->assertSame(1, Submission::count());
    }

    // ------------------------------------------------------------ forms & leads (core, always on)

    public function test_the_contact_form_is_core_and_needs_no_feature_switch(): void
    {
        $this->assertSame([], config('platform.modules'));
        $c = $this->client(); // no features configured at all
        $this->assertArrayHasKey('contact_form', $c->allowedSectionTypes());

        $form = $this->add($c, 'contact_form', 'Form', ['title' => 'Talk to us']);
        $this->get('/acme')->assertSee('Talk to us');
        $this->post("/acme/submit/{$form->id}", ['name' => 'Ann', 'phone' => '1'])->assertRedirect();
        $this->assertSame(1, Submission::count());
    }

    public function test_hiding_the_form_type_for_one_client_stops_it_showing_and_accepting_posts(): void
    {
        $c = $this->client('acme', ['features' => ['types' => ['hero', 'text']]]);
        $form = $this->add($c, 'contact_form', 'Form', ['title' => 'Talk to us']);
        $this->add($c, 'text', 'About', ['title' => 'Visible']);

        $this->get('/acme')->assertOk()->assertSee('Visible')->assertDontSee('Talk to us');
        $this->post("/acme/submit/{$form->id}", ['name' => 'Ann', 'phone' => '1'])->assertNotFound();
        $this->assertNotNull(Section::find($form->id)); // data kept
        $this->assertSame(0, Submission::count());
    }

    public function test_leads_page_lists_only_own_leads_and_opening_one_marks_it_read(): void
    {
        $a = $this->client('alpha');
        $b = $this->client('beta');
        $mine = Submission::create(['client_id' => $a->id, 'payload' => ['name' => 'Alpha Person', 'phone' => '+49 1', 'email' => 'a@x.io', 'message' => "Line one\nLine two"]]);
        $theirs = Submission::create(['client_id' => $b->id, 'payload' => ['name' => 'Beta Person']]);
        $owner = $this->member($a);

        $this->actingAs($owner)->get('/manage/alpha/leads')->assertOk()
            ->assertSee('Alpha Person')->assertDontSee('Beta Person');
        $this->assertNull($mine->fresh()->read_at); // listing does not mark as read

        $this->get("/manage/alpha/leads/{$mine->id}")->assertOk()->assertSee('Line one')->assertSee('a@x.io');
        $this->assertNotNull($mine->fresh()->read_at);

        // another client's lead is not reachable through my client, in any way
        $this->get("/manage/alpha/leads/{$theirs->id}")->assertNotFound();
        $this->post("/manage/alpha/leads/{$theirs->id}/read", ['read' => 1])->assertNotFound();
        $this->delete("/manage/alpha/leads/{$theirs->id}")->assertNotFound();
        $this->assertNull($theirs->fresh()->read_at);
        $this->assertNotNull(Submission::withoutGlobalScopes()->find($theirs->id));
        $this->actingAs($owner)->get('/manage/beta/leads')->assertForbidden();
    }

    public function test_leads_can_be_marked_read_unread_filtered_bulk_read_and_deleted(): void
    {
        $c = $this->client();
        $l1 = Submission::create(['client_id' => $c->id, 'payload' => ['name' => 'First Lead']]);
        $l2 = Submission::create(['client_id' => $c->id, 'payload' => ['name' => 'Second Lead']]);
        $this->actingAs($this->member($c));

        $this->post("/manage/acme/leads/{$l1->id}/read", ['read' => 1])->assertRedirect();
        $this->assertNotNull($l1->fresh()->read_at);
        $this->get('/manage/acme/leads?filter=unread')->assertSee('Second Lead')->assertDontSee('First Lead');

        $this->post("/manage/acme/leads/{$l1->id}/read", ['read' => 0]);
        $this->assertNull($l1->fresh()->read_at);

        $this->get('/manage/acme/sections')->assertInertia(fn ($p) => $p->where('menu', fn ($m) => collect($m)->firstWhere('route', 'manage.leads.index')['badge'] === 2)); // sidebar shows the unread count
        $this->post('/manage/acme/leads/read-all')->assertRedirect();
        $this->assertSame(0, Submission::whereNull('read_at')->count());
        $this->get('/manage/acme/sections')->assertInertia(fn ($p) => $p->where('menu', fn ($m) => collect($m)->firstWhere('route', 'manage.leads.index')['badge'] === 0));

        $this->delete("/manage/acme/leads/{$l2->id}")->assertRedirect('/manage/acme/leads');
        $this->assertNull(Submission::withoutGlobalScopes()->find($l2->id));
    }

    public function test_a_new_lead_emails_the_owners_only_with_reply_to_the_visitor(): void
    {
        Mail::fake();
        $c = $this->client('acme', ['email' => 'shop@acme.test']);
        $owner = $this->member($c, 'owner');
        $editor = $this->member($c, 'editor');
        $form = $this->add($c, 'contact_form', 'Form');

        $this->post("/acme/submit/{$form->id}", ['name' => "Ann\nBcc: evil@x.io", 'email' => 'ann@visitor.io', 'message' => 'Hello'])->assertRedirect();

        Mail::assertSent(NewLeadMail::class, 1);
        Mail::assertSent(NewLeadMail::class, function (NewLeadMail $m) use ($owner, $editor) {
            return $m->hasTo($owner->email) && ! $m->hasTo($editor->email)
                && $m->hasReplyTo('ann@visitor.io')
                && ! str_contains($m->envelope()->subject, "\n"); // header injection is impossible
        });
    }

    public function test_the_alert_falls_back_to_the_business_email_when_there_is_no_owner(): void
    {
        Mail::fake();
        $c = $this->client('acme', ['email' => 'shop@acme.test']);
        $form = $this->add($c, 'contact_form', 'Form');

        $this->post("/acme/submit/{$form->id}", ['name' => 'Ann', 'phone' => '1'])->assertRedirect();
        Mail::assertSent(NewLeadMail::class, fn ($m) => $m->hasTo('shop@acme.test'));
    }

    public function test_no_email_for_spam_or_invalid_submissions(): void
    {
        Mail::fake();
        $c = $this->client();
        $this->member($c);
        $form = $this->add($c, 'contact_form', 'Form');

        $this->post("/acme/submit/{$form->id}", ['name' => 'Bot', 'email' => 'b@x.io', 'website' => 'http://spam']);
        $this->post("/acme/submit/{$form->id}", ['name' => 'NoContact']);
        Mail::assertNothingSent();
    }

    public function test_a_failing_mail_server_never_loses_the_lead_or_breaks_the_page(): void
    {
        $c = $this->client();
        $this->member($c);
        $form = $this->add($c, 'contact_form', 'Form');
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP down'));

        $this->post("/acme/submit/{$form->id}", ['name' => 'Ann', 'phone' => '1'])->assertRedirect();
        $this->assertSame(1, Submission::count()); // stored before the mail attempt
    }

    // ------------------------------------------------------------ uploaded images are cleaned up

    private function sectionWithImages(Client $c, array $paths): Section
    {
        foreach ($paths as $p) {
            Storage::disk('uploads')->put($p, 'img');
        }

        return $this->add($c, 'gallery', 'Gallery', [
            'images' => array_map(fn ($p) => ['image' => $p, 'caption' => 'x'], $paths),
        ]);
    }

    public function test_deleting_a_section_deletes_its_images_and_nothing_else(): void
    {
        Storage::fake('uploads');
        $c = $this->client();
        $other = $this->sectionWithImages($c, ["clients/{$c->id}/keep.png"]);
        $s = $this->sectionWithImages($c, ["clients/{$c->id}/a.png", "clients/{$c->id}/b.png"]);

        $this->actingAs($this->member($c))->delete("/manage/acme/sections/{$s->id}")->assertRedirect();

        Storage::disk('uploads')->assertMissing(["clients/{$c->id}/a.png", "clients/{$c->id}/b.png"]);
        Storage::disk('uploads')->assertExists("clients/{$c->id}/keep.png"); // other section untouched
        $this->assertNotNull(Section::find($other->id));
    }

    public function test_replacing_or_removing_an_image_deletes_the_old_file_and_keeps_unchanged_ones(): void
    {
        Storage::fake('uploads');
        $c = $this->client();
        $s = $this->sectionWithImages($c, ["clients/{$c->id}/one.png", "clients/{$c->id}/two.png"]);
        Storage::disk('uploads')->put("clients/{$c->id}/three.png", 'img');

        // edit: keep row one, replace row two with three
        $content = $s->content;
        $content['images'][1]['image'] = "clients/{$c->id}/three.png";
        $s->update(['content' => $content]);
        Storage::disk('uploads')->assertExists(["clients/{$c->id}/one.png", "clients/{$c->id}/three.png"]);
        Storage::disk('uploads')->assertMissing("clients/{$c->id}/two.png");

        // editing something else leaves every image alone
        $content = $s->fresh()->content;
        $content['title'] = ['en' => 'New title'];
        $s->update(['content' => $content]);
        Storage::disk('uploads')->assertExists(["clients/{$c->id}/one.png", "clients/{$c->id}/three.png"]);

        // removing a whole row deletes its file
        $content = $s->fresh()->content;
        array_shift($content['images']);
        $s->update(['content' => $content]);
        Storage::disk('uploads')->assertMissing("clients/{$c->id}/one.png");
        Storage::disk('uploads')->assertExists("clients/{$c->id}/three.png");
    }

    public function test_image_cleanup_can_only_ever_touch_the_sections_own_client_folder(): void
    {
        Storage::fake('uploads');
        $a = $this->client('alpha');
        $b = $this->client('beta');
        Storage::disk('uploads')->put("clients/{$b->id}/betas.png", 'img');
        Storage::disk('uploads')->put('secret.txt', 'x');

        // a tampered row pointing at another client's file / a path outside clients/
        $s = $this->add($a, 'gallery', 'G');
        $s->update(['content' => ['images' => [
            ['image' => "clients/{$b->id}/betas.png"], ['image' => 'secret.txt'], ['image' => "clients/{$a->id}/../secret.txt"],
        ]]]);
        $s->delete();

        Storage::disk('uploads')->assertExists(["clients/{$b->id}/betas.png", 'secret.txt']);
    }

    public function test_adding_a_translation_keeps_existing_images(): void
    {
        Storage::fake('uploads');
        $c = $this->client('acme', ['default_locale' => 'de', 'locales' => ['de', 'en']]);
        $s = $this->sectionWithImages($c, ["clients/{$c->id}/pic.png"]);

        app(Builder::class)->translate($s, 'en', ['title' => 'Gallery', 'images' => [['caption' => 'A caption']]]);

        $fresh = $s->fresh();
        $this->assertSame("clients/{$c->id}/pic.png", $fresh->content['images'][0]['image']);
        $this->assertSame('A caption', $fresh->content['images'][0]['caption']['en']);
        Storage::disk('uploads')->assertExists("clients/{$c->id}/pic.png");
    }

    public function test_the_prune_command_also_removes_the_images_of_pruned_sections(): void
    {
        Storage::fake('uploads');
        $c = $this->client();
        $this->sectionWithImages($c, ["clients/{$c->id}/gone.png"]);

        $this->artisan('sections:prune gallery --force')->assertExitCode(0);
        Storage::disk('uploads')->assertMissing("clients/{$c->id}/gone.png");
    }

    public function test_a_type_can_be_removed_from_one_client_only(): void
    {
        $c1 = $this->client('one', ['features' => ['forms' => true, 'types' => ['hero', 'text']]]);
        $c2 = $this->client('two');
        foreach ([$c1, $c2] as $c) {
            $this->add($c, 'text', 'T', ['title' => 'Keeps']);
            $this->add($c, 'faq', 'F', ['title' => 'Faq heading']);
        }

        $this->get('/one')->assertSee('Keeps')->assertDontSee('Faq heading');
        $this->get('/two')->assertSee('Keeps')->assertSee('Faq heading');
    }

    // ------------------------------------------------------------ comparison table, team, hero slider

    public function test_comparison_table_section_renders_feature_rows_with_yes_no_marks(): void
    {
        $c = $this->client();
        $this->add($c, 'comparison_table', 'Why us', [
            'our_label' => 'Us', 'competitor_label' => 'Others',
            'rows' => [
                ['feature' => 'Licensed staff', 'ours' => true, 'competitor' => false],
                ['feature' => 'Public reviews', 'ours' => true, 'competitor' => true],
            ],
        ]);

        $this->get('/acme')->assertOk()
            ->assertSee('Licensed staff')
            ->assertSee('Public reviews')
            ->assertSee('Us')
            ->assertSee('Others');
    }

    public function test_team_section_renders_members_with_name_and_role(): void
    {
        $c = $this->client();
        $this->add($c, 'team', 'Our team', [
            'items' => [
                ['name' => 'Jane Doe', 'role' => 'Founder'],
                ['name' => 'John Roe', 'role' => 'Electrician'],
            ],
        ]);

        $this->get('/acme')->assertOk()->assertSee('Jane Doe')->assertSee('Founder')->assertSee('John Roe');
    }

    public function test_hero_with_two_or_more_images_renders_an_auto_rotating_slider(): void
    {
        $c = $this->client();
        $this->add($c, 'hero', 'Hero', [
            'headline' => 'Slider hero',
            'slides' => [['image' => "clients/{$c->id}/a.jpg"], ['image' => "clients/{$c->id}/b.jpg"]],
        ]);

        $html = $this->get('/acme')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'class="hero-slide"'));
        $this->assertStringContainsString('hero-slides', $html);
    }

    public function test_hero_with_a_single_or_no_slide_image_falls_back_to_a_plain_image(): void
    {
        $c = $this->client();
        $this->add($c, 'hero', 'One', ['headline' => 'One image', 'slides' => [['image' => "clients/{$c->id}/a.jpg"]]]);
        $html = $this->get('/acme')->assertOk()->getContent();
        $this->assertStringNotContainsString('hero-slides', $html);
        $this->assertStringContainsString("clients/{$c->id}/a.jpg", $html);
    }

    public function test_prune_command_removes_sections_of_unregistered_types(): void
    {
        $c = $this->client();
        $this->add($c, 'text', 'Keep');
        Section::create(['client_id' => $c->id, 'type' => 'ghost', 'name' => 'Ghost', 'content' => [], 'config' => []]);

        $this->artisan('sections:prune --unknown --force')->assertExitCode(0);
        $this->assertSame(['Keep'], Section::pluck('name')->all());
    }

    public function test_every_dashboard_screen_and_every_section_type_renders(): void
    {
        $c = $this->client('acme', ['locales' => ['en', 'de'], 'phone' => '+1 555 0100', 'email' => 'hi@acme.test']);
        $this->actingAs($this->superAdmin());

        $this->get('/manage/acme/sections/create')->assertOk()->assertSee('Contact form');
        foreach (array_keys($c->allowedSectionTypes()) as $type) {
            $this->get("/manage/acme/sections/create?type=$type")->assertOk();
            $s = $this->add($c, $type, ucfirst($type));
            $this->get("/manage/acme/sections/{$s->id}/edit")->assertOk();
        }

        foreach (['/manage/acme/sections', '/manage/acme/profile', '/manage/acme/leads',
                     '/admin/clients', '/admin/clients/create', '/admin/clients/acme/edit', '/admin/admins', '/acme'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->assertCount(count($c->allowedSectionTypes()), $c->sections);
    }

    public function test_profile_saves_full_business_details_locales_theme_and_custom_fields(): void
    {
        $c = $this->client();
        $this->actingAs($this->member($c))->put('/manage/acme/profile', [
            'name' => 'Acme Bakery', 'legal_name' => 'Acme Bakery Ltd', 'business_type' => 'Bakery', 'tagline' => 'Fresh daily',
            'email' => 'hi@acme.test', 'phone' => '+44 20 0000', 'website' => 'https://acme.test',
            'address_line1' => '1 High St', 'postal_code' => 'N1', 'city' => 'London', 'country' => 'UK',
            'registration_no' => '12345', 'tax_id' => 'GB123', 'timezone' => 'Europe/London', 'currency' => 'gbp',
            'default_locale' => 'en', 'locales' => ['de', 'ar'], 'theme_brand' => '#15803d', 'theme_accent' => '#f59e0b', 'theme_font' => 'serif',
            'extra' => [['label' => 'Licence', 'value' => 'L-99'], ['label' => '', 'value' => '']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $c->refresh();
        $this->assertSame('Acme Bakery', $c->name);
        $this->assertSame('GBP', $c->currency);
        $this->assertSame(['en', 'de', 'ar'], $c->locales); // default language always first
        $this->assertSame(['brand' => '#15803d', 'accent' => '#f59e0b', 'font' => 'serif'], $c->theme);
        $this->assertSame([['label' => 'Licence', 'value' => 'L-99']], $c->profile_extra); // empty row dropped

        $this->actingAs($this->member($c))->put('/manage/acme/profile', [
            'name' => 'X', 'timezone' => 'Europe/London', 'default_locale' => 'en', 'theme_brand' => 'red; background:url(x)', 'theme_accent' => '#000000', 'theme_font' => 'sans',
        ])->assertSessionHasErrors('theme_brand'); // CSS injection through the color field is refused
    }

    public function test_browsing_public_pages_does_not_use_up_login_attempts(): void
    {
        $c = $this->client();
        $owner = $this->member($c);
        $this->add($c, 'text', 'About');

        for ($i = 0; $i < 30; $i++) {
            $this->get('/acme')->assertOk();
        }
        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])->assertRedirect('/home');
    }

    public function test_repeated_bad_logins_are_rate_limited(): void
    {
        $owner = $this->member($this->client());
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => $owner->email, 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])->assertStatus(429);
    }

    // ------------------------------------------------------------ platform marketing site & design v2

    public function test_the_root_shows_the_home_client_and_its_slug_redirects_to_root(): void
    {
        $home = $this->client('erp', ['default_locale' => 'en']);
        $this->add($home, 'hero', 'Hero', ['headline' => 'Marketing headline']);

        $this->get('/')->assertOk()->assertSee('Marketing headline')
            ->assertSee(route('login'), false)          // header has a "Log in" button
            ->assertDontSee('Made with');               // no "powered by" on the platform's own site

        $this->get('/erp')->assertStatus(301)->assertRedirect(url('/'));
        $this->get('/erp?lang=en')->assertStatus(301)->assertRedirect(url('/') . '?lang=en');
        $this->assertSame(url('/'), $home->publicUrl());
    }

    public function test_the_root_header_shows_the_dashboard_link_to_a_signed_in_user_despite_the_page_cache(): void
    {
        $home = $this->client('erp', ['default_locale' => 'en', 'locales' => ['en', 'ar']]);
        $s = $this->add($home, 'text', 'About', ['title' => 'Marketing headline', 'body' => 'Body']);
        app(Builder::class)->translate($s, 'ar', ['title' => 'عنوان تسويقي', 'body' => 'نص']); // Arabic is complete, so it is served
        $user = User::factory()->create(['name' => 'Sara Nabil']);

        // A guest renders (and caches) the page first; the signed-in user must still get their own header.
        $this->get('/')->assertOk()->assertSee('>Log in<', false)
            ->assertHeader('Cache-Control', 'no-cache, private');

        $this->actingAs($user)->get('/?lang=ar')->assertOk()
            ->assertSee(route('home'), false)->assertSee('لوحة التحكم')->assertSee('Sara')
            ->assertDontSee('تسجيل الدخول');

        auth()->logout();
        $this->get('/')->assertOk()->assertSee('>Log in<', false)->assertDontSee('Sara');

        // Client sites stay session-free and publicly cacheable.
        $this->add($this->client('acme'), 'hero', 'Hero');
        $this->get('/acme')->assertOk()->assertCookieMissing(config('session.cookie'))
            ->assertHeader('Cache-Control', 'max-age=60, public');
    }

    public function test_the_platform_site_is_pinned_on_top_of_the_clients_list(): void
    {
        $home = $this->client('erp');       // created first, so "newest first" alone would put it last
        $this->client('alpha');
        $this->client('beta');

        $this->actingAs($this->superAdmin())->get('/admin/clients')->assertInertia(fn ($p) => $p
            ->where('clients.data.0.id', $home->id)->where('clients.data.0.is_home', true)
            ->where('clients.data.1.slug', 'beta')->where('clients.data.2.slug', 'alpha'));
    }

    public function test_the_root_goes_to_login_when_there_is_no_home_client(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->client('erp', ['status' => 'draft']); // a hidden home client behaves the same
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_client_sites_have_a_phone_button_and_a_powered_by_link_and_no_login_button(): void
    {
        $c = $this->client('acme', ['phone' => '+49 221 123456']);
        $this->add($c, 'text', 'About', ['title' => 'Hi']);

        $this->get('/acme')->assertOk()
            ->assertSee('tel:+49221123456', false)
            ->assertSee('Made with')->assertSee(url('/'), false)
            ->assertDontSee(route('login'), false);
        $this->assertSame(route('site.show', $c), $c->publicUrl());
    }

    public function test_arabic_pages_are_right_to_left_with_translated_page_labels(): void
    {
        $c = $this->client('ar-shop', ['default_locale' => 'ar', 'locales' => ['ar']]);
        $this->add($c, 'contact_form', 'Form', ['title' => 'تواصل']);

        $this->get('/ar-shop')->assertOk()
            ->assertSee('dir="rtl"', false)->assertSee('lang="ar"', false)
            ->assertSee('الاسم')->assertSee('صُنع بواسطة');
    }

    public function test_every_supported_language_has_all_page_labels(): void
    {
        foreach (array_keys(config('platform.languages')) as $lang) {
            foreach (array_keys(config('ui')) as $key) {
                $this->assertNotEmpty(config("ui.$key.$lang"), "config/ui.php is missing [$key] for [$lang]");
            }
        }
    }

    public function test_new_section_types_render_their_content_safely(): void
    {
        $c = $this->client();
        $b = app(Builder::class);
        $b->add($c, 'hero', 'Hero', [
            'eyebrow' => 'Small label', 'headline' => 'Big claim',
            'highlights' => [['text' => 'Point one'], ['text' => ''], ['text' => 'Point two']],
        ]);
        $b->add($c, 'stats', 'Numbers', ['items' => [['value' => '25+', 'label' => 'Years'], ['value' => '24/7', 'label' => 'Open']]]);
        $b->add($c, 'plans', 'Plans', ['items' => [
            ['name' => 'Basic', 'price' => '€9', 'period' => '/ month', 'features' => "Alpha\nBeta", 'button_label' => 'Pick', 'button_url' => '#c'],
            ['name' => 'Pro', 'price' => '€29', 'features' => "Gamma\n<b>Delta</b>\n\n", 'button_label' => 'Buy', 'button_url' => '#c', 'highlighted' => true],
        ]]);
        $b->add($c, 'list', 'Cards', ['items' => [['title' => 'Demo', 'link' => '/some-page', 'link_label' => 'Open it']]]);

        $html = $this->get('/acme')->assertOk()
            ->assertSee('Small label')->assertSee('Point one')->assertSee('Point two')
            ->assertSee('25+')->assertSee('24/7')
            ->assertSee('€29')->assertSee('Gamma')
            ->assertSee('href="/some-page"', false)->assertSee('Open it')
            ->assertSee('plan reveal hot', false)                       // only the highlighted plan
            ->assertSee('&lt;b&gt;Delta&lt;/b&gt;', false)->assertDontSee('<b>Delta</b>', false)
            ->getContent();

        $this->assertSame(2, substr_count($html, 'class="stat reveal"'));
        $this->assertSame(2, preg_match_all('~<li>Point (one|two)</li>~', $html));  // the blank highlight is skipped
        $this->assertStringNotContainsString('<li></li>', $html);                   // blank plan-feature lines too
        $this->assertSame(4, preg_match_all('~<li>(Alpha|Beta|Gamma|&lt;b&gt;Delta&lt;/b&gt;)</li>~', $html));
    }

    public function test_links_inside_list_rows_are_sanitised_like_every_other_url(): void
    {
        $c = $this->client();
        $this->actingAs($this->member($c))->post('/manage/acme/sections', [
            'type' => 'plans', 'name' => 'Plans',
            'content' => ['items' => [
                ['name' => ['en' => 'Evil'], 'button_label' => ['en' => 'Buy'], 'button_url' => 'javascript:alert(1)', 'highlighted' => '1'],
                ['name' => ['en' => 'Fine'], 'button_label' => ['en' => 'Buy'], 'button_url' => 'https://example.com/pay'],
            ]],
        ])->assertRedirect();

        $items = $c->sections()->first()->content['items'];
        $this->assertSame('', $items[0]['button_url']);
        $this->assertTrue($items[0]['highlighted']);
        $this->assertSame('https://example.com/pay', $items[1]['button_url']);
        $this->get('/acme')->assertDontSee('javascript:', false);
    }

    public function test_the_menu_uses_a_short_label_per_language_and_falls_back_to_the_heading(): void
    {
        $c = $this->client('multi', ['default_locale' => 'de', 'locales' => ['de', 'en']]);
        $b = app(Builder::class);

        $a = $b->add($c, 'text', 'A', ['title' => 'Sehr langer Titel für den Bereich', 'body' => 'Text'], ['show_in_nav' => true, 'nav_label' => 'Kurz'], 'a');
        $b->translate($a, 'en', ['title' => 'A very long title for the section', 'body' => 'Text'], ['nav_label' => 'Short']);
        $n = $b->add($c, 'text', 'N', ['title' => 'Ohne Kurzlabel', 'body' => 'Text'], ['show_in_nav' => true], 'n'); // no menu label -> heading
        $b->translate($n, 'en', ['title' => 'No short label', 'body' => 'Text']);

        $de = $this->get('/multi')->assertOk();
        $de->assertSee('href="#a">Kurz</a>', false)->assertSee('href="#n">Ohne Kurzlabel</a>', false);

        $this->get('/multi?lang=en')->assertSee('href="#a">Short</a>', false)
            ->assertSee('href="#n">No short label</a>', false); // no menu label: the heading, in the visitor's language
    }

    // ------------------------------------------------------------ the Vue dashboards (Inertia)

    public function test_the_admin_dashboard_summarises_the_platform(): void
    {
        $a = $this->client('alpha');
        $b = $this->client('beta', ['status' => 'draft']);
        Submission::create(['client_id' => $a->id, 'payload' => ['name' => 'Ann', 'phone' => '1']]);
        Submission::create(['client_id' => $b->id, 'payload' => ['name' => 'Bob', 'phone' => '2'], 'read_at' => now()]);

        $this->actingAs($this->superAdmin())->get('/admin')->assertOk()->assertInertia(fn ($p) => $p
            ->component('Admin/Dashboard')
            ->where('kpis.clients', 2)->where('kpis.active', 1)->where('kpis.inactive', 1)
            ->where('kpis.leads', 2)->where('kpis.unread', 1)->where('kpis.leads30', 2)
            ->has('series', 30)                       // a gap-free 30-day chart
            ->has('recentLeads', 2)
            // what needs someone: beta is hidden, and neither has an owner or contact details
            ->where('attention', fn ($a) => collect($a)->contains(fn ($r) => $r['slug'] === 'beta' && $r['reason'] === 'draft')
                && collect($a)->where('reason', 'no_owner')->count() === 2));
    }

    public function test_the_overview_flags_leads_left_unanswered_for_days_first(): void
    {
        $a = $this->client('alpha', ['phone' => '1']);
        $this->member($a);
        Submission::create(['client_id' => $a->id, 'payload' => ['name' => 'New', 'phone' => '1']]); // fresh: not flagged
        foreach ([1, 2] as $i) {
            $old = Submission::create(['client_id' => $a->id, 'payload' => ['name' => "Old $i", 'phone' => '1']]);
            $old->forceFill(['created_at' => now()->subDays(3)])->save();
        }

        $this->actingAs($this->superAdmin())->get('/admin')->assertInertia(fn ($p) => $p
            ->where('attention', [['slug' => 'alpha', 'name' => 'Alpha', 'reason' => 'waiting', 'count' => 2]]));
    }

    public function test_a_clients_dashboard_only_counts_its_own_leads_and_has_a_setup_checklist(): void
    {
        $a = $this->client('alpha', ['phone' => '1', 'email' => 'a@x.io']);
        $b = $this->client('beta');
        $this->add($a, 'contact_form', 'Form');
        Submission::create(['client_id' => $a->id, 'payload' => ['name' => 'Mine', 'phone' => '1']]);
        Submission::create(['client_id' => $b->id, 'payload' => ['name' => 'Theirs', 'phone' => '2']]);

        $this->actingAs($this->member($a))->get('/manage/alpha')->assertOk()->assertInertia(fn ($p) => $p
            ->component('Manage/Dashboard')
            ->where('kpis.leads', 1)->where('kpis.unread', 1)->where('kpis.sections', 1)
            ->has('recentLeads', 1)->where('recentLeads.0.name', 'Mine')
            ->where('checklist.0.done', true)         // phone + email are filled in
            ->where('checklist.1.done', false)        // no logo yet
            ->where('checklist.5.done', true));       // a contact form is live
    }

    public function test_shared_props_give_the_layout_the_user_the_client_and_a_sidebar_menu(): void
    {
        $c = $this->client();
        $owner = $this->member($c);
        Submission::create(['client_id' => $c->id, 'payload' => ['name' => 'X', 'phone' => '1']]);

        $this->actingAs($owner)->get('/manage/acme/sections')->assertInertia(fn ($p) => $p
            ->where('auth.user.email', $owner->email)->where('auth.user.is_super_admin', false)
            ->where('client.slug', 'acme')->where('client.url', route('site.show', $c))
            ->where('menu', fn ($m) => collect($m)->pluck('label')->all() === ['Overview', 'Leads', 'Sections', 'Business profile', 'Team'])
            ->where('menu', fn ($m) => collect($m)->firstWhere('route', 'manage.leads.index')['badge'] === 1)
            ->where('menu', fn ($m) => collect($m)->firstWhere('route', 'manage.leads.index')['icon'] === 'inbox'));
    }

    public function test_the_admin_edit_page_does_not_masquerade_as_a_managed_client(): void
    {
        $this->client('alpha');

        // the page's own data is "target"; the shared "client" prop (the client being MANAGED) stays empty here
        $this->actingAs($this->superAdmin())->get('/admin/clients/alpha/edit')->assertInertia(fn ($p) => $p
            ->component('Admin/Clients/Edit')->where('target.slug', 'alpha')->where('client', null)->where('menu', []));
    }

    public function test_the_section_editor_receives_the_schema_as_ordered_lists(): void
    {
        $c = $this->client();
        $s = $this->add($c, 'reviews', 'Reviews', ['title' => 'Hi', 'items' => [['author' => 'A', 'rating' => '4', 'text' => 'Nice']]]);

        $this->actingAs($this->member($c))->get("/manage/acme/sections/{$s->id}/edit")->assertInertia(fn ($p) => $p
            ->component('Manage/Sections/Form')->where('mode', 'edit')->where('section.type', 'reviews')
            ->where('fields.0.name', 'title')->where('fields.0.translatable', true)
            ->where('fields.1.type', 'list')
            // star options stay in the order 5,4,3,2,1 (a JS object would have scrambled numeric keys)
            ->where('fields.1.fields', fn ($f) => collect($f)->firstWhere('name', 'rating')['options'][0]['value'] === '5')
            ->where('content.items.0.author', 'A')->where('config.background', 'light'));
    }

    public function test_sections_can_be_reordered_by_drag_and_drop(): void
    {
        $c = $this->client();
        $one = $this->add($c, 'text', 'One', ['title' => 'AAA']);
        $two = $this->add($c, 'text', 'Two', ['title' => 'BBB']);
        $three = $this->add($c, 'text', 'Three', ['title' => 'CCC']);
        $owner = $this->member($c);
        $this->get('/acme')->assertSeeInOrder(['AAA', 'BBB', 'CCC']);

        $this->actingAs($owner)->post('/manage/acme/sections/reorder', ['ids' => [$three->id, $one->id, $two->id]])->assertRedirect();

        $this->assertSame(['Three', 'One', 'Two'], $c->sections()->pluck('name')->all());
        $this->get('/acme')->assertSeeInOrder(['CCC', 'AAA', 'BBB']);   // cache was invalidated
    }

    public function test_reordering_rejects_ids_that_are_not_the_clients_own(): void
    {
        $a = $this->client('alpha');
        $b = $this->client('beta');
        $mine = $this->add($a, 'text', 'Mine');
        $other = $this->add($b, 'text', 'Other');
        $owner = $this->member($a);

        foreach ([[$mine->id, $other->id], [$other->id], [], [$mine->id, $mine->id]] as $ids) {
            $this->actingAs($owner)->post('/manage/alpha/sections/reorder', ['ids' => $ids])->assertStatus(422);
        }
        $this->assertSame(1, $mine->fresh()->position);
        $this->assertSame(1, $other->fresh()->position); // untouched
        $this->actingAs($owner)->post('/manage/beta/sections/reorder', ['ids' => [$other->id]])->assertForbidden();
    }

    public function test_the_home_client_cannot_be_renamed_or_deleted_so_the_root_never_goes_blank(): void
    {
        $admin = $this->superAdmin();
        $this->client(config('platform.home_client'));
        $home = config('platform.home_client');

        $this->actingAs($admin)->put("/admin/clients/$home", ['name' => 'X', 'slug' => 'renamed', 'status' => 'active'])
            ->assertSessionHasErrors('slug');
        $this->actingAs($admin)->delete("/admin/clients/$home")->assertSessionHasErrors('client');

        $this->assertTrue(Client::where('slug', $home)->exists());
        $this->actingAs($admin)->put("/admin/clients/$home", ['name' => 'Renamed', 'slug' => $home, 'status' => 'active'])
            ->assertSessionHasNoErrors();
    }

    public function test_reordering_rejects_a_duplicated_id_even_when_the_count_matches(): void
    {
        $a = $this->client('alpha');
        $one = $this->add($a, 'text', 'One');
        $two = $this->add($a, 'text', 'Two');
        $owner = $this->member($a);

        $this->actingAs($owner)->post('/manage/alpha/sections/reorder', ['ids' => [$one->id, $one->id]])->assertStatus(422);
        $this->assertSame([1, 2], [$one->fresh()->position, $two->fresh()->position]);
    }

    public function test_the_leads_page_search_and_filter_run_on_the_server(): void
    {
        $c = $this->client();
        Submission::create(['client_id' => $c->id, 'payload' => ['name' => 'Anna', 'message' => 'wallbox please'], 'read_at' => now()]);
        Submission::create(['client_id' => $c->id, 'payload' => ['name' => 'Ben', 'message' => 'sockets']]);
        $owner = $this->member($c);

        $this->actingAs($owner)->get('/manage/acme/leads')->assertInertia(fn ($p) => $p->has('leads.data', 2)->where('unread', 1)->where('total', 2));
        $this->get('/manage/acme/leads?filter=unread')->assertInertia(fn ($p) => $p->has('leads.data', 1)->where('leads.data.0.name', 'Ben'));
        $this->get('/manage/acme/leads?q=wallbox')->assertInertia(fn ($p) => $p->has('leads.data', 1)->where('leads.data.0.name', 'Anna'));
        $this->get('/manage/acme/leads?q=100%25')->assertInertia(fn ($p) => $p->has('leads.data', 0)); // "%" is literal, not a wildcard
    }

    // ------------------------------------------------------------ dashboard locale (English/Arabic)

    public function test_dashboard_defaults_to_english_and_the_switcher_persists_arabic_in_session(): void
    {
        $owner = $this->member($this->client());

        $this->actingAs($owner)->get('/manage/acme')->assertInertia(fn ($p) => $p->where('locale', 'en')->where('direction', 'ltr'));

        $this->actingAs($owner)->post('/dashboard-locale/ar')->assertRedirect();
        $this->assertSame('ar', session('dashboard_locale'));

        $this->actingAs($owner)->get('/manage/acme')->assertInertia(fn ($p) => $p->where('locale', 'ar')->where('direction', 'rtl'));

        // an unknown code is ignored rather than corrupting the session
        $this->post('/dashboard-locale/fr');
        $this->assertSame('ar', session('dashboard_locale'));
    }

    public function test_the_dashboard_locale_does_not_need_login_and_does_not_affect_the_public_site(): void
    {
        $c = $this->client('acme', ['default_locale' => 'de']);
        $this->add($c, 'text', 'About', ['title' => 'Hallo']);

        $this->post('/dashboard-locale/ar')->assertRedirect();
        $this->assertSame('ar', session('dashboard_locale'));

        // the client's own site language is unrelated to the dashboard's language
        $this->get('/acme')->assertSee('Hallo')->assertSee('dir="ltr"', false);
    }

    public function test_arabic_dashboard_pages_render_right_to_left_and_in_arabic(): void
    {
        $c = $this->client();
        $owner = $this->member($c);
        $this->post('/dashboard-locale/ar');

        $this->actingAs($owner)->get('/manage/acme')
            ->assertOk()->assertSee('dir="rtl"', false)->assertSee('lang="ar"', false);
    }

    public function test_validation_errors_are_arabic_when_the_dashboard_locale_is_arabic(): void
    {
        $owner = $this->member($this->client());
        $this->post('/dashboard-locale/ar');

        // missing "name" on the profile form
        $this->actingAs($owner)->put('/manage/acme/profile', ['default_locale' => 'en', 'timezone' => 'UTC', 'theme_brand' => '#000000', 'theme_accent' => '#f59e0b', 'theme_font' => 'sans'])
            ->assertSessionHasErrors('name');
        $this->assertStringContainsString('مطلوب', session('errors')->get('name')[0]);

        $this->post('/dashboard-locale/en');
        $this->actingAs($owner)->put('/manage/acme/profile', ['default_locale' => 'en', 'timezone' => 'UTC', 'theme_brand' => '#000000', 'theme_accent' => '#f59e0b', 'theme_font' => 'sans'])
            ->assertSessionHasErrors('name');
        $this->assertStringContainsString('required', session('errors')->get('name')[0]);
    }

    public function test_flash_status_messages_are_translated_to_arabic(): void
    {
        $c = $this->client();
        $owner = $this->member($c);
        $this->post('/dashboard-locale/ar');

        $this->actingAs($owner)->put('/manage/acme/profile', [
            'name' => 'Acme', 'default_locale' => 'en', 'timezone' => 'UTC', 'theme_brand' => '#000000', 'theme_accent' => '#f59e0b', 'theme_font' => 'sans',
        ])->assertSessionHas('status', 'تم حفظ الملف التعريفي.');
    }

    // ------------------------------------------------------------ public-site themes (designs)

    // ------------------------------------------------------------ design preview (manage/{client}/preview/{design})
    // NOT the public /{slug} route: it deliberately has no session (see bootstrap/app.php), so it has no
    // way to know who's allowed to preview what — this lives under the authenticated manage/ prefix instead.

    public function test_custom_domains_no_longer_exist(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('clients', 'custom_domain'));
    }

    public function test_login_redirects_each_role_to_its_home(): void
    {
        $c = $this->client();
        $owner = $this->member($c);
        $admin = $this->superAdmin();

        $this->post('/login', ['email' => $owner->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($owner);
        $this->get('/home')->assertRedirect('/manage/acme');

        $this->actingAs($admin)->get('/home')->assertRedirect('/admin');
    }

    // ------------------------------------------------------------ per-editor permissions

}
