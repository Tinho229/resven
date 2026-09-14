<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReservationRequest;
use App\Http\Requests\Admin\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Equipement;
use App\Models\Reservation;
use App\Models\Salle;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    /**
     * Liste toutes les réservations avec filtres (statut, salle, utilisateur, recherche, dates) et pagination.
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            Reservation::actualiserStatutsAutomatiques();

            $query = Reservation::query()
                ->with(['salle.images', 'user', 'createur', 'equipements'])
                ->latest('date_heure_debut');

            // Filtre par statut
            if ($request->filled('status')) {
                $query->status($request->status);
            }

            // Filtre par salle
            if ($request->filled('salle_id')) {
                $query->forSalle((int) $request->salle_id);
            }

            // Filtre par utilisateur
            if ($request->filled('user_id')) {
                $query->forUser((int) $request->user_id);
            }

            // Recherche textuelle
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filtre par plage de dates
            if ($request->filled('date_debut')) {
                $query->where('date_heure_debut', '>=', $request->date_debut);
            }
            if ($request->filled('date_fin')) {
                $query->where('date_heure_fin', '<=', $request->date_fin);
            }

            // Sans pagination si demandé
            if ($request->get('all') === 'true' || $request->get('paginate') === 'false') {
                $reservations = $query->get();
                return response()->json([
                    'message' => 'Liste des réservations récupérée avec succès.',
                    'data' => ReservationResource::collection($reservations),
                ], 200);
            }

            $perPage = (int) $request->get('per_page', 15);
            $reservations = $query->paginate($perPage);

            return response()->json([
                'message' => 'Liste des réservations récupérée avec succès.',
                'data' => ReservationResource::collection($reservations),
                'meta' => [
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                    'per_page' => $reservations->perPage(),
                    'total' => $reservations->total(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des réservations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enregistre une nouvelle réservation (pour un utilisateur existant ou un client externe).
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $salle = Salle::find($validated['salle_id']);
        if (!$salle) {
            return response()->json([
                'message' => 'La salle sélectionnée est introuvable.',
            ], 404);
        }

        // Vérification du statut de la salle (impossible de réserver une salle indisponible)
        if ($salle->status !== 'disponible') {
            return response()->json([
                'message' => "Impossible : la salle '{$salle->nom}' est actuellement indisponible.",
                'errors' => [
                    'salle_id' => ["Impossible de réserver la salle '{$salle->nom}' car elle est actuellement indisponible."],
                ],
            ], 422);
        }

        // Vérification de la capacité de la salle
        if ($validated['nombre_personnes'] > $salle->capacite) {
            return response()->json([
                'message' => "Impossible : le nombre de personnes ({$validated['nombre_personnes']}) dépasse la capacité maximale de la salle '{$salle->nom}' ({$salle->capacite} places).",
                'errors' => [
                    'nombre_personnes' => ["La capacité maximale est de {$salle->capacite} places."],
                ],
            ], 422);
        }

        // Vérification de la disponibilité et du stock des équipements demandés sur le créneau horaire
        if (!empty($validated['equipements']) && is_array($validated['equipements'])) {
            foreach ($validated['equipements'] as $item) {
                $equipement = Equipement::find($item['id']);
                if (!$equipement) {
                    continue;
                }

                if ($equipement->status !== 'disponible' || $equipement->stock_total <= 0) {
                    return response()->json([
                        'message' => "Impossible : l'équipement '{$equipement->nom}' est actuellement indisponible ou en rupture de stock.",
                        'errors' => [
                            'equipements' => ["Impossible de réserver l'équipement '{$equipement->nom}' car il est indisponible ou en rupture de stock."],
                        ],
                    ], 422);
                }

                $requestedQty = (int) ($item['quantity'] ?? 1);
                $availableStockOnPeriod = $equipement->getAvailableStockForPeriod(
                    $validated['date_heure_debut'],
                    $validated['date_heure_fin']
                );

                if ($requestedQty > $availableStockOnPeriod) {
                    return response()->json([
                        'message' => "Impossible : stock insuffisant pour l'équipement '{$equipement->nom}' sur ce créneau horaire. Stock restant : {$availableStockOnPeriod}, demandé : {$requestedQty}.",
                        'errors' => [
                            'equipements' => ["Impossible de réserver l'équipement '{$equipement->nom}' sur ce créneau horaire (stock disponible : {$availableStockOnPeriod})."],
                        ],
                    ], 422);
                }
            }
        }

        // Vérification des conflits de disponibilité de la salle
        $conflict = Reservation::overlapping(
            $salle->id,
            $validated['date_heure_debut'],
            $validated['date_heure_fin']
        )->first();

        if ($conflict) {
            return response()->json([
                'message' => "Impossible : la salle '{$salle->nom}' est déjà réservée sur ce créneau horaire (du {$conflict->date_heure_debut->format('d/m/Y H:i')} au {$conflict->date_heure_fin->format('d/m/Y H:i')}).",
                'errors' => [
                    'date_heure_debut' => ["Impossible de réserver ce créneau horaire car il chevauche une réservation existante."],
                ],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $reservation = Reservation::create([
                'salle_id' => $validated['salle_id'],
                'user_id' => $validated['user_id'] ?? null,
                'nom_client' => $validated['nom_client'] ?? null,
                'telephone_client' => $validated['telephone_client'] ?? null,
                'date_heure_debut' => $validated['date_heure_debut'],
                'date_heure_fin' => $validated['date_heure_fin'],
                'nombre_personnes' => $validated['nombre_personnes'],
                'status' => $validated['status'] ?? 'confirmee',
                
                'cree_par_id' => $request->user()?->id,
            ]);

            // Attacher les équipements avec les quantités
            if (!empty($validated['equipements']) && is_array($validated['equipements'])) {
                $attachData = [];
                foreach ($validated['equipements'] as $item) {
                    $attachData[$item['id']] = ['quantity' => $item['quantity'] ?? 1];
                }
                $reservation->equipements()->sync($attachData);
            }

            DB::commit();

            return response()->json([
                'message' => 'Réservation créée avec succès.',
                'data' => new ReservationResource($reservation->load(['salle.images', 'user', 'createur', 'equipements'])),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de la réservation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche les détails complets d'une réservation.
     */
    public function show(string $id): JsonResponse
    {
        try {
            Reservation::actualiserStatutsAutomatiques();

            $reservation = Reservation::with(['salle.images', 'user', 'createur', 'equipements'])->find($id);

            if (!$reservation) {
                return response()->json([
                    'message' => 'Réservation introuvable.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'message' => 'Détails de la réservation récupérés avec succès.',
                'data' => new ReservationResource($reservation),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération de la réservation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Met à jour les informations d'une réservation.
     */
    public function update(UpdateReservationRequest $request, string $id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'message' => 'Réservation introuvable.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        $salleId = $validated['salle_id'] ?? $reservation->salle_id;
        $debut = $validated['date_heure_debut'] ?? $reservation->date_heure_debut;
        $fin = $validated['date_heure_fin'] ?? $reservation->date_heure_fin;
        $nombrePersonnes = $validated['nombre_personnes'] ?? $reservation->nombre_personnes;

        $salle = Salle::find($salleId);
        if (!$salle) {
            return response()->json([
                'message' => 'La salle sélectionnée est introuvable.',
            ], 404);
        }

        // Vérification du statut demandé : interdire confirmation si expirée
        if (isset($validated['status']) && $validated['status'] === 'confirmee') {
            if ($reservation->isExpired() || \Carbon\Carbon::parse($debut)->isPast()) {
                return response()->json([
                    'message' => "Impossible de confirmer : le délai de réservation a expiré (la date de début est dépassée).",
                    'errors' => [
                        'status' => ["Impossible de confirmer une réservation dont la date de début est passée."],
                    ],
                ], 422);
            }
        }

        // Vérification du statut de la salle
        if ($salle->status !== 'disponible') {
            return response()->json([
                'message' => "Impossible : la salle '{$salle->nom}' est actuellement indisponible et ne peut pas être réservée.",
                'errors' => [
                    'salle_id' => ["Impossible de réserver la salle '{$salle->nom}' car elle est actuellement indisponible."],
                ],
            ], 422);
        }

        // Vérification de la capacité
        if ($nombrePersonnes > $salle->capacite) {
            return response()->json([
                'message' => "Impossible : le nombre de personnes ({$nombrePersonnes}) dépasse la capacité maximale de la salle '{$salle->nom}' ({$salle->capacite} places).",
                'errors' => [
                    'nombre_personnes' => ["Impossible : la capacité maximale est de {$salle->capacite} places."],
                ],
            ], 422);
        }

        // Vérification de la disponibilité et du stock des équipements sur le créneau horaire
        if (isset($validated['equipements']) && is_array($validated['equipements'])) {
            foreach ($validated['equipements'] as $item) {
                $equipement = Equipement::find($item['id']);
                if (!$equipement) {
                    continue;
                }

                if ($equipement->status !== 'disponible' || $equipement->stock_total <= 0) {
                    return response()->json([
                        'message' => "Impossible : l'équipement '{$equipement->nom}' est actuellement indisponible ou en rupture de stock.",
                        'errors' => [
                            'equipements' => ["Impossible de réserver l'équipement '{$equipement->nom}' car il est indisponible ou en rupture de stock."],
                        ],
                    ], 422);
                }

                $requestedQty = (int) ($item['quantity'] ?? 1);
                $availableStockOnPeriod = $equipement->getAvailableStockForPeriod(
                    (string) $debut,
                    (string) $fin,
                    $reservation->id
                );

                if ($requestedQty > $availableStockOnPeriod) {
                    return response()->json([
                        'message' => "Impossible : stock insuffisant pour l'équipement '{$equipement->nom}' sur ce créneau horaire. Stock restant : {$availableStockOnPeriod}, demandé : {$requestedQty}.",
                        'errors' => [
                            'equipements' => ["Impossible de réserver l'équipement '{$equipement->nom}' sur ce créneau horaire (stock disponible : {$availableStockOnPeriod})."],
                        ],
                    ], 422);
                }
            }
        }

        // Vérification des conflits si dates ou salle modifiées
        $conflict = Reservation::overlapping(
            $salleId,
            (string) $debut,
            (string) $fin,
            $reservation->id
        )->first();

        if ($conflict) {
            return response()->json([
                'message' => "Impossible : la salle '{$salle->nom}' est déjà réservée sur ce créneau horaire (du {$conflict->date_heure_debut->format('d/m/Y H:i')} au {$conflict->date_heure_fin->format('d/m/Y H:i')}).",
                'errors' => [
                    'date_heure_debut' => ["Impossible de réserver ce créneau horaire car il chevauche une réservation existante."],
                ],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $reservation->update($validated);

            if (isset($validated['equipements']) && is_array($validated['equipements'])) {
                $attachData = [];
                foreach ($validated['equipements'] as $item) {
                    $attachData[$item['id']] = ['quantity' => $item['quantity'] ?? 1];
                }
                $reservation->equipements()->sync($attachData);
            }

            DB::commit();

            return response()->json([
                'message' => 'Réservation mise à jour avec succès.',
                'data' => new ReservationResource($reservation->fresh(['salle', 'user', 'createur', 'equipements'])),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour de la réservation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirme une réservation en attente.
     */
    public function confirmer(string $id): JsonResponse
    {
        try {
            $reservation = Reservation::with(['salle', 'equipements'])->find($id);

            if (!$reservation) {
                return response()->json([
                    'message' => 'Réservation introuvable.',
                ], 404);
            }

            // Vérification de l'expiration du délai
            if ($reservation->isExpired()) {
                if ($reservation->status === 'en_attente') {
                    $reservation->status = 'rejetee';
                    $reservation->save();
                }
                return response()->json([
                    'message' => "Impossible de confirmer cette réservation : son délai est expiré (la date de début est déjà passée). Son statut est passé à 'rejeté'.",
                ], 422);
            }

            // Vérification que la réservation est bien en attente
            if ($reservation->status !== 'en_attente') {
                return response()->json([
                    'message' => "Impossible de confirmer : cette réservation a déjà le statut '{$reservation->status}' et ne peut plus être modifiée.",
                ], 422);
            }

            // Vérification de la disponibilité de la salle
            if ($reservation->salle && $reservation->salle->status !== 'disponible') {
                return response()->json([
                    'message' => "Impossible de confirmer : la salle '{$reservation->salle->nom}' est actuellement marquée comme indisponible.",
                ], 422);
            }

            // Vérification de la disponibilité et du stock des équipements associés sur le créneau
            foreach ($reservation->equipements as $equipement) {
                if ($equipement->status !== 'disponible' || $equipement->stock_total <= 0) {
                    return response()->json([
                        'message' => "Impossible de confirmer : l'équipement '{$equipement->nom}' est actuellement indisponible ou en rupture de stock.",
                    ], 422);
                }

                $requestedQty = (int) ($equipement->pivot->quantity ?? 1);
                $availableStockOnPeriod = $equipement->getAvailableStockForPeriod(
                    (string) $reservation->date_heure_debut,
                    (string) $reservation->date_heure_fin,
                    $reservation->id
                );

                if ($requestedQty > $availableStockOnPeriod) {
                    return response()->json([
                        'message' => "Impossible de confirmer : stock insuffisant pour l'équipement '{$equipement->nom}' sur ce créneau horaire (restant : {$availableStockOnPeriod}, demandé : {$requestedQty}).",
                    ], 422);
                }
            }

            // Vérifier les conflits éventuels avec une autre réservation déjà confirmée
            $conflict = Reservation::overlapping(
                $reservation->salle_id,
                (string) $reservation->date_heure_debut,
                (string) $reservation->date_heure_fin,
                $reservation->id
            )->where('status', 'confirmee')->first();

            if ($conflict) {
                return response()->json([
                    'message' => 'Impossible de confirmer : un autre événement est déjà confirmé sur ce créneau.',
                ], 422);
            }

            $reservation->status = 'confirmee';
            $reservation->save();

            return response()->json([
                'message' => 'Réservation confirmée avec succès.',
                'data' => new ReservationResource($reservation->load(['salle', 'user', 'createur', 'equipements'])),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la confirmation de la réservation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rejette ou annule une réservation.
     */
    public function rejeter(string $id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);

            if (!$reservation) {
                return response()->json([
                    'message' => 'Réservation introuvable.',
                ], 404);
            }

            $reservation->status = 'rejetee';
            $reservation->save();

            return response()->json([
                'message' => 'Réservation rejetée / annulée avec succès.',
                'data' => new ReservationResource($reservation->load(['salle', 'user', 'createur', 'equipements'])),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors du rejet de la réservation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Termine et clôture une réservation effectuée.
     */
    public function terminer(string $id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);

            if (!$reservation) {
                return response()->json([
                    'message' => 'Réservation introuvable.',
                ], 404);
            }

            $reservation->status = 'terminee';
            $reservation->terminee_at = now();
            $reservation->save();

            return response()->json([
                'message' => 'Réservation clôturée et marquée comme terminée avec succès.',
                'data' => new ReservationResource($reservation->load(['salle', 'user', 'createur', 'equipements'])),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la clôture de la réservation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprime (soft delete) une réservation.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);

            if (!$reservation) {
                return response()->json([
                    'message' => 'Réservation introuvable.',
                    'data' => null,
                ], 404);
            }

            $reservation->delete();

            return response()->json([
                'message' => 'Réservation supprimée avec succès.',
                'data' => null,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression de la réservation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
