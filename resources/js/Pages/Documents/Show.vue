<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';

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

const confirmingDeletion = ref(false);
const deletionForm = useForm({});

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

const getStatusClass = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-amber-50 text-amber-700';
        case 'processing':
            return 'bg-blue-50 text-blue-700';
        case 'completed':
            return 'bg-emerald-50 text-emerald-700';
        case 'failed':
            return 'bg-rose-50 text-rose-700';
        default:
            return 'bg-slate-50 text-slate-700';
    }
};

const confirmDeletion = () => {
    confirmingDeletion.value = true;
};

const deleteDocument = () => {
    deletionForm.delete(route('documents.destroy', props.document.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
        },
        onError: () => {
            confirmingDeletion.value = false;
        },
        onFinish: () => {
            confirmingDeletion.value = false;
        }
    });
};
</script>

<template>
    <Head title="Document Details" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <Link
                    :href="route('documents.index')"
                    class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700"
                >
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Documents
                </Link>
                <h2 class="mt-2 text-2xl font-bold leading-tight text-slate-900">
                    {{ props.document.title }}
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl">
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                            <!-- Document Information -->
                            <div class="md:col-span-2">
                                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                                    <h3 class="text-lg font-semibold text-slate-900">Document Information</h3>
                                    
                                    <dl class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                                        <div class="rounded-lg bg-slate-50 p-4">
                                            <dt class="text-sm font-medium text-slate-600">Title</dt>
                                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ props.document.title }}</dd>
                                        </div>
                                        
                                        <div class="rounded-lg bg-slate-50 p-4">
                                            <dt class="text-sm font-medium text-slate-600">Original Filename</dt>
                                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ props.document.original_filename }}</dd>
                                        </div>
                                        
                                        <div class="rounded-lg bg-slate-50 p-4">
                                            <dt class="text-sm font-medium text-slate-600">File Size</dt>
                                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ formatFileSize(props.document.file_size) }}</dd>
                                        </div>
                                        
                                        <div class="rounded-lg bg-slate-50 p-4">
                                            <dt class="text-sm font-medium text-slate-600">Type</dt>
                                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ props.document.mime_type }}</dd>
                                        </div>
                                        
                                        <div class="rounded-lg bg-slate-50 p-4">
                                            <dt class="text-sm font-medium text-slate-600">Chunks Processed</dt>
                                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ props.document.chunk_count }}</dd>
                                        </div>
                                        
                                        <div class="rounded-lg bg-slate-50 p-4">
                                            <dt class="text-sm font-medium text-slate-600">Uploaded</dt>
                                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ formatDate(props.document.created_at) }}</dd>
                                        </div>
                                        
                                        <div class="rounded-lg bg-slate-50 p-4 sm:col-span-2">
                                            <dt class="text-sm font-medium text-slate-600">Status</dt>
                                            <dd class="mt-1">
                                                <span
                                                    class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
                                                    :class="getStatusClass(props.document.processing_status)"
                                                >
                                                    {{ props.document.processing_status.charAt(0).toUpperCase() + props.document.processing_status.slice(1) }}
                                                </span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Actions Panel -->
                            <div>
                                <div class="space-y-6">
                                    <!-- Processing Status Card -->
                                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                                        <h3 class="text-lg font-semibold text-slate-900">Processing Status</h3>
                                        
                                        <div class="mt-5">
                                            <div class="flex items-start">
                                                <div class="mr-3 flex-shrink-0">
                                                    <div v-if="props.document.processing_status === 'completed'" class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100">
                                                        <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </div>
                                                    <div v-else-if="props.document.processing_status === 'failed'" class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100">
                                                        <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </div>
                                                    <div v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100">
                                                        <svg class="h-5 w-5 text-amber-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-slate-900 capitalize">
                                                        {{ props.document.processing_status }}
                                                    </p>
                                                    <p v-if="props.document.processing_status === 'pending'" class="mt-1 text-xs text-slate-600">
                                                        Waiting to be processed
                                                    </p>
                                                    <p v-else-if="props.document.processing_status === 'processing'" class="mt-1 text-xs text-slate-600">
                                                        Processing document content
                                                    </p>
                                                    <p v-else-if="props.document.processing_status === 'completed'" class="mt-1 text-xs text-slate-600">
                                                        Ready for querying
                                                    </p>
                                                    <p v-else-if="props.document.processing_status === 'failed'" class="mt-1 text-xs text-slate-600">
                                                        Processing failed
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- View PDF Card -->
                                    <div v-if="props.document.mime_type === 'application/pdf'" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                                        <h3 class="text-lg font-semibold text-slate-900">View Document</h3>
                                        
                                        <div class="mt-5">
                                            <p class="text-sm text-slate-600">
                                                Open the original PDF file in your browser.
                                            </p>
                                            
                                            <a
                                                :href="route('documents.view-file', props.document.id)"
                                                target="_blank"
                                                class="mt-4 inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                                            >
                                                Open PDF
                                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Delete Document Card -->
                                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                                        <h3 class="text-lg font-semibold text-slate-900">Danger Zone</h3>
                                        
                                        <div class="mt-5">
                                            <p class="text-sm text-slate-600">
                                                Permanently remove this document and all associated data.
                                            </p>
                                            
                                            <button
                                                type="button"
                                                class="mt-4 inline-flex items-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600"
                                                @click="confirmDeletion"
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

        <!-- Delete Confirmation Modal -->
        <Modal :show="confirmingDeletion" @close="confirmingDeletion = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Delete document?</h2>
                
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to delete the document "{{ props.document.title }}"? This action cannot be undone.
                </p>
                
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50"
                        @click="confirmingDeletion = false"
                    >
                        Cancel
                    </button>
                    
                    <button
                        type="button"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600"
                        :class="{ 'opacity-75 cursor-not-allowed': deletionForm.processing }"
                        :disabled="deletionForm.processing"
                        @click="deleteDocument"
                    >
                        <span v-if="deletionForm.processing">Deleting...</span>
                        <span v-else>Delete document</span>
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>