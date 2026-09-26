<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The 5 old themes were replaced outright (config/platform.php) — point existing clients at their closest new match. */
    private const MAP = [
        'classic' => 'nova',
        'bold' => 'brutal',
        'minimal' => 'editorial',
        'elegant' => 'noir',
        'vibrant' => 'carnival',
    ];

    public function up(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('clients')->where('design', $old)->update(['design' => $new]);
        }
    }

    public function down(): void
    {
        foreach (array_flip(self::MAP) as $new => $old) {
            DB::table('clients')->where('design', $new)->update(['design' => $old]);
        }
    }
};
