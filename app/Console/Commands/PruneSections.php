<?php

namespace App\Console\Commands;

use App\Models\Section;
use App\Sections\Registry;
use Illuminate\Console\Command;

class PruneSections extends Command
{
    protected $signature = 'sections:prune {type? : Section type key to delete everywhere} {--unknown : Delete sections whose type is no longer registered} {--force}';
    protected $description = 'Delete leftover sections after removing a section type or module.';

    public function handle(Registry $registry): int
    {
        $query = Section::query()->withoutGlobalScopes();

        if ($type = $this->argument('type')) {
            $query->where('type', $type);
        } elseif ($this->option('unknown')) {
            $query->whereNotIn('type', array_keys($registry->all()));
        } else {
            $this->error('Give a type key, or use --unknown.');

            return self::INVALID;
        }

        $count = $query->count();
        if ($count === 0) {
            $this->info('Nothing to delete.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete $count section(s) across all clients?")) {
            return self::FAILURE;
        }

        $query->get()->each->delete();
        $this->info("Deleted $count section(s).");

        return self::SUCCESS;
    }
}
