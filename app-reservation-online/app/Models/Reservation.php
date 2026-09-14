<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Reservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'date_heure_debut',
        'date_heure_fin',
        'nombre_personnes',
        'status',
        'user_id',
        'salle_id',
        'terminee_at',
        'nom_client',
        'telephone_client',
        'cree_par_id',
    ];


    protected $casts = [
        'date_heure_debut' => 'datetime',
        'date_heure_fin' => 'datetime',
        'terminee_at' => 'datetime',
        'nombre_personnes' => 'integer',
        'user_id' => 'integer',
        'salle_id' => 'integer',
        'cree_par_id' => 'integer',
    ];

    protected $appends = ['nom_demandeur'];

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class, 'salle_id');
    }


    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par_id');
    }


    public function equipements(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipement::class,
            'equipement_reservation'
        )->withPivot('quantity');
    }


    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par_id');
    }

    public function getNomDemandeurAttribute(): string
    {
        return $this->user?->nom ?? $this->nom_client ?? 'Client sans compte';
    }

    public function getNomAfficheAttribute(): string
    {
        if ($this->user) {
            return $this->user->nom;
        }

        return $this->nom_client ?? 'Client inconnu';
    }

    /**
     * Téléphone effectif du client.
     */


    public function getTelephoneAfficheAttribute(): ?string
    {
        if ($this->user && !empty($this->user->telephone)) {
            return $this->user->telephone;
        }

        return $this->telephone_client;
    }

    /**
     * Scope pour filtrer par statut (en_attente, confirmee, rejetee, terminee).
     */
    public function scopeStatus($query, ?string $status)
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer par salle.
     */
    public function scopeForSalle($query, ?int $salleId)
    {
        if (empty($salleId)) {
            return $query;
        }

        return $query->where('salle_id', $salleId);
    }

    /**
     * Scope pour filtrer par utilisateur inscrit.
     */
    public function scopeForUser($query, ?int $userId)
    {
        if (empty($userId)) {
            return $query;
        }

        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour rechercher par nom de client, téléphone, ou nom de salle.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('nom_client', 'like', "%{$search}%")
                ->orWhere('telephone_client', 'like', "%{$search}%")
                ->orWhereHas('user', function ($u) use ($search) {
                    $u->where('nom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                })
                ->orWhereHas('salle', function ($s) use ($search) {
                    $s->where('nom', 'like', "%{$search}%")
                        ->orWhere('localisation', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Scope pour vérifier les conflits de créneaux sur une salle donnée.
     */
    public function scopeOverlapping($query, int $salleId, string $debut, string $fin, ?int $excludeId = null)
    {
        return $query->where('salle_id', $salleId)
            ->whereIn('status', ['en_attente', 'confirmee'])
            ->where('date_heure_debut', '<', $fin)
            ->where('date_heure_fin', '>', $debut)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
    }

    /**
     * Rejette automatiquement toutes les réservations en attente dont le délai a expiré (date_heure_debut <= now()).
     */
    public static function rejeterReservationsExpirees(): int
    {
        return static::where('status', 'en_attente')
            ->where('date_heure_debut', '<=', now())
            ->update(['status' => 'rejetee']);
    }

    /**
     * Clôture automatiquement toutes les réservations confirmées dont la durée est écoulée (date_heure_fin <= now()).
     */
    public static function terminerReservationsPassees(): int
    {
        return static::where('status', 'confirmee')
            ->where('date_heure_fin', '<=', now())
            ->update([
                'status' => 'terminee',
                'terminee_at' => now(),
            ]);
    }

    /**
     * Actualise les statuts automatiques (rejet des expirées et clôture des confirmées écoulées).
     */
    public static function actualiserStatutsAutomatiques(): array
    {
        return [
            'rejetees' => static::rejeterReservationsExpirees(),
            'terminees' => static::terminerReservationsPassees(),
        ];
    }

    /**
     * Vérifie si la date de début de la réservation est passée.
     */
    public function isExpired(): bool
    {
        return $this->date_heure_debut && $this->date_heure_debut->isPast();
    }

    /**
     * Vérifie si la date de fin de la réservation est passée (durée écoulée).
     */
    public function isFinished(): bool
    {
        return $this->date_heure_fin && $this->date_heure_fin->isPast();
    }
}

