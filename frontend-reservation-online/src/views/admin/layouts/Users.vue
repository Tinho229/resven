<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AppAdmin from '@/components/admin/AppAdmin.vue'
import { useAdminUsersStore } from '@/store/adminUsers'
import { useAuthStore } from '@/store/auth'
import {
  UserPlus,
  Eye,
  Pencil,
  Trash2,
  AlertTriangle,
  RefreshCw,
  Search,
  Mail,
  Shield,
  Phone,
} from 'lucide-vue-next'

const adminUsersStore = useAdminUsersStore()
const authStore = useAuthStore()

const search = ref('')
const roleFilter = ref('')
const sortOrder = ref('desc') // 'desc' ou 'asc'

// Modale de confirmation de suppression
const isDeleteModalOpen = ref(false)
const userToDelete = ref(null)
const isDeleting = ref(false)

const loadUsers = async () => {
  try {
    const params = {
      all: 'true',
    }
    if (roleFilter.value) {
      params.role = roleFilter.value
    }
    if (search.value) {
      params.search = search.value
    }
    await adminUsersStore.fetchUsers(params)
  } catch (error) {
    console.error('Erreur chargement utilisateurs :', error)
  }
}

onMounted(() => {
  loadUsers()
})

const handleSearch = () => {
  loadUsers()
}

const handleRoleChange = () => {
  loadUsers()
}

const filteredUsers = computed(() => {
  const result = [...adminUsersStore.users]

  result.sort((a, b) => {
    return sortOrder.value === 'desc' ? b.id - a.id : a.id - b.id
  })

  return result
})

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'admin':
      return 'bg-purple-100 text-purple-700 border-purple-200'
    case 'responsable':
      return 'bg-blue-100 text-blue-700 border-blue-200'
    case 'client':
    case 'user':
    default:
      return 'bg-slate-100 text-slate-700 border-slate-200'
  }
}

const openDeleteModal = (user) => {
  userToDelete.value = user
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  userToDelete.value = null
}

const confirmDelete = async () => {
  if (!userToDelete.value) return
  isDeleting.value = true

  try {
    await adminUsersStore.deleteUser(userToDelete.value.id)
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
          <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-800">
            Gestion des Utilisateurs
          </h1>
          <p class="mt-1 text-xs text-slate-500">
            Consultez, filtrez et administrez l'ensemble des comptes de la plateforme.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
          <button
            type="button"
            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 active:scale-95 cursor-pointer"
            @click="loadUsers"
          >
            <RefreshCw :size="14" :class="{ 'animate-spin': adminUsersStore.loading }" />
            <span>Actualiser</span>
          </button>

          <RouterLink
            :to="{ name: 'create-user' }"
            class="inline-flex items-center gap-2 rounded-xl border border-neutral-900 bg-neutral-900 px-4 py-2 text-xs font-medium uppercase tracking-widest text-white shadow-sm transition hover:bg-black active:scale-95"
          >
            <UserPlus :size="15" />
            <span>Ajouter un utilisateur</span>
          </RouterLink>
        </div>
      </div>

      <!-- FLASH MESSAGES -->
      <div
        v-if="adminUsersStore.successMessage"
        class="mb-4 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800"
      >
        <span>{{ adminUsersStore.successMessage }}</span>
        <button class="font-bold text-emerald-700 hover:text-emerald-900 cursor-pointer" @click="adminUsersStore.successMessage = null">
          ×
        </button>
      </div>

      <div
        v-if="adminUsersStore.errorMessage"
        class="mb-4 flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800"
      >
        <span>{{ adminUsersStore.errorMessage }}</span>
        <button class="font-bold text-rose-700 hover:text-rose-900 cursor-pointer" @click="adminUsersStore.errorMessage = null">
          ×
        </button>
      </div>

      <!-- BARRE DE RECHERCHE & FILTRES RESPONSIVE -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4 rounded-t-xl border border-b-0 border-slate-200 bg-white p-3.5 sm:p-4 shadow-sm">
        <div class="flex flex-1 flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 sm:gap-3">
          <div class="relative w-full sm:w-72">
            <input
              v-model="search"
              type="text"
              placeholder="Rechercher par nom, email..."
              class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 sm:py-1.5 pl-9 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
              @input="handleSearch"
            />
            <Search :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          </div>

          <select
            v-model="roleFilter"
            class="w-full sm:w-auto rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 sm:py-1.5 text-sm text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white"
            @change="handleRoleChange"
          >
            <option value="">Tous les rôles</option>
            <option value="admin">Administrateur</option>
            <option value="responsable">Responsable</option>
            <option value="client">Client</option>
          </select>

          <span class="text-xs text-slate-400 sm:self-center">
            {{ filteredUsers.length }} compte(s)
          </span>
        </div>

        <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <span>Tri :</span>
            <select
              v-model="sortOrder"
              class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 sm:py-1 text-xs font-medium text-slate-700 outline-none focus:border-blue-500"
            >
              <option value="desc">Plus récents</option>
              <option value="asc">Plus anciens</option>
            </select>
          </div>
        </div>
      </div>

      <!-- CHARGEMENT -->
      <div v-if="adminUsersStore.loading" class="flex flex-col items-center justify-center py-20 rounded-b-xl border border-slate-200 bg-white">
        <div class="h-8 w-8 animate-spin rounded-full border-3 border-slate-800 border-t-transparent"></div>
        <p class="mt-3 text-xs font-medium text-slate-500">Chargement des utilisateurs...</p>
      </div>

      <!-- LISTE VIDE -->
      <div
        v-else-if="filteredUsers.length === 0"
        class="flex flex-col items-center justify-center py-16 text-center rounded-b-xl border border-slate-200 bg-white"
      >
        <p class="font-semibold text-slate-800">Aucun utilisateur trouvé</p>
        <p class="mt-1 text-xs text-slate-400">
          Ajustez vos filtres ou enregistrez un nouvel utilisateur.
        </p>
      </div>

      <template v-else>
        <!-- VUE CARTES SUR MOBILE (< md) -->
        <div class="block md:hidden border border-slate-200 rounded-b-xl bg-slate-50/50 p-3 space-y-3">
          <div
            v-for="user in filteredUsers"
            :key="'card-user-' + user.id"
            class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs space-y-3"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 font-bold text-[#4F46E5]">
                  {{ (user.nom || user.name || 'U').charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900">{{ user.nom || user.name }}</h3>
                  <span class="text-[11px] font-mono text-slate-400">#{{ user.id }}</span>
                </div>
              </div>

              <span
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize border"
                :class="getRoleBadgeClass(user.role)"
              >
                {{ user.role }}
              </span>
            </div>

            <div class="rounded-lg bg-slate-50 p-2.5 text-xs text-slate-600 space-y-1">
              <div class="flex items-center gap-2">
                <Mail :size="13" class="text-slate-400 shrink-0" />
                <span class="truncate">{{ user.email }}</span>
              </div>
              <div v-if="user.telephone" class="flex items-center gap-2">
                <Phone :size="13" class="text-slate-400 shrink-0" />
                <span>{{ user.telephone }}</span>
              </div>
            </div>

            <!-- Actions carte mobile -->
            <div class="flex items-center justify-end gap-1.5 pt-2 border-t border-slate-100">
              <RouterLink
                :to="{ name: 'info-user', params: { id: user.id } }"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                title="Voir la fiche"
              >
                <Eye :size="14" />
              </RouterLink>

              <RouterLink
                :to="{ name: 'update-user', params: { id: user.id } }"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                title="Modifier"
              >
                <Pencil :size="14" />
              </RouterLink>

              <button
                type="button"
                :disabled="authStore.user?.id === user.id"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                title="Supprimer"
                @click="openDeleteModal(user)"
              >
                <Trash2 :size="14" />
              </button>
            </div>
          </div>
        </div>

        <!-- TABLEAU DES UTILISATEURS SUR DESKTOP (>= md) -->
        <div class="hidden md:block overflow-x-auto rounded-b-xl border border-slate-200 bg-white shadow-sm">
          <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-slate-50 text-xs text-slate-400 uppercase tracking-wider">
              <tr>
                <th class="py-3 px-4 font-medium w-16">ID</th>
                <th class="py-3 px-4 font-medium">Nom</th>
                <th class="py-3 px-4 font-medium">Email</th>
                <th class="py-3 px-4 font-medium">Rôle</th>
                <th class="py-3 px-4 font-medium text-right w-28">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-600">
              <tr
                v-for="user in filteredUsers"
                :key="user.id"
                class="hover:bg-slate-50 transition"
              >
                <!-- 1. ID -->
                <td class="py-3.5 px-4 font-mono text-xs text-slate-400">
                  #{{ user.id }}
                </td>

                <!-- 2. NOM -->
                <td class="py-3.5 px-4 font-semibold text-slate-800">
                  {{ user.nom || user.name }}
                </td>

                <!-- 3. EMAIL -->
                <td class="py-3.5 px-4 text-xs text-slate-500">
                  {{ user.email }}
                </td>

                <!-- 4. RÔLE -->
                <td class="py-3.5 px-4">
                  <span
                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize border"
                    :class="getRoleBadgeClass(user.role)"
                  >
                    • {{ user.role }}
                  </span>
                </td>

                <!-- 5. ACTIONS -->
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <RouterLink
                      :to="{ name: 'info-user', params: { id: user.id } }"
                      title="Voir les détails"
                      class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    >
                      <Eye :size="13" />
                    </RouterLink>

                    <RouterLink
                      :to="{ name: 'update-user', params: { id: user.id } }"
                      title="Modifier"
                      class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    >
                      <Pencil :size="13" />
                    </RouterLink>

                    <button
                      type="button"
                      title="Supprimer"
                      :disabled="authStore.user?.id === user.id"
                      class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-rose-500 transition hover:bg-rose-50 hover:border-rose-300 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                      @click="openDeleteModal(user)"
                    >
                      <Trash2 :size="13" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
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
              Êtes-vous sûr de vouloir supprimer l'utilisateur
              <strong class="text-gray-800">{{ userToDelete?.nom }}</strong> ({{ userToDelete?.email }}) ?
            </p>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-xl border border-neutral-300 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900 cursor-pointer"
            @click="closeDeleteModal"
          >
            Annuler
          </button>

          <button
            type="button"
            :disabled="isDeleting"
            class="rounded-xl border border-rose-600 bg-rose-600 px-5 py-2.5 text-xs font-medium uppercase tracking-widest text-white transition hover:bg-rose-700 disabled:opacity-50 cursor-pointer"
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
