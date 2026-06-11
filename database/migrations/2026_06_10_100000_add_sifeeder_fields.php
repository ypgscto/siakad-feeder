<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('siakad_user_id')->nullable()->after('id');
            $table->string('siakad_login')->nullable()->after('siakad_user_id');
            $table->string('jenis_user', 10)->nullable()->after('siakad_login');
            $table->string('role', 20)->default('operator')->after('jenis_user');
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('feeder_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type');
            $table->json('payload_summary')->nullable();
            $table->integer('feeder_error_code')->nullable();
            $table->text('feeder_error_desc')->nullable();
            $table->boolean('success')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeder_sync_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['siakad_user_id', 'siakad_login', 'jenis_user', 'role', 'is_active']);
        });
    }
};
