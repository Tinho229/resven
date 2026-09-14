<script setup>
import { computed } from "vue";
import { Clock, CheckCircle2, XCircle, CircleSlash, CheckCheck, AlertCircle } from "@lucide/vue";
import { useReservationStatut } from "@/composables/useReservationStatut";

const props = defineProps({
  statut: {
    type: String,
    default: "",
  },
  reservation: {
    type: Object,
    default: null,
  },
});

const { getStatutEffectif } = useReservationStatut();

const statutAffiche = computed(() => {
  if (props.reservation) {
    return getStatutEffectif(props.reservation);
  }
  return props.statut;
});

const config = {
  en_attente: { label: "En attente", icon: Clock, classes: "bg-amber-50 text-amber-800 border-amber-200" },
  confirmee: { label: "Confirmée", icon: CheckCircle2, classes: "bg-emerald-50 text-emerald-800 border-emerald-200" },
  rejetee: { label: "Rejetée", icon: XCircle, classes: "bg-red-50 text-red-800 border-red-200" },
  terminee: { label: "Terminée", icon: CheckCheck, classes: "bg-slate-100 text-slate-600 border-slate-200" },
  annulee: { label: "Annulée", icon: CircleSlash, classes: "bg-slate-100 text-slate-500 border-slate-200" },
  expiree: { label: "Expirée", icon: AlertCircle, classes: "bg-rose-50 text-rose-700 border-rose-200" },
};
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border"
    :class="config[statutAffiche]?.classes || 'bg-slate-100 text-slate-600 border-slate-200'"
  >
    <component :is="config[statutAffiche]?.icon" class="w-3.5 h-3.5" />
    {{ config[statutAffiche]?.label || statutAffiche }}
  </span>
</template>
