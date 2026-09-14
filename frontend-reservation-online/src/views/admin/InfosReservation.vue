<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import AppAdmin from '@/components/admin/AppAdmin.vue'
import { useAdminReservationsStore } from '@/store/adminReservations'
import {
  ArrowLeft,
  Pencil,
  MapPin,
  Clock,
  Users as UsersIcon,
  CheckCircle2,
  AlertCircle,
  Loader2,
  ArrowUpRight,
  Sparkles,
  Server,
  Calendar,
  Check,
  X,
  Flag,
  User,
  Phone,
  Mail,
} from 'lucide-vue-next'

const route = useRoute()
const adminReservationsStore = useAdminReservationsStore()

const reservationId = route.params.id
const reservation = ref(null)
const isFetching = ref(true)

const defaultPlaceholder =
  'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'

const loadDetails = async () => {
  isFetching.value = true
  try {
    reservation.value = await adminReservationsStore.fetchReservation(reservationId)
  } catch (error) {
    console.error('Erreur chargement détails réservation :', error)
  } finally {
    isFetching.value = false
  }
}

onMounted(() => {
  loadDetails()
})

const activeImage = computed(() => {
  if (reservation.value?.salle?.images && reservation.value.salle.images.length > 0) {
    return reservation.value.salle.images[0].url || reservation.value.salle.images[0].path || defaultPlaceholder
  }
  return defaultPlaceholder
})

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  try {
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(dateString))
  } catch {
    return dateString
  }
}

const handleConfirm = async () => {
  try {
    await adminReservationsStore.confirmReservation(reservationId)
    await loadDetails()
  } catch (e) {
    console.error('Erreur confirmation :', e)
    await loadDetails()
  }
}

const handleReject = async () => {
  try {
    await adminReservationsStore.rejectReservation(reservationId)
    await loadDetails()
  } catch (e) {
    console.error('Erreur rejet :', e)
  }
}

const handleTerminate = async () => {
  try {
    await adminReservationsStore.terminateReservation(reservationId)
    await loadDetails()
  } catch (e) {
    console.error('Erreur clôture :', e)
  }
}
</script>

<template>
  <AppAdmin>
    <div class="mx-auto max-w-[1180px] text-[#151515]">
      <!-- EN-TÊTE & RETOUR -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <RouterLink
          :to="{ name: 'admin-reservations' }"
          class="inline-flex items-center gap-2 text-xs font-semibold text-[#777] transition hover:text-[#191919]"
        >
          <ArrowLeft :size="16" />
          <span>Retour à la liste des réservations</span>
        </RouterLink>

        <div v-if="reservation" class="flex flex-wrap items-center gap-2">
          <!-- Confirmer -->
          <button
            v-if="reservation.status === 'en_attente' && new Date(reservation.date_heure_debut) > new Date()"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-[8px] bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 active:scale-95"
            @click="handleConfirm"
          >
            <Check :size="14" />
            <span>Confirmer</span>
          </button>
          <span
            v-else-if="reservation.status === 'en_attente'"
            class="inline-flex items-center gap-1.5 rounded-[8px] border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600"
          >
            Délai expiré (non confirmable)
          </span>

          <!-- Clôturer -->
          <button
            v-if="reservation.status === 'confirmee'"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-[8px] bg-slate-800 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-black active:scale-95"
            @click="handleTerminate"
          >
            <Flag :size="14" />
            <span>Marquer terminée</span>
          </button>

          <!-- Rejeter -->
          <button
            v-if="reservation.status === 'en_attente' || reservation.status === 'confirmee'"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-[8px] border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 active:scale-95"
            @click="handleReject"
          >
            <X :size="14" />
            <span>Rejeter / Annuler</span>
          </button>

          <!-- Modifier -->
          <RouterLink
            :to="{ name: 'update-reservation', params: { id: reservationId } }"
            class="inline-flex items-center gap-2 rounded-xl border border-neutral-900 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-900 transition hover:bg-neutral-900 hover:text-white"
          >
            <Pencil :size="13" />
            <span>Modifier</span>
          </RouterLink>
        </div>
      </div>

      <!-- CHARGEMENT -->
      <div
        v-if="isFetching"
        class="flex min-h-[420px] items-center justify-center rounded-[15px] border border-[#ecebe7] bg-white"
      >
        <div class="flex flex-col items-center gap-3">
          <Loader2 :size="34" class="animate-spin text-slate-800" />
          <p class="text-sm text-[#777]">Chargement du dossier de réservation...</p>
        </div>
      </div>

      <!-- ERREUR -->
      <div
        v-else-if="adminReservationsStore.errorMessage && !reservation"
        class="rounded-[15px] border border-[#eaded9] bg-white p-10 text-center"
      >
        <AlertCircle :size="42" class="mx-auto mb-3 text-slate-800" />
        <h2 class="text-xl font-semibold text-[#191919]">Réservation introuvable</h2>
        <p class="mt-2 text-sm text-[#777]">{{ adminReservationsStore.errorMessage }}</p>
        <RouterLink
          :to="{ name: 'admin-reservations' }"
          class="mt-6 inline-flex items-center gap-2 rounded-[9px] bg-[#191919] px-5 py-3 text-xs font-semibold text-white transition hover:bg-black"
        >
          Retourner aux réservations
        </RouterLink>
      </div>

      <!-- DOSSIER RÉSERVATION : STYLE SALLEINFOSUSER -->
      <div v-else-if="reservation">
        <section class="overflow-hidden rounded-[15px] border border-[#ecebe7] bg-white shadow-sm">
          <div class="grid min-h-[465px] grid-cols-1 lg:grid-cols-[1.06fr_0.98fr_1fr]">
            <!-- IMAGE GAUCHE -->
            <div class="relative min-h-[390px] overflow-hidden bg-[#e9e8e4] lg:min-h-0">
              <img
                :src="activeImage"
                :alt="reservation.salle?.nom || 'Salle réservée'"
                class="absolute inset-0 h-full w-full object-cover"
              />

              <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-black/5"></div>

              <div class="absolute left-5 top-5">
                <div
                  class="inline-flex items-center gap-1.5 rounded-full border border-white/35 bg-white/15 px-3 py-1.5 text-[11px] font-medium text-white backdrop-blur-md"
                >
                  <Sparkles :size="12" />
                  <span>Dossier #{{ reservation.id }}</span>
                </div>
              </div>

              <div class="absolute bottom-5 left-5 right-5 flex items-end justify-between gap-4">
                <div>
                  <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-white/70">
                    {{ reservation.salle?.nom || 'Salle #' + reservation.salle_id }}
                  </p>
                  <p class="mt-1 text-xl font-semibold leading-tight text-white">
                    {{ reservation.nom_affiche }}
                  </p>
                </div>

                <div
                  class="shrink-0 rounded-full px-3 py-1.5 text-[10px] font-semibold text-white backdrop-blur-md capitalize"
                  :class="{
                    'bg-emerald-500/90': reservation.status === 'confirmee',
                    'bg-amber-500/90': reservation.status === 'en_attente' && new Date(reservation.date_heure_debut) > new Date(),
                    'bg-slate-700/90': reservation.status === 'terminee',
                    'bg-rose-500/90': reservation.status === 'rejetee' || (reservation.status === 'en_attente' && new Date(reservation.date_heure_debut) <= new Date()),
                  }"
                >
                  {{ reservation.status === 'en_attente' && new Date(reservation.date_heure_debut) <= new Date() ? 'Expirée' : reservation.status }}
                </div>
              </div>
            </div>

            <!-- CENTRE : HIÉRARCHIE ÉDITORIALE & BÉNÉFICIAIRE -->
            <div class="flex flex-col justify-between border-b border-[#ecebe7] px-7 py-9 sm:px-10 lg:border-b-0 lg:border-r lg:border-[#ecebe7]">
              <div>
                <p class="text-[13px] font-medium text-[#7b7b7b]">Bénéficiaire de l'événement</p>

                <h1 class="mt-5 max-w-[320px] font-serif text-[42px] leading-[0.98] tracking-[-0.04em] text-[#191919]">
                  {{ reservation.nom_affiche }}
                </h1>

                <div class="mt-4 flex items-start gap-2 text-[13px] leading-5 text-[#777]">
                  <MapPin :size="15" class="mt-0.5 shrink-0 text-slate-800" />
                  <span>{{ reservation.salle?.nom }} — {{ reservation.salle?.localisation || 'Localisation non spécifiée' }}</span>
                </div>

                <div class="mt-10 flex items-end gap-2">
                  <span class="font-serif text-[44px] leading-none tracking-[-0.04em] text-[#000000]">
                    Contact
                  </span>
                </div>

                <!-- COORDONNÉES CLIENT -->
                <div class="mt-4 space-y-2.5 text-[13px] text-[#555]">
                  <div class="flex items-center gap-2.5">
                    <Phone :size="15" class="text-slate-800 shrink-0" />
                    <span>{{ reservation.telephone_affiche || 'Téléphone non renseigné' }}</span>
                  </div>

                  <div v-if="reservation.user" class="flex items-center gap-2.5">
                    <Mail :size="15" class="text-slate-800 shrink-0" />
                    <span class="break-all">{{ reservation.user.email }}</span>
                  </div>

                  <div class="flex items-center gap-2.5">
                    <User :size="15" class="text-slate-800 shrink-0" />
                    <span class="text-xs">
                      {{ reservation.user_id ? 'Compte utilisateur enregistré' : 'Client direct / physique' }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="mt-10 space-y-2.5">
                <RouterLink
                  :to="{ name: 'update-reservation', params: { id: reservationId } }"
                  class="inline-flex w-full items-center justify-between rounded-xl border border-neutral-900 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-900 transition hover:bg-neutral-900 hover:text-white"
                >
                  <span>Modifier le dossier</span>
                  <ArrowUpRight :size="15" />
                </RouterLink>

                <RouterLink
                  :to="{ name: 'admin-reservations' }"
                  class="inline-flex w-full items-center justify-center rounded-xl border border-neutral-300 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900"
                >
                  Annuler / Retour
                </RouterLink>
              </div>
            </div>

            <!-- DROITE : CRÉNEAUX, ÉQUIPEMENTS & GESTION -->
            <div class="flex flex-col justify-between px-7 py-9 sm:px-10">
              <div>
                <p class="text-[12px] font-medium text-slate-800">Ce qui est inclus</p>

                <div class="mt-6 space-y-4">
                  <div class="flex gap-3 border-b border-[#efeee9] pb-3.5">
                    <Clock :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                    <div>
                      <p class="text-[12px] font-medium text-[#5d5d5d]">Créneau début</p>
                      <p class="mt-0.5 text-[13px] font-medium text-[#222]">
                        {{ formatDateTime(reservation.date_heure_debut) }}
                      </p>
                    </div>
                  </div>

                  <div class="flex gap-3 border-b border-[#efeee9] pb-3.5">
                    <Clock :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                    <div>
                      <p class="text-[12px] font-medium text-[#5d5d5d]">Créneau fin</p>
                      <p class="mt-0.5 text-[13px] font-medium text-[#222]">
                        {{ formatDateTime(reservation.date_heure_fin) }}
                      </p>
                    </div>
                  </div>

                  <div class="flex gap-3 border-b border-[#efeee9] pb-3.5">
                    <UsersIcon :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                    <div>
                      <p class="text-[12px] font-medium text-[#5d5d5d]">Participants</p>
                      <p class="mt-0.5 text-[13px] font-medium text-[#222]">
                        {{ reservation.nombre_personnes }} personnes prévues
                      </p>
                    </div>
                  </div>

                  <div class="flex gap-3">
                    <Server :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                    <div class="min-w-0 flex-1">
                      <p class="text-[12px] font-medium text-[#5d5d5d]">Équipements associés</p>
                      <div
                        v-if="!reservation.equipements || reservation.equipements.length === 0"
                        class="mt-1 text-[12px] text-[#777]"
                      >
                        Aucun matériel réservé
                      </div>
                      <div v-else class="mt-1.5 flex flex-wrap gap-1.5">
                        <span
                          v-for="eq in reservation.equipements"
                          :key="eq.id"
                          class="inline-flex items-center gap-1 rounded-[6px] border border-[#deddd9] bg-[#fafaf8] px-2 py-0.5 text-[11px] font-medium text-[#333]"
                        >
                          {{ eq.nom }}
                          <span class="font-semibold text-slate-700">×{{ eq.quantity }}</span>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- DÉTAILS ADMINISTRATIFS & HISTORIQUE -->
              <div class="mt-9 border-t border-[#ecebe7] pt-6">
                <div class="flex items-center gap-2 mb-3">
                  <Calendar :size="16" class="text-slate-800" />
                  <h2 class="text-[12px] font-semibold uppercase tracking-[0.08em] text-[#222]">
                    Données administratives
                  </h2>
                </div>

                <div class="space-y-2 rounded-[8px] border border-[#deddd9] bg-[#fafaf8] p-3 text-[11px]">
                  <div class="flex items-center justify-between text-[#777]">
                    <span>Statut</span>
                    <span
                      class="font-semibold uppercase"
                      :class="{
                        'text-emerald-700': reservation.status === 'confirmee',
                        'text-amber-700': reservation.status === 'en_attente',
                        'text-slate-700': reservation.status === 'terminee',
                        'text-rose-700': reservation.status === 'rejetee',
                      }"
                    >
                      {{ reservation.status }}
                    </span>
                  </div>

                  <div class="flex items-center justify-between text-[#777]">
                    <span>Créée par</span>
                    <span class="font-semibold text-[#191919]">
                      {{ reservation.createur?.nom || (reservation.creer_par ? 'Admin #' + reservation.creer_par : 'Système') }}
                    </span>
                  </div>

                  <div class="flex items-center justify-between text-[#777]">
                    <span>Enregistrée le</span>
                    <span class="font-semibold text-[#191919]">{{ formatDateTime(reservation.created_at) }}</span>
                  </div>

                  <div v-if="reservation.terminee_at" class="flex items-center justify-between text-[#777]">
                    <span>Clôturée le</span>
                    <span class="font-semibold text-[#191919]">{{ formatDateTime(reservation.terminee_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </AppAdmin>
</template>
