<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The product went back to one design and simple owner/editor roles: the columns that fed the rest are gone. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clients', 'design')) {
            Schema::table('clients', fn (Blueprint $t) => $t->dropColumn('design'));
        }
        if (Schema::hasColumn('client_user', 'permissions')) {
            Schema::table('client_user', fn (Blueprint $t) => $t->dropColumn('permissions'));
        }
    }

    public function down(): void
    {
        Schema::table('clients', fn (Blueprint $t) => $t->string('design', 40)->default('nova'));
        Schema::table('client_user', fn (Blueprint $t) => $t->text('permissions')->nullable());
    }
};
