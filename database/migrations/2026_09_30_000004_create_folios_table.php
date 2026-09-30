<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->restrictOnDelete();
            $table->integer('folio')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folios');
    }
};
