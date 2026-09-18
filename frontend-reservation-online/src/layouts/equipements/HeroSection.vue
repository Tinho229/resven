
<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import { ArrowUpRight, Play, Pause, X } from 'lucide-vue-next'
import router from '@/router'

/*
|--------------------------------------------------------------------------
| Image de fond
|--------------------------------------------------------------------------
*/

function handleReserveHero() {
    router.push({ name: 'user-create-reservation' })
}

const heroImage = '/images/hero/equipements/equipement.png'

/*
|--------------------------------------------------------------------------
| Carrousel d'aperçu — fondu enchaîné à deux calques (fix du bug d'image figée)
|--------------------------------------------------------------------------
*/

const previewImages = [
    '/images/hero/equipements/equipement-1.png',
    '/images/hero/equipements/equipement-2.png',
    '/images/hero/equipements/equipement-4.png'

]

const previewIndex = ref(0)
const isPlaying = ref(true)
let previewInterval = null

// Deux calques superposés, fondu lent + très léger zoom pour la douceur
const layers = reactive([
    { src: previewImages[0], opacity: 1 },
    { src: previewImages[1] ?? previewImages[0], opacity: 0 },
])
const activeLayer = ref(0)

const pad = (n) => String(n).padStart(2, '0')

function goToNextPreview() {
    const nextIndex = (previewIndex.value + 1) % previewImages.length
    const inactive = activeLayer.value === 0 ? 1 : 0

    layers[inactive].src = previewImages[nextIndex]
    layers[inactive].opacity = 1
    layers[activeLayer.value].opacity = 0

    activeLayer.value = inactive
    previewIndex.value = nextIndex
}

function startPreview() {
    clearInterval(previewInterval)
    previewInterval = setInterval(goToNextPreview, 5000)
}

function togglePreview() {
    isPlaying.value = !isPlaying.value
    if (isPlaying.value) {
        startPreview()
    } else {
        clearInterval(previewInterval)
    }
}

/*
|--------------------------------------------------------------------------
| Statistiques (compteur animé au montage, plus lent et doux)
|--------------------------------------------------------------------------
*/

const stats = reactive([
    { target: 20, unit: 'K+', label: 'Voyageurs Comblés', current: 0 },
    { target: 2, unit: 'K+', label: 'Séjours Sur Mesure', current: 0 },
    { target: 5, unit: 'K+', label: 'Adresses D\'Exception', current: 0 },
])

const prefersReducedMotion =
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches

function animateStats() {
    if (prefersReducedMotion) {
        stats.forEach((s) => (s.current = s.target))
        return
    }

    const duration = 1600
    const startTime = performance.now()

    function tick(now) {
        const elapsed = now - startTime
        const progress = Math.min(elapsed / duration, 1)
        const eased = 1 - Math.pow(1 - progress, 4)

        stats.forEach((s) => {
            s.current = Math.round(s.target * eased)
        })

        if (progress < 1) {
            requestAnimationFrame(tick)
        }
    }

    requestAnimationFrame(tick)
}

/*
|--------------------------------------------------------------------------
| Réservation
|--------------------------------------------------------------------------
*/

const isBookingOpen = ref(false)
const isSubmitting = ref(false)
const isSubmitted = ref(false)
const formError = ref('')

const bookingForm = reactive({
    name: '',
    email: '',
    checkIn: '',
    checkOut: '',
    guests: 2,
})

function openBooking() {
    isBookingOpen.value = true
    isSubmitted.value = false
    formError.value = ''
}

function closeBooking() {
    isBookingOpen.value = false
}

function submitBooking() {
    if (!bookingForm.name || !bookingForm.email || !bookingForm.checkIn || !bookingForm.checkOut) {
        formError.value = 'Merci de compléter chaque champ avant de continuer.'
        return
    }
    if (bookingForm.checkOut <= bookingForm.checkIn) {
        formError.value = 'La date de départ doit suivre la date d\'arrivée.'
        return
    }

    formError.value = ''
    isSubmitting.value = true

    // Simule un appel réseau — à remplacer par votre appel API réel
    setTimeout(() => {
        isSubmitting.value = false
        isSubmitted.value = true
    }, 900)
}

function onKeydown(e) {
    if (e.key === 'Escape' && isBookingOpen.value) {
        closeBooking()
    }
}

/*
|--------------------------------------------------------------------------
| Cycle de vie
|--------------------------------------------------------------------------
*/

onMounted(() => {
    // Préchargement en arrière-plan des images du carrousel
    previewImages.forEach((src) => {
        const img = new Image()
        img.src = src
    })

    startPreview()
    window.addEventListener('keydown', onKeydown)

    // Léger délai pour que le compteur démarre après l'apparition des stats
    setTimeout(animateStats, prefersReducedMotion ? 0 : 700)
})

onBeforeUnmount(() => {
    clearInterval(previewInterval)
    window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
    <section class="egypt-hero">

        <!-- ================================================== -->
        <!-- IMAGE DE FOND (effet Ken Burns discret) -->
        <!-- ================================================== -->

        <img
            :src="heroImage"
            alt="Vue apaisante d'une destination de voyage"
            class="egypt-hero__image"
            loading="eager"
            fetchpriority="high"
            decoding="async"
        />

        <!-- ================================================== -->
        <!-- DEGRADES DE LISIBILITE -->
        <!-- ================================================== -->

        <div class="egypt-hero__gradient-bottom" aria-hidden="true"></div>
        <div class="egypt-hero__gradient-left" aria-hidden="true"></div>

        <!-- ================================================== -->
        <!-- TITRE MONUMENTAL -->
        <!-- ================================================== -->

        <h1 class="egypt-hero__title reveal reveal--title" aria-label="Pick Reservation">
            PEAK   EQUIPEMENTS
        </h1>

        <!-- ================================================== -->
        <!-- CONTENU BAS -->
        <!-- ================================================== -->

        <div class="egypt-hero__content">

            <!-- STATS + CTA + DESCRIPTION -->
            <div class="egypt-hero__bottom-left">

                <div class="egypt-hero__stats">
                    <div
                        v-for="(stat, index) in stats"
                        :key="stat.label"
                        class="egypt-hero__stat reveal"
                        :style="{ animationDelay: `${0.7 + index * 0.16}s` }"
                    >
                        <p class="egypt-hero__stat-value">
                            {{ stat.current }}{{ stat.unit }}
                        </p>
                        <p class="egypt-hero__stat-label">
                            {{ stat.label }}
                        </p>
                    </div>
                </div>

                <div class="egypt-hero__cta-row">
                    <button
                        type="button"
                        class="egypt-hero__book-btn reveal"
                        style="animation-delay: 1.2s"
                        @click="handleReserveHero"
                    >
                        Réserver Maintenant
                        <span class="egypt-hero__book-btn-icon">
                            <ArrowUpRight :size="14" aria-hidden="true" />
                        </span>
                    </button>

                    <p
                        class="egypt-hero__description reveal"
                        style="animation-delay: 1.35s"
                    >
                        Choisissez votre séjour idéal et confirmez votre
                        réservation en quelques instants, en toute sérénité.
                    </p>
                </div>

            </div>

            <!-- CARROUSEL D'APERCU -->
            <div class="egypt-hero__preview reveal" style="animation-delay: 0.9s">

                <div class="egypt-hero__preview-frame">
                    <img
                        v-for="(layer, i) in layers"
                        :key="i"
                        :src="layer.src"
                        alt="Aperçu d'un lieu de séjour disponible à la réservation"
                        class="egypt-hero__preview-image"
                        :style="{ opacity: layer.opacity }"
                    />

                    <button
                        type="button"
                        class="egypt-hero__preview-toggle"
                        :aria-label="isPlaying ? 'Mettre en pause le diaporama' : 'Lancer le diaporama'"
                        @click="togglePreview"
                    >
                        <Pause v-if="isPlaying" :size="11" aria-hidden="true" />
                        <Play v-else :size="11" aria-hidden="true" />
                    </button>

                    <span class="egypt-hero__preview-count">
                        {{ pad(previewIndex + 1) }}/{{ pad(previewImages.length) }}
                    </span>
                </div>

            </div>

        </div>

        <!-- ================================================== -->
        <!-- MODALE DE RESERVATION -->
        <!-- ================================================== -->



    </section>
</template>


<style scoped>

/* ================================================================
   TYPOGRAPHIE - Bricolage Grotesque (titres) + Inter (texte)
================================================================ */

.egypt-hero {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
    position: relative;
    width: 100%;
    min-height: 100vh;
    overflow: hidden;
    background-color: #0a0a0a;
    color: #ffffff;
}


/* ================================================================
   IMAGE + KEN BURNS (ralenti pour plus de douceur)
================================================================ */

.egypt-hero__image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 65%;
    display: block;
    animation: kenBurns 32s ease-in-out infinite alternate;
    will-change: transform;
}

@keyframes kenBurns {
    from { transform: scale(1); }
    to { transform: scale(1.08); }
}


/* ================================================================
   DEGRADES
================================================================ */

.egypt-hero__gradient-bottom {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to top,
        rgba(5, 5, 5, 0.92) 0%,
        rgba(5, 5, 5, 0.55) 32%,
        rgba(5, 5, 5, 0.05) 60%,
        transparent 100%
    );
}

.egypt-hero__gradient-left {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to right,
        rgba(5, 5, 5, 0.35) 0%,
        transparent 45%
    );
}


/* ================================================================
   TITRE
================================================================ */

.egypt-hero__title {
    position: absolute;
    top: 20%;
    left: 0;
    width: 100%;
    margin: 0;
    font-family: 'Bricolage Grotesque', 'Inter', sans-serif;
    font-weight: 800;
    font-size: clamp(40px, 9vw, 168px);
    letter-spacing: -2px;
    color: rgba(255, 255, 255, 0.9);
    text-align: center;
    line-height: 1;
    white-space: nowrap;
    pointer-events: none;
    user-select: none;
    text-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
}


/* ================================================================
   CONTENU BAS
================================================================ */

.egypt-hero__content {
    position: relative;
    z-index: 10;
    min-height: 100vh;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
    padding: 24px clamp(20px, 5vw, 64px) clamp(28px, 5vw, 56px);
}

.egypt-hero__bottom-left {
    max-width: 620px;
}

.egypt-hero__stats {
    display: flex;
    gap: clamp(24px, 5vw, 48px);
    margin-bottom: 22px;
}

.egypt-hero__stat-value {
    margin: 0;
    font-size: clamp(26px, 4vw, 36px);
    font-weight: 700;
    letter-spacing: -0.5px;
    font-variant-numeric: tabular-nums;
}

.egypt-hero__stat-label {
    margin: 4px 0 0;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.65);
}

.egypt-hero__cta-row {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.egypt-hero__book-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    color: #0a0a0a;
    border: none;
    border-radius: 999px;
    padding: 13px 20px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 300ms ease, box-shadow 300ms ease;
    flex-shrink: 0;
}

.egypt-hero__book-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px -8px rgba(255, 255, 255, 0.35);
}

.egypt-hero__book-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px #0a0a0a, 0 0 0 4px #ffffff;
}

.egypt-hero__book-btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #0a0a0a;
    color: #ffffff;
    transition: transform 300ms ease;
}

.egypt-hero__book-btn:hover .egypt-hero__book-btn-icon {
    transform: translate(2px, -2px);
}

.egypt-hero__description {
    max-width: 260px;
    font-size: 12.5px;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
}


/* ================================================================
   CARROUSEL D'APERCU
================================================================ */

.egypt-hero__preview {
    flex-shrink: 0;
    width: clamp(150px, 20vw, 200px);
    display: none;
}

@media (min-width: 640px) {
    .egypt-hero__preview {
        display: block;
    }
}

.egypt-hero__preview-frame {
    position: relative;
    aspect-ratio: 4 / 3;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 20px 45px -12px rgba(0, 0, 0, 0.55);
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(6px);
}

.egypt-hero__preview-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transform: scale(1.02);
    transition: opacity 1.4s cubic-bezier(0.45, 0, 0.2, 1),
                transform 6s cubic-bezier(0.25, 1, 0.5, 1);
}

.egypt-hero__preview-toggle {
    position: absolute;
    bottom: 8px;
    left: 8px;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.5);
    color: #ffffff;
    cursor: pointer;
    transition: background 200ms ease;
    z-index: 2;
}

.egypt-hero__preview-toggle:hover {
    background: rgba(0, 0, 0, 0.7);
}

.egypt-hero__preview-toggle:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px #ffffff;
}

.egypt-hero__preview-count {
    position: absolute;
    bottom: 8px;
    right: 10px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.5px;
    color: rgba(255, 255, 255, 0.85);
    font-variant-numeric: tabular-nums;
    z-index: 2;
}


/* ================================================================
   MODALE DE RESERVATION
================================================================ */



.egypt-booking-modal {
    position: relative;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
    background: #111111;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 20px;
    padding: 32px 28px 28px;
    box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.7);
    animation: modalRise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes modalRise {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.egypt-booking-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    cursor: pointer;
    transition: background 200ms ease;
}

.egypt-booking-close:hover {
    background: rgba(255, 255, 255, 0.18);
}

.egypt-booking-title {
    margin: 0 0 6px;
    font-family: 'Bricolage Grotesque', 'Inter', sans-serif;
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.egypt-booking-subtitle {
    margin: 0 0 22px;
    font-size: 13px;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.6);
}

.egypt-booking-form {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.egypt-booking-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.egypt-booking-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.75);
}

.egypt-booking-field input {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 10px;
    padding: 10px 12px;
    font-family: inherit;
    font-size: 13px;
    color: #ffffff;
    outline: none;
    transition: border-color 200ms ease, background 200ms ease;
    color-scheme: dark;
}

.egypt-booking-field input:focus {
    border-color: rgba(255, 255, 255, 0.5);
    background: rgba(255, 255, 255, 0.1);
}

.egypt-booking-field--guests {
    max-width: 140px;
}

.egypt-booking-error {
    margin: 0;
    font-size: 12.5px;
    color: #ff8a8a;
}

.egypt-booking-submit {
    margin-top: 6px;
    background: #ffffff;
    color: #0a0a0a;
    border: none;
    border-radius: 999px;
    padding: 13px 20px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 250ms ease, opacity 250ms ease;
}

.egypt-booking-submit:hover:not(:disabled) {
    transform: translateY(-2px);
}

.egypt-booking-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.egypt-booking-success {
    text-align: left;
    animation: fadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.4s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}


/* ================================================================
   ENTREE EN SCENE (fade-up délicat + léger flou)
================================================================ */

.reveal {
    animation: fadeUp 1.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.reveal--title {
    animation: titleReveal 1.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    animation-delay: 0.2s;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(22px);
        filter: blur(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
        filter: blur(0);
    }
}

@keyframes titleReveal {
    from {
        opacity: 0;
        transform: translateY(34px) scale(0.97);
        filter: blur(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
}


/* ================================================================
   PREFERS-REDUCED-MOTION
================================================================ */

@media (prefers-reduced-motion: reduce) {
    .egypt-hero__image {
        animation: none;
    }
    .reveal,
    .reveal--title,
    .egypt-booking-modal,
    .egypt-booking-success {
        animation: none;
        opacity: 1;
        transform: none;
        filter: none;
    }
    .egypt-hero__book-btn,
    .egypt-hero__book-btn-icon,
    .egypt-hero__preview-toggle,
    .egypt-hero__preview-image,
    .egypt-booking-submit,
    .egypt-booking-close {
        transition: none;
    }
    .modal-fade-enter-active,
    .modal-fade-leave-active {
        transition: none;
    }
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 640px) {
    .egypt-hero__content {
        align-items: flex-start;
        flex-direction: column-reverse;
    }
    .egypt-hero__stats {
        gap: 20px;
    }
    .egypt-hero__cta-row {
        flex-direction: column;
        align-items: flex-start;
    }
    .egypt-booking-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .egypt-hero__title {
        white-space: normal;
        word-break: break-word;
        font-size: clamp(32px, 11vw, 72px);
        letter-spacing: -1px;
        padding: 0 12px;
    }
    .egypt-hero__content {
        padding: 16px 16px clamp(20px, 6vw, 36px);
        gap: 16px;
    }
    .egypt-hero__stat-value { font-size: 22px; }
    .egypt-hero__stat-label { font-size: 10px; }
    .egypt-hero__description { max-width: 100%; font-size: 12px; }
}
</style>