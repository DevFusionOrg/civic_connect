<template>
  <div class="motion-preset-fade min-h-screen bg-cream-50 pb-12">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Admin Dashboard</h1>
        <p class="mt-2 text-slate-600">Overview of system status and activity</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="border-gold-500 h-12 w-12 animate-spin rounded-full border-b-2"></div>
      </div>

      <!-- Error State -->
      <div
        v-else-if="error"
        class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700"
      >
        {{ error }}
      </div>

      <div v-else class="space-y-8">
        <!-- Key Metrics -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
          <div
            class="motion-preset-slide-up-sm motion-delay-75 overflow-hidden rounded-xl bg-white shadow border border-gold-200/50 backdrop-blur-sm"
          >
            <div class="p-5">
              <div class="flex items-center">
                <div class="shrink-0 rounded-md bg-gold-100 p-3">
                  <UserGroupIcon class="h-6 w-6 text-gold-700" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="truncate text-sm font-medium text-slate-600">Total Users</dt>
                    <dd class="text-2xl font-semibold text-slate-900">{{ stats.users_total }}</dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-cream-50 px-5 py-3">
              <div class="text-sm">
                <router-link to="/admin/users" class="font-medium text-gold-700 hover:text-gold-800"
                  >View all users</router-link
                >
              </div>
            </div>
          </div>

          <div
            class="motion-preset-slide-up-sm motion-delay-100 overflow-hidden rounded-xl bg-white shadow border border-gold-200/50 backdrop-blur-sm"
          >
            <div class="p-5">
              <div class="flex items-center">
                <div class="shrink-0 rounded-md bg-saffron-100 p-3">
                  <ExclamationTriangleIcon class="h-6 w-6 text-saffron-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="truncate text-sm font-medium text-slate-600">Total Issues</dt>
                    <dd class="text-2xl font-semibold text-slate-900">{{ stats.issues_total }}</dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-cream-50 px-5 py-3">
              <div class="text-sm">
                <router-link
                  to="/admin/issues"
                  class="font-medium text-gold-700 hover:text-gold-800"
                  >View all issues</router-link
                >
              </div>
            </div>
          </div>

          <div
            class="motion-preset-slide-up-sm motion-delay-150 overflow-hidden rounded-xl bg-white shadow border border-gold-200/50 backdrop-blur-sm"
          >
            <div class="p-5">
              <div class="flex items-center">
                <div class="shrink-0 rounded-md bg-green-100 p-3">
                  <CheckCircleIcon class="h-6 w-6 text-green-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="truncate text-sm font-medium text-slate-600">Resolved Issues</dt>
                    <dd class="text-2xl font-semibold text-slate-900">
                      {{ stats.issues_by_status?.resolved || 0 }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-cream-50 px-5 py-3">
              <div class="text-sm">
                <span class="text-slate-600">Resolved</span>
              </div>
            </div>
          </div>

          <div
            class="motion-preset-slide-up-sm motion-delay-200 overflow-hidden rounded-xl bg-white shadow border border-gold-200/50 backdrop-blur-sm"
          >
            <div class="p-5">
              <div class="flex items-center">
                <div class="shrink-0 rounded-md bg-gold-100 p-3">
                  <ClockIcon class="h-6 w-6 text-gold-700" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="truncate text-sm font-medium text-slate-600">Recent Activity</dt>
                    <dd class="text-2xl font-semibold text-slate-900">
                      {{ stats.recent_activity_count }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-cream-50 px-5 py-3">
              <div class="text-sm">
                <router-link
                  to="/admin/audit-logs"
                  class="font-medium text-gold-700 hover:text-gold-800"
                  >View audit logs</router-link
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Charts / Detailed Breakdown -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
          <!-- Issues by Status -->
          <div class="motion-preset-slide-up-sm motion-delay-300 rounded-xl bg-white p-6 shadow border border-gold-200/50">
            <h3 class="mb-4 text-lg font-medium text-slate-900">Issues by Status</h3>
            <div class="space-y-4">
              <div
                v-for="(count, status) in stats.issues_by_status"
                :key="status"
                class="flex items-center"
              >
                <span class="w-24 text-sm font-medium text-slate-600 capitalize">{{
                  status.replace('_', ' ')
                }}</span>
                <div class="mr-4 ml-4 flex-1">
                  <div class="h-2 overflow-hidden rounded-full bg-gold-100">
                    <div
                      class="h-full rounded-full"
                      :class="{
                        'bg-gold-500': status === 'pending_review',
                        'bg-saffron-500': status === 'in_progress',
                        'bg-green-500': status === 'resolved',
                      }"
                      :style="{ width: `${(count / stats.issues_total) * 100}%` }"
                    ></div>
                  </div>
                </div>
                <span class="text-sm font-semibold text-slate-900">{{ count }}</span>
              </div>
              <div v-if="!stats.issues_total" class="text-center text-slate-600">
                No issues found
              </div>
            </div>
          </div>

          <!-- Users by Role -->
          <div class="motion-preset-slide-up-sm motion-delay-300 rounded-xl bg-white p-6 shadow border border-gold-200/50">
            <h3 class="mb-4 text-lg font-medium text-slate-900">Users by Role</h3>
            <div class="space-y-4">
              <div
                v-for="(count, role) in stats.users_by_role"
                :key="role"
                class="flex items-center"
              >
                <span class="w-24 text-sm font-medium text-slate-600 capitalize">{{ role }}</span>
                <div class="mr-4 ml-4 flex-1">
                  <div class="h-2 overflow-hidden rounded-full bg-gold-100">
                    <div
                      class="h-full rounded-full bg-gold-500"
                      :style="{ width: `${(count / stats.users_total) * 100}%` }"
                    ></div>
                  </div>
                </div>
                <span class="text-sm font-semibold text-slate-900">{{ count }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Access -->
        <div class="motion-preset-slide-up-sm motion-delay-500 rounded-xl bg-white p-6 shadow border border-gold-200/50">
          <h3 class="mb-4 text-lg font-medium text-slate-900">Quick Access</h3>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <router-link
              to="/admin/users"
              class="flex items-center gap-3 rounded-lg border border-gold-200 p-4 transition-colors hover:border-gold-400 hover:bg-gold-50"
            >
              <div class="rounded-md bg-gold-100 p-2">
                <UsersIcon class="h-5 w-5 text-gold-700" />
              </div>
              <div>
                <div class="font-medium text-slate-900">Manage Users</div>
                <div class="text-sm text-slate-600">View all users</div>
              </div>
            </router-link>

            <router-link
              to="/admin/staff"
              class="flex items-center gap-3 rounded-lg border border-gold-200 p-4 transition-colors hover:border-gold-400 hover:bg-gold-50"
            >
              <div class="rounded-md bg-saffron-100 p-2">
                <IdentificationIcon class="h-5 w-5 text-saffron-700" />
              </div>
              <div>
                <div class="font-medium text-slate-900">Manage Staff</div>
                <div class="text-sm text-slate-600">View staff accounts</div>
              </div>
            </router-link>

            <router-link
              to="/admin/issues"
              class="flex items-center gap-3 rounded-lg border border-gold-200 p-4 transition-colors hover:border-gold-400 hover:bg-gold-50"
            >
              <div class="rounded-md bg-saffron-100 p-2">
                <ExclamationTriangleIcon class="h-5 w-5 text-saffron-700" />
              </div>
              <div>
                <div class="font-medium text-slate-900">Manage Issues</div>
                <div class="text-sm text-slate-600">View all issues</div>
              </div>
            </router-link>

            <router-link
              to="/admin/audit-logs"
              class="flex items-center gap-3 rounded-lg border border-gold-200 p-4 transition-colors hover:border-gold-400 hover:bg-gold-50"
            >
              <div class="rounded-md bg-green-100 p-2">
                <ClipboardDocumentListIcon class="h-5 w-5 text-green-700" />
              </div>
              <div>
                <div class="font-medium text-slate-900">Audit Logs</div>
                <div class="text-sm text-slate-600">View activity logs</div>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
  UserGroupIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  UsersIcon,
  IdentificationIcon,
  ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'

const stats = ref({
  users_total: 0,
  issues_total: 0,
  users_by_role: {},
  issues_by_status: {},
  recent_activity_count: 0,
})

const loading = ref(true)
const error = ref(null)

const fetchStats = async () => {
  loading.value = true
  try {
    const response = await axios.get('http://localhost/civic-connect/backend/api/admin/stats')
    if (response.data.success) {
      stats.value = response.data.stats
    }
  } catch (err) {
    error.value = 'Failed to load dashboard statistics'
    console.error('Error fetching stats:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>
