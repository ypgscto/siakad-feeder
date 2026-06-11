<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeder_prodi_maps', function (Blueprint $table) {
            $table->id();
            $table->string('siakad_prodi_id')->unique();
            $table->uuid('feeder_id_prodi');
            $table->uuid('feeder_id_prodi_asal')->nullable();
            $table->uuid('feeder_id_prodi_rpl')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeder_prodi_maps');
    }
};
