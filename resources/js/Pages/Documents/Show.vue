<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Document {
    id: number;
    title: string;
    original_filename: string;
    file_size: number;
    mime_type: string;
    processing_status: string;
    chunk_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    document: Document;
}

const props = defineProps<Props>();

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (dateString: string): string => {
    const options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const getStatusClass = (status: string): string => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
        case 'processing':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100';
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
        case 'failed':
            return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100';
    }
};
</script>

<template>
    <Head title="Document Details" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Document Details
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6">
                            <Link
                                :href="route('documents.index')"
                                class="inline-flex items-center text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to Documents
                            </Link>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Document Information</h3>

                                <dl class="grid grid-cols-1 gap-4">
                                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ props.document.title }}</dd>
                                    </div>

                                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Original Filename</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ props.document.original_filename }}</dd>
                                    </div>

                                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">File Size</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatFileSize(props.document.file_size) }}</dd>
                                    </div>

                                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ props.document.mime_type }}</dd>
                                    </div>

                                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                        <dd class="mt-1">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="getStatusClass(props.document.processing_status)"
                                            >
                                                {{ props.document.processing_status.charAt(0).toUpperCase() + props.document.processing_status.slice(1) }}
                                            </span>
                                        </dd>
                                    </div>

                                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Chunks Processed</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ props.document.chunk_count }}</dd>
                                    </div>

                                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Uploaded</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(props.document.created_at) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Actions</h3>

                                <div class="flex flex-col gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Processing Status</h4>
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            <div class="flex items-center">
                                                <div class="mr-3">
                                                    <div v-if="props.document.processing_status === 'completed'" class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </div>
                                                    <div v-else-if="props.document.processing_status === 'failed'" class="w-6 h-6 rounded-full bg-red-500 flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </div>
                                                    <div v-else class="w-6 h-6 rounded-full bg-yellow-500 flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-white animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                                        {{ props.document.processing_status }}
                                                    </p>
                                                    <p v-if="props.document.processing_status === 'pending'" class="text-xs text-gray-500 dark:text-gray-400">
                                                        Waiting to be processed
                                                    </p>
                                                    <p v-else-if="props.document.processing_status === 'processing'" class="text-xs text-gray-500 dark:text-gray-400">
                                                        Processing document content
                                                    </p>
                                                    <p v-else-if="props.document.processing_status === 'completed'" class="text-xs text-gray-500 dark:text-gray-400">
                                                        Ready for querying
                                                    </p>
                                                    <p v-else-if="props.document.processing_status === 'failed'" class="text-xs text-gray-500 dark:text-gray-400">
                                                        Processing failed
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Delete Document</h4>
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                                                Permanently remove this document and all associated data.
                                            </p>
                                            <button
                                                @click="$inertia.delete(route('documents.destroy', props.document.id))"
                                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out"
                                                onclick="return confirm('Are you sure you want to delete this document? This action cannot be undone.')"
                                            >
                                                Delete Document
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>