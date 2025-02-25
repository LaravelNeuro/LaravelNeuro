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
        Schema::create('laravel_neuro_network_datasets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('project_id');
                $table->foreign('project_id')
                      ->references('id')->on('laravel_neuro_network_projects')
                      ->onUpdate('cascade')->onDelete('cascade');            
            $table->unsignedBigInteger('template_id');
                $table->foreign('template_id')
                      ->references('id')->on('laravel_neuro_network_dataset_templates')
                      ->onUpdate('cascade')->onDelete('cascade');
            $table->json('data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laravel_neuro_network_datasets');
    }
};
