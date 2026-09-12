<script setup>
import {
    LayoutDashboard,
    Table2,
    SlidersHorizontal,
    Server,
    Image as ImageIcon,
    File,
    LogIn,
    Calendar as CalendarIcon,
    X,
} from 'lucide-vue-next'
import { useAuthStore } from '@/store/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()

const props = defineProps({
    activeItem: {
        type: String,
        default: 'Dashboard',
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close'])

const handleNavClick = () => {
    emit('close')
}

const router = useRouter()
</script>

<template>
    <aside
        class="fixed left-0 top-0 z-50 flex h-screen w-[280px] flex-col
               border-r border-[#E2E8F0] bg-white text-[#0F172A]
               transition-transform duration-300 ease-in-out lg:translate-x-0"
        :class="isOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0'"
    >
        <!-- LOGO -->
        <div class="flex h-[84px] shrink-0 items-center justify-between px-8">
            <div class="flex items-center gap-3">
                <!-- Logo -->
                <div class="relative flex h-7 w-7 items-center justify-center">
                    <span
                        class="absolute h-[5px] w-7 rotate-0 rounded-full bg-[#4F46E5]"
                    ></span>

                    <span
                        class="absolute h-[5px] w-7 rotate-90 rounded-full bg-[#4F46E5]"
                    ></span>

                    <span
                        class="absolute h-[5px] w-7 rotate-45 rounded-full bg-[#4F46E5]"
                    ></span>

                    <span
                        class="absolute h-[5px] w-7 -rotate-45 rounded-full bg-[#4F46E5]"
                    ></span>
                </div>

                <span class="font-bricolage text-[22px] font-bold tracking-[-0.5px] text-[#0F172A]">
                  Resven
                </span>
            </div>

            <!-- BOUTON FERMER (MOBILE / TABLETTE) -->
            <button
                type="button"
                @click="emit('close')"
                class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 lg:hidden transition cursor-pointer"
                aria-label="Fermer le menu"
            >
                <X :size="20" />
            </button>
        </div>

        <!-- SIDEBAR CONTENT (AVEC SCROLL VERTICAL PROPRE) -->
        <div class="flex-1 overflow-y-auto px-6 pb-5">

            <!-- MENU -->
            <div class="mb-7">
                <p
                    class="mb-3 px-3 text-[12px] font-semibold uppercase
                           tracking-[1.2px] text-[#64748B]"
                >
                    USER
                </p>

                <RouterLink :to="{name : 'admin-users'}"
                    @click="handleNavClick"
                    class="flex h-[50px] w-full items-center gap-4 rounded-[10px]
                           bg-[#EEF2FF] px-3 text-left transition-all duration-150 active:scale-[0.98]"
                >
                    <LayoutDashboard
                        :size="20"
                        :stroke-width="1.8"
                        :class="$route.name === router.name ? 'text-[#4F46E5]' : 'text-[#64748B] transition-colors duration-200 group-hover:text-[#0F172A]' "

                    />

                    <span  :class="$route.name === router.name ? 'text-[15px] font-semibold text-[#3730A3]' : 'text-[#64748B] transition-colors duration-200 group-hover:text-[#0F172A]' "
                     >
                        Utilisateur
                    </span>
                </RouterLink>

                <p
                    class=" mt-7 mb-3 px-3 text-[12px] font-semibold uppercase
                           tracking-[1.2px] text-[#64748B]"
                >
                    Statistiques
                </p>

                <nav class="space-y-1 ">

                    <!-- Blank Page -->


                    <!-- Dashboard -->
                    <RouterLink :to="{name : 'admin-dashboard'}"
                        @click="handleNavClick"
                        class="group flex h-[50px] w-full items-center gap-4
                               rounded-[10px] px-3 text-left
                               transition-colors duration-200 active:scale-[0.98]"
                        :class="$route.name === 'admin-dashboard' ? 'bg-[#EEF2FF]' : 'hover:bg-[#F8FAFC]'"
                    >
                        <LayoutDashboard
                            :size="20"
                            :stroke-width="1.8"
                            :class="$route.name === 'admin-dashboard' ? 'text-[#4F46E5]' : 'text-[#64748B] transition-colors duration-200 group-hover:text-[#0F172A]'"
                        />

                        <span
                            :class="$route.name === 'admin-dashboard' ? 'text-[15px] font-semibold text-[#3730A3]' : 'text-[15px] font-medium text-[#64748B] transition-colors duration-200 group-hover:text-[#0F172A]'"
                        >
                            Dashboard
                        </span>
                    </RouterLink>

                    <!-- Calendar / Agenda -->
                    <RouterLink :to="{name : 'admin-calendar'}"
                        @click="handleNavClick"
                        class="group flex h-[50px] w-full items-center gap-4
                               rounded-[10px] px-3 text-left
                               transition-colors duration-200 active:scale-[0.98]"
                        :class="$route.name === 'admin-calendar' ? 'bg-[#EEF2FF]' : 'hover:bg-[#F8FAFC]'"
                    >
                        <CalendarIcon
                            :size="20"
                            :stroke-width="1.8"
                            :class="$route.name === 'admin-calendar' ? 'text-[#4F46E5]' : 'text-[#64748B] transition-colors duration-200 group-hover:text-[#0F172A]'"
                        />

                        <span
                            :class="$route.name === 'admin-calendar' ? 'text-[15px] font-semibold text-[#3730A3]' : 'text-[15px] font-medium text-[#64748B] transition-colors duration-200 group-hover:text-[#0F172A]'"
                        >
                            Agenda des Salles
                        </span>
                    </RouterLink>
                </nav>
            </div>



            <!-- COMPONENTS -->
            <div class="mb-7">
                <p
                    class="mb-3 px-3 text-[12px] font-semibold uppercase
                           tracking-[1.2px] text-[#64748B]"
                >
                    Reservation & Service
                </p>

                <nav class="space-y-1">

                    <!-- Basic Tables -->
                    <RouterLink :to="{name : 'admin-reservations'}"
                        @click="handleNavClick"
                        class="group flex h-[50px] w-full items-center gap-4
                               rounded-[10px] px-3 text-left
                               transition-colors duration-200 hover:bg-[#F8FAFC] active:scale-[0.98]"
                    >
                        <Table2
                            :size="20"
                            :stroke-width="1.6"

                        />

                        <span
                            class="text-[15px] font-medium text-[#64748B]
                                   transition-colors duration-200 group-hover:text-[#0F172A]"
                        >
                            Reservations
                        </span>
                    </RouterLink>

                    <!-- Forms -->
                    <RouterLink :to="{name : 'admin-salles'}"
                        @click="handleNavClick"
                        class="group flex h-[50px] w-full items-center gap-4
                               rounded-[10px] px-3 text-left
                               transition-colors duration-200 hover:bg-[#F8FAFC] active:scale-[0.98]"
                    >
                        <SlidersHorizontal
                            :size="20"
                            :stroke-width="1.6"
                            class="text-[#64748B] transition-colors duration-200 group-hover:text-[#0F172A]"
                        />

                        <span
                            class="text-[15px] font-medium text-[#64748B]
                                   transition-colors duration-200 group-hover:text-[#0F172A]"
                        >
                            Salles
                        </span>
                    </RouterLink>

                    <!-- RouterLinks -->
                    <RouterLink :to="{name : 'admin-equipments'}"
                        @click="handleNavClick"
                        class="group flex h-[50px] w-full items-center gap-4
                               rounded-[10px] px-3 text-left
                               transition-colors duration-200 hover:bg-[#F8FAFC] active:scale-[0.98]"
                    >
                        <Server
                            :size="20"
                            :stroke-width="1.6"
                            class="text-[#64748B] transition-colors duration-200 group-hover:text-[#0F172A]"
                        />

                        <span
                            class="text-[15px] font-medium text-[#64748B]
                                   transition-colors duration-200 group-hover:text-[#0F172A]"
                        >
                            Equipements
                        </span>
                    </RouterLink>
                    <RouterLink :to="{name : 'admin-galeries'}"
                        @click="handleNavClick"
                        class="group flex h-[50px] w-full items-center gap-4
                               rounded-[10px] px-3 text-left
                               transition-colors duration-200 hover:bg-[#F8FAFC] active:scale-[0.98]"
                    >
                        <ImageIcon
                            :size="20"
                            :stroke-width="1.6"
                            class="text-[#64748B] transition-colors duration-200 group-hover:text-[#0F172A]"
                        />

                        <span
                            class="text-[15px] font-medium text-[#64748B]
                                   transition-colors duration-200 group-hover:text-[#0F172A]"
                        >
                            Galeries
                        </span>
                    </RouterLink>
                </nav>
            </div>

            <!-- PAGES -->

        </div>

        <!-- USER -->
        <div class="shrink-0 px-6 pb-5">
            <div
                class="flex h-[70px] items-center rounded-[16px]
                       border border-[#E2E8F0] bg-[#F8FAFC] px-3"
            >
                <!-- Avatar -->
                <div
                    class="mr-3 flex h-[44px] w-[44px] shrink-0
                           items-center justify-center overflow-hidden
                           rounded-full bg-[#EEF2FF] font-bold text-[#4F46E5]"
                >
                    {{ (authStore.currentUser?.nom || 'A').charAt(0).toUpperCase() }}
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-[14px] font-semibold text-[#0F172A]">
                        {{ authStore.currentUser?.nom || 'Administrateur' }}
                    </p>

                    <p class="mt-0.5 truncate text-[12px] text-[#64748B]">
                        {{ authStore.currentUser?.email || 'admin@email.com' }}
                    </p>
                </div>
            </div>
        </div>
    </aside>
</template>