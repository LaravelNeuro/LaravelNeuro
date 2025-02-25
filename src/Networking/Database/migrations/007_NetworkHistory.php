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
            $table->unsignedBigInteger('project_id');
                $table->foreign('project_id')
                      ->references('id')->on('laravel_neuro_network_projects')
                      ->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('unit_id')->nullable();  
                $table->foreign('unit_id')
                      ->references('id')->on('laravel_neuro_network_units')
                      ->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('agent_id')->nullable();
                $table->foreign('agent_id')
                      ->references('id')->on('laravel_neuro_network_agents')
                      ->onUpdate('cascade')->onDelete('cascade'); 
            $table->enum('entryType', ['PROMPT', 'RESPONSE', 'PLUGIN', 'ERROR', 'OTHER']);  
            $table->mediumText('content')->nullable();
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
