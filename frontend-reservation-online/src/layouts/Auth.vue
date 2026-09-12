<template>
    <div class="min-h-screen bg-white">
    <!-- Logo -->
    <header class="absolute left-4 top-6 z-10">
      <RouterLink to="/">
        <div class="text-[27px] font-black tracking-[-1.8px] text-black">
          Resven
        </div>
      </RouterLink>
    </header>

    <!-- Zone principale -->
    <main class="grid min-h-screen grid-cols-2">

      <!-- Partie gauche -->
      <section class="flex items-center justify-center px-10 pt-10">

        <!-- Ici Vue affichera Login.vue ou Register.vue -->
        <div class="w-full max-w-[468px]">
          <slot></slot>
        </div>

      </section>

      <!-- Partie droite -->
      <section class="flex items-center justify-end py-5 pr-4">

        <div
          class="h-[calc(100vh-40px)] w-full overflow-hidden rounded-tl-[30px] rounded-bl-[30px] rounded-tr-[30px] rounded-br-[60px]"
        >
          <img
            src="../../public/images/image.png"
            alt = "reserve"
            class="h-full w-full object-cover"
          />
        </div>

      </section>

    </main>
  </div>
</template>

<script setup>

</script>

<style scoped>

</style>