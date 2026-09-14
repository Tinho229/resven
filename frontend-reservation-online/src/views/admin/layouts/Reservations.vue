<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AppAdmin from '@/components/admin/AppAdmin.vue'
import { useAdminReservationsStore } from '@/store/adminReservations'
import { useAdminSallesStore } from '@/store/adminSalles'
import {
  Plus,
  Eye,
  Pencil,
  Trash2,
  AlertTriangle,
  RefreshCw,
  Search,
  Check,
  Flag,
  X,
} from 'lucide-vue-next'

const adminReservationsStore = useAdminReservationsStore()
const adminSallesStore = useAdminSallesStore()

const search = ref('')
const status = ref('')
const selectedSalle = ref('')
const sortOrder = ref('desc') // 'desc' ou 'asc'

// Modale de confirmation de suppression
const isDeleteModalOpen = ref(false)
const reservationToDelete = ref(null)
const isDeleting = ref(false)

const loadReservations = async () => {
  try {
    const params = {
      all: 'true',
    }
    if (status.value) {
      params.status = status.value
    }
    if (selectedSalle.value) {
      params.salle_id = selectedSalle.value
    }
    if (search.value) {
      params.search = search.value
    }
    await adminReservationsStore.fetchReservations(params)
  } catch (error) {
    console.error('Erreur chargement réservations :', error)
  }
}

onMounted(async () => {
  await Promise.all([
    loadReservations(),
    adminSallesStore.salles.length === 0 ? adminSallesStore.fetchSalles({ all: 'true' }) : Promise.resolve(),
  ])
})

const handleSearch = () => {
  loadReservations()
}

const handleStatusChange = () => {
  loadReservations()
}

const handleSalleChange = () => {
  loadReservations()
}

const filteredReservations = computed(() => {
  const result = [...adminReservationsStore.reservations]

  result.sort((a, b) => {
    return sortOrder.value === 'desc' ? b.id - a.id : a.id - b.id
  })

  return result
})

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  try {
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date)
  } catch {
    return dateString
  }
}

const getStatusBadge = (st, dateDebut) => {
  switch (st) {
    case 'confirmee':
      return { label: 'Confirmée', class: 'bg-emerald-100 text-emerald-700' }
    case 'en_attente':
      if (dateDebut && new Date(dateDebut) <= new Date()) {
        return { label: 'Expirée (Rejetée)', class: 'bg-rose-100 text-rose-700' }
      }
      return { label: 'En attente', class: 'bg-amber-100 text-amber-700' }
    case 'terminee':
      return { label: 'Terminée', class: 'bg-slate-100 text-slate-700' }
    case 'rejetee':
      return { label: 'Rejetée', class: 'bg-rose-100 text-rose-700' }
    default:
      return { label: st || 'Inconnu', class: 'bg-gray-100 text-gray-700' }
  }
}

const handleConfirm = async (id) => {
  try {
    await adminReservationsStore.confirmReservation(id)
    await loadReservations()
  } catch (e) {
    console.error('Erreur confirmation :', e)
    await loadReservations()
  }
}

const handleReject = async (id) => {
  try {
    await adminReservationsStore.rejectReservation(id)
    await loadReservations()
  } catch (e) {
    console.error('Erreur rejet :', e)
  }
}

const handleTerminate = async (id) => {
  try {
    await adminReservationsStore.terminateReservation(id)
    await loadReservations()
  } catch (e) {
    console.error('Erreur clôture :', e)
  }
}

const openDeleteModal = (reservation) => {
  reservationToDelete.value = reservation
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  reservationToDelete.value = null
}

const confirmDelete = async () => {
  if (!reservationToDelete.value) return
  isDeleting.value = true

  try {
    await adminReservationsStore.deleteReservation(reservationToDelete.value.id)
    closeDeleteModal()
  } catch (error) {
    console.error('Erreur lors de la suppression :', error)
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <AppAdmin>
    <div class="min-h-screen bg-[#F8FAFC]">
      <!-- EN-TÊTE DE PAGE -->
      <div class="mb-6 mt-2 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-800">
            Gestion des Réservations
          </h1>
          <p class="mt-1 text-xs text-slate-500">
            Suivez, confirmez, clôturez ou modifiez l'ensemble des créneaux réservés.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="button"
            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 active:scale-95 cursor-pointer"
            @click="loadReservations"
          >
            <RefreshCw :size="14" :class="{ 'animate-spin': adminReservationsStore.loading }" />
            <span>Actualiser</span>
          </button>

          <RouterLink
            :to="{ name: 'create-reservation' }"
            class="inline-flex items-center gap-2 rounded-xl border border-neutral-900 bg-neutral-900 px-4 py-2 text-xs font-medium uppercase tracking-widest text-white shadow-sm transition hover:bg-black active:scale-95"
          >
            <Plus :size="15" />
            <span>Nouvelle réservation</span>
          </RouterLink>
        </div>
      </div>

      <!-- MESSAGES FLASH -->
      <div
        v-if="adminReservationsStore.successMessage"
        class="mb-4 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800"
      >
        <span>{{ adminReservationsStore.successMessage }}</span>
        <button class="font-bold text-emerald-700 hover:text-emerald-900" @click="adminReservationsStore.successMessage = null">
          ×
        </button>
      </div>

      <div
        v-if="adminReservationsStore.errorMessage"
        class="mb-4 flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800"
      >
        <span>{{ adminReservationsStore.errorMessage }}</span>
        <button class="font-bold text-rose-700 hover:text-rose-900" @click="adminReservationsStore.errorMessage = null">
          ×
        </button>
      </div>

      <!-- BARRE DE RECHERCHE & FILTRES (STYLE DIGILAB) -->
      <div class="flex flex-wrap items-center justify-between gap-4 rounded-t-xl border border-b-0 border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-1 flex-wrap items-center gap-3">
          <!-- Recherche -->
          <div class="relative w-64">
            <input
              v-model="search"
              type="text"
              placeholder="Rechercher bénéficiaire, salle..."
              class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 pl-9 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
              @input="handleSearch"
            />
            <Search :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          </div>

          <!-- Filtre Statut -->
          <select
            v-model="status"
            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white"
            @change="handleStatusChange"
          >
            <option value="">Tous les statuts</option>
            <option value="en_attente">En attente</option>
            <option value="confirmee">Confirmée</option>
            <option value="terminee">Terminée</option>
            <option value="rejetee">Rejetée</option>
          </select>

          <!-- Filtre Salle -->
          <select
            v-model="selectedSalle"
            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white"
            @change="handleSalleChange"
          >
            <option value="">Toutes les salles</option>
            <option
              v-for="salle in adminSallesStore.salles"
              :key="salle.id"
              :value="salle.id"
            >
              {{ salle.nom }}
            </option>
          </select>

          <span class="text-xs text-slate-400">
            {{ filteredReservations.length }} réservation(s) affichée(s)
          </span>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <span>Tri :</span>
            <select
              v-model="sortOrder"
              class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700 outline-none focus:border-blue-500"
            >
              <option value="desc">Plus récentes (ID Décroissant)</option>
              <option value="asc">Plus anciennes (ID Croissant)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- TABLEAU DES RÉSERVATIONS (STYLE DIGILAB - 1 INFORMATION PAR COLONNE STRICTEMENT) -->
      <div class="overflow-x-auto rounded-b-xl border border-slate-200 bg-white shadow-sm">
        <!-- CHARGEMENT -->
        <div v-if="adminReservationsStore.loading" class="flex flex-col items-center justify-center py-20">
          <div class="h-8 w-8 animate-spin rounded-full border-3 border-slate-800 border-t-transparent"></div>
          <p class="mt-3 text-xs font-medium text-slate-500">Chargement des réservations...</p>
        </div>

        <!-- LISTE VIDE -->
        <div
          v-else-if="filteredReservations.length === 0"
          class="flex flex-col items-center justify-center py-16 text-center"
        >
          <p class="font-semibold text-slate-800">Aucune réservation trouvée</p>
          <p class="mt-1 text-xs text-slate-400">
            Modifiez vos filtres ou créez une nouvelle réservation.
          </p>
        </div>

        <!-- TABLE -->
        <table v-else class="w-full text-left text-sm whitespace-nowrap">
          <thead class="bg-slate-50 text-xs text-slate-400 uppercase tracking-wider">
            <tr>
              <th class="py-3 px-4 font-medium w-16">ID</th>
              <th class="py-3 px-4 font-medium">Bénéficiaire</th>
              <th class="py-3 px-4 font-medium">Salle</th>
              <th class="py-3 px-4 font-medium">Date début</th>
              <th class="py-3 px-4 font-medium">Date fin</th>
              <th class="py-3 px-4 font-medium">Statut</th>
              <th class="py-3 px-4 font-medium text-right w-36">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-600">
            <tr
              v-for="res in filteredReservations"
              :key="res.id"
              class="hover:bg-slate-50 transition"
            >
              <!-- 1. ID -->
              <td class="py-3.5 px-4 font-mono text-xs text-slate-400">
                #{{ res.id }}
              </td>

              <!-- 2. BÉNÉFICIAIRE -->
              <td class="py-3.5 px-4 font-semibold text-slate-800">
                {{ res.nom_affiche }}
              </td>

              <!-- 3. SALLE -->
              <td class="py-3.5 px-4 font-medium text-slate-700">
                {{ res.salle?.nom || 'Salle #' + res.salle_id }}
              </td>

              <!-- 4. DATE DÉBUT -->
              <td class="py-3.5 px-4 text-xs text-slate-600">
                {{ formatDateTime(res.date_heure_debut) }}
              </td>

              <!-- 5. DATE FIN -->
              <td class="py-3.5 px-4 text-xs text-slate-600">
                {{ formatDateTime(res.date_heure_fin) }}
              </td>

              <!-- 6. STATUT -->
              <td class="py-3.5 px-4">
                <span
                  class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                  :class="getStatusBadge(res.status, res.date_heure_debut).class"
                >
                  • {{ getStatusBadge(res.status, res.date_heure_debut).label }}
                </span>
              </td>

              <!-- 7. ACTIONS -->
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Validation rapide : Confirmer -->
                  <button
                    v-if="res.status === 'en_attente' && new Date(res.date_heure_debut) > new Date()"
                    type="button"
                    title="Confirmer la réservation"
                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-emerald-200 text-emerald-600 transition hover:bg-emerald-50 cursor-pointer"
                    @click="handleConfirm(res.id)"
                  >
                    <Check :size="13" />
                  </button>

                  <!-- Validation rapide : Clôturer -->
                  <button
                    v-if="res.status === 'confirmee'"
                    type="button"
                    title="Marquer terminée"
                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-300 text-slate-700 transition hover:bg-slate-100 cursor-pointer"
                    @click="handleTerminate(res.id)"
                  >
                    <Flag :size="12" />
                  </button>

                  <!-- Consulter -->
                  <RouterLink
                    :to="{ name: 'info-reservation', params: { id: res.id } }"
                    title="Voir le dossier"
                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                  >
                    <Eye :size="13" />
                  </RouterLink>

                  <!-- Modifier -->
                  <RouterLink
                    :to="{ name: 'update-reservation', params: { id: res.id } }"
                    title="Modifier"
                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                  >
                    <Pencil :size="13" />
                  </RouterLink>

                  <!-- Supprimer -->
                  <button
                    type="button"
                    title="Supprimer"
                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-rose-500 transition hover:bg-rose-50 hover:border-rose-300 cursor-pointer"
                    @click="openDeleteModal(res)"
                  >
                    <Trash2 :size="13" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODALE DE CONFIRMATION DE SUPPRESSION -->
    <div
      v-if="isDeleteModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
            <AlertTriangle :size="24" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900">
              Confirmer l'annulation / suppression
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              Êtes-vous sûr de vouloir supprimer la réservation #{{ reservationToDelete?.id }}
              pour <strong class="text-gray-800">{{ reservationToDelete?.nom_affiche }}</strong> ?
            </p>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-xl border border-neutral-300 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900"
            @click="closeDeleteModal"
          >
            Annuler
          </button>

          <button
            type="button"
            :disabled="isDeleting"
            class="rounded-xl border border-rose-600 bg-rose-600 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-white transition hover:bg-rose-700 disabled:opacity-50"
            @click="confirmDelete"
          >
            <span v-if="isDeleting">Suppression...</span>
            <span v-else>Supprimer définitivement</span>
          </button>
        </div>
      </div>
    </div>
  </AppAdmin>
</template>
