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
        Schema::create('laravel_neuro_network_history', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->int('project');   
            $table->int('unit')->nullable();  
            $table->int('agent')->nullable();
            $table->enum('entryType', ['prompt', 'response', 'plugin']);  
            $table->longText('content')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laravel_neuro_network_history');
    }
};
