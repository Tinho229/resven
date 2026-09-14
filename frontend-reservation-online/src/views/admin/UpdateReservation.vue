<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import AppAdmin from '@/components/admin/AppAdmin.vue'
import { useAdminReservationsStore } from '@/store/adminReservations'
import { useAdminSallesStore } from '@/store/adminSalles'
import { useAdminUsersStore } from '@/store/adminUsers'
import { useAdminEquipementsStore } from '@/store/adminEquipements'
import { toInputDateTime, toApiDateTime } from '@/helpers/dateHelper'
import {
  ArrowLeft,
  Calendar,
  Clock,
  MapPin,
  Users,
  Package,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Building2,
  Plus,
  Minus,
  Trash2,
  Pencil,
  Save,
  Info,
  User,
  Phone,
  Sparkles,
  Check,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const adminReservationsStore = useAdminReservationsStore()
const adminSallesStore = useAdminSallesStore()
const adminUsersStore = useAdminUsersStore()
const adminEquipementsStore = useAdminEquipementsStore()

const reservationId = route.params.id

const isFetching = ref(true)
const fetchError = ref(null)
const isSubmitting = ref(false)
const beneficiaryType = ref('user') // 'user' ou 'direct'

const form = reactive({
  salle_id: null,
  user_id: null,
  nom_client: '',
  telephone: '',
  date_heure_debut: '',
  date_heure_fin: '',
  nombre_personnes: 1,
  status: 'en_attente',
})

const selectedEquipements = ref([]) // [{ equipement_id, nom, quantity, stock_total }]

const defaultPlaceholder =
  'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'

onMounted(async () => {
  adminReservationsStore.clearErrors()
  try {
    isFetching.value = true
    fetchError.value = null

    const [res] = await Promise.all([
      adminReservationsStore.fetchReservation(reservationId),
      adminSallesStore.fetchSalles({ all: 'true' }),
      adminUsersStore.fetchUsers({ all: 'true' }),
      adminEquipementsStore.fetchEquipements({ all: 'true' }),
    ])

    if (res) {
      if (res.user_id) {
        beneficiaryType.value = 'user'
        form.user_id = Number(res.user_id)
      } else {
        beneficiaryType.value = 'direct'
        form.nom_client = res.nom_affiche || res.nom_client || ''
        form.telephone = res.telephone_affiche || res.telephone_client || res.telephone || ''
      }

      form.salle_id = Number(res.salle_id)
      form.date_heure_debut = toInputDateTime(res.date_heure_debut)
      form.date_heure_fin = toInputDateTime(res.date_heure_fin)
      form.nombre_personnes = Number(res.nombre_personnes) || 1
      form.status = res.status || 'en_attente'

      if (res.equipements && Array.isArray(res.equipements)) {
        selectedEquipements.value = res.equipements.map((eq) => ({
          equipement_id: eq.id,
          nom: eq.nom,
          quantity: eq.pivot?.quantity || eq.quantity || 1,
          stock_total: eq.stock_total || 99,
        }))
      }
    }
  } catch (error) {
    fetchError.value =
      adminReservationsStore.errorMessage || 'Impossible de charger les données de cette réservation.'
    console.error('Erreur chargement réservation :', error)
  } finally {
    isFetching.value = false
  }
})

// Computeds
const salles = computed(() => adminSallesStore.salles || [])
const users = computed(() => adminUsersStore.users || [])
const allEquipements = computed(() => adminEquipementsStore.equipements || [])

const selectedSalle = computed(() => {
  if (!form.salle_id) return null
  return salles.value.find((s) => s.id === Number(form.salle_id)) || null
})

const salleCoverUrl = computed(() => {
  if (!selectedSalle.value) return defaultPlaceholder
  const imgs = selectedSalle.value.images
  if (imgs && imgs.length > 0) {
    return imgs[0].url || imgs[0].path || defaultPlaceholder
  }
  return defaultPlaceholder
})

const formatPrice = (p) =>
  p != null ? new Intl.NumberFormat('fr-FR').format(p) + ' FCFA' : 'Sur demande'

const isOverCapacity = computed(() => {
  if (!selectedSalle.value) return false
  return Number(form.nombre_personnes) > selectedSalle.value.capacite
})

const availableEquipementsToAdd = computed(() => {
  const selectedIds = selectedEquipements.value.map((e) => e.equipement_id)
  return allEquipements.value.filter((eq) => !selectedIds.includes(eq.id))
})

// Équipements Handlers
const addEquipement = (eq) => {
  if (selectedEquipements.value.some((e) => e.equipement_id === eq.id)) return
  selectedEquipements.value.push({
    equipement_id: eq.id,
    nom: eq.nom,
    quantity: 1,
    stock_total: eq.stock_total || 99,
  })
}

const removeEquipement = (eqId) => {
  selectedEquipements.value = selectedEquipements.value.filter((e) => e.equipement_id !== eqId)
}

const incrementQty = (item) => {
  if (item.quantity < item.stock_total) item.quantity++
}

const decrementQty = (item) => {
  if (item.quantity > 1) item.quantity--
}

// Mise à jour
const handleUpdateReservation = async () => {
  isSubmitting.value = true
  adminReservationsStore.clearErrors()

  try {
    const payload = {
      salle_id: Number(form.salle_id),
      date_heure_debut: toApiDateTime(form.date_heure_debut),
      date_heure_fin: toApiDateTime(form.date_heure_fin),
      nombre_personnes: Number(form.nombre_personnes),
      status: form.status,
    }

    if (beneficiaryType.value === 'user') {
      payload.user_id = Number(form.user_id)
      payload.nom_client = null
      payload.telephone_client = null
      payload.telephone = null
    } else {
      payload.user_id = null
      payload.nom_client = form.nom_client
      payload.telephone_client = form.telephone
      payload.telephone = form.telephone
    }

    payload.equipements = selectedEquipements.value.map((e) => ({
      id: Number(e.equipement_id),
      quantity: Number(e.quantity),
    }))

    await adminReservationsStore.updateReservation(reservationId, payload)
    router.push({ name: 'admin-reservations' })
  } catch (error) {
    console.error('Erreur mise à jour réservation :', error)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <AppAdmin>
    <div class="mx-auto max-w-[1240px] text-[#151515]">
      <div class="grid grid-cols-1 lg:grid-cols-[1.05fr_0.95fr] gap-5 min-h-[640px]">
        <!-- GAUCHE : HERO / CARTE SALLE SÉLECTIONNÉE -->
        <section class="relative min-h-[480px] lg:min-h-full overflow-hidden rounded-[20px] border border-[#ecebe7] bg-[#141515] flex flex-col justify-between p-6 sm:p-8">
          <img
            :src="salleCoverUrl"
            :alt="selectedSalle?.nom || 'Salle'"
            class="absolute inset-0 h-full w-full object-cover transition-opacity duration-500"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/30"></div>

          <!-- En-tête gauche -->
          <div class="relative z-10 flex items-center justify-between">
            <RouterLink
              :to="{ name: 'info-reservation', params: { id: reservationId } }"
              class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-black/40 px-3.5 py-1.5 text-[11px] uppercase tracking-[0.08em] text-white backdrop-blur-md transition hover:bg-white/10"
            >
              <ArrowLeft :size="13" />
              <span>Retour aux détails</span>
            </RouterLink>

            <div class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] text-white/90 backdrop-blur-md">
              <Pencil :size="12" />
              <span>Modification Admin</span>
            </div>
          </div>

          <!-- Pied gauche -->
          <div class="relative z-10 mt-auto pt-16">
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <span class="rounded-full border border-white/20 bg-black/30 px-3 py-1 text-[10px] uppercase tracking-[0.12em] text-white/80 backdrop-blur-sm">
                Dossier #{{ reservationId }}
              </span>
              <span
                class="rounded-full border px-3 py-1 text-[10px] uppercase tracking-[0.12em] backdrop-blur-sm"
                :class="{
                  'border-emerald-300/35 bg-emerald-500/20 text-emerald-100': form.status === 'confirmee',
                  'border-amber-300/35 bg-amber-500/20 text-amber-100': form.status === 'en_attente',
                  'border-slate-400/35 bg-slate-600/30 text-slate-100': form.status === 'terminee',
                  'border-rose-300/35 bg-rose-500/20 text-rose-100': form.status === 'rejetee',
                }"
              >
                {{ form.status }}
              </span>
            </div>

            <h1 class="font-serif text-[42px] sm:text-[54px] leading-[0.95] tracking-[-0.03em] text-white">
              {{ selectedSalle ? selectedSalle.nom : 'MODIFIER LA RÉSERVATION' }}
            </h1>

            <div v-if="selectedSalle" class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-[11px] uppercase tracking-[0.08em] text-white/80">
              <span class="inline-flex items-center gap-1.5">
                <MapPin :size="13" class="text-white/70" />
                {{ selectedSalle.localisation || 'Sur site' }}
              </span>
              <span class="inline-flex items-center gap-1.5">
                <Users :size="13" class="text-white/70" />
                {{ selectedSalle.capacite }} personnes max
              </span>
              <span class="text-white font-semibold">
                {{ formatPrice(selectedSalle.prix) }}
              </span>
            </div>
          </div>
        </section>

        <!-- DROITE : FORMULAIRE -->
        <section class="flex flex-col justify-between rounded-[20px] border border-[#ecebe7] bg-white p-6 sm:p-10 shadow-sm overflow-y-auto">
          <div class="mx-auto w-full max-w-[540px]">
            <!-- CHARGEMENT INITIAL -->
            <div v-if="isFetching" class="flex flex-col items-center justify-center py-16">
              <Loader2 :size="36" class="animate-spin text-[#181818]" />
              <p class="mt-4 text-[13px] text-[#777]">Chargement du dossier de réservation...</p>
            </div>

            <!-- ERREUR DE CHARGEMENT -->
            <div v-else-if="fetchError" class="rounded-[12px] border border-rose-200 bg-rose-50 p-6 text-center">
              <AlertCircle :size="32" class="mx-auto mb-3 text-rose-600" />
              <h3 class="font-serif text-[18px] text-rose-900">Impossible de charger la réservation</h3>
              <p class="mt-1 text-[12px] text-rose-700">{{ fetchError }}</p>
              <div class="mt-5">
                <RouterLink
                  :to="{ name: 'admin-reservations' }"
                  class="inline-flex rounded-[8px] border border-gray-300 bg-white px-5 py-2.5 text-[11px] uppercase tracking-[0.08em] text-gray-700 transition hover:bg-gray-50"
                >
                  Retour à la liste des réservations
                </RouterLink>
              </div>
            </div>

            <!-- FORMULAIRE DE MODIFICATION -->
            <form v-else @submit.prevent="handleUpdateReservation" class="space-y-6">
              <!-- En-tête -->
              <div class="text-center">
                <div class="mb-3 flex items-center justify-center gap-2 text-gray-300">
                  <span class="h-px w-7 bg-gray-200"></span>
                  <span class="text-[14px]">◇</span>
                  <span class="h-px w-7 bg-gray-200"></span>
                </div>
                <h2 class="font-serif text-[32px] sm:text-[38px] uppercase leading-none tracking-[0.02em] text-[#191919]">
                  Modification
                </h2>
                <p class="mx-auto mt-2.5 max-w-[420px] text-[13px] leading-relaxed text-[#777]">
                  Ajustez les dates, la salle, le bénéficiaire ou les équipements associés.
                </p>
              </div>

              <!-- ALERTE D'ERREUR DU STORE -->
              <div
                v-if="adminReservationsStore.errorMessage"
                class="flex items-start gap-3 rounded-[9px] border border-rose-200 bg-rose-50 p-3.5 text-[12px] text-rose-700"
              >
                <AlertCircle :size="16" class="mt-0.5 shrink-0 text-rose-500" />
                <span>{{ adminReservationsStore.errorMessage }}</span>
              </div>

              <!-- SECTION : STATUT ADMINISTRATIF -->
              <div class="space-y-2">
                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Statut du dossier</p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                  <label
                    class="flex cursor-pointer items-center justify-between rounded-[8px] border p-2 text-xs font-semibold transition"
                    :class="form.status === 'confirmee' ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-[#deddd9] bg-[#fafaf8] text-[#555]'"
                  >
                    <span>Confirmée</span>
                    <input type="radio" value="confirmee" v-model="form.status" class="sr-only" />
                    <CheckCircle2 v-if="form.status === 'confirmee'" :size="13" />
                  </label>

                  <label
                    class="flex cursor-pointer items-center justify-between rounded-[8px] border p-2 text-xs font-semibold transition"
                    :class="form.status === 'en_attente' ? 'border-amber-600 bg-amber-50 text-amber-800' : 'border-[#deddd9] bg-[#fafaf8] text-[#555]'"
                  >
                    <span>En attente</span>
                    <input type="radio" value="en_attente" v-model="form.status" class="sr-only" />
                    <CheckCircle2 v-if="form.status === 'en_attente'" :size="13" />
                  </label>

                  <label
                    class="flex cursor-pointer items-center justify-between rounded-[8px] border p-2 text-xs font-semibold transition"
                    :class="form.status === 'terminee' ? 'border-slate-800 bg-slate-100 text-slate-800' : 'border-[#deddd9] bg-[#fafaf8] text-[#555]'"
                  >
                    <span>Terminée</span>
                    <input type="radio" value="terminee" v-model="form.status" class="sr-only" />
                    <CheckCircle2 v-if="form.status === 'terminee'" :size="13" />
                  </label>

                  <label
                    class="flex cursor-pointer items-center justify-between rounded-[8px] border p-2 text-xs font-semibold transition"
                    :class="form.status === 'rejetee' ? 'border-rose-600 bg-rose-50 text-rose-800' : 'border-[#deddd9] bg-[#fafaf8] text-[#555]'"
                  >
                    <span>Rejetée</span>
                    <input type="radio" value="rejetee" v-model="form.status" class="sr-only" />
                    <CheckCircle2 v-if="form.status === 'rejetee'" :size="13" />
                  </label>
                </div>
              </div>

              <!-- SECTION : SALLE -->
              <div class="space-y-2">
                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Salle</p>
                <div class="grid grid-cols-1 gap-2.5 max-h-52 overflow-y-auto pr-1">
                  <label
                    v-for="salle in salles"
                    :key="salle.id"
                    class="flex cursor-pointer items-center gap-3 rounded-[9px] border p-3 transition-all"
                    :class="form.salle_id === salle.id
                      ? 'border-[#181818] bg-gray-50'
                      : 'border-[#ecebe7] bg-[#fafaf8] hover:border-gray-300'"
                  >
                    <input type="radio" :value="salle.id" v-model="form.salle_id" class="sr-only" />
                    <div class="h-11 w-11 shrink-0 overflow-hidden rounded-[7px] border border-gray-200 bg-gray-100">
                      <img
                        :src="salle.images?.[0]?.url || salle.images?.[0]?.path || defaultPlaceholder"
                        :alt="salle.nom"
                        class="h-full w-full object-cover"
                      />
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="truncate text-[13px] font-medium text-[#191919]">{{ salle.nom }}</p>
                      <p class="text-[11px] text-[#777] flex items-center gap-1 mt-0.5">
                        <MapPin :size="10" />
                        <span class="truncate">{{ salle.localisation }}</span>
                        <span>•</span>
                        <Users :size="10" />
                        <span>{{ salle.capacite }} pers.</span>
                      </p>
                    </div>
                    <span v-if="form.salle_id === salle.id" class="shrink-0 rounded-full bg-[#181818] p-0.5 text-white">
                      <CheckCircle2 :size="14" />
                    </span>
                  </label>
                </div>
              </div>

              <!-- SECTION : CRÉNEAU DATE & HEURE -->
              <div class="space-y-3">
                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Date & Horaire</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">Début</label>
                    <input
                      v-model="form.date_heure_debut"
                      type="datetime-local"
                      required
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-2.5 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>
                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">Fin</label>
                    <input
                      v-model="form.date_heure_fin"
                      type="datetime-local"
                      required
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-2.5 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>
                </div>
              </div>

              <!-- SECTION : PARTICIPANTS -->
              <div class="space-y-2">
                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Participants & Capacité</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">Nombre de personnes</label>
                    <input
                      v-model.number="form.nombre_personnes"
                      type="number"
                      min="1"
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-2.5 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>
                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#777]">Capacité de la salle</label>
                    <div class="flex h-[43px] items-center rounded-[9px] border border-gray-200 bg-gray-50 px-4 text-[13px] text-[#555]">
                      {{ selectedSalle?.capacite ? `${selectedSalle.capacite} places` : '—' }}
                    </div>
                  </div>
                </div>
                <p v-if="isOverCapacity" class="text-[11px] text-rose-600 font-medium">
                  Attention : le nombre de personnes dépasse la capacité maximale de la salle.
                </p>
              </div>

              <!-- SECTION : BÉNÉFICIAIRE -->
              <div class="space-y-3">
                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Bénéficiaire</p>
                <div class="grid grid-cols-2 gap-3">
                  <button
                    type="button"
                    @click="beneficiaryType = 'user'"
                    class="flex items-center justify-center gap-2 rounded-[9px] border p-2.5 text-xs font-semibold transition cursor-pointer"
                    :class="beneficiaryType === 'user'
                      ? 'border-[#181818] bg-[#181818] text-white'
                      : 'border-[#deddd9] bg-[#fafaf8] text-[#555] hover:bg-white'"
                  >
                    <User :size="14" />
                    <span>Compte Utilisateur</span>
                  </button>

                  <button
                    type="button"
                    @click="beneficiaryType = 'direct'"
                    class="flex items-center justify-center gap-2 rounded-[9px] border p-2.5 text-xs font-semibold transition cursor-pointer"
                    :class="beneficiaryType === 'direct'
                      ? 'border-[#181818] bg-[#181818] text-white'
                      : 'border-[#deddd9] bg-[#fafaf8] text-[#555] hover:bg-white'"
                  >
                    <Users :size="14" />
                    <span>Client Direct</span>
                  </button>
                </div>

                <div v-if="beneficiaryType === 'user'">
                  <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">Sélectionner l'utilisateur</label>
                  <select
                    v-model="form.user_id"
                    class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-2.5 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                  >
                    <option :value="null">Sélectionnez un compte</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">
                      {{ u.nom || u.name }} — {{ u.email }}
                    </option>
                  </select>
                </div>

                <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">Nom du client</label>
                    <input
                      v-model="form.nom_client"
                      type="text"
                      placeholder="Ex: Jean Dupont"
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-2.5 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>
                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">Téléphone</label>
                    <input
                      v-model="form.telephone"
                      type="tel"
                      placeholder="Ex: +229 90 00 00 00"
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-2.5 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>
                </div>
              </div>

              <!-- SECTION : ÉQUIPEMENTS -->
              <div class="space-y-3">
                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Équipements inclus</p>

                <!-- Déjà sélectionnés -->
                <div v-if="selectedEquipements.length > 0" class="space-y-2">
                  <div
                    v-for="item in selectedEquipements"
                    :key="item.equipement_id"
                    class="flex items-center gap-3 rounded-[9px] border border-gray-200 bg-white p-3 shadow-xs"
                  >
                    <div class="min-w-0 flex-1">
                      <p class="truncate text-[13px] font-medium text-[#191919]">{{ item.nom }}</p>
                      <p class="text-[11px] text-[#777]">Quantité : {{ item.quantity }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                      <button
                        type="button"
                        @click="decrementQty(item)"
                        class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer"
                      >
                        <Minus :size="12" />
                      </button>
                      <span class="w-6 text-center text-[13px] font-bold text-[#191919]">{{ item.quantity }}</span>
                      <button
                        type="button"
                        @click="incrementQty(item)"
                        class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer"
                      >
                        <Plus :size="12" />
                      </button>
                      <button
                        type="button"
                        @click="removeEquipement(item.equipement_id)"
                        class="ml-2 flex h-7 w-7 items-center justify-center text-rose-500 hover:text-rose-700 cursor-pointer"
                      >
                        <Trash2 :size="14" />
                      </button>
                    </div>
                  </div>
                </div>
                <div v-else class="rounded-[9px] border border-dashed border-[#deddd9] p-4 text-center text-xs text-[#777]">
                  Aucun équipement sélectionné pour le moment.
                </div>

                <!-- Ajouter d'autres équipements -->
                <div v-if="availableEquipementsToAdd.length > 0" class="pt-2">
                  <p class="mb-1.5 text-[11px] font-semibold text-[#777]">Ajouter un équipement du catalogue</p>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="eq in availableEquipementsToAdd"
                      :key="eq.id"
                      type="button"
                      @click="addEquipement(eq)"
                      class="inline-flex items-center gap-1.5 rounded-[7px] border border-[#deddd9] bg-[#fafaf8] px-2.5 py-1.5 text-[11px] text-[#333] transition hover:bg-white hover:border-[#181818] cursor-pointer"
                    >
                      <Plus :size="11" />
                      <span>{{ eq.nom }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- BOUTONS D'ACTION -->
              <div class="pt-4 space-y-2.5">
                <button
                  type="submit"
                  :disabled="isSubmitting"
                  class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-900 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-900 transition hover:bg-neutral-900 hover:text-white disabled:opacity-50 cursor-pointer"
                >
                  <Loader2 v-if="isSubmitting" :size="14" class="animate-spin" />
                  <span>{{ isSubmitting ? 'Enregistrement...' : 'Enregistrer les modifications' }}</span>
                </button>

                <RouterLink
                  :to="{ name: 'info-reservation', params: { id: reservationId } }"
                  class="inline-flex w-full items-center justify-center rounded-xl border border-neutral-300 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900"
                >
                  Annuler et retourner au dossier
                </RouterLink>
              </div>
            </form>
          </div>
        </section>
      </div>
    </div>
  </AppAdmin>
</template>
