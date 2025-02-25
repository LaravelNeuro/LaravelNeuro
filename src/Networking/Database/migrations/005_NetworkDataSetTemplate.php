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
        Schema::create('laravel_neuro_network_dataset_templates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();          
            $table->unsignedBigInteger('unit_id');
                $table->foreign('unit_id')
                      ->references('id')->on('laravel_neuro_network_units')
                      ->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->text('completionPrompt');
            $table->json('completionResponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laravel_neuro_network_dataset_templates');
    }
};
