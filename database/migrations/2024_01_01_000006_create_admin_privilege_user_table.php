<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_privilege_user', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('privilege_id')
                ->constrained('admin_privileges')
                ->cascadeOnDelete();

            $table->timestamp('assigned_at')->useCurrent();

            $table->primary(['user_id', 'privilege_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_privilege_user');
    }
};
