<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEquipementRequest;
use App\Http\Requests\Admin\UpdateEquipementRequest;
use App\Http\Resources\EquipementResource;
use App\Models\Equipement;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EquipementController extends Controller
{
    public function __construct(
        protected CloudinaryService $cloudinaryService
    ) {}

    /**
     * Liste tous les équipements avec filtres et pagination.
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $query = Equipement::query()->withCount('reservations')->latest();

            // Filtre par statut
            if ($request->filled('status')) {
                $query->status($request->status);
            }

            // Recherche par nom ou description
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filtre par stock minimum
            if ($request->filled('min_stock')) {
                $query->where('stock_total', '>=', (int) $request->min_stock);
            }

            // Sans pagination si demandé
            if ($request->get('all') === 'true' || $request->get('paginate') === 'false') {
                $equipements = $query->get();
                return response()->json([
                    'message' => 'Liste des équipements récupérée avec succès.',
                    'data' => EquipementResource::collection($equipements),
                ], 200);
            }

            $perPage = (int) $request->get('per_page', 15);
            $equipements = $query->paginate($perPage);

            return response()->json([
                'message' => 'Liste des équipements récupérée avec succès.',
                'data' => EquipementResource::collection($equipements),
                'meta' => [
                    'current_page' => $equipements->currentPage(),
                    'last_page' => $equipements->lastPage(),
                    'per_page' => $equipements->perPage(),
                    'total' => $equipements->total(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des équipements.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enregistre un nouvel équipement.
     */
    public function store(StoreEquipementRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $imagePath = null;

            // Upload de l'image via Cloudinary (ou fallback local) si fournie
            if ($request->hasFile('image')) {
                $imagePath = $this->cloudinaryService->upload($request->file('image'), 'equipements');
            }

            $equipement = Equipement::create([
                'nom' => $validated['nom'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 'disponible',
                'stock_total' => $validated['stock_total'],
                'image' => $imagePath,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Équipement créé avec succès.',
                'data' => new EquipementResource($equipement),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            // Supprimer le fichier uploadé en cas d'erreur
            if (!empty($imagePath)) {
                $this->cloudinaryService->delete($imagePath);
            }

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de l\'équipement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche les détails d'un équipement spécifique.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $equipement = Equipement::withCount('reservations')->find($id);

            if (!$equipement) {
                return response()->json([
                    'message' => 'Équipement non trouvé.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'message' => 'Détails de l\'équipement récupérés avec succès.',
                'data' => new EquipementResource($equipement),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération de l\'équipement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Met à jour les informations d'un équipement.
     */
    public function update(UpdateEquipementRequest $request, string $id): JsonResponse
    {
        $equipement = Equipement::find($id);

        if (!$equipement) {
            return response()->json([
                'message' => 'Équipement non trouvé.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();
        $oldImage = $equipement->image;
        $newImage = null;

        DB::beginTransaction();
        try {
            // Upload nouvelle image via Cloudinary si envoyée
            if ($request->hasFile('image')) {
                $newImage = $this->cloudinaryService->upload($request->file('image'), 'equipements');
                $equipement->image = $newImage;
            }

            if (isset($validated['nom'])) {
                $equipement->nom = $validated['nom'];
            }

            if (array_key_exists('description', $validated)) {
                $equipement->description = $validated['description'];
            }

            if (isset($validated['status'])) {
                $equipement->status = $validated['status'];
            }

            if (isset($validated['stock_total'])) {
                $equipement->stock_total = $validated['stock_total'];
            }

            $equipement->save();

            DB::commit();

            // Supprimer l'ancienne image si remplacée
            if ($newImage && $oldImage && $newImage !== $oldImage) {
                $this->cloudinaryService->delete($oldImage);
            }

            return response()->json([
                'message' => 'Équipement mis à jour avec succès.',
                'data' => new EquipementResource($equipement->loadCount('reservations')),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            // Supprimer la nouvelle image si l'opération a échoué
            if (!empty($newImage)) {
                $this->cloudinaryService->delete($newImage);
            }

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'équipement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprime un équipement.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $equipement = Equipement::find($id);

            if (!$equipement) {
                return response()->json([
                    'message' => 'Équipement non trouvé.',
                    'data' => null,
                ], 404);
            }

            $oldImage = $equipement->image;
            $equipement->delete();

            if (!empty($oldImage)) {
                $this->cloudinaryService->delete($oldImage);
            }

            return response()->json([
                'message' => 'Équipement supprimé avec succès.',
                'data' => null,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression de l\'équipement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
