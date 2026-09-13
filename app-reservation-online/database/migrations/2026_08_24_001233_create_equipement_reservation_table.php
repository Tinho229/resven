<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipement_reservation', function (Blueprint $table) {
            $table->foreignId('reservation_id')
                ->constrained('reservations')
                ->cascadeOnDelete();

            $table->foreignId('equipement_id')
                ->constrained('equipements')
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity');

            $table->primary([
                'reservation_id',
                'equipement_id'
            ]);


            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipement_reservation');
    }
};
