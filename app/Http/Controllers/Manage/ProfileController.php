<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Sections\Renderer;
use App\Sections\Schema;
use App\Sections\Translations;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /** Repeater for free-form profile fields (uses the same generic field UI as sections). */
    public static function extraField(): array
    {
        return ['type' => 'list', 'label' => __('Custom profile fields'), 'max' => 30, 'fields' => [
            'label' => ['type' => 'text', 'label' => __('Label (e.g. Licence no.)'), 'max' => 80],
            'value' => ['type' => 'text', 'label' => __('Value'), 'max' => 255],
        ]];
    }

    public function edit(Client $client)
    {
        return Inertia::render('Manage/Profile', [
            'profile' => [
                ...$client->only([
                    'name', 'legal_name', 'business_type', 'tagline', 'email', 'phone', 'website',
                    'address_line1', 'address_line2', 'postal_code', 'city', 'region', 'country',
                    'registration_no', 'tax_id', 'timezone', 'currency', 'default_locale',
                ]),
                'locales' => $client->siteLocales(),
                'theme_brand' => $client->theme['brand'] ?? '#2563eb',
                'theme_accent' => $client->theme['accent'] ?? '#f59e0b',
                'theme_font' => $client->theme['font'] ?? 'sans',
                'logo' => $client->logo_path ? asset('uploads/' . $client->logo_path) : null,
                'extra' => $client->profile_extra ?? [],
            ],
            // how far each extra language is: visitors only get it once it's complete (Translations::publicLocales)
            'translation' => Translations::status($client),
            'extraFields' => Schema::forClient(self::extraField()['fields']),
            'languages' => collect(config('platform.languages'))->map(fn ($n, $c) => ['value' => $c, 'label' => $n])->values()->all(),
            'timezones' => DateTimeZone::listIdentifiers(),
            'fonts' => array_keys(Renderer::FONTS),
            'suggestions' => collect(config('platform.business_kinds'))->except('other')->map(fn ($k) => __($k['label']))->values()->all(),
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:190'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:190'],
            'address_line2' => ['nullable', 'string', 'max:190'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:100'],
            'registration_no' => ['nullable', 'string', 'max:120'],
            'tax_id' => ['nullable', 'string', 'max:120'],
            'timezone' => ['required', Rule::in(DateTimeZone::listIdentifiers())],
            'currency' => ['nullable', 'alpha', 'size:3'],
            'default_locale' => ['required', Rule::in(array_keys(config('platform.languages')))],
            'locales' => ['nullable', 'array'],
            'locales.*' => [Rule::in(array_keys(config('platform.languages')))],
            'theme_brand' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_font' => ['required', Rule::in(array_keys(Renderer::FONTS))],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ]);

        $update = collect($data)->except(['locales', 'theme_brand', 'theme_accent', 'theme_font', 'logo'])->all();
        $update['currency'] = isset($data['currency']) ? strtoupper($data['currency']) : null;
        $update['locales'] = array_values(array_unique([$data['default_locale'], ...($data['locales'] ?? [])]));
        $update['profile_extra'] = Schema::sanitize(['extra' => self::extraField()], (array) $request->input(), [], $client)['extra'];

        $update['theme'] = ['brand' => $data['theme_brand'], 'accent' => $data['theme_accent'], 'font' => $data['theme_font']];

        $ownFolder = "clients/{$client->id}/";
        if ($request->hasFile('logo')) {
            $this->deleteOwn($client->logo_path, $ownFolder);
            $update['logo_path'] = $request->file('logo')->store("clients/{$client->id}", 'uploads');
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteOwn($client->logo_path, $ownFolder);
            $update['logo_path'] = null;
        }

        $client->update($update);

        return back()->with('status', __('Profile saved.'));
    }

    private function deleteOwn(?string $path, string $folder): void
    {
        if ($path && str_starts_with($path, $folder) && ! str_contains($path, '..')) {
            Storage::disk('uploads')->delete($path);
        }
    }
}
