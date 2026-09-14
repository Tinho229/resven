<?php

use App\Models\Reservation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reservations:actualiser-statuts', function () {
    $res = Reservation::actualiserStatutsAutomatiques();
    $this->info("{$res['rejetees']} réservation(s) expirée(s) rejetée(s), {$res['terminees']} réservation(s) clôturée(s) terminée(s).");
})->purpose('Actualiser les statuts : rejeter les expirées et clôturer les confirmées dont la durée est terminée');

Schedule::command('reservations:actualiser-statuts')->everyMinute();


