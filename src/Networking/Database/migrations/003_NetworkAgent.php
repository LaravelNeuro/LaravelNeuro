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
        Schema::create('laravel_neuro_network_agents', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('unit_id');
                $table->foreign('unit_id')
                      ->references('id')->on('laravel_neuro_network_units')
                      ->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('api')->nullable();
            $table->enum('apiType', ['CHATCOMPLETION', 'IMAGE', 'BASIC', 'TTS', 'STT', 'VIDEO'])->default('CHATCOMPLETION');
            $table->string('pipeline');
            $table->text('role')->nullable();
            $table->text('prompt')->nullable();
            $table->string('promptClass');
            $table->boolean('validateOutput')->default(false);
            $table->string('outputModel')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laravel_neuro_network_agents');
    }
};
