<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="imageUrl"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
        @click="$emit('close')"
      >
        <!-- Close button -->
        <button
          @click="$emit('close')"
          class="absolute top-4 right-4 rounded-full bg-black/50 p-2 text-white transition-colors hover:bg-white/20"
        >
          <XMarkIcon class="h-8 w-8" />
        </button>

        <!-- Image Container -->
        <div class="relative max-h-[90vh] max-w-[90vw] overflow-hidden rounded-lg" @click.stop>
          <img
            :src="imageUrl"
            :alt="alt || 'Full size image'"
            class="h-full w-full object-contain"
          />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/24/solid'

defineProps({
  imageUrl: {
    type: String,
    default: null,
  },
  alt: {
    type: String,
    default: '',
  },
})

defineEmits(['close'])
</script>
