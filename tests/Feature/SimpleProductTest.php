<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Sections\Builder;
use App\Sections\Registry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** After the simplification: one design, classic sections, plain owner/editor roles. */
class SimpleProductTest extends TestCase
{
    use RefreshDatabase;

    private function client(): Client
    {
        return Client::create(['slug' => 'acme', 'name' => 'Acme', 'default_locale' => 'en', 'locales' => ['en'], 'status' => 'active']);
    }

    public function test_every_section_type_renders_on_the_public_page(): void
    {
        $c = $this->client();
        $builder = app(Builder::class);

        foreach (array_keys(app(Registry::class)->all()) as $key) {
            $builder->add($c, $key, "Section {$key}", [], [], "sec-{$key}");
        }

        $html = $this->get('/acme')->assertOk()->getContent();
        foreach (array_keys(app(Registry::class)->all()) as $key) {
            $this->assertStringContainsString('id="sec-' . str_replace('_', '-', $key) . '"', $html, "{$key} must render");
        }
        $this->assertStringContainsString('data-theme="nova"', $html);
    }

    public function test_an_editor_can_edit_content_and_sections_like_an_owner(): void
    {
        $c = $this->client();
        $editor = User::factory()->create();
        $c->users()->attach($editor->id, ['role' => 'editor']);
        $s = app(Builder::class)->add($c, 'text', 'About', ['title' => 'Before']);

        $this->actingAs($editor)->get('/manage/acme/sections')->assertOk();
        $this->actingAs($editor)->put("/manage/acme/sections/{$s->id}", [
            'name' => 'About', 'content' => ['title' => ['en' => 'After']], 'config' => ['variant' => 'left', 'background' => 'light'],
        ])->assertRedirect();

        $this->assertStringContainsString('After', json_encode($s->fresh()->content));
    }

    public function test_the_removed_screens_are_gone(): void
    {
        $c = $this->client();
        $u = User::factory()->create();
        $c->users()->attach($u->id, ['role' => 'owner']);

        $this->actingAs($u)->get('/manage/acme/preview/nova')->assertNotFound();
        $this->get('/guide')->assertNotFound();
    }
}
