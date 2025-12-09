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
        Schema::create('tallcms_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('tallcms_menus')->onDelete('cascade');
            $table->string('label');
            $table->enum('type', ['page', 'external', 'custom', 'separator', 'header']);
            $table->foreignId('page_id')->nullable()->constrained('tallcms_pages')->onDelete('cascade');
            $table->text('url')->nullable(); // For external/custom URLs
            $table->json('meta')->nullable(); // icons, css_class, open_in_new_tab, etc.
            $table->boolean('is_active')->default(true);
            
            // Add nested set columns using kalnoy/nestedset
            $table->nestedSet();
            
            $table->timestamps();
            
            $table->index(['menu_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tallcms_menu_items');
    }
};
