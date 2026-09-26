<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Sections\Builder;
use App\Sections\Translations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A language is offered to visitors only once the whole page exists in it; the dashboard shows what's missing. */
class TranslationsTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = Client::create(['slug' => 'noor', 'name' => 'Noor', 'default_locale' => 'ar', 'locales' => ['ar', 'en', 'it'], 'status' => 'active']);
    }

    private function text(string $name, array $ar, array $en = []): \App\Models\Section
    {
        $s = app(Builder::class)->add($this->client, 'text', $name, $ar);
        if ($en) {
            app(Builder::class)->translate($s, 'en', $en);
        }

        return $s->fresh();
    }

    public function test_an_added_language_without_any_text_is_not_offered_and_never_mixes_into_the_page(): void
    {
        $this->text('About', ['title' => 'من نحن', 'body' => 'قصتنا'], ['title' => 'About us', 'body' => 'Our story']);

        $this->assertSame(['ar', 'en'], Translations::publicLocales($this->client));

        $page = $this->get('/noor')->assertOk();
        $page->assertSee('?lang=en', false)->assertDontSee('?lang=it', false); // no Italian in the language menu
        $this->get('/noor?lang=it')->assertSee('من نحن')->assertSee('lang="ar"', false); // an old link: all Arabic, not a mix
        $this->get('/noor?lang=en')->assertSee('About us')->assertSee('lang="en"', false);
    }

    public function test_only_live_sections_count_and_a_list_row_missing_a_translation_counts(): void
    {
        $this->text('About', ['title' => 'من نحن', 'body' => 'قصتنا'], ['title' => 'About us', 'body' => 'Our story']);
        $hidden = $this->text('Old', ['title' => 'قديم', 'body' => 'قديم']);
        $hidden->update(['is_enabled' => false]); // switched off: doesn't block English

        $this->assertTrue(Translations::status($this->client)['en']['complete']);

        // an FAQ whose title is translated but one question isn't: English is blocked by that one row
        $faq = app(Builder::class)->add($this->client, 'faq', 'FAQ', ['title' => 'أسئلة', 'items' => [['question' => 'سؤال؟', 'answer' => 'جواب']]]);
        app(Builder::class)->translate($faq, 'en', ['title' => 'Questions']);
        $status = Translations::status($this->client)['en'];
        $this->assertFalse($status['complete']);
        $this->assertSame([$faq->id], array_column($status['missing'], 'id'));
        $this->assertSame(['ar'], Translations::publicLocales($this->client));
    }

    public function test_the_dashboard_shows_each_language_progress_and_which_sections_lack_it(): void
    {
        $owner = User::factory()->create();
        $this->client->users()->attach($owner->id, ['role' => 'owner']);
        $done = $this->text('About', ['title' => 'من نحن', 'body' => 'قصتنا'], ['title' => 'About us', 'body' => 'Our story']);
        $todo = $this->text('Menu', ['title' => 'المنيو', 'body' => 'كل الأصناف']);

        $this->actingAs($owner)->get('/manage/noor/profile')->assertInertia(fn ($p) => $p
            ->where('translation.en.complete', false)->where('translation.en.sections', 2)
            ->where('translation.en.missing', [['id' => $todo->id, 'name' => 'Menu', 'texts' => 2]])
            ->where('translation.it.missing', fn ($m) => count($m) === 2)
            ->missing('translation.ar')); // the main language is always complete

        $this->actingAs($owner)->get('/manage/noor/sections')->assertInertia(fn ($p) => $p
            ->where('hiddenLanguages', ['en', 'it'])
            ->where('sections', fn ($s) => collect($s)->firstWhere('id', $done->id)['missing_languages'] === ['it']
                && collect($s)->firstWhere('id', $todo->id)['missing_languages'] === ['en', 'it']));
    }
}
