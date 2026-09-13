<?php

namespace App\Services;

use Cloudinary;
use Cloudinary\Uploader;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CloudinaryService
{
    protected bool $isConfigured = false;

    public function __construct()
    {
        $cloudinaryUrl = config('services.cloudinary.url') ?: env('CLOUDINARY_URL');

        if (!empty($cloudinaryUrl)) {
            try {
                Cloudinary::config_from_url($cloudinaryUrl);
                $this->isConfigured = true;
            } catch (Exception $e) {
                Log::error("Erreur d'initialisation Cloudinary avec URL : " . $e->getMessage());
                $this->isConfigured = false;
            }
        } elseif (env('CLOUDINARY_CLOUD_NAME') && env('CLOUDINARY_API_KEY') && env('CLOUDINARY_API_SECRET')) {
            try {
                Cloudinary::config([
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key' => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET'),
                    'secure' => true,
                ]);
                $this->isConfigured = true;
            } catch (Exception $e) {
                Log::error("Erreur d'initialisation Cloudinary avec clés séparées : " . $e->getMessage());
                $this->isConfigured = false;
            }
        }
    }

    /**
     * Vérifie si Cloudinary est correctement configuré.
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Upload un fichier vers Cloudinary ou fallback vers le stockage local.
     *
     * @param UploadedFile|string $file
     * @param string $folder Ex: 'salles', 'equipements'
     * @return string Chemin local ou URL sécurisée Cloudinary
     */
    public function upload(UploadedFile|string $file, string $folder = 'uploads'): string
    {
        if ($this->isConfigured()) {
            try {
                $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

                $result = Uploader::upload($filePath, [
                    'folder' => 'rapid-reservation/' . trim($folder, '/'),
                    'resource_type' => 'image',
                ]);

                if (!empty($result['secure_url'])) {
                    return $result['secure_url'];
                }

                if (!empty($result['url'])) {
                    return $result['url'];
                }
            } catch (Exception $e) {
                Log::error("Échec de l'upload Cloudinary, repli sur le stockage local : " . $e->getMessage());
            }
        }

        // Repli sur le stockage local si Cloudinary non configuré ou échec
        if ($file instanceof UploadedFile) {
            return $file->store('images/' . trim($folder, '/'), 'public');
        }

        return $file;
    }

    /**
     * Supprime un fichier depuis Cloudinary ou le stockage local.
     *
     * @param string|null $pathOrUrl
     * @return bool
     */
    public function delete(?string $pathOrUrl): bool
    {
        if (empty($pathOrUrl)) {
            return false;
        }

        // Cas Cloudinary
        if ($this->isCloudinaryUrl($pathOrUrl)) {
            try {
                $publicId = $this->extractPublicId($pathOrUrl);
                if ($publicId) {
                    Uploader::destroy($publicId);
                    return true;
                }
            } catch (Exception $e) {
                Log::warning("Impossible de supprimer le fichier Cloudinary : " . $e->getMessage());
                return false;
            }
        }

        // Cas stockage local
        if (Storage::disk('public')->exists($pathOrUrl)) {
            return Storage::disk('public')->delete($pathOrUrl);
        }

        return false;
    }

    /**
     * Détermine si l'URL provient de Cloudinary.
     */
    public function isCloudinaryUrl(string $url): bool
    {
        return str_contains($url, 'cloudinary.com');
    }

    /**
     * Extrait le public_id d'une URL Cloudinary.
     */
    public function extractPublicId(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        // Pattern pour capturer le chemin après /image/upload/...
        if (preg_match('#/image/upload/(?:[a-z]_[^/]+,?)*/?(?:v\d+/)?(.+?)(?:\.[a-zA-Z0-9]+)?$#i', $path, $matches)) {
            $publicId = $matches[1];
            // Nettoyer si préfixé par un numéro de version restant
            return preg_replace('#^v\d+/#', '', $publicId);
        }

        return null;
    }
}
