<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import AppAdmin from '@/components/admin/AppAdmin.vue'
import { useAdminReservationsStore } from '@/store/adminReservations'
import { formatDateTime, parseLocalDate } from '@/helpers/dateHelper'
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
const actionLoading = ref(false)
const feedbackMessage = ref(null)

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

// --- Carrousel d'images ---
const currentImageIndex = ref(0)
let slideshowTimer = null

const imagesList = computed(() => {
  const salle = reservation.value?.salle
  if (!salle) return [defaultPlaceholder]
  const rawImages = salle.images?.data ?? salle.images
  if (!rawImages || !Array.isArray(rawImages) || rawImages.length === 0) return [defaultPlaceholder]
  const urls = rawImages.map(img => img?.url || img?.path || img?.image_url).filter(Boolean)
  return urls.length > 0 ? urls : [defaultPlaceholder]
})

const activeImage = computed(() => imagesList.value[currentImageIndex.value] ?? defaultPlaceholder)

const startSlideshow = () => {
  stopSlideshow()
  if (imagesList.value.length > 1) {
    slideshowTimer = setInterval(() => {
      currentImageIndex.value = (currentImageIndex.value + 1) % imagesList.value.length
    }, 4000)
  }
}

const stopSlideshow = () => {
  if (slideshowTimer) {
    clearInterval(slideshowTimer)
    slideshowTimer = null
  }
}

const goToImage = (index) => {
  currentImageIndex.value = index
  startSlideshow()
}

// Redémarre le carrousel quand la réservation (et ses images) est chargée
watch(imagesList, () => {
  currentImageIndex.value = 0
  startSlideshow()
}, { immediate: false })

onMounted(() => {
  loadDetails()
})

onUnmounted(() => {
  stopSlideshow()
})

const statusInfo = computed(() => {
  if (!reservation.value) return { label: '', class: '', bgClass: '', isExpired: false, isFinished: false }
  const st = reservation.value.status
  const debut = parseLocalDate(reservation.value.date_heure_debut)
  const fin = parseLocalDate(reservation.value.date_heure_fin)
  const now = new Date()

  if (st === 'en_attente') {
    if (debut && debut <= now) {
      return {
        label: 'Expirée (Rejetée)',
        class: 'text-rose-700',
        bgClass: 'bg-rose-500/90',
        isExpired: true,
        isFinished: false,
      }
    }
    return {
      label: 'En attente',
      class: 'text-amber-700',
      bgClass: 'bg-amber-500/90',
      isExpired: false,
      isFinished: false,
    }
  }

  if (st === 'confirmee') {
    if (fin && fin <= now) {
      return {
        label: 'Terminée',
        class: 'text-slate-700',
        bgClass: 'bg-slate-700/90',
        isExpired: false,
        isFinished: true,
      }
    }
    return {
      label: 'Confirmée',
      class: 'text-emerald-700',
      bgClass: 'bg-emerald-500/90',
      isExpired: false,
      isFinished: false,
    }
  }

  if (st === 'terminee') {
    return {
      label: 'Terminée',
      class: 'text-slate-700',
      bgClass: 'bg-slate-700/90',
      isExpired: false,
      isFinished: true,
    }
  }

  if (st === 'rejetee') {
    return {
      label: 'Rejetée',
      class: 'text-rose-700',
      bgClass: 'bg-rose-500/90',
      isExpired: true,
      isFinished: false,
    }
  }

  if (st === 'annulee') {
    return {
      label: 'Annulée',
      class: 'text-gray-700',
      bgClass: 'bg-gray-500/90',
      isExpired: false,
      isFinished: false,
    }
  }

  return { label: st || 'Inconnu', class: 'text-gray-700', bgClass: 'bg-gray-500/90', isExpired: false, isFinished: false }
})

const handleConfirm = async () => {
  actionLoading.value = true
  feedbackMessage.value = null
  try {
    const res = await adminReservationsStore.confirmReservation(reservationId)
    feedbackMessage.value = {
      type: 'success',
      text: res?.message || 'Réservation confirmée avec succès.',
    }
    await loadDetails()
  } catch (e) {
    const msg = e?.response?.data?.message || adminReservationsStore.errorMessage || 'Impossible de confirmer cette réservation.'
    feedbackMessage.value = { type: 'error', text: msg }
    await loadDetails()
  } finally {
    actionLoading.value = false
  }
}

const handleReject = async () => {
  actionLoading.value = true
  feedbackMessage.value = null
  try {
    const res = await adminReservationsStore.rejectReservation(reservationId)
    feedbackMessage.value = {
      type: 'success',
      text: res?.message || 'Réservation rejetée avec succès.',
    }
    await loadDetails()
  } catch (e) {
    const msg = e?.response?.data?.message || adminReservationsStore.errorMessage || 'Impossible de rejeter cette réservation.'
    feedbackMessage.value = { type: 'error', text: msg }
  } finally {
    actionLoading.value = false
  }
}

const handleTerminate = async () => {
  actionLoading.value = true
  feedbackMessage.value = null
  try {
    const res = await adminReservationsStore.terminateReservation(reservationId)
    feedbackMessage.value = {
      type: 'success',
      text: res?.message || 'Réservation clôturée avec succès.',
    }
    await loadDetails()
  } catch (e) {
    const msg = e?.response?.data?.message || adminReservationsStore.errorMessage || 'Impossible de clôturer cette réservation.'
    feedbackMessage.value = { type: 'error', text: msg }
  } finally {
    actionLoading.value = false
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
            v-if="reservation.status === 'en_attente' && !statusInfo.isExpired"
            type="button"
            :disabled="actionLoading"
            class="inline-flex items-center gap-1.5 rounded-[8px] bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 active:scale-95 disabled:opacity-50 cursor-pointer"
            @click="handleConfirm"
          >
            <Loader2 v-if="actionLoading" :size="14" class="animate-spin" />
            <Check v-else :size="14" />
            <span>Confirmer</span>
          </button>
          <span
            v-else-if="reservation.status === 'en_attente' && statusInfo.isExpired"
            class="inline-flex items-center gap-1.5 rounded-[8px] border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600"
          >
            Délai expiré (non confirmable)
          </span>

          <!-- Clôturer -->
          <button
            v-if="reservation.status === 'confirmee' && !statusInfo.isFinished"
            type="button"
            :disabled="actionLoading"
            class="inline-flex items-center gap-1.5 rounded-[8px] bg-slate-800 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-black active:scale-95 disabled:opacity-50 cursor-pointer"
            @click="handleTerminate"
          >
            <Loader2 v-if="actionLoading" :size="14" class="animate-spin" />
            <Flag v-else :size="14" />
            <span>Marquer terminée</span>
          </button>

          <!-- Rejeter -->
          <button
            v-if="reservation.status === 'en_attente' || reservation.status === 'confirmee'"
            type="button"
            :disabled="actionLoading"
            class="inline-flex items-center gap-1.5 rounded-[8px] border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 active:scale-95 disabled:opacity-50 cursor-pointer"
            @click="handleReject"
          >
            <Loader2 v-if="actionLoading" :size="14" class="animate-spin" />
            <X v-else :size="14" />
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

      <!-- BANNIÈRE DE NOTIFICATION ACTION -->
      <div
        v-if="feedbackMessage"
        class="mb-6 flex items-center justify-between rounded-[10px] p-3.5 text-xs font-medium"
        :class="feedbackMessage.type === 'success' ? 'border border-emerald-200 bg-emerald-50 text-emerald-800' : 'border border-rose-200 bg-rose-50 text-rose-800'"
      >
        <span>{{ feedbackMessage.text }}</span>
        <button type="button" @click="feedbackMessage = null" class="ml-3 text-sm font-bold opacity-70 hover:opacity-100">&times;</button>
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
            <!-- IMAGE GAUCHE (Carrousel) -->
            <div class="relative min-h-[390px] overflow-hidden bg-[#e9e8e4] lg:min-h-0">
              <!-- Images avec transition fondue -->
              <transition-group name="img-fade" tag="div" class="absolute inset-0">
                <img
                  v-for="(imgUrl, idx) in imagesList"
                  :key="imgUrl"
                  v-show="idx === currentImageIndex"
                  :src="imgUrl"
                  :alt="reservation.salle?.nom || 'Salle réservée'"
                  class="absolute inset-0 h-full w-full object-cover"
                />
              </transition-group>

              <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-black/5"></div>

              <!-- Points de navigation (visible si plusieurs images) -->
              <div
                v-if="imagesList.length > 1"
                class="absolute bottom-16 left-0 right-0 flex justify-center gap-1.5 z-10"
              >
                <button
                  v-for="(_, idx) in imagesList"
                  :key="idx"
                  type="button"
                  @click="goToImage(idx)"
                  class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"
                  :class="idx === currentImageIndex ? 'w-5 bg-white' : 'w-1.5 bg-white/50 hover:bg-white/80'"
                />
              </div>

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
                    'bg-emerald-500/90': reservation.status === 'confirmee' && new Date(reservation.date_heure_fin) > new Date(),
                    'bg-amber-500/90': reservation.status === 'en_attente' && new Date(reservation.date_heure_debut) > new Date(),
                    'bg-slate-700/90': reservation.status === 'terminee' || (reservation.status === 'confirmee' && new Date(reservation.date_heure_fin) <= new Date()),
                    'bg-rose-500/90': reservation.status === 'rejetee' || (reservation.status === 'en_attente' && new Date(reservation.date_heure_debut) <= new Date()),
                  }"
                >
                  {{ reservation.status === 'en_attente' && new Date(reservation.date_heure_debut) <= new Date() ? 'Expirée' : (reservation.status === 'confirmee' && new Date(reservation.date_heure_fin) <= new Date() ? 'Terminée' : reservation.status) }}
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
                        'text-emerald-700': reservation.status === 'confirmee' && !statusInfo.isFinished,
                        'text-amber-700': reservation.status === 'en_attente' && !statusInfo.isExpired,
                        'text-slate-700': reservation.status === 'terminee' || statusInfo.isFinished,
                        'text-rose-700': reservation.status === 'rejetee' || statusInfo.isExpired,
                        'text-gray-500': reservation.status === 'annulee',
                      }"
                    >
                      {{ statusInfo.label }}
                    </span>
                  </div>

                  <div class="flex items-center justify-between text-[#777]">
                    <span>Créée par</span>
                    <span class="font-semibold text-[#191919]">
                      {{ reservation.createur?.nom || (reservation.cree_par_id ? 'Admin #' + reservation.cree_par_id : 'Système') }}
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

<style scoped>
/* Transition fondue pour le carrousel d'images */
.img-fade-enter-active,
.img-fade-leave-active {
  transition: opacity 0.8s ease;
  position: absolute;
  inset: 0;
}
.img-fade-enter-from,
.img-fade-leave-to {
  opacity: 0;
}
.img-fade-enter-to,
.img-fade-leave-from {
  opacity: 1;
}
</style>
