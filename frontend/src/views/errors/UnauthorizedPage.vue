<template>
  <div class="min-h-screen bg-linear-to-br from-blue-50 to-indigo-100 flex items-center justify-center px-4">
    <div class="text-center">
      <h1 class="text-6xl font-bold text-gray-900 mb-4">401</h1>
      <h2 class="text-2xl font-semibold text-gray-700 mb-4">Unauthorized</h2>
      <p class="text-gray-600 mb-8">You don't have permission to access this page.</p>
      <div class="flex gap-4 justify-center">
        <router-link to="/" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
          Go Home
        </router-link>
        <router-link to="/login" class="bg-white text-indigo-600 px-6 py-3 rounded-lg font-semibold border-2 border-indigo-600 hover:bg-indigo-50 transition">
          Sign In
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>
