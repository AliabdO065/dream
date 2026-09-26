<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform assistants: staff who work in /admin and on every client's site, but can't delete or suspend
 * clients or manage the platform's own staff. A user is an admin OR an assistant, never both (AdminController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_assistant')->default(false)->after('is_super_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_assistant'));
    }
};
