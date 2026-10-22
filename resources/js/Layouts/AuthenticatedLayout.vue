
<script setup lang="ts">
import { ref } from 'vue';
import DocuMindLogo from '@/Components/DocuMindLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-background text-slate-900">
        <nav class="border-b border-slate-200 bg-navy-900 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex h-full items-center gap-8">
                        <Link
                            :href="route('dashboard')"
                            class="flex shrink-0 items-center gap-2.5"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-600"
                            >
                                <DocuMindLogo
                                    class="h-5 w-5 text-white"
                                />
                            </div>

                            <span
                                class="text-lg font-semibold tracking-tight text-white"
                            >
                                DocuMind
                            </span>
                        </Link>

                        <div class="hidden h-full items-center gap-1 sm:flex">
                            <Link
                                :href="route('dashboard')"
                                :class="[
                                    'flex h-full items-center border-b-2 px-3 py-2 text-sm font-medium transition-colors duration-150',
                                    route().current('dashboard')
                                        ? 'border-primary-500 text-white'
                                        : 'border-transparent text-slate-400 hover:text-white',
                                ]"
                            >
                                Dashboard
                            </Link>

                            <Link
                                :href="route('documents.index')"
                                :class="[
                                    'flex h-full items-center border-b-2 px-3 py-2 text-sm font-medium transition-colors duration-150',
                                    route().current('documents.index') || route().current('documents.show') || route().current('documents.search')
                                        ? 'border-primary-500 text-white'
                                        : 'border-transparent text-slate-400 hover:text-white',
                                ]"
                            >
                                Documents
                            </Link>

                            <Link
                                :href="route('question.answer.page')"
                                :class="[
                                    'flex h-full items-center border-b-2 px-3 py-2 text-sm font-medium transition-colors duration-150',
                                    route().current('question.answer.page')
                                        ? 'border-primary-500 text-white'
                                        : 'border-transparent text-slate-400 hover:text-white',
                                ]"
                            >
                                AI Chat
                            </Link>
                        </div>
                    </div>

                    <div class="hidden items-center sm:flex">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                >
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white"
                                    >
                                        {{
                                            $page.props.auth.user.name
                                                .charAt(0)
                                                .toUpperCase()
                                        }}
                                    </span>

                                    <span class="max-w-32 truncate text-sm">
                                        {{ $page.props.auth.user.name }}
                                    </span>

                                    <svg
                                        class="h-4 w-4 text-slate-400"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    Profile
                                </DropdownLink>

                                <DropdownLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                >
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-2 text-slate-300 transition hover:bg-slate-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 sm:hidden"
                        :aria-expanded="showingNavigationDropdown"
                        aria-label="Toggle navigation"
                        @click="
                            showingNavigationDropdown =
                                !showingNavigationDropdown
                        "
                    >
                        <svg
                            v-if="!showingNavigationDropdown"
                            class="h-5 w-5"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>

                        <svg
                            v-else
                            class="h-5 w-5"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                v-if="showingNavigationDropdown"
                class="border-t border-slate-800 bg-navy-900 px-4 pb-4 pt-3 sm:hidden"
            >
                <div class="space-y-1">
                    <Link
                        :href="route('dashboard')"
                        class="block rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            route().current('dashboard')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        "
                        @click="showingNavigationDropdown = false"
                    >
                        Dashboard
                    </Link>

                    <Link
                        :href="route('documents.index')"
                        class="block rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            route().current('documents.index') || route().current('documents.show') || route().current('documents.search')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        "
                        @click="showingNavigationDropdown = false"
                    >
                        Documents
                    </Link>

                    <Link
                        :href="route('question.answer.page')"
                        class="block rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            route().current('question.answer.page')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        "
                        @click="showingNavigationDropdown = false"
                    >
                        AI Chat
                    </Link>
                </div>

                <div class="mt-4 border-t border-slate-800 pt-4">
                    <div class="px-3">
                        <p class="truncate text-sm font-semibold text-white">
                            {{ $page.props.auth.user.name }}
                        </p>
                        <p class="mt-1 truncate text-xs text-slate-400">
                            {{ $page.props.auth.user.email }}
                        </p>
                    </div>

                    <div class="mt-3 space-y-1">
                        <Link
                            :href="route('profile.edit')"
                            class="block rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white"
                        >
                            Profile
                        </Link>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <header
            v-if="$slots.header"
            class="border-b border-slate-200 bg-white"
        >
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
