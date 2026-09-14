<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from "@headlessui/vue";
import { X, Phone, Users, Calendar, Package, UserCog, MapPin, Banknote } from "@lucide/vue";
import StatusBadge from "./StatusBadge.vue";
import { useReservationStatut } from "@/composables/useReservationStatut";
import { formatDateTime } from "@/helpers/dateHelper";

defineProps({
  open: Boolean,
  reservation: {
    type: Object,
    default: null,
  },
});

const { estEnCours, estTerminee, estAVenir, tempsRestant, tempsAvantDebut } = useReservationStatut();

const emit = defineEmits(["fermer"]);

function formatDate(dateString) {
  if (!dateString) return "—";
  return formatDateTime(dateString);
}
</script>

<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" class="relative z-50" @close="emit('fermer')">
      <TransitionChild enter="duration-200 ease-out" enter-from="opacity-0" enter-to="opacity-100"
        leave="duration-150 ease-in" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 flex items-center justify-center px-4 py-8 overflow-y-auto">
        <TransitionChild enter="duration-200 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100"
          leave="duration-150 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
          <DialogPanel v-if="reservation"
            class="w-full max-w-lg bg-white rounded-xl shadow-xl p-6 max-h-[85vh] overflow-y-auto">
            <div class="flex items-start justify-between mb-4">
              <div>
                <DialogTitle class="text-base font-semibold text-slate-900">
                  Réservation #{{ reservation.id }}
                </DialogTitle>
                <p class="text-xs text-slate-500 mt-0.5">
                  Créée le {{ formatDate(reservation.created_at) }}
                </p>
              </div>
              <button type="button" class="text-slate-400 hover:text-slate-600" @click="emit('fermer')">
                <X class="w-5 h-5" />
              </button>
            </div>

            <div class="mb-5 flex items-center gap-2">
              <StatusBadge :statut="reservation.status" />
              <span v-if="estEnCours(reservation)"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-600 text-white">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
                EN COURS · {{ tempsRestant(reservation) }}
              </span>
              <span v-if="estAVenir(reservation)"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-violet-50 text-violet-600 border border-violet-200">
                {{ tempsAvantDebut(reservation) }}
              </span>
              <span v-if="estTerminee(reservation)"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                Terminée
              </span>
            </div>

            <!-- Section Demandeur -->
            <div class="mb-5">
              <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Demandeur</h4>
              <div class="flex gap-3">
                <Users class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                <div>
                  <p class="font-medium text-slate-900">{{ reservation.nom_demandeur }}</p>
                  <p v-if="!reservation.user" class="text-xs text-slate-500 mt-0.5">Client sans compte</p>
                  <p v-if="reservation.user?.email" class="text-xs text-slate-500">{{ reservation.user.email }}</p>
                </div>
              </div>
              <div v-if="reservation.user?.telephone || reservation.telephone_client" class="flex gap-3 mt-2">
                <Phone class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                <p class="text-slate-700">{{ reservation.user?.telephone || reservation.telephone_client }}</p>
              </div>
              <div v-if="reservation.cree_par" class="flex gap-3 mt-2">
                <UserCog class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                <p class="text-slate-700">Créée manuellement par {{ reservation.cree_par.nom }}</p>
              </div>
            </div>

            <!-- Section Période -->
            <div class="mb-5">
              <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Période</h4>
              <div class="flex gap-3">
                <Calendar class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                <div>
                  <p class="text-slate-700">
                    {{ formatDate(reservation.date_heure_debut) }} → {{ formatDate(reservation.date_heure_fin) }}
                  </p>
                  <p class="text-xs text-slate-500 mt-0.5">{{ reservation.nombre_personnes }} personne(s)</p>
                </div>
              </div>
            </div>

            <!-- Section Salle complète -->
            <div v-if="reservation.salle" class="mb-5">
              <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Salle réservée</h4>

              <div v-if="reservation.salle.images?.length" class="flex gap-2 mb-3 overflow-x-auto">
                <img v-for="image in reservation.salle.images" :key="image.id" :src="image.path"
                  :alt="image.designation || reservation.salle.nom"
                  class="w-24 h-16 object-cover rounded-lg border border-slate-200 shrink-0" />
              </div>

              <p class="font-medium text-slate-900">{{ reservation.salle.nom }}</p>
              <p class="text-sm text-slate-600 mt-1">{{ reservation.salle.description }}</p>

              <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
                <div class="flex items-center gap-2 text-slate-600">
                  <Users class="w-3.5 h-3.5 text-slate-400" />
                  Capacité : {{ reservation.salle.capacite }}
                </div>
                <div class="flex items-center gap-2 text-slate-600">
                  <MapPin class="w-3.5 h-3.5 text-slate-400" />
                  {{ reservation.salle.localisation }}
                </div>
                <div class="flex items-center gap-2 text-slate-600 col-span-2">
                  <Banknote class="w-3.5 h-3.5 text-slate-400" />
                  {{ reservation.salle.prix }} FCFA
                </div>
              </div>
            </div>

            <!-- Section Équipements complets -->
            <div v-if="reservation.equipements?.length" class="mb-2">
              <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Équipements demandés</h4>
              <div v-for="eq in reservation.equipements" :key="eq.id"
                class="flex items-start gap-3 py-2 border-b border-slate-100 last:border-0">
                <Package class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                <div class="flex-1">
                  <p class="font-medium text-slate-900">{{ eq.nom }} × {{ eq.pivot.quantity }}</p>
                  <p class="text-xs text-slate-500 mt-0.5">{{ eq.description }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">Stock total : {{ eq.stock_total }}</p>
                </div>
              </div>
            </div>

            <div class="mt-6 flex justify-end">
              <button type="button"
                class="px-4 py-2 text-sm font-medium text-slate-600 rounded-lg hover:bg-slate-100 transition"
                @click="emit('fermer')">
                Fermer
              </button>
            </div>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
