<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

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

const performSearch = () => {
    if (searchQuery.value.trim().length === 0) {
        return;
    }

    isLoading.value = true;

    router.visit(route('documents.search', { q: searchQuery.value }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
        }
    });
};

let searchTimeout: number | undefined;
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (searchQuery.value.trim().length > 0) {
        // @ts-ignore
        searchTimeout = setTimeout(() => {
            performSearch();
        }, 300);
    }
});

const highlightMatches = (text: string, query: string): string => {
    if (!query) return text;

    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800">$1</mark>');
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Search Documents
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h3 class="text-lg font-medium mb-4">Search Your Documents</h3>

                            <form @submit.prevent="performSearch">
                                <div class="flex flex-col md:flex-row gap-4">
                                    <div class="flex-1">
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Enter search terms..."
                                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg p-3 bg-white dark:text-gray-400 focus:outline-none dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400"
                                            :disabled="isLoading"
                                        />
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                            Search through your processed documents
                                        </p>
                                    </div>

                                    <div class="md:w-32">
                                        <button
                                            type="submit"
                                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-150 ease-in-out"
                                            :disabled="isLoading || !searchQuery.trim()"
                                            :class="{ 'opacity-75 cursor-not-allowed': isLoading || !searchQuery.trim() }"
                                        >
                                            <span v-if="!isLoading">Search</span>
                                            <span v-else>Searching...</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div v-if="searchQuery">
                            <div v-if="isLoading" class="text-center py-8">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">Searching your documents...</p>
                            </div>

                            <div v-else-if="results.length > 0">
                                <h3 class="text-lg font-medium mb-4">
                                    Found {{ results.length }} result{{ results.length !== 1 ? 's' : '' }} for "{{ searchQuery }}"
                                </h3>

                                <div class="space-y-4">
                                    <div
                                        v-for="result in results"
                                        :key="result.chunk_id"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-medium text-blue-600 dark:text-blue-400">
                                                {{ result.document_title }}
                                            </h4>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Chunk #{{ result.chunk_order + 1 }}
                                                <span v-if="result.page_number"> • Page {{ result.page_number }}</span>
                                            </span>
                                        </div>

                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300 mb-3"
                                            v-html="highlightMatches(truncateText(result.chunk_text), searchQuery)"
                                        ></div>

                                        <div class="text-xs text-gray-500 dark:text-gray-400 flex justify-between">
                                            <span>{{ result.character_count }} characters</span>
                                            <span>Relevance: {{ Math.round(result.relevance_score) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No results found</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    No matches found for "{{ searchQuery }}" in your processed documents.
                                </p>
                            </div>
                        </div>

                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Search your documents</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Enter a search term above to find content in your processed documents.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>