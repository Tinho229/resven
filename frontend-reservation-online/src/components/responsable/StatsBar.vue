<script setup>
import { ListChecks, Clock, CheckCircle2, XCircle, CheckCheck } from "@lucide/vue";

defineProps({
  compteurs: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-start justify-between">
      <div>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ compteurs.total }}</p>
      </div>
      <ListChecks class="w-5 h-5 text-slate-400" />
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4 border-l-4 border-l-amber-500 flex items-start justify-between">
      <div>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">En attente</p>
        <p class="mt-1 text-2xl font-semibold text-amber-700">{{ compteurs.en_attente }}</p>
      </div>
      <Clock class="w-5 h-5 text-amber-500" />
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4 border-l-4 border-l-emerald-500 flex items-start justify-between">
      <div>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Confirmées</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ compteurs.confirmee }}</p>
      </div>
      <CheckCircle2 class="w-5 h-5 text-emerald-500" />
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4 border-l-4 border-l-slate-400 flex items-start justify-between">
      <div>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Terminées</p>
        <p class="mt-1 text-2xl font-semibold text-slate-700">{{ compteurs.terminee ?? 0 }}</p>
      </div>
      <CheckCheck class="w-5 h-5 text-slate-400" />
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-start justify-between">
      <div>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Rejetées</p>
        <p class="mt-1 text-2xl font-semibold text-red-700">{{ compteurs.rejetee }}</p>
      </div>
      <XCircle class="w-5 h-5 text-red-500" />
    </div>
  </div>
</template>
