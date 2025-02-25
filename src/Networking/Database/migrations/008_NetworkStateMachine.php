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
        Schema::create('laravel_neuro_network_state_machines', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->enum('type', ['INITIAL', 'FINAL', 'PROCESSING', 'INTERMEDIARY']);  
            $table->boolean('active');
            $table->unsignedBigInteger('project_id');
                $table->foreign('project_id')
                      ->references('id')->on('laravel_neuro_network_projects')
                      ->onUpdate('cascade')->onDelete('cascade');   
            $table->text('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laravel_neuro_network_state_machines');
    }
};
