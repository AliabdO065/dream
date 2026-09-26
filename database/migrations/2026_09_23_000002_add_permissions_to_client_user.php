<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_user', function (Blueprint $t) {
            // Only meaningful for role=editor (an owner always has full access — see User::can()).
            // null = the default editor preset (content + images). An array of granted keys overrides it.
            $t->json('permissions')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('client_user', function (Blueprint $t) {
            $t->dropColumn('permissions');
        });
    }
};
