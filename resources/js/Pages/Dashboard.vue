<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    stats: {
        totalDocuments: number;
        processedDocuments: number;
        questionsAsked: number;
    };
}>();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-primary-600">Workspace</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Dashboard
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <!-- Welcome Section -->
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="grid gap-8 p-8 sm:p-10 md:grid-cols-[1fr_auto] md:items-center">
                        <div class="max-w-2xl">
                            <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">
                                AI KNOWLEDGE BASE
                            </span>

                            <h1 class="mt-5 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                                Your knowledge, one question away.
                            </h1>

                            <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">
                                Bring your documents together, find information faster,
                                and get answers grounded in your own files.
                            </p>

                            <div class="mt-7 flex flex-wrap gap-3">
                                <Link
                                    :href="route('question.answer.page')"
                                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                                >
                                    Ask your documents
                                    <span class="ml-2" aria-hidden="true">→</span>
                                </Link>

                                <Link
                                    :href="route('documents.index')"
                                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-300"
                                >
                                    View documents
                                </Link>
                            </div>
                        </div>

                        <div class="hidden h-40 w-40 items-center justify-center rounded-2xl bg-primary-50 md:flex">
                            <svg
                                viewBox="0 0 100 100"
                                class="h-24 w-24 text-primary-600"
                                fill="none"
                                aria-hidden="true"
                            >
                                <rect x="23" y="12" width="48" height="62" rx="7" stroke="currentColor" stroke-width="3" />
                                <path d="M35 30H59M35 41H59M35 52H51" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                <circle cx="68" cy="68" r="17" fill="#DBEAFE" stroke="currentColor" stroke-width="3" />
                                <path d="M63 68L67 72L74 64" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>
                </section>

                <!-- Stats Section -->
                <section>
                    <div class="mb-5">
                        <h3 class="text-lg font-semibold text-slate-900">Overview</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            Your document workspace at a glance.
                        </p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-slate-600">Total documents</p>
                                <span class="rounded-lg bg-primary-50 p-2 text-primary-600" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M6 3.75h8l4 4V20.25H6z" stroke-linejoin="round" />
                                        <path d="M14 3.75v4h4M9 12h6M9 16h6" stroke-linecap="round" />
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-4 text-3xl font-bold text-slate-900">
                                {{ stats.totalDocuments }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">Documents in your library</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-slate-600">Processed files</p>
                                <span class="rounded-lg bg-emerald-50 p-2 text-emerald-600" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-4 text-3xl font-bold text-slate-900">
                                {{ stats.processedDocuments }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">Ready to search and ask questions</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow sm:col-span-2 lg:col-span-1">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-slate-600">Questions asked</p>
                                <span class="rounded-lg bg-violet-50 p-2 text-violet-600" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M8.5 9a3.5 3.5 0 0 1 7 0c0 2.5-3.5 2.5-3.5 5" stroke-linecap="round" />
                                        <circle cx="12" cy="18" r=".8" fill="currentColor" />
                                        <circle cx="12" cy="12" r="9" />
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-4 text-3xl font-bold text-slate-900">
                                {{ stats.questionsAsked }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">Questions across your workspace</p>
                        </div>
                    </div>
                </section>

                <!-- Quick Actions Section -->
                <section>
                    <div class="mb-5">
                        <h3 class="text-lg font-semibold text-slate-900">Quick actions</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            Pick up where you want to start.
                        </p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <Link
                            :href="route('documents.index')"
                            class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-primary-200 hover:shadow"
                        >
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M12 16V4m-4 4 4-4 4 4M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <h4 class="mt-5 font-semibold text-slate-900">Manage documents</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Upload files and manage your document library.
                            </p>
                            <span class="mt-4 inline-block text-sm font-semibold text-primary-600 group-hover:text-primary-700">
                                Open library →
                            </span>
                        </Link>

                        <Link
                            :href="route('documents.search')"
                            class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-primary-200 hover:shadow"
                        >
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="10.5" cy="10.5" r="6.5" />
                                    <path d="m15.5 15.5 5 5" stroke-linecap="round" />
                                </svg>
                            </div>
                            <h4 class="mt-5 font-semibold text-slate-900">Search documents</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Find relevant information across your uploaded files.
                            </p>
                            <span class="mt-4 inline-block text-sm font-semibold text-primary-600 group-hover:text-primary-700">
                                Start searching →
                            </span>
                        </Link>

                        <Link
                            :href="route('question.answer.page')"
                            class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-primary-200 hover:shadow"
                        >
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M5 5.75A2.75 2.75 0 0 1 7.75 3h8.5A2.75 2.75 0 0 1 19 5.75v7.5A2.75 2.75 0 0 1 16.25 16H11l-5 4v-4.5A2.75 2.75 0 0 1 5 13.75z" stroke-linejoin="round" />
                                    <path d="M9 8h6M9 11.5h4" stroke-linecap="round" />
                                </svg>
                            </div>
                            <h4 class="mt-5 font-semibold text-slate-900">Ask AI</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Ask questions and get answers based on your documents.
                            </p>
                            <span class="mt-4 inline-block text-sm font-semibold text-primary-600 group-hover:text-primary-700">
                                Start a conversation →
                            </span>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>