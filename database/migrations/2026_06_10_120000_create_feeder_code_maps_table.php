<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeder_code_maps', function (Blueprint $table) {
            $table->id();
            $table->string('category', 40);
            $table->string('siakad_key', 120);
            $table->string('siakad_label')->nullable();
            $table->string('feeder_value', 40);
            $table->string('feeder_label')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['category', 'siakad_key']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeder_code_maps');
    }
};
