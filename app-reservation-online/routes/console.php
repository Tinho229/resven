<?php

use App\Models\Reservation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reservations:rejeter-expirees', function () {
    $nb = Reservation::rejeterReservationsExpirees();
    $this->info("{$nb} réservation(s) expirée(s) ont été rejetée(s) avec succès.");
})->purpose('Passer automatiquement au statut rejetée les réservations en attente dont le délai a expiré');

Schedule::command('reservations:rejeter-expirees')->everyMinute();

