<script setup>
import { ref, reactive, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import Auth from '@/layouts/Auth.vue'
import { useAuthStore } from '@/store/auth'

const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

onMounted(() => {
  authStore.clearErrors()
})

const handleLogin = async () => {
  try {
    await authStore.login({
      email: form.email,
      password: form.password,
    })
  } catch (error) {
    // Erreurs déjà capturées et stockées dans authStore
  }
}
</script>

<template>
  <Auth>
    <div>
      <!-- Titre -->
      <h1 class="mb-5 text-center text-[26px] font-extrabold tracking-[-1px] text-[#111111] sm:mb-6 sm:text-[32px] sm:tracking-[-1.5px]">
        Connexion
      </h1>

      <!-- Message d'erreur global -->
      <div
        v-if="authStore.errorMessage"
        class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3.5 text-[14px] text-red-700"
      >
        {{ authStore.errorMessage }}
      </div>

      <!-- Message de succès -->
      <div
        v-if="authStore.successMessage"
        class="mb-5 rounded-lg border border-green-200 bg-green-50 p-3.5 text-[14px] text-green-700"
      >
        {{ authStore.successMessage }}
      </div>

      <!-- Boutons informatifs -->
      <div class="mb-5 grid grid-cols-2 gap-2 sm:mb-6 sm:gap-3">
        <div class="flex h-[36px] items-center justify-center rounded-full bg-[#e6f0f6] text-[12px] font-medium text-[#111111] sm:h-[38px] sm:text-[13px]">
          <span> Rapide & Simple</span>
        </div>
        <div class="flex h-[36px] items-center justify-center rounded-full bg-[#e6f0f6] text-[12px] font-medium text-[#111111] sm:h-[38px] sm:text-[13px]">
          <span> Accès Sécurisé</span>
        </div>
      </div>

      <!-- Formulaire -->
      <form class="space-y-4" @submit.prevent="handleLogin">
        <!-- E-mail -->
        <div>
          <label for="email" class="mb-1.5 block text-[14px] font-medium text-[#111111]">
            E-mail <span class="text-red-500">*</span>
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            placeholder="votre.email@exemple.com"
            class="h-[42px] w-full rounded-[8px] border border-[#c9c9c9] bg-white px-3.5 text-[14px] outline-none transition focus:border-black focus:ring-1 focus:ring-black"
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': authStore.errors.email }"
          />
          <p
            v-if="authStore.errors.email"
            class="mt-1 text-[13px] text-red-600"
          >
            {{ authStore.errors.email[0] }}
          </p>
        </div>

        <!-- Mot de passe -->
        <div>
          <label for="password" class="mb-1.5 block text-[14px] font-medium text-[#111111]">
            Mot de passe <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              autocomplete="current-password"
              placeholder="••••••••"
              class="h-[42px] w-full rounded-[8px] border border-[#c9c9c9] bg-white px-3.5 pr-10 text-[14px] outline-none transition focus:border-black focus:ring-1 focus:ring-black"
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': authStore.errors.password }"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800"
            >
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
            </button>
          </div>
          <p
            v-if="authStore.errors.password"
            class="mt-1 text-[13px] text-red-600"
          >
            {{ authStore.errors.password[0] }}
          </p>
        </div>

        <!-- Mot de passe oublié & Rester connecté -->
        <div class="flex items-center justify-between pt-1">
          <label class="flex cursor-pointer items-center gap-2">
            <input
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 rounded border-gray-300 text-black focus:ring-black"
            />
            <span class="text-[13px] text-gray-700">Rester connecté</span>
          </label>
        </div>

        <!-- Bouton de soumission -->
        <button
          type="submit"
          :disabled="authStore.loading"
          class="mt-4 flex h-[42px] w-full items-center justify-center rounded-full bg-[#111111] text-[14px] font-medium text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-60"
        >
          <svg
            v-if="authStore.loading"
            class="mr-2 h-4 w-4 animate-spin text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>{{ authStore.loading ? 'Connexion en cours...' : 'Se connecter' }}</span>
        </button>
      </form>

      <!-- Lien Inscription -->
      <div class="mt-8 text-center text-[14px] text-gray-600">
        <p>
          Vous n'avez pas encore de compte ?
          <RouterLink
            :to="{ name: 'register' }"
            class="font-semibold text-[#3158d4] hover:underline"
          >
            Inscrivez-vous
          </RouterLink>
        </p>
      </div>
    </div>
  </Auth>
</template>