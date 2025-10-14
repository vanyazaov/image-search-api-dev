<?php
// database/migrations/xxxx_xx_xx_xxxxxx_update_users_table_for_subscriptions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('buyer'); // buyer | seller | admin
            $table->string('api_key', 64)->unique()->nullable();
            $table->integer('request_limit')->default(1000);
            $table->integer('requests_used')->default(0);
            $table->timestamp('subscription_valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role','api_key', 'request_limit', 'requests_used', 
                               'subscription_valid_until', 'is_active', 'meta']);
        });
    }
};
