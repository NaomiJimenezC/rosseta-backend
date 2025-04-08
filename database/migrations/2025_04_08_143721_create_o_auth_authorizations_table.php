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
        Schema::create('oauth_authorizations', function (Blueprint $table) {
            $table->id('authorization_id'); // Especificamos el nombre de la clave primaria
            $table->foreignId('oauth_app_id')->constrained('oauth_apps')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('access_token');
            $table->text('refresh_token')->nullable(); // El refresh token podría ser opcional
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_authorizations');
    }
};