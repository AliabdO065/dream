<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A client = one tenant = one public site at /{slug}.
        // Columns are for things we search/filter/route on; the long tail lives in profile_extra.
        Schema::create('clients', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('legal_name')->nullable();
            $t->string('business_type')->nullable()->index();   // free text, any industry
            $t->string('tagline')->nullable();
            $t->string('status', 20)->default('active')->index(); // draft | active | suspended

            $t->string('email')->nullable();
            $t->string('phone', 50)->nullable();
            $t->string('website')->nullable();
            $t->string('address_line1')->nullable();
            $t->string('address_line2')->nullable();
            $t->string('postal_code', 20)->nullable();
            $t->string('city')->nullable();
            $t->string('region')->nullable();
            $t->string('country', 100)->nullable();
            $t->string('registration_no')->nullable();
            $t->string('tax_id')->nullable();

            $t->string('timezone', 64)->default('UTC');
            $t->string('currency', 3)->nullable();
            $t->string('default_locale', 10)->default('en');
            $t->json('locales')->nullable();
            $t->string('logo_path')->nullable();

            $t->json('theme')->nullable();          // {brand: "#hex", font: "sans|serif|rounded"}
            $t->json('features')->nullable();       // {forms: bool, types: null|[keys]}
            $t->json('profile_extra')->nullable();  // [{label, value}, ...] any custom profile fields

            $t->unsignedInteger('content_version')->default(1); // bumped on every change -> cache key
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('client_user', function (Blueprint $t) {
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role', 20)->default('owner'); // owner | editor
            $t->timestamps();
            $t->primary(['client_id', 'user_id']);
        });

        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->string('type', 50);        // key of a registered section type (NOT a foreign key)
            $t->string('name');            // admin-facing label
            $t->string('anchor', 80)->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->boolean('is_enabled')->default(true);
            $t->json('config')->nullable();   // layout/behaviour (variant, background, show_in_nav)
            $t->json('content')->nullable();  // words + images, translatable per locale
            $t->timestamps();
            $t->index(['client_id', 'is_enabled', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
        Schema::dropIfExists('client_user');
        Schema::dropIfExists('clients');
    }
};
