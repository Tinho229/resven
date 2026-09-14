<script setup>
import { ref, computed, watch } from "vue";
import StatusBadge from "./StatusBadge.vue";
import ConfirmActionDialog from "./ConfirmActionDialog.vue";
import DetailsReservationModal from "./DetailsReservationModal.vue";
import { useReservationStatut } from "@/composables/useReservationStatut";
import { Phone, Eye, Search, Loader2, ChevronLeft, ChevronRight, Check, X } from "lucide-vue-next";
import { formatDateTime, parseLocalDate } from "@/helpers/dateHelper";

const props = defineProps({
  reservations: { type: Array, required: true },
  actionLoadingId: { type: [Number, String, null], default: null },
  showActions: { type: Boolean, default: true },
});

const emit = defineEmits(["confirmer", "rejeter", "annuler"]);

const { estEnCours, estTerminee, estAVenir, tempsRestant, tempsAvantDebut, estExpiree, peutEtreConfirmee } = useReservationStatut();

// Recherche
const recherche = ref("");
const reservationsFiltrees = computed(() => {
  if (!recherche.value.trim()) return props.reservations;
  const terme = recherche.value.trim().toLowerCase();
  return props.reservations.filter((r) =>
    r.nom_demandeur?.toLowerCase().includes(terme) ||
    r.salle?.nom?.toLowerCase().includes(terme)
  );
});

// Pagination
const pageActuelle = ref(1);
const parPage = 8;

const totalPages = computed(() =>
  Math.max(1, Math.ceil(reservationsFiltrees.value.length / parPage))
);
const reservationsPage = computed(() => {
  const debut = (pageActuelle.value - 1) * parPage;
  return reservationsFiltrees.value.slice(debut, debut + parPage);
});

watch([recherche, () => props.reservations.length], () => {
  pageActuelle.value = 1;
});

function pagePrecedente() { if (pageActuelle.value > 1) pageActuelle.value--; }
function pageSuivante() { if (pageActuelle.value < totalPages.value) pageActuelle.value++; }

// Boîte de confirmation
const dialogue = ref({ ouvert: false, action: null, reservation: null });
function demanderAction(action, reservation) { dialogue.value = { ouvert: true, action, reservation }; }
function annulerDialogue() { dialogue.value = { ouvert: false, action: null, reservation: null }; }
function confirmerDialogue() {
  const { action, reservation } = dialogue.value;
  emit(action, reservation.id);
  annulerDialogue();
}

// Modale de détails
const detailsOuverts = ref(false);
const reservationSelectionnee = ref(null);
function ouvrirDetails(reservation) {
  reservationSelectionnee.value = reservation;
  detailsOuverts.value = true;
}

function formatDate(dateString) {
  return formatDateTime(dateString);
}
function formatDateCourt(dateString) {
  const d = parseLocalDate(dateString);
  if (!d) return "—";
  return new Intl.DateTimeFormat("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  }).format(d);
}

function categorieDate(reservation) {
  const aujourdHui = new Date();
  aujourdHui.setHours(0, 0, 0, 0);
  const demain = new Date(aujourdHui);
  demain.setDate(demain.getDate() + 1);
  const debut = parseLocalDate(reservation.date_heure_debut);
  if (!debut) return "À venir";
  if (debut < aujourdHui) return "Passées";
  if (debut < demain) return "Aujourd'hui";
  return "À venir";
}

const reservationsGroupees = computed(() => {
  const ordre = ["Aujourd'hui", "À venir", "Passées"];
  const groupes = {};
  for (const reservation of reservationsPage.value) {
    const cat = categorieDate(reservation);
    if (!groupes[cat]) groupes[cat] = [];
    groupes[cat].push(reservation);
  }
  return ordre
    .filter((cat) => groupes[cat]?.length)
    .map((cat) => ({ label: cat, items: groupes[cat] }));
});
</script>

<template>
  <div>
    <!-- Barre de recherche -->
    <div class="mb-4 relative max-w-xs">
      <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
      <input
        v-model="recherche"
        type="text"
        placeholder="Rechercher…"
        class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
      />
    </div>

    <!-- Aucune réservation -->
    <div v-if="reservationsFiltrees.length === 0"
      class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-500 text-sm">
      Aucune réservation ne correspond.
    </div>

    <div v-else>
      <!-- Vue MOBILE : cartes empilées -->
      <div class="flex flex-col gap-3 md:hidden">
        <template v-for="groupe in reservationsGroupees" :key="groupe.label">
          <!-- Séparateur de groupe -->
          <div class="px-1 pt-1">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
              {{ groupe.label }}
            </span>
          </div>

          <!-- Cartes réservation -->
          <div
            v-for="reservation in groupe.items"
            :key="reservation.id"
            class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm"
            :class="{
              'border-l-4 border-l-amber-400': reservation.status === 'en_attente' && !estExpiree(reservation),
              'border-l-4 border-l-blue-500': estEnCours(reservation),
              'border-l-4 border-l-rose-400': estExpiree(reservation),
            }"
          >
            <!-- En-tête de carte -->
            <div class="flex items-start justify-between gap-2 mb-3">
              <div>
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="font-semibold text-slate-900 text-sm">{{ reservation.nom_demandeur }}</span>
                  <span
                    v-if="!reservation.user"
                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-violet-50 text-violet-700 border border-violet-200"
                  >
                    <Phone class="w-2.5 h-2.5" />Manuel
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">{{ reservation.salle?.nom }}</p>
              </div>
              <div class="flex items-center gap-1.5 shrink-0">
                <StatusBadge :statut="reservation.status" />
                <button
                  type="button"
                  class="p-1.5 rounded-lg border border-slate-200 text-slate-400 hover:text-slate-700 hover:bg-slate-50 transition"
                  title="Voir les détails"
                  @click="ouvrirDetails(reservation)"
                >
                  <Eye class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- Dates -->
            <div class="text-xs text-slate-500 mb-3 bg-slate-50 rounded-lg px-3 py-2">
              <span class="font-medium text-slate-700">{{ formatDateCourt(reservation.date_heure_debut) }}</span>
              <span class="mx-1.5 text-slate-400">→</span>
              <span class="font-medium text-slate-700">{{ formatDateCourt(reservation.date_heure_fin) }}</span>
            </div>

            <!-- Badges d'état -->
            <div class="flex flex-wrap gap-1.5 mb-3" v-if="estEnCours(reservation) || estAVenir(reservation) || estExpiree(reservation)">
              <span v-if="estExpiree(reservation) && reservation.status === 'en_attente'"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-rose-50 text-rose-600 border border-rose-200">
                Délai expiré
              </span>
              <span v-if="estEnCours(reservation)"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-600 text-white">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
                EN COURS · {{ tempsRestant(reservation) }}
              </span>
              <span v-if="estAVenir(reservation)"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-violet-50 text-violet-600 border border-violet-200">
                {{ tempsAvantDebut(reservation) }}
              </span>
            </div>

            <!-- Actions -->
            <div v-if="showActions">
              <div v-if="reservation.status === 'en_attente'" class="flex items-center gap-2 flex-wrap">
                <button
                  v-if="peutEtreConfirmee(reservation)"
                  type="button"
                  :disabled="actionLoadingId === reservation.id"
                  class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                  @click="demanderAction('confirmer', reservation)"
                >
                  <Loader2 v-if="actionLoadingId === reservation.id" class="w-3.5 h-3.5 animate-spin" />
                  <Check v-else class="w-3.5 h-3.5" />
                  Confirmer
                </button>
                <span v-else class="text-[11px] font-medium text-rose-600 px-2 py-1 bg-rose-50 rounded border border-rose-200">
                  Expirée
                </span>
                <button
                  type="button"
                  :disabled="actionLoadingId === reservation.id"
                  class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-red-600 border border-red-200 rounded-lg hover:bg-red-50 disabled:opacity-50 transition"
                  @click="demanderAction('rejeter', reservation)"
                >
                  <Loader2 v-if="actionLoadingId === reservation.id" class="w-3.5 h-3.5 animate-spin" />
                  <X v-else class="w-3.5 h-3.5" />
                  Rejeter
                </button>
              </div>
              <div v-else-if="reservation.status === 'confirmee' && !estTerminee(reservation)">
                <button
                  type="button"
                  :disabled="actionLoadingId === reservation.id"
                  class="w-full flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-red-600 border border-red-200 rounded-lg hover:bg-red-50 disabled:opacity-50 transition"
                  @click="demanderAction('annuler', reservation)"
                >
                  <Loader2 v-if="actionLoadingId === reservation.id" class="w-3.5 h-3.5 animate-spin" />
                  Annuler la réservation
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Vue DESKTOP : tableau -->
      <div class="hidden md:block bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs font-medium text-slate-500 uppercase tracking-wide border-b border-slate-200 bg-slate-50/70">
                <th class="px-4 py-3">Demandeur</th>
                <th class="px-4 py-3">Salle</th>
                <th class="px-4 py-3 hidden lg:table-cell">Période</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3 w-8"></th>
                <th v-if="showActions" class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody v-for="groupe in reservationsGroupees" :key="groupe.label">
              <tr>
                <td colspan="6" class="px-4 py-2 bg-slate-50 text-xs font-semibold text-slate-400 uppercase tracking-wide">
                  {{ groupe.label }}
                </td>
              </tr>
              <tr
                v-for="reservation in groupe.items"
                :key="reservation.id"
                class="border-b border-slate-100 last:border-0 hover:bg-slate-50/70 transition"
                :class="{
                  'border-l-4 border-l-amber-400': reservation.status === 'en_attente' && !estExpiree(reservation),
                  'border-l-4 border-l-blue-500 bg-blue-50/30': estEnCours(reservation),
                  'border-l-4 border-l-rose-400': estExpiree(reservation),
                }"
              >
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-medium text-slate-900">{{ reservation.nom_demandeur }}</span>
                    <span v-if="!reservation.user"
                      class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-violet-50 text-violet-700 border border-violet-200">
                      <Phone class="w-3 h-3" />Manuel
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-600 text-sm">{{ reservation.salle?.nom }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs hidden lg:table-cell whitespace-nowrap">
                  {{ formatDate(reservation.date_heure_debut) }} → {{ formatDate(reservation.date_heure_fin) }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <StatusBadge :statut="reservation.status" />
                    <span v-if="reservation.status === 'en_attente' && estExpiree(reservation)"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-rose-50 text-rose-600 border border-rose-200">
                      Expiré
                    </span>
                    <span v-if="estEnCours(reservation)"
                      class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-600 text-white">
                      <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
                      {{ tempsRestant(reservation) }}
                    </span>
                    <span v-if="estAVenir(reservation)"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-violet-50 text-violet-600 border border-violet-200">
                      {{ tempsAvantDebut(reservation) }}
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <button type="button"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                    title="Voir les détails"
                    @click="ouvrirDetails(reservation)">
                    <Eye class="w-4 h-4" />
                  </button>
                </td>
                <td v-if="showActions" class="px-4 py-3 text-right">
                  <div v-if="reservation.status === 'en_attente'" class="flex items-center justify-end gap-2">
                    <button v-if="peutEtreConfirmee(reservation)" type="button"
                      :disabled="actionLoadingId === reservation.id"
                      class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                      @click="demanderAction('confirmer', reservation)">
                      <Loader2 v-if="actionLoadingId === reservation.id" class="w-3.5 h-3.5 animate-spin" />
                      <Check v-else class="w-3 h-3" />
                      Confirmer
                    </button>
                    <span v-else class="text-[11px] font-medium text-rose-600 px-2 py-1 bg-rose-50 rounded border border-rose-200">
                      Expirée
                    </span>
                    <button type="button" :disabled="actionLoadingId === reservation.id"
                      class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 disabled:opacity-50 transition"
                      @click="demanderAction('rejeter', reservation)">
                      <Loader2 v-if="actionLoadingId === reservation.id" class="w-3.5 h-3.5 animate-spin" />
                      <X v-else class="w-3 h-3" />
                      Rejeter
                    </button>
                  </div>
                  <div v-else-if="reservation.status === 'confirmee' && !estTerminee(reservation)" class="flex justify-end">
                    <button type="button" :disabled="actionLoadingId === reservation.id"
                      class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 disabled:opacity-50 transition"
                      @click="demanderAction('annuler', reservation)">
                      <Loader2 v-if="actionLoadingId === reservation.id" class="w-3.5 h-3.5 animate-spin" />
                      Annuler
                    </button>
                  </div>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="reservationsFiltrees.length > parPage"
          class="flex items-center justify-between px-4 py-3 border-t border-slate-200 bg-slate-50/50">
          <p class="text-xs text-slate-500">
            Page {{ pageActuelle }} / {{ totalPages }} — {{ reservationsFiltrees.length }} réservation(s)
          </p>
          <div class="flex gap-2">
            <button type="button" :disabled="pageActuelle === 1"
              class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 transition"
              @click="pagePrecedente">
              <ChevronLeft class="w-4 h-4" />
            </button>
            <button type="button" :disabled="pageActuelle === totalPages"
              class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 transition"
              @click="pageSuivante">
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination mobile -->
      <div v-if="reservationsFiltrees.length > parPage"
        class="flex items-center justify-between mt-3 md:hidden">
        <p class="text-xs text-slate-500">
          Page {{ pageActuelle }} / {{ totalPages }}
        </p>
        <div class="flex gap-2">
          <button type="button" :disabled="pageActuelle === 1"
            class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-40 transition"
            @click="pagePrecedente">
            <ChevronLeft class="w-4 h-4" />
          </button>
          <button type="button" :disabled="pageActuelle === totalPages"
            class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-40 transition"
            @click="pageSuivante">
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <ConfirmActionDialog
      :open="dialogue.ouvert"
      :titre="dialogue.action === 'rejeter' ? 'Rejeter la réservation ?' : dialogue.action === 'annuler' ? 'Annuler la réservation ?' : 'Confirmer la réservation ?'"
      :message="dialogue.action === 'rejeter'
        ? `La demande de ${dialogue.reservation?.nom_demandeur} pour ${dialogue.reservation?.salle?.nom} sera rejetée.`
        : dialogue.action === 'annuler'
          ? `La réservation de ${dialogue.reservation?.nom_demandeur} pour ${dialogue.reservation?.salle?.nom} sera annulée.`
          : `La demande de ${dialogue.reservation?.nom_demandeur} pour ${dialogue.reservation?.salle?.nom} sera confirmée.`"
      :label-confirmer="dialogue.action === 'rejeter' ? 'Rejeter' : dialogue.action === 'annuler' ? 'Annuler' : 'Confirmer'"
      :variant="dialogue.action === 'rejeter' || dialogue.action === 'annuler' ? 'danger' : 'primaire'"
      @confirmer="confirmerDialogue"
      @annuler="annulerDialogue"
    />

    <DetailsReservationModal
      :open="detailsOuverts"
      :reservation="reservationSelectionnee"
      @fermer="detailsOuverts = false"
    />
  </div>
</template>
