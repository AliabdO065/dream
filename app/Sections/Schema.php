<?php

namespace App\Sections;

use App\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * One field schema drives everything for a section type: validation/sanitising,
 * default content, the admin form, and the shape of the stored JSON.
 *
 * Field types: text, textarea, url, select, checkbox, image, list (repeater of sub-fields).
 * text/textarea/url fields marked 'translatable' are stored as {locale: value}.
 */
class Schema
{
    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    public const IMAGE_MAX_BYTES = 4 * 1024 * 1024;

    /** Files stored by the request currently inside withUploadCleanup(). */
    private static array $uploaded = [];

    /**
     * Run a save operation; if it throws (a later image fails validation, the database write fails, …),
     * every file uploaded during it is deleted again, so a failed save never leaves files behind.
     */
    public static function withUploadCleanup(callable $save): mixed
    {
        $outer = self::$uploaded;
        self::$uploaded = [];

        try {
            return $save();
        } catch (\Throwable $e) {
            if (self::$uploaded) {
                Storage::disk('uploads')->delete(self::$uploaded);
            }
            throw $e;
        } finally {
            self::$uploaded = $outer; // never leaks into the next request on a long-lived worker
        }
    }

    // ---------------------------------------------------------------- input -> stored

    /** Build clean, schema-shaped data from raw request input. Unknown keys are dropped. */
    public static function sanitize(array $fields, array $input, array $files, Client $client): array
    {
        $out = [];
        foreach ($fields as $name => $f) {
            $out[$name] = self::sanitizeField($f, $input[$name] ?? null, $files[$name] ?? null, $client);
        }

        return $out;
    }

    private static function sanitizeField(array $f, mixed $value, mixed $file, Client $client): mixed
    {
        switch ($f['type']) {
            case 'text':
            case 'textarea':
            case 'url':
                if (! empty($f['translatable'])) {
                    $out = [];
                    foreach ($client->siteLocales() as $locale) {
                        $out[$locale] = self::clean($f['type'], is_array($value) ? ($value[$locale] ?? '') : '', $f);
                    }

                    return $out;
                }

                return self::clean($f['type'], $value, $f);

            case 'select':
                return is_string($value) && array_key_exists($value, $f['options']) ? $value : array_key_first($f['options']);

            case 'checkbox':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'image':
                return self::image($value, $file, $client);

            case 'list':
                $out = [];
                $rows = is_array($value) ? array_slice($value, 0, $f['max'] ?? 50, true) : [];
                foreach ($rows as $key => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $rowFiles = is_array($file) && is_array($file[$key] ?? null) ? $file[$key] : [];
                    $clean = self::sanitize($f['fields'], $row, $rowFiles, $client);
                    if (! self::rowIsEmpty($f['fields'], $clean)) {
                        $out[] = $clean;
                    }
                }

                return $out;
        }

        return null;
    }

    private static function clean(string $type, mixed $v, array $f): string
    {
        if (! is_scalar($v)) {
            return '';
        }
        $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $v) ?? '');
        $v = mb_substr($v, 0, $f['max'] ?? ($type === 'textarea' ? 5000 : 255));

        // Links may only be http(s), mailto, tel, "#anchor" or a path — never javascript: etc.
        if ($type === 'url' && $v !== '' && ! preg_match('~^(https?://|mailto:|tel:|/|#)~i', $v)) {
            return '';
        }

        return $v;
    }

    private static function image(mixed $existing, mixed $file, Client $client): string
    {
        if ($file instanceof UploadedFile && $file->isValid()) {
            if (! in_array($file->getMimeType(), self::IMAGE_MIMES, true) || $file->getSize() > self::IMAGE_MAX_BYTES) {
                throw ValidationException::withMessages([
                    'content' => 'Images must be JPG, PNG, WebP or GIF and at most 4 MB.',
                ]);
            }

            return self::$uploaded[] = $file->store("clients/{$client->id}", 'uploads');
        }

        // Keep the existing image, but only if it is inside THIS client's folder.
        $existing = is_string($existing) ? trim($existing) : '';

        return str_starts_with($existing, "clients/{$client->id}/") && ! str_contains($existing, '..') ? $existing : '';
    }

    private static function rowIsEmpty(array $fields, array $row): bool
    {
        foreach ($fields as $name => $f) {
            if (in_array($f['type'], ['select', 'checkbox'], true)) {
                continue;
            }
            $v = $row[$name] ?? null;
            if (is_array($v) ? array_filter($v, fn ($x) => $x !== '' && $x !== []) : $v !== '') {
                return false;
            }
        }

        return true;
    }

    // ---------------------------------------------------------------- stored -> view

    /** Flatten stored content to plain values for one locale (with fallbacks). Images become URLs. */
    public static function resolve(array $fields, array $content, string $locale, string $fallback): array
    {
        $out = [];
        foreach ($fields as $name => $f) {
            $v = $content[$name] ?? null;
            $out[$name] = match ($f['type']) {
                'text', 'textarea', 'url' => ! empty($f['translatable'])
                    ? self::pick($v, $locale, $fallback)
                    : (is_string($v) ? $v : ''),
                'select' => is_string($v) && isset($f['options'][$v]) ? $v : array_key_first($f['options']),
                'checkbox' => (bool) $v,
                'image' => is_string($v) && $v !== '' ? asset('uploads/' . $v) : '',
                'list' => array_map(
                    fn ($row) => self::resolve($f['fields'], is_array($row) ? $row : [], $locale, $fallback),
                    is_array($v) ? array_values($v) : []
                ),
                default => null,
            };
        }

        return $out;
    }

    /**
     * How much of this content exists in $locale: [texts, missing] — the translatable texts that are filled in
     * in some language, and how many of those are empty in $locale (and would fall back to another language).
     */
    public static function coverage(array $fields, array $content, string $locale): array
    {
        $texts = $missing = 0;
        foreach ($fields as $name => $f) {
            $v = $content[$name] ?? null;
            if (in_array($f['type'], ['text', 'textarea', 'url'], true) && ! empty($f['translatable'])) {
                $filled = fn ($x) => is_string($x) && trim($x) !== '';
                if (is_array($v) && array_filter($v, $filled)) {
                    $texts++;
                    $missing += $filled($v[$locale] ?? null) ? 0 : 1;
                }
            } elseif ($f['type'] === 'list' && is_array($v)) {
                foreach ($v as $row) {
                    [$t, $m] = self::coverage($f['fields'], is_array($row) ? $row : [], $locale);
                    $texts += $t;
                    $missing += $m;
                }
            }
        }

        return [$texts, $missing];
    }

    private static function pick(mixed $v, string $locale, string $fallback): string
    {
        if (! is_array($v)) {
            return is_string($v) ? $v : '';
        }
        foreach ([$locale, $fallback] as $l) {
            if (! empty($v[$l]) && is_string($v[$l])) {
                return $v[$l];
            }
        }
        foreach ($v as $x) {
            if (is_string($x) && $x !== '') {
                return $x;
            }
        }

        return '';
    }

    // ---------------------------------------------------------------- for the dashboard front-end

    /**
     * The schema in the shape the Vue dashboard needs: ordered lists instead of maps (JavaScript
     * reorders numeric-looking keys, which would scramble e.g. the 5…1 star options).
     */
    public static function forClient(array $fields): array
    {
        $out = [];
        foreach ($fields as $name => $f) {
            $f['name'] = $name;
            if (isset($f['options'])) {
                $f['options'] = array_map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label], $f['options'], array_keys($f['options']));
            }
            if (isset($f['fields'])) {
                $f['fields'] = self::forClient($f['fields']);
            }
            $out[] = $f;
        }

        return $out;
    }

    // ---------------------------------------------------------------- images

    /** Every uploaded-image path stored in a content array (schema-aware: only real image fields count). */
    public static function images(array $fields, array $content): array
    {
        $paths = [];
        foreach ($fields as $name => $f) {
            $v = $content[$name] ?? null;
            if ($f['type'] === 'image' && is_string($v) && $v !== '') {
                $paths[] = $v;
            } elseif ($f['type'] === 'list' && is_array($v)) {
                foreach ($v as $row) {
                    if (is_array($row)) {
                        array_push($paths, ...self::images($f['fields'], $row));
                    }
                }
            }
        }

        return $paths;
    }

    // ---------------------------------------------------------------- plain -> stored

    /**
     * Like localize(), but only the translatable text fields, and only keys present in $plain.
     * Merged over existing content it adds a language without touching images, prices or settings.
     */
    public static function localizeTranslatable(array $fields, array $plain, string $locale): array
    {
        $out = [];
        foreach ($fields as $name => $f) {
            if (! array_key_exists($name, $plain)) {
                continue;
            }
            if (in_array($f['type'], ['text', 'textarea', 'url'], true) && ! empty($f['translatable'])) {
                $out[$name] = [$locale => (string) $plain[$name]];
            } elseif ($f['type'] === 'list') {
                $out[$name] = array_map(fn ($row) => self::localizeTranslatable($f['fields'], (array) $row, $locale), array_values((array) $plain[$name]));
            }
        }

        return $out;
    }


    /** Turn plain values (e.g. defaults) into the stored shape for one locale. */
    public static function localize(array $fields, array $plain, string $locale): array
    {
        $out = [];
        foreach ($fields as $name => $f) {
            $v = $plain[$name] ?? null;
            $out[$name] = match ($f['type']) {
                'text', 'textarea', 'url' => ! empty($f['translatable']) ? [$locale => (string) ($v ?? '')] : (string) ($v ?? ''),
                'select' => $v ?? array_key_first($f['options']),
                'checkbox' => (bool) $v,
                'image' => (string) ($v ?? ''),
                'list' => array_map(fn ($row) => self::localize($f['fields'], (array) $row, $locale), (array) ($v ?? [])),
                default => null,
            };
        }

        return $out;
    }
}
