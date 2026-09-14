<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AppAdmin from '@/components/admin/AppAdmin.vue'
import { useAdminImagesStore } from '@/store/adminImages'
import { useAdminSallesStore } from '@/store/adminSalles'
import {
  Plus,
  Eye,
  Pencil,
  Trash2,
  AlertTriangle,
  RefreshCw,
  Search,
  LayoutGrid,
  List,
  Image as ImageIcon,
  DoorOpen,
} from 'lucide-vue-next'

const adminImagesStore = useAdminImagesStore()
const adminSallesStore = useAdminSallesStore()

const search = ref('')
const selectedSalle = ref('')
const sortOrder = ref('desc') // 'desc' ou 'asc'
const viewMode = ref('table') // 'table' par défaut pour le design demandé ou 'grid'

// Modale de confirmation de suppression
const isDeleteModalOpen = ref(false)
const imageToDelete = ref(null)
const isDeleting = ref(false)

const loadImages = async () => {
  try {
    const params = {
      all: 'true',
    }
    if (selectedSalle.value) {
      params.salle_id = selectedSalle.value
    }
    if (search.value) {
      params.search = search.value
    }
    await adminImagesStore.fetchImages(params)
  } catch (error) {
    console.error('Erreur chargement images :', error)
  }
}

onMounted(async () => {
  await Promise.all([
    loadImages(),
    adminSallesStore.salles.length === 0 ? adminSallesStore.fetchSalles({ all: 'true' }) : Promise.resolve(),
  ])
})

const handleSearch = () => {
  loadImages()
}

const handleSalleChange = () => {
  loadImages()
}

const filteredImages = computed(() => {
  const result = [...adminImagesStore.images]

  result.sort((a, b) => {
    return sortOrder.value === 'desc' ? b.id - a.id : a.id - b.id
  })

  return result
})

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  try {
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(date)
  } catch {
    return dateString
  }
}

const openDeleteModal = (img) => {
  imageToDelete.value = img
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  imageToDelete.value = null
}

const confirmDelete = async () => {
  if (!imageToDelete.value) return
  isDeleting.value = true

  try {
    await adminImagesStore.deleteImage(imageToDelete.value.id)
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
            Galerie & Médias
          </h1>
          <p class="mt-1 text-xs text-slate-500">
            Gérez les visuels, photos de couverture et médias associés à vos salles.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="button"
            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 active:scale-95 cursor-pointer"
            @click="loadImages"
          >
            <RefreshCw :size="14" :class="{ 'animate-spin': adminImagesStore.loading }" />
            <span>Actualiser</span>
          </button>

          <RouterLink
            :to="{ name: 'create-image' }"
            class="inline-flex items-center gap-2 rounded-xl border border-neutral-900 bg-neutral-900 px-4 py-2 text-xs font-medium uppercase tracking-widest text-white shadow-sm transition hover:bg-black active:scale-95"
          >
            <Plus :size="15" />
            <span>Ajouter une image</span>
          </RouterLink>
        </div>
      </div>

      <!-- MESSAGES FLASH -->
      <div
        v-if="adminImagesStore.successMessage"
        class="mb-4 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800"
      >
        <span>{{ adminImagesStore.successMessage }}</span>
        <button class="font-bold text-emerald-700 hover:text-emerald-900" @click="adminImagesStore.successMessage = null">
          ×
        </button>
      </div>

      <div
        v-if="adminImagesStore.errorMessage"
        class="mb-4 flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800"
      >
        <span>{{ adminImagesStore.errorMessage }}</span>
        <button class="font-bold text-rose-700 hover:text-rose-900" @click="adminImagesStore.errorMessage = null">
          ×
        </button>
      </div>

      <!-- BARRE DE RECHERCHE & FILTRES RESPONSIVE -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4 rounded-t-xl border border-b-0 border-slate-200 bg-white p-3.5 sm:p-4 shadow-sm">
        <div class="flex flex-1 flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 sm:gap-3">
          <!-- Recherche -->
          <div class="relative w-full sm:w-64">
            <input
              v-model="search"
              type="text"
              placeholder="Rechercher une photo..."
              class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 sm:py-1.5 pl-9 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
              @input="handleSearch"
            />
            <Search :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          </div>

          <!-- Filtre Salle -->
          <select
            v-model="selectedSalle"
            class="w-full sm:w-auto rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 sm:py-1.5 text-sm text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white"
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

          <!-- Toggle vue Tableau / Grille -->
          <div class="flex items-center self-start sm:self-auto rounded-lg border border-slate-200 bg-slate-50 p-0.5">
            <button
              type="button"
              class="flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold transition cursor-pointer"
              :class="viewMode === 'table' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
              @click="viewMode = 'table'"
            >
              <List :size="13" />
              <span>Tableau</span>
            </button>
            <button
              type="button"
              class="flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold transition cursor-pointer"
              :class="viewMode === 'grid' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
              @click="viewMode = 'grid'"
            >
              <LayoutGrid :size="13" />
              <span>Grille</span>
            </button>
          </div>

          <span class="text-xs text-slate-400 sm:self-center">
            {{ filteredImages.length }} image(s)
          </span>
        </div>

        <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <span>Tri :</span>
            <select
              v-model="sortOrder"
              class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 sm:py-1 text-xs font-medium text-slate-700 outline-none focus:border-blue-500"
            >
              <option value="desc">Plus récentes</option>
              <option value="asc">Plus anciennes</option>
            </select>
          </div>
        </div>
      </div>

      <!-- VUE GRILLE OPTIONNELLE -->
      <div v-if="viewMode === 'grid'" class="rounded-b-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div
          v-if="filteredImages.length === 0"
          class="flex flex-col items-center justify-center py-16 text-center"
        >
          <p class="font-semibold text-slate-800">Aucune photo trouvée</p>
          <p class="mt-1 text-xs text-slate-400">Modifiez vos critères de recherche ou ajoutez une nouvelle image.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          <div
            v-for="img in filteredImages"
            :key="img.id"
            class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs transition hover:shadow-md"
          >
            <div class="relative h-44 w-full overflow-hidden bg-slate-100">
              <img
                v-if="img.url"
                :src="img.url"
                :alt="img.nom"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
              />
              <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                <ImageIcon :size="32" />
              </div>
            </div>
            <div class="p-3">
              <p class="font-semibold text-slate-800 truncate text-xs">{{ img.nom }}</p>
              <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ img.salle?.nom || 'Salle non définie' }}</p>
              <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2 text-[11px] text-slate-400">
                <span>{{ formatDate(img.created_at) }}</span>
                <div class="flex items-center gap-1">
                  <RouterLink :to="{ name: 'info-image', params: { id: img.id } }" class="p-1 text-slate-500 hover:text-slate-800">
                    <Eye :size="13" />
                  </RouterLink>
                  <RouterLink :to="{ name: 'update-image', params: { id: img.id } }" class="p-1 text-slate-500 hover:text-slate-800">
                    <Pencil :size="13" />
                  </RouterLink>
                  <button type="button" @click="openDeleteModal(img)" class="p-1 text-rose-500 hover:text-rose-700">
                    <Trash2 :size="13" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TABLEAU DES IMAGES (STYLE DIGILAB - 1 INFORMATION PAR COLONNE STRICTEMENT) -->
      <div v-else class="overflow-x-auto rounded-b-xl border border-slate-200 bg-white shadow-sm">
        <!-- CHARGEMENT -->
        <div v-if="adminImagesStore.loading" class="flex flex-col items-center justify-center py-20">
          <div class="h-8 w-8 animate-spin rounded-full border-3 border-slate-800 border-t-transparent"></div>
          <p class="mt-3 text-xs font-medium text-slate-500">Chargement de la galerie...</p>
        </div>

        <!-- LISTE VIDE -->
        <div
          v-else-if="filteredImages.length === 0"
          class="flex flex-col items-center justify-center py-16 text-center"
        >
          <p class="font-semibold text-slate-800">Aucune photo trouvée</p>
          <p class="mt-1 text-xs text-slate-400">
            Modifiez vos filtres ou téléversez une nouvelle photo.
          </p>
        </div>

        <!-- TABLE -->
        <table v-else class="w-full text-left text-sm whitespace-nowrap">
          <thead class="bg-slate-50 text-xs text-slate-400 uppercase tracking-wider">
            <tr>
              <th class="py-3 px-4 font-medium w-16">ID</th>
              <th class="py-3 px-4 font-medium w-24 text-center">Aperçu</th>
              <th class="py-3 px-4 font-medium">Nom de l'image</th>
              <th class="py-3 px-4 font-medium">Salle associée</th>
              <th class="py-3 px-4 font-medium text-right w-28">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-600">
            <tr
              v-for="img in filteredImages"
              :key="img.id"
              class="hover:bg-slate-50 transition"
            >
              <!-- 1. ID -->
              <td class="py-3.5 px-4 font-mono text-xs text-slate-400">
                #{{ img.id }}
              </td>

              <!-- 2. APERÇU -->
              <td class="py-3.5 px-4 text-center">
                <div class="mx-auto h-11 w-16 flex-shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                  <img
                    v-if="img.url"
                    :src="img.url"
                    :alt="img.nom"
                    class="h-full w-full object-cover"
                  />
                  <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                    <ImageIcon :size="16" />
                  </div>
                </div>
              </td>

              <!-- 3. NOM -->
              <td class="py-3.5 px-4 font-semibold text-slate-800">
                {{ img.nom }}
              </td>

              <!-- 4. SALLE ASSOCIÉE -->
              <td class="py-3.5 px-4 text-xs font-medium text-slate-700">
                {{ img.salle?.nom || 'Salle #' + img.salle_id }}
              </td>

              <!-- 5. ACTIONS -->
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <RouterLink
                    :to="{ name: 'info-image', params: { id: img.id } }"
                    title="Voir les détails"
                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                  >
                    <Eye :size="13" />
                  </RouterLink>

                  <RouterLink
                    :to="{ name: 'update-image', params: { id: img.id } }"
                    title="Modifier"
                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                  >
                    <Pencil :size="13" />
                  </RouterLink>

                  <button
                    type="button"
                    title="Supprimer"
                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-rose-500 transition hover:bg-rose-50 hover:border-rose-300 cursor-pointer"
                    @click="openDeleteModal(img)"
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
              Confirmer la suppression
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              Êtes-vous sûr de vouloir supprimer l'image
              <strong class="text-gray-800">{{ imageToDelete?.nom }}</strong> ?
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
