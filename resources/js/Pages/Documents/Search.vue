<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface SearchResult {
    document_id: number;
    document_title: string;
    chunk_id: number;
    chunk_text: string;
    chunk_order: number;
    page_number: number | null;
    character_count: number;
    relevance_score: number;
}

interface Props {
    query: string;
    results: SearchResult[];
}

const props = defineProps<Props>();

const searchQuery = ref(props.query);
const isLoading = ref(false);
const canSubmit = ref(true);

const performSearch = () => {
    if (searchQuery.value.trim().length === 0 || !canSubmit.value) {
        return;
    }

    canSubmit.value = false;
    isLoading.value = true;

    router.visit(route('documents.search', { q: searchQuery.value }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isLoading.value = false;
            canSubmit.value = true;
        },
        onError: () => {
            isLoading.value = false;
            canSubmit.value = true;
        }
    });
};

const highlightMatches = (text: string, query: string): string => {
    if (!query) return text;

    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark class="bg-amber-200">$1</mark>');
};

const truncateText = (text: string, maxLength: number = 200): string => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};
</script>

<template>
    <Head title="Search Documents" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">
                Search Documents
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl">
                    <div class="p-6">
                        <div class="mb-8 rounded-xl border border-slate-200 bg-slate-50 p-6">
                            <h3 class="text-lg font-medium text-slate-900">Search Your Documents</h3>

                            <form @submit.prevent="performSearch" class="mt-4">
                                <div class="flex flex-col gap-4 md:flex-row">
                                    <div class="flex-1">
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Enter search terms..."
                                            class="block w-full rounded-lg border border-slate-300 px-4 py-3 text-sm text-slate-900 placeholder-slate-500 focus:border-primary-500 focus:ring-primary-500"
                                            :disabled="isLoading"
                                        />
                                        <p class="mt-2 text-sm text-slate-600">
                                            Search through your processed documents
                                        </p>
                                    </div>

                                    <div class="md:w-32">
                                        <button
                                            type="submit"
                                            class="w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-not-allowed disabled:opacity-75"
                                            :disabled="isLoading || !searchQuery.trim()"
                                        >
                                            <span v-if="!isLoading">Search</span>
                                            <span v-else>Searching...</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div v-if="searchQuery">
                            <div v-if="isLoading" class="text-center py-12">
                                <div class="inline-flex items-center justify-center">
                                    <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary-600 border-t-transparent"></div>
                                </div>
                                <p class="mt-4 text-slate-600">Searching your documents...</p>
                            </div>

                            <div v-else-if="results.length > 0">
                                <h3 class="text-lg font-medium text-slate-900">
                                    Found {{ results.length }} result{{ results.length !== 1 ? 's' : '' }} for "{{ searchQuery }}"
                                </h3>

                                <div class="mt-5 space-y-4">
                                    <div
                                        v-for="result in results"
                                        :key="result.chunk_id"
                                        class="rounded-lg border border-slate-200 p-5 transition hover:border-primary-200 hover:shadow-sm"
                                    >
                                        <div class="flex justify-between">
                                            <h4 class="font-medium text-primary-600">
                                                {{ result.document_title }}
                                            </h4>
                                            <span class="text-xs text-slate-500">
                                                Chunk #{{ result.chunk_order + 1 }}
                                                <span v-if="result.page_number"> • Page {{ result.page_number }}</span>
                                            </span>
                                        </div>

                                        <div
                                            class="mt-3 text-sm text-slate-700"
                                            v-html="highlightMatches(truncateText(result.chunk_text), searchQuery)"
                                        ></div>

                                        <div class="mt-3 flex justify-between text-xs text-slate-500">
                                            <span>{{ result.character_count }} characters</span>
                                            <span>Relevance: {{ Math.round(result.relevance_score) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="rounded-xl border border-dashed border-slate-300 p-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-slate-900">No results found</h3>
                                <p class="mt-2 text-sm text-slate-600">
                                    No matches found for "{{ searchQuery }}" in your processed documents.
                                </p>
                            </div>
                        </div>

                        <div v-else class="rounded-xl border border-dashed border-slate-300 p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-slate-900">Search your documents</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Enter a search term above to find content in your processed documents.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>