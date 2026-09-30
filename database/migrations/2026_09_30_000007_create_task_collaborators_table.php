<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->restrictOnDelete();
            $table->foreignId('collaborator_id')->constrained()->restrictOnDelete();
            $table->decimal('hours', 8, 2);
            $table->decimal('total', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_collaborators');
    }
};
