<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Salle;
use App\Models\Equipement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Reservation::rejeterReservationsExpirees();

        $reservations = Reservation::with(['salle.images', 'equipements'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        Reservation::rejeterReservationsExpirees();
        $reservation->refresh();

        if ($reservation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez consulter que vos propres réservations.',
            ], 403);
        }

        $reservation->load(['salle.images', 'equipements', 'user']);

        return response()->json([
            'success' => true,
            'data' => $reservation,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'salle_id' => ['required', 'integer', 'exists:salles,id'],

            'date_heure_debut' => ['required', 'date', 'after_or_equal:now'],

            'date_heure_fin' => [
                'required',
                'date',
                'after:date_heure_debut',
            ],

            'nombre_personnes' => [
                'required',
                'integer',
                'min:1',
            ],

            'equipements' => [
                'nullable',
                'array',
            ],

            'equipements.*.equipement_id' => [
                'required',
                'integer',
                'distinct',
                'exists:equipements,id',
            ],

            'equipements.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $salle = Salle::findOrFail($validated['salle_id']);

        // Vérification de la capacité de la salle
        if ($validated['nombre_personnes'] > $salle->capacite) {
            throw ValidationException::withMessages([
                'nombre_personnes' => [
                    "Cette salle a une capacité maximale de {$salle->capacite} personnes.",
                ],
            ]);
        }

        // Vérification de la disponibilité de la salle
        if (
            !$salle->estDisponible(
                $validated['date_heure_debut'],
                $validated['date_heure_fin']
            )
        ) {
            throw ValidationException::withMessages([
                'salle_id' => [
                    "La salle n'est pas disponible pour cette période.",
                ],
            ]);
        }

        $reservation = DB::transaction(function () use ($validated, $request) {

            $reservation = Reservation::create([
                'date_heure_debut' => $validated['date_heure_debut'],
                'date_heure_fin' => $validated['date_heure_fin'],
                'nombre_personnes' => $validated['nombre_personnes'],
                'status' => 'en_attente',
                'user_id' => $request->user()->id,
                'salle_id' => $validated['salle_id'],
            ]);

            if (!empty($validated['equipements'])) {
                $equipements = [];

                foreach ($validated['equipements'] as $equipementData) {
                    $equipement = Equipement::findOrFail(
                        $equipementData['equipement_id']
                    );

                    $disponible = $equipement->quantiteDisponible(
                        $validated['date_heure_debut'],
                        $validated['date_heure_fin']
                    );

                    if ($equipementData['quantity'] > $disponible) {
                        throw ValidationException::withMessages([
                            'equipements' => [
                                "Il ne reste que {$disponible} unité(s) disponible(s) pour {$equipement->nom} sur cette période.",
                            ],
                        ]);
                    }

                    $equipements[$equipement->id] = [
                        'quantity' => $equipementData['quantity'],
                    ];
                }

                $reservation->equipements()->attach($equipements);
            }

            return $reservation;
        });

        $reservation->load(['salle.images', 'equipements']);

        return response()->json([
            'success' => true,
            'message' => 'Réservation créée avec succès.',
            'data' => $reservation,
        ], 201);
    }

    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que vos propres réservations.',
            ], 403);
        }

        if ($reservation->isExpired()) {
            if ($reservation->status === 'en_attente') {
                $reservation->update(['status' => 'rejetee']);
            }
            throw ValidationException::withMessages([
                'status' => ["Cette réservation a expiré (date de début dépassée) et ne peut plus être modifiée."],
            ]);
        }

        if (!in_array($reservation->status, ['en_attente', 'confirmee'])) {
            throw ValidationException::withMessages([
                'status' => ["Cette réservation ne peut plus être modifiée (statut actuel : {$reservation->status})."],
            ]);
        }

        $validated = $request->validate([
            'salle_id' => ['sometimes', 'required', 'integer', 'exists:salles,id'],
            'date_heure_debut' => ['sometimes', 'required', 'date'],
            'date_heure_fin' => ['sometimes', 'required', 'date', 'after:date_heure_debut'],
            'nombre_personnes' => ['sometimes', 'required', 'integer', 'min:1'],
            'equipements' => ['nullable', 'array'],
            'equipements.*.equipement_id' => ['required_with:equipements', 'integer', 'distinct', 'exists:equipements,id'],
            'equipements.*.quantity' => ['required_with:equipements', 'integer', 'min:1'],
        ]);

        $salleId = $validated['salle_id'] ?? $reservation->salle_id;
        $debut = $validated['date_heure_debut'] ?? $reservation->date_heure_debut;
        $fin = $validated['date_heure_fin'] ?? $reservation->date_heure_fin;
        $nombrePersonnes = $validated['nombre_personnes'] ?? $reservation->nombre_personnes;

        $salle = Salle::findOrFail($salleId);

        if ($salle->status !== 'disponible') {
            throw ValidationException::withMessages([
                'salle_id' => ["Impossible : la salle '{$salle->nom}' est actuellement indisponible."],
            ]);
        }

        if ($nombrePersonnes > $salle->capacite) {
            throw ValidationException::withMessages([
                'nombre_personnes' => ["Cette salle a une capacité maximale de {$salle->capacite} personnes."],
            ]);
        }

        // Vérification des chevauchements de salle en excluant la réservation actuelle
        $conflict = Reservation::overlapping($salleId, (string) $debut, (string) $fin, $reservation->id)->first();
        if ($conflict) {
            throw ValidationException::withMessages([
                'date_heure_debut' => ["La salle '{$salle->nom}' est déjà réservée sur ce créneau horaire."],
            ]);
        }

        // Vérification du stock des équipements sur la période
        if (isset($validated['equipements']) && is_array($validated['equipements'])) {
            foreach ($validated['equipements'] as $eqItem) {
                $equipement = Equipement::find($eqItem['equipement_id']);
                if (!$equipement) continue;

                if ($equipement->status !== 'disponible' || $equipement->stock_total <= 0) {
                    throw ValidationException::withMessages([
                        'equipements' => ["L'équipement '{$equipement->nom}' est actuellement indisponible ou en rupture de stock."],
                    ]);
                }

                $available = $equipement->getAvailableStockForPeriod((string) $debut, (string) $fin, $reservation->id);
                $requestedQty = (int) ($eqItem['quantity'] ?? 1);
                if ($requestedQty > $available) {
                    throw ValidationException::withMessages([
                        'equipements' => ["Stock insuffisant pour {$equipement->nom} sur ce créneau (disponible : {$available}, demandé : {$requestedQty})."],
                    ]);
                }
            }
        }

        DB::transaction(function () use ($reservation, $validated, $salleId, $debut, $fin, $nombrePersonnes) {
            $reservation->update([
                'salle_id' => $salleId,
                'date_heure_debut' => $debut,
                'date_heure_fin' => $fin,
                'nombre_personnes' => $nombrePersonnes,
                'status' => 'en_attente',
            ]);

            if (isset($validated['equipements']) && is_array($validated['equipements'])) {
                $attachData = [];
                foreach ($validated['equipements'] as $item) {
                    $attachData[$item['equipement_id']] = ['quantity' => $item['quantity'] ?? 1];
                }
                $reservation->equipements()->sync($attachData);
            }
        });

        $reservation->load(['salle.images', 'equipements', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Réservation modifiée avec succès.',
            'data' => $reservation,
        ]);
    }

    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->user_id !== $request->user()->id) {
            abort(403, 'Vous ne pouvez annuler que vos propres réservations.');
        }

        if ($reservation->isExpired()) {
            if ($reservation->status === 'en_attente') {
                $reservation->update(['status' => 'rejetee']);
            }
            throw ValidationException::withMessages([
                'status' => ["Cette réservation a expiré (date de début dépassée) et ne peut plus être annulée."],
            ]);
        }

        if (!in_array($reservation->status, ['en_attente', 'confirmee'])) {
            throw ValidationException::withMessages([
                'status' => ["Cette réservation ne peut plus être annulée (statut actuel : {$reservation->status})."],
            ]);
        }

        $reservation->update(['status' => 'annulee']);

        return response()->json([
            'success' => true,
            'message' => 'Réservation annulée avec succès.',
        ]);
    }
}
