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
        Schema::create('laravel_neuro_network_units', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->int('corporation');
            $table->string('name');
            $table->string('description');
            $table->string('defaultReceiver');
            $table->string('defaultReceiverType');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laravel_neuro_network_units');
    }
};
