<template>
  <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-cream-50 via-cream-100 to-cream-50 text-slate-800">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div class="dashboard-orb absolute -left-24 top-20 h-80 w-80 rounded-full bg-gold-300/15 blur-3xl"></div>
      <div class="dashboard-orb absolute right-[-6rem] top-32 h-96 w-96 rounded-full bg-saffron-500/10 blur-3xl"></div>
      <div class="dashboard-orb absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-gold-200/15 blur-3xl"></div>
    </div>

    <div class="relative w-full px-4 py-6 sm:px-6 lg:px-8 xl:px-10 2xl:px-12">
      <section
        class="dashboard-hero relative overflow-hidden rounded-[2rem] border border-gold-200/70 bg-gradient-to-br from-cream-50/80 to-cream-100/80 p-6 shadow-[0_24px_90px_rgba(212,175,55,0.12)] backdrop-blur-2xl sm:p-8"
      >
        <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.72),rgba(255,253,247,0.18))]"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-200 to-transparent"></div>
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-gold-300/20 blur-3xl"></div>

        <div class="relative grid gap-6 xl:grid-cols-[1.6fr_1fr] xl:items-end">
          <div class="dashboard-copy max-w-3xl">
            <span class="inline-flex items-center rounded-full border border-gold-200/80 bg-gold-50/90 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-gold-700">
              Community overview
            </span>
            <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
              Welcome, {{ authStore.user?.first_name || 'Citizen' }}!
            </h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
              Your dashboard is designed to keep the experience calm, clean, and easy to scan while still showing what matters most in your community.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
              <router-link
                to="/report-issue"
                class="dashboard-action dashboard-report-cta inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-gold-500 to-saffron-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-gold-500/20 transition-transform hover:-translate-y-0.5"
              >
                <PlusCircleIcon class="h-5 w-5" />
                Report issue
              </router-link>
              <router-link
                to="/issues"
                class="dashboard-action inline-flex items-center gap-2 rounded-full border border-gold-200/80 bg-white/70 px-5 py-3 text-sm font-semibold text-gold-700 shadow-sm backdrop-blur-xl transition-colors hover:border-gold-300 hover:bg-gold-50/90"
              >
                <ListBulletIcon class="h-5 w-5" />
                Browse issues
              </router-link>
            </div>
          </div>

          <div class="dashboard-glass-panel relative overflow-hidden rounded-[1.75rem] border border-gold-200/70 bg-white/55 p-5 shadow-[0_20px_60px_rgba(212,175,55,0.12)] backdrop-blur-2xl">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(212,175,55,0.45),transparent_35%),radial-gradient(circle_at_bottom_left,rgba(255,245,214,0.5),transparent_35%)]"></div>
            <div class="relative space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gold-700">Activity Graph</p>
                  <p class="mt-1 text-sm text-slate-600">Visual status breakdown of your reports</p>
                </div>
                <div class="rounded-2xl bg-gold-100/80 p-3 text-gold-700">
                  <MapPinIcon class="h-6 w-6" />
                </div>
              </div>

              <div class="grid grid-cols-5 gap-3">
                <div class="col-span-3 space-y-3 rounded-2xl border border-gold-200/70 bg-white/55 p-4 backdrop-blur-xl">
                  <div
                    v-for="row in activityBars"
                    :key="row.label"
                    class="grid grid-cols-[5.8rem_1fr_2.2rem] items-center gap-2"
                  >
                    <span class="text-xs font-medium text-slate-600">{{ row.label }}</span>
                    <div class="h-2.5 rounded-full bg-gold-100/70">
                      <div
                        class="h-2.5 rounded-full transition-all duration-500"
                        :class="row.color"
                        :style="{ width: `${row.width}%` }"
                      ></div>
                    </div>
                    <span class="text-right text-xs font-semibold text-slate-700">{{ row.value }}</span>
                  </div>
                </div>

                <div class="col-span-2 rounded-2xl border border-gold-200/70 bg-white/55 p-4 backdrop-blur-xl">
                  <p class="text-xs font-medium text-slate-500">Completion</p>
                  <div class="mt-3 flex items-center justify-center">
                    <div class="relative h-20 w-20">
                      <svg class="h-20 w-20 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="48" fill="none" stroke="rgb(212 175 55 / 0.35)" stroke-width="12" />
                        <circle
                          cx="60"
                          cy="60"
                          r="48"
                          fill="none"
                          stroke="rgb(212 175 55)"
                          stroke-width="12"
                          stroke-linecap="round"
                          :stroke-dasharray="completionRing"
                        />
                      </svg>
                      <div class="absolute inset-0 flex items-center justify-center text-lg font-bold text-slate-900">
                        {{ completionRate }}%
                      </div>
                    </div>
                  </div>
                  <p class="mt-2 text-center text-[11px] text-slate-600">of your reports resolved</p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3 rounded-2xl border border-gold-200 bg-gold-50/80 p-4">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.15em] text-gold-900">Engagement</p>
                  <p class="mt-1 text-2xl font-bold text-slate-900">{{ engagementScore }}</p>
                  <p class="mt-1 text-xs text-slate-600">upvotes per issue</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.15em] text-gold-900">Community Volume</p>
                  <p class="mt-1 text-2xl font-bold text-slate-900">{{ stats.totalIssues }}</p>
                  <p class="mt-1 text-xs text-slate-600">active issues tracked citywide</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="dashboard-card rounded-[1.5rem] border border-gold-200/70 bg-white/55 p-6 shadow-[0_18px_60px_rgba(212,175,55,0.08)] backdrop-blur-2xl">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gold-700">My Issues</p>
              <p class="mt-3 text-4xl font-bold text-slate-900">{{ stats.myIssuesCount }}</p>
              <p class="mt-2 text-sm text-slate-600">Issues you have reported</p>
            </div>
            <div class="rounded-2xl bg-gold-100/80 p-3 text-gold-700 shadow-inner shadow-white/60">
              <FlagIcon class="h-7 w-7" />
            </div>
          </div>
          <router-link
            to="/my-issues"
            class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-gold-700 transition-colors hover:text-gold-900"
          >
            View all
            <ArrowRightIcon class="h-4 w-4" />
          </router-link>
        </div>

        <div class="dashboard-card rounded-[1.5rem] border border-gold-200/70 bg-white/55 p-6 shadow-[0_18px_60px_rgba(212,175,55,0.08)] backdrop-blur-2xl">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-saffron-600">Resolved</p>
              <p class="mt-3 text-4xl font-bold text-slate-900">{{ stats.resolvedCount }}</p>
              <p class="mt-2 text-sm text-slate-600">Issues marked complete</p>
            </div>
            <div class="rounded-2xl bg-saffron-100/80 p-3 text-saffron-600 shadow-inner shadow-white/60">
              <CheckCircleIcon class="h-7 w-7" />
            </div>
          </div>
        </div>

        <div class="dashboard-card rounded-[1.5rem] border border-gold-200/70 bg-white/55 p-6 shadow-[0_18px_60px_rgba(212,175,55,0.08)] backdrop-blur-2xl">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gold-700">Upvotes</p>
              <p class="mt-3 text-4xl font-bold text-slate-900">{{ stats.totalUpvotes }}</p>
              <p class="mt-2 text-sm text-slate-600">Community support received</p>
            </div>
            <div class="rounded-2xl bg-gold-100/80 p-3 text-gold-700 shadow-inner shadow-white/60">
              <HandThumbUpIcon class="h-7 w-7" />
            </div>
          </div>
        </div>

        <div class="dashboard-card rounded-[1.5rem] border border-gold-200/70 bg-white/55 p-6 shadow-[0_18px_60px_rgba(212,175,55,0.08)] backdrop-blur-2xl">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-saffron-600">Community</p>
              <p class="mt-3 text-4xl font-bold text-slate-900">{{ stats.totalIssues }}</p>
              <p class="mt-2 text-sm text-slate-600">Issues currently tracked</p>
            </div>
            <div class="rounded-2xl bg-saffron-100/80 p-3 text-saffron-600 shadow-inner shadow-white/60">
              <MapPinIcon class="h-7 w-7" />
            </div>
          </div>
        </div>
      </section>

      <section class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <router-link
          to="/report-issue"
          class="dashboard-action group relative overflow-hidden rounded-[1.75rem] border border-gold-300/70 bg-gradient-to-br from-gold-500 via-gold-400 to-saffron-500 p-7 text-white shadow-[0_24px_70px_rgba(212,175,55,0.24)] transition-transform hover:-translate-y-1"
        >
          <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_30%)]"></div>
          <div class="relative flex items-center gap-4">
            <div class="rounded-2xl bg-white/15 p-3 backdrop-blur-xl">
              <PlusCircleIcon class="h-8 w-8 text-white" />
            </div>
            <div>
              <h3 class="text-xl font-bold">Report Issue</h3>
              <p class="mt-1 text-gold-50/90">Add a new community concern in a few quick steps.</p>
            </div>
          </div>
        </router-link>

        <router-link
          to="/issues"
          class="dashboard-action group relative overflow-hidden rounded-[1.75rem] border border-gold-200/70 bg-white/60 p-7 shadow-[0_18px_60px_rgba(212,175,55,0.10)] backdrop-blur-2xl transition-transform hover:-translate-y-1"
        >
          <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(212,175,55,0.45),transparent_32%)]"></div>
          <div class="relative flex items-center gap-4">
            <div class="rounded-2xl bg-gold-100/90 p-3 text-gold-700">
              <ListBulletIcon class="h-8 w-8" />
            </div>
            <div>
              <h3 class="text-xl font-bold text-slate-900 transition-colors group-hover:text-gold-800">
                Browse Issues
              </h3>
              <p class="mt-1 text-slate-600">Explore community updates and upvote what matters.</p>
            </div>
          </div>
        </router-link>
      </section>

      <section class="mt-6 grid grid-cols-1 gap-6 2xl:grid-cols-12">
        <div class="dashboard-panel 2xl:col-span-7 rounded-[1.75rem] border border-gold-200/70 bg-white/55 shadow-[0_18px_60px_rgba(212,175,55,0.08)] backdrop-blur-2xl">
          <div class="flex items-center justify-between border-b border-gold-100/80 px-6 py-5">
            <div>
              <h2 class="text-2xl font-bold text-slate-900">Recent Community Issues</h2>
              <p class="mt-1 text-sm text-slate-600">The latest issues being discussed in your area</p>
            </div>
            <router-link to="/issues" class="text-sm font-semibold text-gold-700 hover:text-gold-900">
              See all →
            </router-link>
          </div>

          <div v-if="isLoading" class="flex min-h-[18rem] items-center justify-center px-6 py-12 text-center">
            <div class="rounded-full bg-gold-100/90 p-4 text-gold-700 shadow-inner shadow-white/60">
              <ArrowPathIcon class="h-8 w-8 animate-spin" />
            </div>
          </div>

          <div v-else-if="recentIssues.length === 0" class="flex min-h-[18rem] flex-col items-center justify-center px-6 py-12 text-center">
            <div class="rounded-full bg-gold-100/70 p-4 text-gold-700">
              <InboxIcon class="h-10 w-10" />
            </div>
            <p class="mt-4 text-lg font-semibold text-slate-900">No issues reported yet</p>
            <p class="mt-2 max-w-md text-sm text-slate-600">Be the first to add a report and help set the conversation in motion.</p>
          </div>

          <div v-else class="divide-y divide-gold-100/80">
            <div
              v-for="issue in recentIssues"
              :key="issue.id"
              @click="router.push(`/issues/${issue.id}`)"
              class="recent-issue-item group flex cursor-pointer items-center gap-4 px-6 py-5 transition-colors hover:bg-white/50"
            >
              <div class="shrink-0">
                <div
                  :class="{
                    'bg-gold-50 text-gold-800 ring-1 ring-gold-200': issue.status === 'pending_review',
                    'bg-saffron-50 text-saffron-800 ring-1 ring-saffron-200': issue.status === 'in_progress',
                    'bg-green-50 text-green-800 ring-1 ring-green-200': issue.status === 'resolved',
                  }"
                  class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]"
                >
                  {{ issue.status }}
                </div>
              </div>

              <div class="min-w-0 grow">
                <div class="truncate text-lg font-semibold text-slate-900 transition-colors group-hover:text-gold-800">
                  {{ issue.title }}
                </div>
                <p class="mt-1 text-sm text-slate-600">
                  {{ issue.category }} • {{ formatDate(issue.created_at) }}
                </p>
              </div>

              <div class="hidden items-center gap-2 rounded-full border border-gold-100 bg-white/80 px-3 py-2 text-sm text-slate-700 sm:flex">
                <HandThumbUpIcon
                  :class="issue.user_has_upvoted ? 'text-saffron-500' : 'text-slate-400'"
                  class="h-4 w-4"
                />
                <span class="font-semibold">{{ issue.upvote_count || 0 }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="dashboard-panel dashboard-my-reports 2xl:col-span-5 rounded-[1.75rem] border border-gold-200/70 bg-white/55 shadow-[0_18px_60px_rgba(212,175,55,0.08)] backdrop-blur-2xl">
          <div class="border-b border-gold-100/80 px-6 py-5">
            <h2 class="text-2xl font-bold text-slate-900">My Reports</h2>
            <p class="mt-1 text-sm text-slate-600">A quick view of your latest submissions</p>
          </div>

          <div v-if="userIssuesLoading" class="flex min-h-[18rem] items-center justify-center px-6 py-12 text-center">
            <div class="rounded-full bg-gold-100/90 p-4 text-gold-700 shadow-inner shadow-white/60">
              <ArrowPathIcon class="h-8 w-8 animate-spin" />
            </div>
          </div>

          <div v-else-if="userIssues.length === 0" class="flex min-h-[18rem] flex-col items-center justify-center px-6 py-12 text-center">
            <div class="rounded-full bg-gold-100/70 p-4 text-gold-700">
              <InboxIcon class="h-12 w-12" />
            </div>
            <p class="mt-4 text-lg font-semibold text-slate-900">You haven't reported any issues yet.</p>
            <p class="mt-2 max-w-md text-sm text-slate-600">Start with a simple report and track everything from this calm, focused dashboard.</p>
            <router-link
              to="/report-issue"
              class="mt-5 inline-flex items-center rounded-full bg-gold-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-gold-500/20 transition-colors hover:bg-gold-700"
            >
              Report your first issue
            </router-link>
          </div>

          <div v-else class="divide-y divide-gold-100/80">
            <div
              v-for="issue in userIssues.slice(0, 3)"
              :key="issue.id"
              class="report-item group flex flex-col gap-4 px-6 py-5 transition-colors hover:bg-white/50 md:flex-row md:items-center md:justify-between"
            >
              <div class="min-w-0 grow">
                <div class="mb-2 flex flex-wrap items-center gap-3">
                  <h3 class="truncate text-lg font-bold text-slate-900 transition-colors group-hover:text-gold-800">
                    {{ issue.title }}
                  </h3>
                  <span
                    :class="{
                      'bg-gold-50 text-gold-700': issue.status === 'pending_review',
                      'bg-saffron-50 text-saffron-700': issue.status === 'in_progress',
                      'bg-green-50 text-green-700': issue.status === 'resolved',
                    }"
                    class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.18em]"
                  >
                    {{ issue.status }}
                  </span>
                </div>
                <p class="mb-2 line-clamp-1 text-sm text-slate-600">{{ issue.description }}</p>
                <p class="text-xs text-slate-500">
                  Reported on {{ new Date(issue.created_at).toLocaleDateString() }}
                </p>
              </div>

              <div class="flex items-center gap-4">
                <div class="min-w-[60px] rounded-2xl border border-gold-100 bg-white/80 px-3 py-2 text-center md:text-right">
                  <p class="text-xs text-slate-500">Upvotes</p>
                  <p class="font-bold text-slate-900">{{ issue.upvote_count || 0 }}</p>
                </div>
                <router-link
                  :to="`/issues/${issue.id}`"
                  class="inline-flex items-center rounded-full bg-gold-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-gold-500/15 transition-colors hover:bg-gold-700"
                >
                  View <ArrowRightIcon class="ml-1 h-4 w-4" />
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </section>

      <router-link
        to="/report-issue"
        class="bg-gold-600 shadow-gold-500/30 hover:bg-gold-700 fixed right-6 bottom-6 z-50 flex items-center justify-center rounded-full p-4 text-white shadow-lg transition-all hover:scale-110 active:scale-95 md:hidden"
      >
        <PlusIcon class="h-6 w-6" />
      </router-link>

      <!-- First-time Onboarding -->
      <div
        v-if="showOnboarding"
        class="fixed inset-0 z-[90]"
      >
        <div class="absolute inset-0 bg-slate-900/55"></div>

        <div
          class="pointer-events-none absolute rounded-2xl border-2 border-gold-300 shadow-[0_0_0_9999px_rgba(15,23,42,0.28)] transition-all duration-300"
          :style="onboardingFocusStyle"
        ></div>

        <div
          ref="onboardingPanelRef"
          class="pointer-events-auto absolute w-full max-w-[14rem] max-h-[calc(100dvh-1.5rem)] overflow-y-auto overflow-x-hidden rounded-2xl border border-gold-100 bg-white shadow-2xl transition-all duration-300"
          :style="onboardingPanelStyle"
        >
          <div class="border-b border-gold-100 bg-gold-50/70 px-3 py-2.5">
            <div class="flex items-center justify-between gap-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gold-700">Dashboard Tour</p>
                <h3 class="mt-1 text-base font-bold text-slate-900">{{ currentOnboardingStep.title }}</h3>
              </div>
              <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                {{ onboardingStepIndex + 1 }} / {{ onboardingSteps.length }}
              </span>
            </div>
          </div>

          <div class="px-3 py-2.5">
            <p class="text-xs leading-5 text-slate-700">{{ currentOnboardingStep.description }}</p>

            <div class="mt-4 grid grid-cols-5 gap-2">
              <span
                v-for="(step, index) in onboardingSteps"
                :key="step.title"
                class="h-1.5 rounded-full transition-colors"
              ></span>
            </div>
          </div>

          <div class="flex items-center justify-between border-t border-gold-100 bg-white px-3 py-2.5">
            <button
              @click="skipOnboarding"
              class="rounded-full border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50"
            >
              Skip
            </button>

            <div class="flex items-center gap-2">
              <button
                v-if="onboardingStepIndex > 0"
                @click="goToPreviousOnboardingStep"
                class="rounded-full border border-gold-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gold-700 transition-colors hover:bg-gold-50"
              >
                Back
              </button>
              <button
                @click="goToNextOnboardingStep"
                class="rounded-full bg-gold-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-gold-700"
              >
                {{ onboardingStepIndex === onboardingSteps.length - 1 ? 'Finish' : 'Next' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import { useIssuesStore } from '../../stores/issuesStore'
import { animate, stagger } from 'animejs'
import {
  ListBulletIcon,
  CheckCircleIcon,
  InboxIcon,
  ArrowRightIcon,
  PlusIcon,
  FlagIcon,
  HandThumbUpIcon,
  MapPinIcon,
  PlusCircleIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/solid'

const router = useRouter()
const authStore = useAuthStore()
const issuesStore = useIssuesStore()

const stats = ref({
  myIssuesCount: 0,
  resolvedCount: 0,
  totalUpvotes: 0,
  totalIssues: 0,
})

const recentIssues = ref([])
const userIssues = ref([])
const isLoading = ref(false)
const userIssuesLoading = ref(false)
const showOnboarding = ref(false)
const onboardingStepIndex = ref(0)
const onboardingPanelRef = ref(null)
const onboardingPanelStyle = ref({ left: '1rem', top: '1rem' })
const onboardingFocusStyle = ref({ left: '0px', top: '0px', width: '0px', height: '0px', opacity: '0' })

const onboardingStorageKey = 'civicconnect-citizen-dashboard-onboarding-complete'

const onboardingSteps = [
  {
    title: 'Overview Section',
    description:
      'This is your main overview. It summarizes community activity and gives you a fast starting point.',
    selector: '.dashboard-hero',
    placement: 'bottom',
  },
  {
    title: 'Issues Tab',
    description:
      'Use this tab to browse all reported issues and support important ones through upvotes.',
    selector: 'a[href="/issues"]',
    placement: 'bottom',
  },
  {
    title: 'Map Tab',
    description:
      'Open the map view to visualize issue locations and explore nearby reports spatially.',
    selector: 'a[href="/issues-map"]',
    placement: 'bottom',
  },
  {
    title: 'Report Issue Button',
    description:
      'This button takes you directly to the reporting flow so you can submit a new issue in a few steps.',
    selector: '.dashboard-report-cta',
    placement: 'bottom',
  },
  {
    title: 'Manage Your Reports',
    description:
      'In My Reports, you can review your latest submissions, check status, and open each report for full details.',
    selector: '.dashboard-my-reports',
    placement: 'top',
  },
]

const currentOnboardingStep = computed(
  () => onboardingSteps[onboardingStepIndex.value] || onboardingSteps[0],
)

const positionOnboarding = async () => {
  if (!showOnboarding.value) return

  await nextTick()

  const step = currentOnboardingStep.value
  const target = step?.selector ? document.querySelector(step.selector) : null

  if (!target) {
    onboardingFocusStyle.value = {
      left: '0px',
      top: '0px',
      width: '0px',
      height: '0px',
      opacity: '0',
    }
    onboardingPanelStyle.value = {
      left: '50%',
      top: '50%',
      transform: 'translate(-50%, -50%)',
    }
    return
  }

  target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' })
  await new Promise((resolve) => setTimeout(resolve, 260))

  const rect = target.getBoundingClientRect()
  const pad = 8
  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight

  const panelRect = onboardingPanelRef.value?.getBoundingClientRect()
  const panelWidth = Math.min(224, Math.max(180, panelRect?.width || 224))
  const panelHeight = Math.min(viewportHeight - 24, Math.max(120, panelRect?.height || 164))

  onboardingFocusStyle.value = {
    left: `${Math.max(10, rect.left - pad)}px`,
    top: `${Math.max(10, rect.top - pad)}px`,
    width: `${Math.max(24, rect.width + pad * 2)}px`,
    height: `${Math.max(24, rect.height + pad * 2)}px`,
    opacity: '1',
  }

  let left = rect.left + rect.width / 2 - panelWidth / 2
  left = Math.max(12, Math.min(left, viewportWidth - panelWidth - 12))

  let top
  if (step.placement === 'top') {
    top = rect.top - panelHeight - 18
    if (top < 12) top = rect.bottom + 18
  } else {
    top = rect.bottom + 18
    if (top + panelHeight > viewportHeight - 12) {
      top = Math.max(12, rect.top - panelHeight - 18)
    }
  }

  onboardingPanelStyle.value = {
    left: `${left}px`,
    top: `${top}px`,
  }
}

const completeOnboarding = () => {
  showOnboarding.value = false
  localStorage.setItem(onboardingStorageKey, 'true')
}

const skipOnboarding = () => {
  completeOnboarding()
}

const goToNextOnboardingStep = () => {
  if (onboardingStepIndex.value >= onboardingSteps.length - 1) {
    completeOnboarding()
    return
  }
  onboardingStepIndex.value += 1
  positionOnboarding()
}

const goToPreviousOnboardingStep = () => {
  onboardingStepIndex.value = Math.max(0, onboardingStepIndex.value - 1)
  positionOnboarding()
}

const handleTourViewportChange = () => {
  if (!showOnboarding.value) return
  positionOnboarding()
}

watch(showOnboarding, async (visible) => {
  if (!visible) return
  await positionOnboarding()
})

watch(onboardingStepIndex, () => {
  positionOnboarding()
})

const statusCounts = computed(() => {
  const base = {
    pending: 0,
    inProgress: 0,
    resolved: 0,
  }

  for (const issue of userIssues.value) {
    if (issue.status === 'pending_review') base.pending += 1
    if (issue.status === 'in_progress') base.inProgress += 1
    if (issue.status === 'resolved') base.resolved += 1
  }

  return base
})

const maxStatusCount = computed(() => Math.max(1, ...Object.values(statusCounts.value)))

const activityBars = computed(() => [
  {
    label: 'Pending',
    value: statusCounts.value.pending,
    width: (statusCounts.value.pending / maxStatusCount.value) * 100,
    color: 'bg-emerald-500/80',
  },
  {
    label: 'In Progress',
    value: statusCounts.value.inProgress,
    width: (statusCounts.value.inProgress / maxStatusCount.value) * 100,
    color: 'bg-amber-500/80',
  },
  {
    label: 'Resolved',
    value: statusCounts.value.resolved,
    width: (statusCounts.value.resolved / maxStatusCount.value) * 100,
    color: 'bg-teal-600/80',
  },
])

const completionRate = computed(() => {
  const total = stats.value.myIssuesCount
  if (!total) return 0
  return Math.round((stats.value.resolvedCount / total) * 100)
})

const completionRing = computed(() => {
  const circumference = 2 * Math.PI * 48
  const filled = (completionRate.value / 100) * circumference
  return `${filled} ${circumference}`
})

const engagementScore = computed(() => {
  const total = stats.value.totalIssues || 0
  if (!total) return '0.0'
  return (stats.value.totalUpvotes / total).toFixed(1)
})

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

const playIntroAnimations = async () => {
  if (prefersReducedMotion()) return

  await nextTick()

  animate('.dashboard-hero', {
    opacity: [0, 1],
    translateY: [24, 0],
    duration: 900,
    delay: 80,
  })

  animate('.dashboard-copy > *', {
    opacity: [0, 1],
    translateY: [18, 0],
    delay: stagger(70, { start: 180 }),
    duration: 700,
  })

  animate('.dashboard-glass-panel', {
    opacity: [0, 1],
    translateX: [24, 0],
    duration: 850,
    delay: 180,
  })

  animate('.dashboard-orb', {
    translateY: [0, -18],
    scale: [1, 1.06],
    duration: 12000,
    direction: 'alternate',
    loop: true,
  })
}

const animateCollection = async (selector, startDelay = 0) => {
  if (prefersReducedMotion()) return

  await nextTick()
  animate(selector, {
    opacity: [0, 1],
    translateY: [18, 0],
    scale: [0.98, 1],
    delay: stagger(80, { start: startDelay }),
    duration: 650,
  })
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (date.toDateString() === today.toDateString()) {
    return 'Today at ' + date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  } else if (date.toDateString() === yesterday.toDateString()) {
    return (
      'Yesterday at ' + date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
    )
  } else {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: date.getFullYear() !== today.getFullYear() ? 'numeric' : undefined,
    })
  }
}

const fetchRecentIssues = async () => {
  isLoading.value = true
  try {
    const allIssues = await issuesStore.fetchIssues({ limit: 5 })
    recentIssues.value = allIssues.slice(0, 5)

    // Calculate stats
    stats.value.totalIssues = allIssues.length
    stats.value.totalUpvotes = allIssues.reduce((sum, issue) => sum + (issue.upvote_count || 0), 0)

    await animateCollection('.recent-issue-item', 260)
  } catch (error) {
    console.error('Error fetching recent issues:', error)
  } finally {
    isLoading.value = false
  }
}

const fetchUserIssues = async () => {
  if (!authStore.user?.id) {
    console.warn('User ID not available, skipping user issues fetch')
    return
  }

  userIssuesLoading.value = true
  try {
    const issues = await issuesStore.fetchUserIssues(authStore.user.id)
    userIssues.value = issues

    // Update stats
    stats.value.myIssuesCount = issues.length
    stats.value.resolvedCount = issues.filter((i) => i.status === 'resolved').length
    stats.value.totalUpvotes = issues.reduce((sum, issue) => sum + (issue.upvote_count || 0), 0)

    await animateCollection('.report-item', 320)
  } catch (error) {
    console.error('Error fetching user issues:', error)
  } finally {
    userIssuesLoading.value = false
  }
}

onMounted(() => {
  const isOnboardingDone = localStorage.getItem(onboardingStorageKey) === 'true'
  if (!isOnboardingDone) {
    showOnboarding.value = true
  }

  window.addEventListener('resize', handleTourViewportChange)
  window.addEventListener('scroll', handleTourViewportChange, true)

  playIntroAnimations()
  fetchRecentIssues()
  fetchUserIssues()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleTourViewportChange)
  window.removeEventListener('scroll', handleTourViewportChange, true)
})
</script>
