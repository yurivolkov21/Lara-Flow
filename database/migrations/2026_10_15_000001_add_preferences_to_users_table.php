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
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->default('UTC')->after('password');
            $table->string('locale', 8)->default('en')->after('timezone');
            $table->string('accent_color')->default('neutral')->after('locale');
            $table->boolean('digest_enabled')->default(false)->after('accent_color');
            $table->time('digest_time')->default('07:00')->after('digest_enabled');
            $table->date('digest_last_sent_on')->nullable()->after('digest_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'locale',
                'accent_color',
                'digest_enabled',
                'digest_time',
                'digest_last_sent_on',
            ]);
        });
    }
};
