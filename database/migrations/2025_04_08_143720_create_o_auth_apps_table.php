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
        Schema::create('oauth_apps', function (Blueprint $table) {
            $table->id('oauth_app_id'); // Especificamos el nombre de la clave primaria
            $table->string('app_name');
            $table->string('client_id')->unique();
            $table->string('client_secret');
            $table->text('redirect_uri'); // Usamos text para permitir URIs más largas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_apps');
    }
};