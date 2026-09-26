<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A client's visual theme for their public site — completely different look, same sections/content.
// See config('platform.designs') and public/css/themes/*.css.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $t) {
            $t->string('design', 30)->default('classic')->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $t) {
            $t->dropColumn('design');
        });
    }
};
