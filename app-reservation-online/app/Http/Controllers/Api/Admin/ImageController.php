<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreImageRequest;
use App\Http\Requests\Admin\UpdateImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Models\Salle;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function __construct(
        protected CloudinaryService $cloudinaryService
    ) {}

    /**
     * Liste toutes les images avec filtres (recherche, salle) et pagination.
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $query = Image::query()->with('salle')->latest();

            // Filtrer par salle
            if ($request->filled('salle_id')) {
                $query->forSalle((int) $request->salle_id);
            }

            // Recherche par nom ou désignation
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Récupérer sans pagination si demandé
            if ($request->get('all') === 'true' || $request->get('paginate') === 'false') {
                $images = $query->get();
                return response()->json([
                    'message' => 'Liste des images récupérée avec succès.',
                    'data' => ImageResource::collection($images),
                ], 200);
            }

            $perPage = (int) $request->get('per_page', 15);
            $images = $query->paginate($perPage);

            return response()->json([
                'message' => 'Liste des images récupérée avec succès.',
                'data' => ImageResource::collection($images),
                'meta' => [
                    'current_page' => $images->currentPage(),
                    'last_page' => $images->lastPage(),
                    'per_page' => $images->perPage(),
                    'total' => $images->total(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des images.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liste toutes les images d'une salle spécifique.
     */
    public function bySalle(string $salleId): JsonResponse
    {
        try {
            $salle = Salle::find($salleId);

            if (!$salle) {
                return response()->json([
                    'message' => 'Salle non trouvée.',
                    'data' => null,
                ], 404);
            }

            $images = Image::query()
                ->where('salle_id', $salleId)
                ->with('salle')
                ->latest()
                ->get();

            return response()->json([
                'message' => "Images de la salle '{$salle->nom}' récupérées avec succès.",
                'data' => ImageResource::collection($images),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des images de la salle.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enregistre une nouvelle image pour une salle.
     */
    public function store(StoreImageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $path = $validated['path'] ?? null;

            // Gestion de l'upload de l'image via Cloudinary (ou stockage local si non configuré)
            if ($request->hasFile('image')) {
                $path = $this->cloudinaryService->upload($request->file('image'), 'salles');
            }

            $image = Image::create([
                'nom' => $validated['nom'],
                'path' => $path,
                'designation' => $validated['designation'] ?? null,
                'salle_id' => $validated['salle_id'],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Image ajoutée avec succès.',
                'data' => new ImageResource($image->load('salle')),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            // Supprimer le fichier uploadé en cas d'échec SQL
            if (!empty($path)) {
                $this->cloudinaryService->delete($path);
            }

            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'enregistrement de l\'image.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche les détails d'une image spécifique.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $image = Image::with('salle')->find($id);

            if (!$image) {
                return response()->json([
                    'message' => 'Image non trouvée.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'message' => 'Détails de l\'image récupérés avec succès.',
                'data' => new ImageResource($image),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération de l\'image.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Met à jour les informations ou remplace le fichier d'une image.
     */
    public function update(UpdateImageRequest $request, string $id): JsonResponse
    {
        $image = Image::find($id);

        if (!$image) {
            return response()->json([
                'message' => 'Image non trouvée.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();
        $oldPath = $image->path;
        $newPath = null;

        DB::beginTransaction();
        try {
            // Remplacement du fichier si une nouvelle image est uploadée
            if ($request->hasFile('image')) {
                $newPath = $this->cloudinaryService->upload($request->file('image'), 'salles');
                $image->path = $newPath;
            } elseif (array_key_exists('path', $validated) && !empty($validated['path'])) {
                $image->path = $validated['path'];
            }

            if (isset($validated['nom'])) {
                $image->nom = $validated['nom'];
            }

            if (array_key_exists('designation', $validated)) {
                $image->designation = $validated['designation'];
            }

            if (isset($validated['salle_id'])) {
                $image->salle_id = $validated['salle_id'];
            }

            $image->save();

            DB::commit();

            // Si un nouveau fichier a été uploadé avec succès et que l'ancien était différent, supprimer l'ancien
            if ($newPath && $oldPath && $newPath !== $oldPath) {
                $this->cloudinaryService->delete($oldPath);
            }

            return response()->json([
                'message' => 'Image mise à jour avec succès.',
                'data' => new ImageResource($image->fresh(['salle'])),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            // Supprimer le nouveau fichier uploadé en cas d'erreur
            if ($newPath) {
                $this->cloudinaryService->delete($newPath);
            }

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'image.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprime une image.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $image = Image::find($id);

            if (!$image) {
                return response()->json([
                    'message' => 'Image non trouvée.',
                    'data' => null,
                ], 404);
            }

            $oldPath = $image->path;
            $image->delete();

            if (!empty($oldPath)) {
                $this->cloudinaryService->delete($oldPath);
            }

            return response()->json([
                'message' => 'Image supprimée avec succès.',
                'data' => null,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression de l\'image.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
