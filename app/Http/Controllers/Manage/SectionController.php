<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Section;
use App\Sections\Builder;
use App\Sections\Registry;
use App\Sections\Schema;
use App\Sections\SectionType;
use App\Sections\Translations;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionController extends Controller
{
    public function index(Client $client)
    {
        $allowed = $client->allowedSectionTypes();
        $all = app(Registry::class)->all();

        // section id => the languages it still lacks text in (only live sections are counted)
        $missing = [];
        foreach (Translations::status($client) as $locale => $s) {
            foreach ($s['missing'] as $m) {
                $missing[$m['id']][] = $locale;
            }
        }

        return Inertia::render('Manage/Sections/Index', [
            'hiddenLanguages' => collect(Translations::status($client))->reject(fn ($s) => $s['complete'])->keys()->all(),
            'sections' => $client->sections()->get()->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'anchor' => $s->anchor,
                'type' => $s->type,
                'type_label' => ($all[$s->type] ?? null)?->label() ?? $s->type,
                'available' => isset($allowed[$s->type]),
                'is_enabled' => $s->is_enabled,
                'in_nav' => (bool) ($s->config['show_in_nav'] ?? false),
                'missing_languages' => $missing[$s->id] ?? [],
                'updated' => $s->updated_at?->diffForHumans(),
            ])->all(),
        ]);
    }

    public function create(Request $request, Client $client)
    {
        $types = $client->allowedSectionTypes();
        $key = $request->query('type');

        if (! $key) {
            return Inertia::render('Manage/Sections/Pick', [
                'types' => collect($types)->map(fn ($t) => ['key' => $t->key(), 'label' => $t->label(), 'description' => $t->description()])->values()->all(),
            ]);
        }

        $type = $types[$key] ?? abort(404);
        $section = new Section(['type' => $key, 'name' => $type->label(), 'is_enabled' => true]);
        $locale = $client->default_locale;
        $content = Schema::localize($type->fields(), $type->defaultContent(), $locale);
        $config = Schema::localize($type->configFields(), [], $locale);

        return Inertia::render('Manage/Sections/Form', $this->formProps($client, $type, $section, $content, $config));
    }

    public function store(Request $request, Client $client, Builder $builder)
    {
        $type = $client->allowedSectionTypes()[$request->input('type')] ?? abort(404);

        // If anything fails after a file was uploaded, the upload is removed again.
        return Schema::withUploadCleanup(function () use ($request, $client, $builder, $type) {
            [$name, $anchor, $content, $config] = $this->validated($request, $client, $type);

            $client->sections()->create([
                'type' => $type->key(),
                'name' => $name,
                'anchor' => $builder->uniqueAnchor($client, $anchor ?: $name),
                'position' => ((int) $client->sections()->max('position')) + 1,
                'is_enabled' => true,
                'content' => $content,
                'config' => $config,
            ]);

            return redirect()->route('manage.sections.index', $client)->with('status', __('Section added.'));
        });
    }

    public function edit(Client $client, int $section)
    {
        $section = $client->sections()->findOrFail($section);
        $type = $client->allowedSectionTypes()[$section->type] ?? abort(404, 'This section type is not available for this client.');

        return Inertia::render('Manage/Sections/Form', $this->formProps($client, $type, $section, $section->content ?? [], $section->config ?? []));
    }

    public function update(Request $request, Client $client, Builder $builder, int $section)
    {
        $section = $client->sections()->findOrFail($section);
        $type = $client->allowedSectionTypes()[$section->type] ?? abort(404);

        return Schema::withUploadCleanup(function () use ($request, $client, $builder, $type, $section) {
            [$name, $anchor, $content, $config] = $this->validated($request, $client, $type);

            $section->update([
                'name' => $name,
                'anchor' => $builder->uniqueAnchor($client, $anchor ?: $name, $section->id),
                'content' => $content,
                'config' => $config,
            ]);

            return redirect()->route('manage.sections.index', $client)->with('status', __('Section saved.'));
        });
    }

    public function destroy(Request $request, Client $client, int $section)
    {
        $client->sections()->findOrFail($section)->delete();

        return back()->with('status', __('Section deleted.'));
    }

    public function toggle(Request $request, Client $client, int $section)
    {
        $section = $client->sections()->findOrFail($section);
        $section->update(['is_enabled' => ! $section->is_enabled]);

        return back();
    }

    public function move(Request $request, Client $client, int $section, string $direction)
    {
        $ids = $client->sections()->pluck('id')->all();
        $i = array_search($section, $ids);
        abort_if($i === false, 404);

        $j = $direction === 'up' ? $i - 1 : $i + 1;
        if (isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            $this->savePositions($client, $ids);
        }

        return back();
    }

    /** Drag & drop: the dashboard sends the complete new order as a list of section ids. */
    public function reorder(Request $request, Client $client)
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        $mine = array_map('intval', $client->sections()->pluck('id')->all());
        // same set of ids, each exactly once: [1, 1, 2] for sections 1, 2, 3 would otherwise leave 3 at its old position
        abort_unless(count($ids) === count($mine) && count(array_unique($ids)) === count($ids) && ! array_diff($ids, $mine), 422, 'The list of sections does not match.');

        $this->savePositions($client, $ids);

        return back();
    }

    private function savePositions(Client $client, array $ids): void
    {
        foreach (array_values($ids) as $pos => $id) {
            Section::where('client_id', $client->id)->whereKey($id)->update(['position' => $pos + 1]);
        }
        $client->bumpVersion(); // bulk update fires no model events
    }

    private function validated(Request $request, Client $client, SectionType $type): array
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'anchor' => ['nullable', 'string', 'max:80'],
        ]);

        // Existing images travel as paths inside "content"; newly chosen files travel separately, under "uploads",
        // in the same shape. Keeping them apart means an image field can carry both without a name clash.
        $files = $request->file('uploads');

        return [
            $request->input('name'),
            $request->input('anchor'),
            Schema::sanitize($type->fields(), (array) $request->input('content', []), is_array($files) ? $files : [], $client),
            Schema::sanitize($type->configFields(), (array) $request->input('config', []), [], $client),
        ];
    }

    private function formProps(Client $client, SectionType $type, Section $section, array $content, array $config): array
    {
        return [
            'mode' => $section->exists ? 'edit' : 'create',
            'section' => [
                'id' => $section->id, 'type' => $type->key(), 'type_label' => $type->label(),
                'description' => $type->description(), 'name' => $section->name, 'anchor' => $section->anchor,
            ],
            'fields' => Schema::forClient($type->fields()),
            'configFields' => Schema::forClient($type->configFields()),
            // empty PHP arrays would become JSON lists; the editor needs objects
            'content' => $content ?: (object) [],
            'config' => $config ?: (object) [],
            'locales' => $client->siteLocales(),
            'defaultLocale' => $client->default_locale,
            'languages' => config('platform.languages'),
            'uploadsUrl' => asset('uploads'),
        ];
    }
}
