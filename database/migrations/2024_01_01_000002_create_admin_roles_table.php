<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_protected')->default(false)->comment('Prevents deletion of built-in roles');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_protected');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
