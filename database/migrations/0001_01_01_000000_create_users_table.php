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
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('username')->unique()->nullable();
    $table->string('email')->unique();
    $table->text('bio')->nullable();
    $table->string('avatar_path')->nullable();
    $table->string('avatar_thumbnail_path')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->enum('role', ['superadmin','admin','staff','publisher', 'viewer'])->default('viewer');
    $table->enum('activity_status', ['active', 'inactive', 'suspended'])->default('active');


        $table->string('stripe_id')->nullable()->after('id');
        $table->string('pm_type')->nullable()->after('stripe_id');
        $table->string('pm_last_four')->nullable()->after('pm_type');
        $table->timestamp('trial_ends_at')->nullable()->after('pm_last_four');
    $table->boolean('is_subscription_enabled')->default(false);
    $table->unsignedInteger('subscription_price')->nullable();
    $table->timestamp('suspended_at')->nullable();
    $table->timestamps();

    $table->index('role');
    $table->index('activity_status');
});

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
