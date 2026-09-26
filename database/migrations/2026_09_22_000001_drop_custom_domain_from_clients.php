<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Decision: every client is served at /{slug} only — there are no per-client domains.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clients', 'custom_domain')) {
            Schema::table('clients', function (Blueprint $t) {
                $t->dropUnique(['custom_domain']);
                $t->dropColumn('custom_domain');
            });
        }
    }

    public function down(): void
    {
        // intentionally empty: the feature was dropped
    }
};
