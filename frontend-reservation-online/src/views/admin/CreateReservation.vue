<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import AppAdmin from '@/components/admin/AppAdmin.vue'
import { useAdminReservationsStore } from '@/store/adminReservations'
import { useAdminSallesStore } from '@/store/adminSalles'
import { useAdminUsersStore } from '@/store/adminUsers'
import { useAdminEquipementsStore } from '@/store/adminEquipements'
import { toApiDateTime, formatDateTime, parseLocalDate } from '@/helpers/dateHelper'
import {
  ArrowLeft,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Building2,
  Package,
  ClipboardCheck,
  Calendar,
  Users,
  MapPin,
  Plus,
  Minus,
  Trash2,
  Clock,
  Sparkles,
  User,
  Phone,
  Mail,
  ShieldCheck,
} from 'lucide-vue-next'

const router = useRouter()
const adminReservationsStore = useAdminReservationsStore()
const adminSallesStore = useAdminSallesStore()
const adminUsersStore = useAdminUsersStore()
const adminEquipementsStore = useAdminEquipementsStore()

// Stepper
const currentStep = ref(1)
const STEPS = [
  { id: 1, label: 'La salle', icon: Building2 },
  { id: 2, label: 'Client & Matériel', icon: Package },
  { id: 3, label: 'Validation', icon: ClipboardCheck },
]

// Mode de bénéficiaire : 'user' (compte existant) ou 'direct' (client physique)
const beneficiaryType = ref('user')

const form = reactive({
  salle_id: null,
  user_id: null,
  nom_client: '',
  telephone: '',
  date_heure_debut: '',
  date_heure_fin: '',
  nombre_personnes: 10,
  status: 'confirmee',
})

const selectedEquipements = ref([]) // [{ equipement_id, nom, stock_total, quantity }]
const step1Error = ref(null)
const step2Error = ref(null)
const isSubmitting = ref(false)

const defaultPlaceholder =
  'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'

onMounted(async () => {
  adminReservationsStore.clearErrors()
  try {
    await Promise.all([
      adminSallesStore.fetchSalles({ all: 'true' }),
      adminUsersStore.fetchUsers({ all: 'true' }),
      adminEquipementsStore.fetchEquipements({ all: 'true' }),
    ])
  } catch (error) {
    console.error('Erreur chargement des données :', error)
  }
})

// Computeds
const salles = computed(() => adminSallesStore.salles || [])
const users = computed(() => adminUsersStore.users || [])
const equipements = computed(() => adminEquipementsStore.equipements || [])

const selectedSalle = computed(() => {
  if (!form.salle_id) return null
  return salles.value.find((s) => s.id === Number(form.salle_id)) || null
})

const selectedUser = computed(() => {
  if (!form.user_id) return null
  return users.value.find((u) => u.id === Number(form.user_id)) || null
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

const dureHeures = computed(() => {
  if (!form.date_heure_debut || !form.date_heure_fin) return 0
  const diff = parseLocalDate(form.date_heure_fin) - parseLocalDate(form.date_heure_debut)
  return Math.max(0, diff / 1000 / 3600)
})

const selectedEquipementIds = computed(() =>
  selectedEquipements.value.map((e) => e.equipement_id)
)

// Navigation Steps
const goStep2 = () => {
  step1Error.value = null
  if (!form.salle_id) {
    step1Error.value = 'Veuillez sélectionner une salle.'
    return
  }
  if (!form.date_heure_debut || !form.date_heure_fin) {
    step1Error.value = 'Veuillez renseigner les dates et heures de début et de fin.'
    return
  }
  if (parseLocalDate(form.date_heure_debut) >= parseLocalDate(form.date_heure_fin)) {
    step1Error.value = 'La date de fin doit être postérieure à la date de début.'
    return
  }
  if (selectedSalle.value?.capacite && form.nombre_personnes > selectedSalle.value.capacite) {
    step1Error.value = `Cette salle a une capacité maximale de ${selectedSalle.value.capacite} personnes.`
    return
  }
  currentStep.value = 2
}

const goStep3 = () => {
  step2Error.value = null
  if (beneficiaryType.value === 'user' && !form.user_id) {
    step2Error.value = 'Veuillez sélectionner un compte utilisateur.'
    return
  }
  if (beneficiaryType.value === 'direct' && !form.nom_client) {
    step2Error.value = 'Veuillez renseigner le nom du client.'
    return
  }
  currentStep.value = 3
}

// Gestion équipements
const addEquipement = (eq) => {
  if (selectedEquipementIds.value.includes(eq.id)) return
  selectedEquipements.value.push({
    equipement_id: eq.id,
    nom: eq.nom,
    stock_total: eq.stock_total || 1,
    quantity: 1,
  })
}

const removeEquipement = (equipementId) => {
  selectedEquipements.value = selectedEquipements.value.filter(
    (e) => e.equipement_id !== equipementId
  )
}

const incrementQty = (item) => {
  if (item.quantity < item.stock_total) item.quantity++
}

const decrementQty = (item) => {
  if (item.quantity > 1) item.quantity--
}

// Soumission
const handleCreateReservation = async () => {
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
    } else {
      payload.nom_client = form.nom_client
      payload.telephone_client = form.telephone
      payload.telephone = form.telephone
    }

    if (selectedEquipements.value.length > 0) {
      payload.equipements = selectedEquipements.value.map((e) => ({
        id: Number(e.equipement_id),
        quantity: Number(e.quantity),
      }))
    }

    await adminReservationsStore.createReservation(payload)
    router.push({ name: 'admin-reservations' })
  } catch (error) {
    console.error('Erreur création réservation :', error)
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
              :to="{ name: 'admin-reservations' }"
              class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-black/40 px-3.5 py-1.5 text-[11px] uppercase tracking-[0.08em] text-white backdrop-blur-md transition hover:bg-white/10"
            >
              <ArrowLeft :size="13" />
              <span>Retour aux réservations</span>
            </RouterLink>

            <div class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] text-white/90 backdrop-blur-md">
              <Sparkles :size="12" />
              <span>Administration</span>
            </div>
          </div>

          <!-- Pied gauche -->
          <div class="relative z-10 mt-auto pt-16">
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <span class="rounded-full border border-white/20 bg-black/30 px-3 py-1 text-[10px] uppercase tracking-[0.12em] text-white/80 backdrop-blur-sm">
                Nouvelle Réservation
              </span>
              <span
                v-if="selectedSalle"
                class="rounded-full border px-3 py-1 text-[10px] uppercase tracking-[0.12em] backdrop-blur-sm"
                :class="selectedSalle.status && selectedSalle.status.toLowerCase() !== 'disponible'
                  ? 'border-rose-300/35 bg-rose-500/20 text-rose-100'
                  : 'border-emerald-300/35 bg-emerald-500/20 text-emerald-100'"
              >
                {{ selectedSalle.status || 'Disponible' }}
              </span>
            </div>

            <h1 class="font-serif text-[42px] sm:text-[54px] leading-[0.95] tracking-[-0.03em] text-white">
              {{ selectedSalle ? selectedSalle.nom : 'CRÉER UNE RÉSERVATION' }}
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
            <p v-else class="mt-4 text-xs text-white/60">
              Sélectionnez une salle dans le formulaire pour configurer ce dossier d'événement.
            </p>
          </div>
        </section>

        <!-- DROITE : STEPPER ET FORMULAIRE -->
        <section class="flex flex-col justify-between rounded-[20px] border border-[#ecebe7] bg-white p-6 sm:p-10 shadow-sm overflow-y-auto">
          <div class="mx-auto w-full max-w-[540px]">
            <!-- Stepper -->
            <div class="mb-8 flex items-center gap-2">
              <template v-for="(step, idx) in STEPS" :key="step.id">
                <button
                  type="button"
                  @click="currentStep >= step.id ? (currentStep = step.id) : null"
                  class="flex items-center gap-2 text-[10px] uppercase tracking-[0.1em] transition"
                  :class="currentStep === step.id ? 'text-[#191919] font-bold' : 'text-[#888] hover:text-[#222]'"
                >
                  <span
                    class="flex h-6 w-6 items-center justify-center rounded-full border text-[9px] font-semibold"
                    :class="
                      currentStep > step.id
                        ? 'border-emerald-600 bg-emerald-600 text-white'
                        : currentStep === step.id
                          ? 'border-[#181818] bg-[#181818] text-white'
                          : 'border-gray-300 text-gray-400 bg-gray-50'
                    "
                  >
                    <CheckCircle2 v-if="currentStep > step.id" :size="11" />
                    <span v-else>{{ step.id }}</span>
                  </span>
                  <span class="hidden sm:inline">{{ step.label }}</span>
                </button>
                <div v-if="idx < STEPS.length - 1" class="h-px flex-1 bg-gray-200"></div>
              </template>
            </div>

            <!-- ALERTE D'ERREUR GLOBALE BACKEND -->
            <div
              v-if="adminReservationsStore.errorMessage"
              class="mb-6 rounded-[9px] border border-rose-200 bg-rose-50 p-3.5 text-[12px] text-rose-700"
            >
              <div class="flex items-start gap-2">
                <AlertCircle :size="15" class="mt-0.5 shrink-0 text-rose-500" />
                <span>{{ adminReservationsStore.errorMessage }}</span>
              </div>
            </div>

            <!-- ÉTAPE 1 : SALLE & DATES -->
            <div v-if="currentStep === 1">
              <div class="text-center">
                <h2 class="font-serif text-[30px] sm:text-[36px] tracking-[-0.03em] text-[#191919]">
                  Salle & Créneau
                </h2>
                <p class="mt-1 text-xs text-[#777]">
                  Sélectionnez la salle et définissez la plage horaire.
                </p>
              </div>

              <div class="mt-8 space-y-5">
                <!-- Choix de la salle -->
                <div>
                  <label class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                    Sélection de la salle
                  </label>
                  <div class="grid grid-cols-1 gap-2.5 max-h-56 overflow-y-auto pr-1">
                    <label
                      v-for="salle in salles"
                      :key="salle.id"
                      class="flex cursor-pointer items-center gap-3 rounded-[9px] border p-3 transition-all"
                      :class="Number(form.salle_id) === Number(salle.id)
                        ? 'border-[#181818] bg-gray-50'
                        : 'border-[#ecebe7] bg-[#fafaf8] hover:border-gray-300'"
                    >
                      <input
                        type="radio"
                        :value="Number(salle.id)"
                        v-model="form.salle_id"
                        class="sr-only"
                      />
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
                          <span class="truncate">{{ salle.localisation || 'Sur site' }}</span>
                          <span>•</span>
                          <Users :size="10" />
                          <span>{{ salle.capacite }} pers.</span>
                        </p>
                      </div>
                      <span v-if="Number(form.salle_id) === Number(salle.id)" class="shrink-0 rounded-full bg-[#181818] p-0.5 text-white">
                        <CheckCircle2 :size="14" />
                      </span>
                    </label>
                  </div>
                </div>

                <!-- Début & Fin -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                      Début
                    </label>
                    <input
                      v-model="form.date_heure_debut"
                      type="datetime-local"
                      required
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-3.5 py-2.5 text-[12px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>

                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                      Fin
                    </label>
                    <input
                      v-model="form.date_heure_fin"
                      type="datetime-local"
                      required
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-3.5 py-2.5 text-[12px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>
                </div>

                <!-- Participants & Capacité -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                      Participants
                    </label>
                    <input
                      v-model.number="form.nombre_personnes"
                      type="number"
                      min="1"
                      placeholder="Ex: 10"
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-3.5 py-2.5 text-[12px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>

                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#777]">
                      Capacité maximale
                    </label>
                    <div class="flex h-[43px] items-center rounded-[9px] border border-gray-200 bg-gray-50 px-3.5 text-[12px] text-[#555]">
                      {{ selectedSalle?.capacite ? `${selectedSalle.capacite} places` : '—' }}
                    </div>
                  </div>
                </div>

                <!-- Erreur Step 1 -->
                <div v-if="step1Error" class="rounded-[9px] border border-rose-200 bg-rose-50 p-3 text-[12px] text-rose-700">
                  <div class="flex items-start gap-2">
                    <AlertCircle :size="15" class="mt-0.5 shrink-0" />
                    <span>{{ step1Error }}</span>
                  </div>
                </div>

                <button
                  type="button"
                  @click="goStep2"
                  class="inline-flex w-full items-center justify-center rounded-xl border border-neutral-900 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-900 transition hover:bg-neutral-900 hover:text-white cursor-pointer"
                >
                  Étape suivante : Bénéficiaire & Équipements
                </button>
              </div>
            </div>

            <!-- ÉTAPE 2 : BÉNÉFICIAIRE & ÉQUIPEMENTS -->
            <div v-else-if="currentStep === 2">
              <div class="text-center">
                <h2 class="font-serif text-[30px] sm:text-[36px] tracking-[-0.03em] text-[#191919]">
                  Bénéficiaire & Matériel
                </h2>
                <p class="mx-auto mt-1 max-w-[420px] text-[12px] text-[#777]">
                  Sélectionnez le type de client et ajoutez les équipements souhaités.
                </p>
              </div>

              <div class="mt-8 space-y-5">
                <!-- Type de bénéficiaire -->
                <div>
                  <label class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                    Type de bénéficiaire
                  </label>
                  <div class="grid grid-cols-2 gap-3">
                    <button
                      type="button"
                      @click="beneficiaryType = 'user'"
                      class="flex items-center justify-center gap-2 rounded-[9px] border p-3 text-xs font-semibold transition cursor-pointer"
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
                      class="flex items-center justify-center gap-2 rounded-[9px] border p-3 text-xs font-semibold transition cursor-pointer"
                      :class="beneficiaryType === 'direct'
                        ? 'border-[#181818] bg-[#181818] text-white'
                        : 'border-[#deddd9] bg-[#fafaf8] text-[#555] hover:bg-white'"
                    >
                      <Users :size="14" />
                      <span>Client Direct</span>
                    </button>
                  </div>
                </div>

                <!-- Champs compte utilisateur -->
                <div v-if="beneficiaryType === 'user'">
                  <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                    Sélectionner l'utilisateur
                  </label>
                  <select
                    v-model="form.user_id"
                    class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-3.5 py-2.5 text-[12px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                  >
                    <option :value="null">Sélectionnez un compte</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">
                      {{ u.nom || u.name }} — {{ u.email }}
                    </option>
                  </select>
                </div>

                <!-- Champs client direct -->
                <div v-else class="space-y-3">
                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                      Nom complet du client
                    </label>
                    <input
                      v-model="form.nom_client"
                      type="text"
                      placeholder="Ex: Jean Dupont"
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-3.5 py-2.5 text-[12px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>

                  <div>
                    <label class="mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                      Numéro de téléphone
                    </label>
                    <input
                      v-model="form.telephone"
                      type="tel"
                      placeholder="Ex: +229 90 00 00 00"
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-3.5 py-2.5 text-[12px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>
                </div>

                <!-- Équipements -->
                <div>
                  <label class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                    Équipements optionnels
                  </label>
                  <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <div
                      v-for="eq in equipements"
                      :key="eq.id"
                      class="flex items-center gap-3 rounded-[9px] border p-2.5 transition"
                      :class="selectedEquipementIds.includes(eq.id) ? 'border-[#181818] bg-gray-50' : 'border-[#ecebe7] bg-[#fafaf8]'"
                    >
                      <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] border border-gray-200 bg-white text-gray-700">
                        <Package :size="15" />
                      </div>

                      <div class="min-w-0 flex-1">
                        <p class="truncate text-[12px] font-medium text-[#222]">{{ eq.nom }}</p>
                        <p class="text-[10px] text-[#777]">Stock : {{ eq.stock_total || 1 }} unité(s)</p>
                      </div>

                      <button
                        v-if="!selectedEquipementIds.includes(eq.id)"
                        type="button"
                        @click="addEquipement(eq)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 cursor-pointer"
                      >
                        <Plus :size="13" />
                      </button>
                      <span v-else class="text-[10px] uppercase font-bold text-emerald-700">
                        Ajouté
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Liste des équipements choisis avec quantité -->
                <div v-if="selectedEquipements.length > 0" class="rounded-[9px] border border-[#ecebe7] bg-[#fafaf8] p-3">
                  <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-[#777]">Matériels retenus</p>
                  <div class="space-y-2">
                    <div
                      v-for="item in selectedEquipements"
                      :key="item.equipement_id"
                      class="flex items-center gap-2 rounded-[7px] border border-gray-200 bg-white px-3 py-2"
                    >
                      <p class="min-w-0 flex-1 truncate text-[12px] font-medium text-[#222]">{{ item.nom }}</p>
                      <div class="flex items-center gap-1.5">
                        <button
                          type="button"
                          @click="decrementQty(item)"
                          class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 text-gray-600 hover:bg-gray-100 cursor-pointer"
                        >
                          <Minus :size="10" />
                        </button>
                        <span class="w-5 text-center text-[12px] font-bold text-[#222]">{{ item.quantity }}</span>
                        <button
                          type="button"
                          @click="incrementQty(item)"
                          class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 text-gray-600 hover:bg-gray-100 cursor-pointer"
                        >
                          <Plus :size="10" />
                        </button>
                      </div>
                      <button
                        type="button"
                        @click="removeEquipement(item.equipement_id)"
                        class="flex h-6 w-6 items-center justify-center text-rose-500 hover:text-rose-700 cursor-pointer ml-1"
                      >
                        <Trash2 :size="12" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Erreur Step 2 -->
                <div v-if="step2Error" class="rounded-[9px] border border-rose-200 bg-rose-50 p-3 text-[12px] text-rose-700">
                  <div class="flex items-start gap-2">
                    <AlertCircle :size="15" class="mt-0.5 shrink-0" />
                    <span>{{ step2Error }}</span>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                  <button
                    type="button"
                    @click="currentStep = 1"
                    class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900 cursor-pointer"
                  >
                    Retour
                  </button>
                  <button
                    type="button"
                    @click="goStep3"
                    class="inline-flex items-center justify-center rounded-xl border border-neutral-900 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-900 transition hover:bg-neutral-900 hover:text-white cursor-pointer"
                  >
                    Continuer
                  </button>
                </div>
              </div>
            </div>

            <!-- ÉTAPE 3 : STATUT & VALIDATION -->
            <div v-else>
              <div class="text-center">
                <h2 class="font-serif text-[30px] sm:text-[36px] tracking-[-0.03em] text-[#191919]">
                  Statut & Validation
                </h2>
                <p class="mx-auto mt-1 max-w-[420px] text-[12px] text-[#777]">
                  Vérifiez le récapitulatif complet et confirmez la création.
                </p>
              </div>

              <div class="mt-8 space-y-5">
                <!-- Choix du statut -->
                <div>
                  <label class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">
                    Statut de la réservation
                  </label>
                  <div class="grid grid-cols-2 gap-2">
                    <label
                      class="flex cursor-pointer items-center justify-between rounded-[8px] border p-2.5 text-xs font-semibold transition"
                      :class="form.status === 'confirmee' ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-[#deddd9] bg-[#fafaf8] text-[#555]'"
                    >
                      <span>Confirmée</span>
                      <input type="radio" value="confirmee" v-model="form.status" class="sr-only" />
                      <CheckCircle2 v-if="form.status === 'confirmee'" :size="14" />
                    </label>

                    <label
                      class="flex cursor-pointer items-center justify-between rounded-[8px] border p-2.5 text-xs font-semibold transition"
                      :class="form.status === 'en_attente' ? 'border-amber-600 bg-amber-50 text-amber-800' : 'border-[#deddd9] bg-[#fafaf8] text-[#555]'"
                    >
                      <span>En attente</span>
                      <input type="radio" value="en_attente" v-model="form.status" class="sr-only" />
                      <CheckCircle2 v-if="form.status === 'en_attente'" :size="14" />
                    </label>
                  </div>
                </div>

                <!-- Récapitulatif -->
                <div class="space-y-3 rounded-[11px] border border-[#ecebe7] bg-[#fafaf8] p-4 text-[12px]">
                  <p class="text-[11px] font-bold uppercase tracking-wider text-[#777]">Détails du dossier</p>

                  <div class="flex items-center justify-between border-b border-gray-200 pb-2">
                    <span class="text-[#777]">Salle</span>
                    <span class="font-bold text-[#191919]">{{ selectedSalle?.nom }}</span>
                  </div>

                  <div class="flex items-center justify-between border-b border-gray-200 pb-2">
                    <span class="text-[#777]">Bénéficiaire</span>
                    <span class="font-bold text-[#191919]">
                      {{ beneficiaryType === 'user' ? (selectedUser?.nom || selectedUser?.name || 'Utilisateur #' + form.user_id) : form.nom_client }}
                    </span>
                  </div>

                  <div class="flex items-center justify-between border-b border-gray-200 pb-2">
                    <span class="text-[#777]">Horaires</span>
                    <span class="font-medium text-[#191919] text-right">
                      {{ formatDateTime(form.date_heure_debut) }}<br />
                      <span class="text-[11px] text-[#777]">au {{ formatDateTime(form.date_heure_fin) }} ({{ dureHeures }}h)</span>
                    </span>
                  </div>

                  <div class="flex items-center justify-between border-b border-gray-200 pb-2">
                    <span class="text-[#777]">Participants</span>
                    <span class="font-semibold text-[#191919]">{{ form.nombre_personnes }} personnes</span>
                  </div>

                  <div class="flex items-start justify-between">
                    <span class="text-[#777]">Équipements</span>
                    <span class="text-right font-medium text-[#191919]">
                      <template v-if="selectedEquipements.length === 0">Aucun</template>
                      <template v-else>
                        <span v-for="(eq, i) in selectedEquipements" :key="eq.equipement_id" class="block">
                          {{ eq.nom }} (×{{ eq.quantity }})
                        </span>
                      </template>
                    </span>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                  <button
                    type="button"
                    @click="currentStep = 2"
                    class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900 cursor-pointer"
                  >
                    Retour
                  </button>

                  <button
                    type="button"
                    @click="handleCreateReservation"
                    :disabled="isSubmitting"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-900 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-900 transition hover:bg-neutral-900 hover:text-white disabled:opacity-50 cursor-pointer"
                  >
                    <Loader2 v-if="isSubmitting" :size="14" class="animate-spin" />
                    <span>{{ isSubmitting ? 'Création...' : 'Confirmer la réservation' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </AppAdmin>
</template>
