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
            $table->int('unit');
            $table->string('name');
            $table->string('model');
            $table->string('api');
            $table->text('role');
            $table->boolean('validateOutput');
            $table->string('outputModel');
            $table->string('pipeReceiverType');
            $table->string('pipeRetrieverType');
            $table->string('pipeRetriever');
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
