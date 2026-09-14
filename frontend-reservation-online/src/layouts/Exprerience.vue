<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { RouterLink } from 'vue-router'

import {
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next'


/* =====================================================
   DONNÉES
===================================================== */

const experiences = [
    {
        title: 'Salle de conférence moderne',
        location: 'Cotonou, Bénin',
        image: '/images/experiences/salle-1.png',
    },
    {
        title: 'Salle de réunion professionnelle',
        location: 'Abomey-Calavi, Bénin',
        image: '/images/experiences/salle-2.png',
    },
    {
        title: 'Espace de travail confortable',
        location: 'Porto-Novo, Bénin',
        image: '/images/experiences/salle-3.png',
    },
    {
        title: 'Salle événementielle',
        location: 'Cotonou, Bénin',
        image: '/images/experiences/salle-4.png',
    },
    {
        title: 'Salle événementielle',
        location: 'Cotonou, Bénin',
        image: '/images/experiences/salle-5.png',
    },
    {
        title: 'Salle événementielle',
        location: 'Cotonou, Bénin',
        image: '/images/experiences/salle-6.png',
    },
]


/* =====================================================
   RÉFÉRENCE DU CAROUSEL
===================================================== */

const carouselRef = ref(null)


/* =====================================================
   INDEX ACTUEL
===================================================== */

const currentIndex = ref(0)


/* =====================================================
   NOMBRE DE CARTES VISIBLES (responsive)
   — mobile (<640px)   : 1 carte
   — tablette (640–1023px) : 2 cartes
   — desktop (≥1024px) : 3 cartes
===================================================== */

const visibleCount = ref(3)

const getVisibleCount = () => {
    const w = window.innerWidth
    if (w < 640) return 1
    if (w < 1024) return 2
    return 3
}


/* =====================================================
   LARGEUR D'UNE CARTE
===================================================== */

const cardWidth = ref(0)

const gap = 20


/* =====================================================
   CALCUL DE LA LARGEUR
===================================================== */

const calculateCardWidth = () => {
    if (!carouselRef.value) return

    visibleCount.value = getVisibleCount()

    const width = carouselRef.value.clientWidth
    const count = visibleCount.value

    // N cartes + (N-1) espacements de 20px
    cardWidth.value = (width - gap * (count - 1)) / count
}


/* =====================================================
   NOMBRE MAXIMUM DE DÉPLACEMENTS
===================================================== */

const maxIndex = () => {
    return Math.max(0, experiences.length - visibleCount.value)
}


/* =====================================================
   SUIVANT
===================================================== */

const next = () => {
    if (currentIndex.value >= maxIndex()) {
        currentIndex.value = 0
        return
    }

    currentIndex.value++
}


/* =====================================================
   PRÉCÉDENT
===================================================== */

const previous = () => {
    if (currentIndex.value <= 0) {
        currentIndex.value = maxIndex()
        return
    }

    currentIndex.value--
}


/* =====================================================
   DÉFILEMENT AUTOMATIQUE
===================================================== */

let interval = null

const startAutoPlay = () => {
    stopAutoPlay()

    interval = setInterval(() => {
        next()
    }, 3500)
}

const stopAutoPlay = () => {
    if (interval) {
        clearInterval(interval)
        interval = null
    }
}


/* =====================================================
   SOURIS / DRAG
===================================================== */

const isDragging = ref(false)

const startX = ref(0)

const currentTranslate = ref(0)

const previousTranslate = ref(0)


const startDrag = (event) => {
    isDragging.value = true

    startX.value = event.clientX

    previousTranslate.value =
        -(currentIndex.value * (cardWidth.value + gap))

    stopAutoPlay()

    if (carouselRef.value) {
        carouselRef.value.setPointerCapture(event.pointerId)
    }
}


const drag = (event) => {
    if (!isDragging.value) return

    const currentX = event.clientX

    const difference = currentX - startX.value

    currentTranslate.value =
        previousTranslate.value + difference
}


const endDrag = (event) => {
    if (!isDragging.value) return

    isDragging.value = false

    const difference =
        event.clientX - startX.value

    if (Math.abs(difference) > 60) {
        if (difference < 0) {
            next()
        } else {
            previous()
        }
    }

    currentTranslate.value =
        -(currentIndex.value * (cardWidth.value + gap))

    startAutoPlay()
}


/* =====================================================
   CALCUL DE LA POSITION
===================================================== */

const getTranslate = () => {
    if (isDragging.value) {
        return currentTranslate.value
    }

    return -(currentIndex.value * (cardWidth.value + gap))
}


/* =====================================================
   REDIMENSIONNEMENT
===================================================== */

const handleResize = () => {
    calculateCardWidth()

    // Clamp l'index si le nombre de cartes visibles augmente
    if (currentIndex.value > maxIndex()) {
        currentIndex.value = maxIndex()
    }

    currentTranslate.value =
        -(currentIndex.value * (cardWidth.value + gap))
}


/* =====================================================
   MOUNT
===================================================== */

onMounted(async () => {
    // Préchargement en arrière-plan des images de la section Expériences
    experiences.forEach((exp) => {
        const img = new Image()
        img.src = exp.image
    })

    await nextTick()

    calculateCardWidth()

    currentTranslate.value =
        -(currentIndex.value * (cardWidth.value + gap))

    startAutoPlay()

    window.addEventListener(
        'resize',
        handleResize
    )
})


/* =====================================================
   UNMOUNT
===================================================== */

onBeforeUnmount(() => {
    stopAutoPlay()

    window.removeEventListener(
        'resize',
        handleResize
    )
})
</script>


<template>

    <section
        class="
            relative
            overflow-hidden
            bg-[#F8FAFC]
            px-6
            py-8
            lg:px-10
            lg:py-6
        "
    >

        <div class="mx-auto max-w-[1400px]">

            <div
                class="
                    grid
                    grid-cols-1
                    items-center
                    gap-8
                    lg:grid-cols-[310px_1fr]
                    lg:gap-8
                "
            >

                <!-- ================================================= -->
                <!-- COLONNE GAUCHE -->
                <!-- ================================================= -->

                <div class="relative z-10">

                    <p
                        class="
                            mb-4
                            text-[12px]
                            font-bold
                            uppercase
                            tracking-[4px]
                            text-[#0F172A]
                        "
                    >
                        EXPÉRIENCES
                    </p>


                    <h2
                        class="
                            max-w-[320px]
                            font-serif
                            text-[42px]
                            font-normal
                            leading-[1.08]
                            tracking-[-1.5px]
                            text-[#0F172A]
                            sm:text-[48px]
                        "
                    >
                        Découvrez

                        <span class="italic">
                            nos espaces
                        </span>
                    </h2>


                    <p
                        class="
                            mt-4
                            max-w-[300px]
                            text-[14px]
                            leading-[1.6]
                            text-[#475569]
                        "
                    >
                        Explorez nos salles et espaces soigneusement
                        sélectionnés pour répondre à vos besoins
                        professionnels et événementiels.
                    </p>


                    <!-- ================================================= -->
                    <!-- NAVIGATION -->
                    <!-- ================================================= -->

                    <div class="mt-5 flex items-center gap-3">

                        <!-- PREVIOUS -->

                        <button
                            type="button"
                            aria-label="Expérience précédente"
                            class="
                                flex
                                h-[42px]
                                w-[42px]
                                items-center
                                justify-center
                                rounded-full
                                border
                                border-[#E2E8F0]
                                bg-white
                                text-[#0F172A]
                                shadow-[0_4px_20px_-4px_rgba(15,23,42,0.06)]
                                transition-all
                                duration-200
                                hover:border-[#4F46E5]
                                hover:bg-[#EEF2FF]
                                hover:text-[#4F46E5]
                                active:scale-[0.96]
                            "
                            @click="previous"
                        >
                            <ChevronLeft
                                :size="19"
                                :stroke-width="1.8"
                            />
                        </button>


                        <!-- NEXT -->

                        <button
                            type="button"
                            aria-label="Expérience suivante"
                            class="
                                flex
                                h-[42px]
                                w-[42px]
                                items-center
                                justify-center
                                rounded-full
                                border
                                border-[#E2E8F0]
                                bg-white
                                text-[#0F172A]
                                shadow-[0_4px_20px_-4px_rgba(15,23,42,0.06)]
                                transition-all
                                duration-200
                                hover:border-[#4F46E5]
                                hover:bg-[#EEF2FF]
                                hover:text-[#4F46E5]
                                active:scale-[0.96]
                            "
                            @click="next"
                        >
                            <ChevronRight
                                :size="19"
                                :stroke-width="1.8"
                            />
                        </button>

                    </div>

                </div>


                <!-- ================================================= -->
                <!-- CAROUSEL -->
                <!-- ================================================= -->

                <div class="relative min-w-0">

                    <!-- Flèches flottantes — visibles sur mobile/tablette uniquement -->
                    <button
                        type="button"
                        aria-label="Précédent"
                        class="
                            mobile-arrow
                            mobile-arrow--left
                            lg:hidden
                        "
                        @click="previous"
                    >
                        <ChevronLeft :size="18" :stroke-width="2" />
                    </button>

                    <button
                        type="button"
                        aria-label="Suivant"
                        class="
                            mobile-arrow
                            mobile-arrow--right
                            lg:hidden
                        "
                        @click="next"
                    >
                        <ChevronRight :size="18" :stroke-width="2" />
                    </button>

                    <!-- Zone de drag -->
                    <div
                        ref="carouselRef"
                        class="
                            overflow-hidden
                            cursor-grab
                            select-none
                        "
                        :class="{
                            'cursor-grabbing': isDragging
                        }"
                        @pointerdown="startDrag"
                        @pointermove="drag"
                        @pointerup="endDrag"
                        @pointercancel="endDrag"
                        @pointerleave="
                            isDragging && endDrag($event)
                        "
                    >

                        <!-- PISTE -->

                        <div
                            class="
                                flex
                                gap-5
                                will-change-transform
                            "
                            :style="{
                                transform: `translate3d(${getTranslate()}px, 0, 0)`,
                                transition: isDragging
                                    ? 'none'
                                    : 'transform 700ms cubic-bezier(0.22, 1, 0.36, 1)'
                            }"
                        >

                            <!-- CARTES -->

                            <article
                                v-for="(experience, index) in experiences"
                                :key="index"
                                class="
                                    group
                                    shrink-0
                                "
                                :style="{
                                    width: `${cardWidth}px`
                                }"
                            >

                                <!-- IMAGE -->

                                <div
                                    class="
                                        relative
                                        aspect-[0.82]
                                        overflow-hidden
                                        bg-[#E2E8F0]
                                    "
                                >

                                    <img
                                        :src="experience.image"
                                        :alt="experience.title"
                                        decoding="async"
                                        draggable="false"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                            transition-transform
                                            duration-700
                                            ease-out
                                            group-hover:scale-[1.03]
                                        "
                                    />

                                </div>


                                <!-- INFORMATIONS -->

                                <div class="pt-3">

                                    <h3
                                        class="
                                            font-serif
                                            text-[19px]
                                            leading-tight
                                            text-[#0F172A]
                                        "
                                    >
                                        {{ experience.title }}
                                    </h3>


                                    <p
                                        class="
                                            mt-1
                                            text-[12px]
                                            text-[#64748B]
                                        "
                                    >
                                        {{ experience.location }}
                                    </p>


                                    <!-- BOUTON -->

                                <RouterLink
                                    :to="{ name: 'salles' }"
                                    class="
                                        inline-block
                                        mt-3
                                        bg-[#0F172A]
                                        px-5
                                        py-3
                                        text-[10px]
                                        font-bold
                                        tracking-[2px]
                                        text-white
                                        transition-colors
                                        duration-200
                                        hover:bg-[#020617]
                                        active:scale-[0.98]
                                    "
                                >
                                    DÉCOUVRIR
                                </RouterLink>

                                </div>

                            </article>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ================================================= -->
            <!-- INDICATEURS -->
            <!-- ================================================= -->

            <div
                class="
                    mx-auto
                    mt-6
                    flex
                    max-w-[1400px]
                    justify-end
                    lg:pr-2
                "
            >

                <div class="flex items-center gap-2">

                    <button
                        v-for="(_, index) in experiences.slice(
                            0,
                            maxIndex() + 1
                        )"
                        :key="index"
                        type="button"
                        class="
                            h-[3px]
                            rounded-full
                            transition-all
                            duration-300
                        "
                        :class="
                            currentIndex === index
                                ? 'w-8 bg-[#0F172A]'
                                : 'w-3 bg-[#CBD5E1]'
                        "
                        @click="currentIndex = index"
                    ></button>

                </div>

            </div>

        </div>

    </section>

</template>


<style scoped>

.font-serif {
    font-family:
        Georgia,
        'Times New Roman',
        serif;
}

/* =====================================================
   FLÈCHES FLOTTANTES (mobile / tablette)
===================================================== */

.mobile-arrow {
    position: absolute;
    top: 40%;
    transform: translateY(-50%);
    z-index: 20;

    display: flex;
    align-items: center;
    justify-content: center;

    width: 38px;
    height: 38px;

    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(6px);
    color: #0F172A;

    box-shadow: 0 4px 16px -4px rgba(15, 23, 42, 0.18);

    cursor: pointer;
    transition: background 200ms ease, transform 200ms ease, box-shadow 200ms ease;
}

.mobile-arrow:hover {
    background: #ffffff;
    box-shadow: 0 6px 20px -4px rgba(15, 23, 42, 0.24);
}

.mobile-arrow:active {
    transform: translateY(-50%) scale(0.94);
}

.mobile-arrow--left {
    left: 8px;
}

.mobile-arrow--right {
    right: 8px;
}

</style>