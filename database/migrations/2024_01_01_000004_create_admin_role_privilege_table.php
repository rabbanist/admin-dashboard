<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_role_privilege', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('admin_roles')
                ->cascadeOnDelete();

            $table->foreignId('privilege_id')
                ->constrained('admin_privileges')
                ->cascadeOnDelete();

            $table->primary(['role_id', 'privilege_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role_privilege');
    }
};
