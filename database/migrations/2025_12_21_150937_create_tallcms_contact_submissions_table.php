<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tallcms_contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->json('form_data');
            $table->string('page_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tallcms_contact_submissions');
    }
};
