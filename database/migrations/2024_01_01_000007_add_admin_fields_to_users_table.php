<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_path', 2048)->nullable()->after('email');
            $table->text('bio')->nullable()->after('profile_photo_path');
            $table->string('phone', 50)->nullable()->after('bio');
            $table->timestamp('last_login_at')->nullable()->after('phone');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('suspended_at')->nullable()->after('last_login_ip');
            $table->text('suspension_reason')->nullable()->after('suspended_at');

            $table->index('suspended_at');
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['suspended_at']);
            $table->dropIndex(['last_login_at']);

            $table->dropColumn([
                'profile_photo_path',
                'bio',
                'phone',
                'last_login_at',
                'last_login_ip',
                'suspended_at',
                'suspension_reason',
            ]);
        });
    }
};
