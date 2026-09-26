<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use App\Sections\Registry;
use App\Sections\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Section extends Model
{
    use BelongsToClient;

    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'config' => 'array',
        'content' => 'array',
    ];

    protected static function booted(): void
    {
        $bump = fn (Section $s) => DB::table('clients')->where('id', $s->client_id)->increment('content_version');
        static::saved($bump);
        static::deleted($bump);

        // No orphaned uploads: deleting a section removes its images...
        static::deleted(fn (Section $s) => $s->deleteFiles($s->imagePaths($s->content ?? [])));

        // ...and so does replacing or removing an image inside a section.
        static::updated(function (Section $s) {
            if ($s->wasChanged('content')) {
                $gone = array_diff($s->imagePaths($s->getOriginal('content') ?? []), $s->imagePaths($s->content ?? []));
                $s->deleteFiles($gone);
            }
        });
    }

    /**
     * Uploaded images in a content array. Only image fields of a registered type count, and only files
     * inside THIS client's folder are ever returned — so nothing outside it can be deleted.
     * (A section of a type that no longer exists can't be read, so its files are left alone.)
     */
    public function imagePaths(array $content): array
    {
        $type = app(Registry::class)->get($this->type);
        if (! $type) {
            return [];
        }

        $folder = "clients/{$this->client_id}/";

        return array_values(array_unique(array_filter(
            Schema::images($type->fields(), $content),
            fn ($p) => str_starts_with($p, $folder) && ! str_contains($p, '..')
        )));
    }

    private function deleteFiles(array $paths): void
    {
        if ($paths) {
            Storage::disk('uploads')->delete($paths);
        }
    }
}
