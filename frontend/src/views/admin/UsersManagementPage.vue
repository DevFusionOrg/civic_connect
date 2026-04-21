<template>
  <div class="min-h-screen bg-cream-50 pb-12">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gold-200/60">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-slate-900">Manage Users</h1>
        <p class="mt-1 text-sm text-slate-600">View users and manage their roles and permissions.</p>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Search -->
      <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="relative w-full max-w-md">
          <MagnifyingGlassIcon
            class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-gold-400"
          />
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search by name or email..."
            class="w-full rounded-lg border-gold-200 bg-white py-2 pr-4 pl-10 text-sm text-slate-800 focus:border-gold-400 focus:ring-gold-300"
          />
        </div>
      </div>

      <!-- Users Table -->
      <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gold-200/50">
        <div v-if="loading" class="flex h-64 items-center justify-center">
          <div
            class="h-8 w-8 animate-spin rounded-full border-4 border-gold-600 border-t-transparent"
          ></div>
        </div>

        <div
          v-else-if="users.length === 0"
          class="flex h-64 flex-col items-center justify-center py-12 text-center"
        >
          <h3 class="mt-2 text-sm font-semibold text-slate-900">No users found</h3>
          <p class="mt-1 text-sm text-slate-600">Try adjusting your search terms.</p>
        </div>

        <table v-else class="min-w-full divide-y divide-gold-100/70">
          <thead class="bg-cream-50">
            <tr>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                User
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Role
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Status
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Joined
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Last Login
              </th>
              <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gold-100/70 bg-white">
            <tr
              v-for="user in users"
              :key="user.id"
              class="group transition-colors hover:bg-gold-50/40"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gold-100 font-semibold text-gold-700"
                  >
                    {{ user.first_name[0] }}{{ user.last_name[0] }}
                  </div>
                  <div class="ml-4">
                    <div class="font-medium text-slate-900">
                      {{ user.first_name }} {{ user.last_name }}
                    </div>
                    <div class="text-sm text-slate-600">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                  :class="{
                    'bg-gold-100 text-gold-800': user.role === 'citizen',
                    'bg-saffron-100 text-saffron-800': user.role === 'staff',
                    'bg-slate-100 text-slate-800': user.role === 'admin',
                  }"
                >
                  {{ user.role }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="
                    user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  "
                >
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-600">
                {{ formatDate(user.created_at) }}
              </td>
              <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-600">
                {{ user.last_login ? formatDate(user.last_login) : 'Never' }}
              </td>
              <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                <button @click="openRoleModal(user)" class="text-gold-700 hover:text-gold-900">
                  Edit Role
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          v-if="pagination.total_pages > 1"
          class="flex items-center justify-between border-t border-gold-200/60 bg-white px-4 py-3 sm:px-6"
        >
          <div class="flex flex-1 justify-between sm:hidden">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="relative inline-flex items-center rounded-md border border-gold-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-gold-50 disabled:opacity-50"
            >
              Previous
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.total_pages"
              class="relative ml-3 inline-flex items-center rounded-md border border-gold-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-gold-50 disabled:opacity-50"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-slate-700">
                Showing
                <span class="font-medium">{{
                  (pagination.current_page - 1) * pagination.limit + 1
                }}</span>
                to
                <span class="font-medium">{{
                  Math.min(pagination.current_page * pagination.limit, pagination.total_items)
                }}</span>
                of
                <span class="font-medium">{{ pagination.total_items }}</span>
                results
              </p>
            </div>
            <div>
              <nav
                class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                aria-label="Pagination"
              >
                <button
                  @click="changePage(pagination.current_page - 1)"
                  :disabled="pagination.current_page === 1"
                  class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gold-500 ring-1 ring-gold-200 ring-inset hover:bg-gold-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
                >
                  <span class="sr-only">Previous</span>
                  <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
                </button>
                <button
                  v-for="page in pagination.total_pages"
                  :key="page"
                  @click="changePage(page)"
                  :class="[
                    page === pagination.current_page
                      ? 'relative z-10 inline-flex items-center bg-gold-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-600'
                      : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-gold-200 ring-inset hover:bg-gold-50 focus:z-20 focus:outline-offset-0',
                  ]"
                >
                  {{ page }}
                </button>
                <button
                  @click="changePage(pagination.current_page + 1)"
                  :disabled="pagination.current_page === pagination.total_pages"
                  class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gold-500 ring-1 ring-gold-200 ring-inset hover:bg-gold-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
                >
                  <span class="sr-only">Next</span>
                  <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Edit Modal -->
    <div
      v-if="editingUser"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Edit User Role</h3>
        <p class="mt-2 text-sm text-slate-600">
          Change role for
          <span class="font-medium text-slate-900"
            >{{ editingUser.first_name }} {{ editingUser.last_name }}</span
          >.
        </p>

        <div class="mt-6 space-y-4">
          <div v-for="role in ['citizen', 'staff', 'admin']" :key="role" class="flex items-center">
            <input
              v-model="selectedRole"
              :id="role"
              name="role"
              type="radio"
              :value="role"
              class="h-4 w-4 border-gold-300 text-gold-600 focus:ring-gold-600"
            />
            <label :for="role" class="ml-3 block text-sm font-medium text-slate-700 capitalize">
              {{ role }}
            </label>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button
            @click="closeRoleModal"
            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-gold-100"
          >
            Cancel
          </button>
          <button
            @click="saveRole"
            :disabled="saving"
            class="rounded-lg bg-gold-600 px-4 py-2 text-sm font-medium text-white hover:bg-gold-700 disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save Role' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { MagnifyingGlassIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

// Simple debounce implementation
const debounce = (func, delay) => {
  let timeoutId
  return (...args) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => func(...args), delay)
  }
}

const users = ref([])
const loading = ref(true)
const searchQuery = ref('')
const pagination = ref({
  current_page: 1,
  total_pages: 1,
  total_items: 0,
  limit: 20,
})

const editingUser = ref(null)
const selectedRole = ref('')
const saving = ref(false)

const fetchUsers = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('http://localhost/civic-connect/backend/api/admin/users', {
      params: {
        page,
        search: searchQuery.value,
      },
    })

    if (response.data.success) {
      users.value = response.data.users
      pagination.value = response.data.pagination
    }
  } catch (error) {
    console.error('Failed to fetch users:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = debounce(() => {
  fetchUsers(1)
}, 300)

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.total_pages) {
    fetchUsers(page)
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const openRoleModal = (user) => {
  editingUser.value = user
  selectedRole.value = user.role
}

const closeRoleModal = () => {
  editingUser.value = null
  selectedRole.value = ''
}

const saveRole = async () => {
  if (!editingUser.value) return

  saving.value = true
  try {
    await axios.put(
      `http://localhost/civic-connect/backend/api/admin/users/${editingUser.value.id}/role`,
      {
        role: selectedRole.value,
      },
    )

    // Update local state
    const userIndex = users.value.findIndex((u) => u.id === editingUser.value.id)
    if (userIndex !== -1) {
      users.value[userIndex].role = selectedRole.value
    }

    closeRoleModal()
  } catch (error) {
    console.error('Failed to update role:', error)
    alert('Failed to update role. Please try again.')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchUsers()
})
</script>
