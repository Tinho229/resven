<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipement;
use App\Models\Image;
use App\Models\Reservation;
use App\Models\Salle;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Retourne toutes les métriques et données du tableau de bord admin.
     */
    public function index(): JsonResponse
    {
        try {
            Reservation::actualiserStatutsAutomatiques();

            // 1. Statistiques globales (cartes du haut)
            $totalUtilisateurs = User::where('role', 'user')->count();
            $totalResponsables = User::where('role', 'responsable')->count();
            $totalAdmins = User::where('role', 'admin')->count();
            $totalUsers = User::count();

            $totalSalles = Salle::count();
            $sallesDisponibles = Salle::where('status', 'disponible')->count();
            $sallesIndisponibles = Salle::where('status', '!=', 'disponible')->count();

            $totalReservations = Reservation::count();
            $totalEquipements = Equipement::count();
            $totalImages = Image::count();

            // 2. Réservations par statut
            $statusCounts = [
                'en_attente' => Reservation::where('status', 'en_attente')->count(),
                'confirmee' => Reservation::where('status', 'confirmee')->count(),
                'rejetee' => Reservation::where('status', 'rejetee')->count(),
                'terminee' => Reservation::where('status', 'terminee')->count(),
                'annulee' => Reservation::where('status', 'annulee')->count(),
            ];

            // 3. Réservations par mois sur l'année en cours
            $currentYear = Carbon::now()->year;
            $moisLabels = [
                1 => 'Janvier',
                2 => 'Février',
                3 => 'Mars',
                4 => 'Avril',
                5 => 'Mai',
                6 => 'Juin',
                7 => 'Juillet',
                8 => 'Août',
                9 => 'Septembre',
                10 => 'Octobre',
                11 => 'Novembre',
                12 => 'Décembre',
            ];

            // Grouper par mois
            $reservationsParMoisRaw = Reservation::select(
                DB::raw('MONTH(date_heure_debut) as mois'),
                DB::raw('COUNT(*) as total')
            )
                ->whereYear('date_heure_debut', $currentYear)
                ->groupBy('mois')
                ->pluck('total', 'mois')
                ->toArray();

            $reservationsParMois = [];
            foreach ($moisLabels as $mNum => $mLabel) {
                $reservationsParMois[] = [
                    'mois_num' => $mNum,
                    'mois' => $mLabel,
                    'total' => (int) ($reservationsParMoisRaw[$mNum] ?? 0),
                ];
            }

            // 4. Réservations récentes (6 dernières)
            $recentReservations = Reservation::with(['salle', 'user'])
                ->latest('created_at')
                ->take(6)
                ->get()
                ->map(function ($res) {
                    return [
                        'id' => $res->id,
                        'client_nom' => $res->nom_affiche,
                        'client_telephone' => $res->telephone_affiche,
                        'salle_nom' => $res->salle?->nom ?? 'Salle #' . $res->salle_id,
                        'salle_localisation' => $res->salle?->localisation,
                        'date_heure_debut' => $res->date_heure_debut?->format('Y-m-d H:i:s'),
                        'date_heure_fin' => $res->date_heure_fin?->format('Y-m-d H:i:s'),
                        'nombre_personnes' => $res->nombre_personnes,
                        'status' => $res->status,
                        'created_at' => $res->created_at?->format('Y-m-d H:i:s'),
                    ];
                });

            // 5. Liste des salles avec caractéristiques
            $sallesList = Salle::with(['images' => fn($q) => $q->take(2)])
                ->withCount(['reservations as reservations_actives_count' => fn($q) => $q->whereIn('status', ['en_attente', 'confirmee'])])
                ->get();

            // 6. Liste des équipements avec caractéristiques
            $equipementsList = Equipement::get();

            // 7. Liste des images récentes avec salle
            $imagesList = Image::with('salle')->latest()->take(10)->get();

            return response()->json([
                'success' => true,
                'message' => 'Données du tableau de bord récupérées avec succès.',
                'data' => [
                    'cartes' => [
                        'total_utilisateurs' => $totalUtilisateurs,
                        'total_responsables' => $totalResponsables,
                        'total_admins' => $totalAdmins,
                        'total_users_all' => $totalUsers,
                        'total_salles' => $totalSalles,
                        'salles_disponibles' => $sallesDisponibles,
                        'salles_indisponibles' => $sallesIndisponibles,
                        'total_reservations' => $totalReservations,
                        'total_equipements' => $totalEquipements,
                        'total_images' => $totalImages,
                    ],
                    'reservations_par_statut' => $statusCounts,
                    'reservations_par_mois' => $reservationsParMois,
                    'recent_reservations' => $recentReservations,
                    'salles' => $sallesList,
                    'equipements' => $equipementsList,
                    'images' => $imagesList,
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données du tableau de bord.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
