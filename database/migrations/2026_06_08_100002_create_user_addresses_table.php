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
        Schema::create('user_addresses', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('user_id');

            $table->string('address_line1', 255);

            $table->string('address_line2', 255)
                ->nullable();

            $table->string('city', 100);

            $table->string('state', 100);

            $table->string('postal_code', 20);

            $table->string('country', 100);

            $table->string('phone', 100)
                ->nullable();

            $table->string('company_name', 255)
                ->nullable();

            $table->string('gstin_number', 100)
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | FOREIGN KEY
            |--------------------------------------------------------------------------
            */
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};