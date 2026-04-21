<template>
  <div class="bg-bg min-h-screen">
    <!-- Hero Section -->
    <section class="relative overflow-hidden px-4 py-12 sm:px-6 md:py-24 lg:px-8">
      <!-- Decorative background -->
      <div
        class="bg-primary/5 absolute top-0 right-0 -z-10 h-[600px] w-[600px] translate-x-1/2 -translate-y-1/2 rounded-full blur-3xl"
      ></div>
      <div
        class="bg-accent/5 absolute bottom-0 left-0 -z-10 h-[400px] w-[400px] -translate-x-1/2 translate-y-1/2 rounded-full blur-3xl"
      ></div>

      <div class="motion-preset-fade mx-auto max-w-4xl text-center">
        <h2
          class="text-primary motion-preset-slide-up-sm motion-delay-200 mb-6 text-4xl font-bold md:text-5xl lg:text-6xl"
        >
          Make Your Community Better
        </h2>
        <p class="text-muted mx-auto mb-8 max-w-2xl text-lg md:text-xl">
          Report city issues, engage with your community, and watch solutions come to life. Every
          voice matters in building a better tomorrow.
        </p>
        <div class="flex flex-col justify-center gap-4 sm:flex-row">
          <template v-if="!authStore.isAuthenticated">
            <router-link
              to="/register"
              class="bg-primary hover:bg-primary/90 inline-block rounded-lg px-8 py-3 font-semibold text-white transition"
            >
              Start Reporting
            </router-link>
            <router-link
              to="/login"
              class="text-primary border-primary hover:bg-primary/5 inline-block rounded-lg border-2 bg-white px-8 py-3 font-semibold transition"
            >
              Sign In
            </router-link>
          </template>
          <template v-else>
            <router-link
              :to="dashboardRoute"
              class="bg-primary hover:bg-primary/90 inline-block rounded-lg px-8 py-3 font-semibold text-white transition"
            >
              Go to Dashboard
            </router-link>
          </template>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section
      id="features"
      class="border-accent/20 border-t bg-white px-4 py-12 sm:px-6 md:py-20 lg:px-8"
    >
      <div class="mx-auto max-w-6xl">
        <div class="mb-12 text-center" v-intersect="'motion-preset-slide-up-sm'">
          <h2 class="text-primary mb-4 text-3xl font-bold md:text-4xl">Why Choose CivicConnect?</h2>
          <p class="text-muted text-lg">Empowering citizens and connecting communities</p>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
          <!-- Feature 1 -->
          <div
            class="border-accent/20 hover:border-accent/40 rounded-xl border p-6 transition hover:shadow-md"
            v-intersect="'motion-preset-slide-up-sm'"
          >
            <div class="bg-accent/10 mb-4 flex h-12 w-12 items-center justify-center rounded-lg">
              <MapPinIcon class="text-accent h-6 w-6" />
            </div>
            <h3 class="text-primary mb-2 text-xl font-bold">Report with Location</h3>
            <p class="text-muted">
              Pin issues on a map with photos and descriptions. Help officials understand exactly
              where problems are.
            </p>
          </div>

          <!-- Feature 2 -->
          <div
            class="border-accent/20 hover:border-accent/40 rounded-xl border p-6 transition hover:shadow-md"
            v-intersect="'motion-preset-slide-up-sm motion-delay-200'"
          >
            <div class="bg-accent/10 mb-4 flex h-12 w-12 items-center justify-center rounded-lg">
              <UserGroupIcon class="text-accent h-6 w-6" />
            </div>
            <h3 class="text-primary mb-2 text-xl font-bold">Community Engagement</h3>
            <p class="text-muted">
              Upvote issues you care about, and build consensus with your neighbors.
            </p>
          </div>

          <!-- Feature 3 -->
          <div
            class="border-accent/20 hover:border-accent/40 rounded-xl border p-6 transition hover:shadow-md"
            v-intersect="'motion-preset-slide-up-sm motion-delay-400'"
          >
            <div class="bg-accent/10 mb-4 flex h-12 w-12 items-center justify-center rounded-lg">
              <ChartBarIcon class="text-accent h-6 w-6" />
            </div>
            <h3 class="text-primary mb-2 text-xl font-bold">Track Progress</h3>
            <p class="text-muted">
              Stay updated with real-time status changes. See when issues are being addressed by
              city staff.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="bg-bg px-4 py-12 sm:px-6 md:py-20 lg:px-8">
      <div class="mx-auto max-w-4xl">
        <div class="mb-12 text-center" v-intersect="'motion-preset-slide-up-sm'">
          <h2 class="text-primary mb-4 text-3xl font-bold md:text-4xl">How It Works</h2>
        </div>

        <div class="space-y-8">
          <div class="flex gap-6" v-intersect="'motion-preset-slide-right-sm'">
            <div class="shrink-0">
              <div
                class="bg-primary flex h-10 w-10 items-center justify-center rounded-full font-bold text-white"
              >
                1
              </div>
            </div>
            <div>
              <h3 class="text-primary mb-2 text-xl font-bold">Create Your Account</h3>
              <p class="text-muted">
                Sign up with your email and verify it. Join thousands of engaged citizens.
              </p>
            </div>
          </div>

          <div class="flex gap-6" v-intersect="'motion-preset-slide-right-sm motion-delay-200'">
            <div class="shrink-0">
              <div
                class="bg-primary flex h-10 w-10 items-center justify-center rounded-full font-bold text-white"
              >
                2
              </div>
            </div>
            <div>
              <h3 class="text-primary mb-2 text-xl font-bold">Report or Explore Issues</h3>
              <p class="text-muted">
                Spot a pothole? Broken light? Submit a report with photos and location. Or browse
                existing issues.
              </p>
            </div>
          </div>

          <div class="flex gap-6" v-intersect="'motion-preset-slide-right-sm motion-delay-400'">
            <div class="shrink-0">
              <div
                class="bg-primary flex h-10 w-10 items-center justify-center rounded-full font-bold text-white"
              >
                3
              </div>
            </div>
            <div>
              <h3 class="text-primary mb-2 text-xl font-bold">Engage & Support</h3>
              <p class="text-muted">Upvote issues you care about.</p>
            </div>
          </div>

          <div class="flex gap-6" v-intersect="'motion-preset-slide-right-sm motion-delay-600'">
            <div class="shrink-0">
              <div
                class="bg-primary flex h-10 w-10 items-center justify-center rounded-full font-bold text-white"
              >
                4
              </div>
            </div>
            <div>
              <h3 class="text-primary mb-2 text-xl font-bold">Monitor Resolution</h3>
              <p class="text-muted">
                Follow progress as city staff addresses issues. Celebrate wins with your community.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Community Stats Section -->
    <section
      id="community"
      class="border-accent/20 border-t bg-white px-4 py-12 sm:px-6 md:py-20 lg:px-8"
    >
      <div class="mx-auto max-w-6xl">
        <div v-if="loading" class="text-center">
          <p class="text-muted">Loading statistics...</p>
        </div>
        <div v-else class="grid grid-cols-1 gap-8 text-center md:grid-cols-3">
          <div>
            <p class="text-primary mb-2 text-3xl font-bold md:text-4xl">
              {{ stats.totalIssues.toLocaleString() }}
            </p>
            <p class="text-muted">Issues Reported</p>
          </div>
          <div>
            <p class="text-primary mb-2 text-3xl font-bold md:text-4xl">
              {{ stats.totalUsers.toLocaleString() }}
            </p>
            <p class="text-muted">Active Citizens</p>
          </div>
          <div>
            <p class="text-primary mb-2 text-3xl font-bold md:text-4xl">
              {{ stats.resolutionRate }}%
            </p>
            <p class="text-muted">Resolution Rate</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary px-4 py-12 text-white sm:px-6 md:py-20 lg:px-8">
      <div class="mx-auto max-w-4xl text-center" v-intersect="'motion-preset-pop'">
        <h2 class="mb-6 text-3xl font-bold md:text-4xl">Ready to Make a Difference?</h2>
        <p class="mb-8 text-lg opacity-90">
          Join our community today and help make cities better for everyone.
        </p>
        <router-link
          to="/register"
          class="text-primary hover:bg-accent/20 inline-block rounded-lg bg-white px-8 py-3 font-semibold transition"
        >
          Get Started Now
        </router-link>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/authStore'
import { MapPinIcon, UserGroupIcon, ChartBarIcon } from '@heroicons/vue/24/outline'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost/civic-connect/backend/api'
const authStore = useAuthStore()

const stats = ref({
  totalIssues: 0,
  totalUsers: 0,
  resolutionRate: 0,
})
const loading = ref(true)

const dashboardRoute = computed(() => {
  if (authStore.isAdmin) return '/admin/dashboard'
  if (authStore.isStaff) return '/staff/dashboard'
  return '/dashboard'
})

const fetchStats = async () => {
  try {
    const response = await axios.get(`${API_URL}/stats.php`, {
      withCredentials: false,
    })
    if (response.data.success) {
      stats.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch statistics:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>
