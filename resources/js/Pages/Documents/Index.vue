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
    created_at: string;
    updated_at: string;
}

interface Props {
    documents: {
        data: Document[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number | null;
        to: number | null;
        total: number;
    };
}

const props = defineProps<Props>();

const uploadForm = useForm({
    file: null as File | null,
});

const confirmingDeletion = ref(false);
const documentToDelete = ref<Document | null>(null);
const deletionForm = useForm({});

const uploadProgress = ref(0);
const isUploading = ref(false);

const submit = () => {
    isUploading.value = true;
    uploadProgress.value = 0;

    uploadForm.post(route('documents.store'), {
        forceFormData: true,
        onSuccess: () => {
            uploadForm.reset();
            isUploading.value = false;
            uploadProgress.value = 0;
        },
        onError: () => {
            isUploading.value = false;
            uploadProgress.value = 0;
        },
        onProgress: (progress) => {
            if (progress?.percentage) {
                uploadProgress.value = progress.percentage;
            }
        }
    });
};

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

const confirmDeletion = (document: Document) => {
    documentToDelete.value = document;
    confirmingDeletion.value = true;
};

const deleteDocument = () => {
    if (!documentToDelete.value) return;
    
    deletionForm.delete(route('documents.destroy', documentToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
            documentToDelete.value = null;
        },
        onError: () => {
            confirmingDeletion.value = false;
        },
        onFinish: () => {
            confirmingDeletion.value = false;
        }
    });
};

const handleFileInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        uploadForm.file = target.files[0];
    }
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
</script>

<template>
    <Head title="Documents" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-primary-600">DOCUMENT LIBRARY</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Documents
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Upload, manage, and search your documents in one place.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Upload Form -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 sm:p-8">
                        <h3 class="text-lg font-semibold text-slate-900">Upload Document</h3>

                        <form @submit.prevent="submit" class="mt-6">
                            <div class="flex flex-col gap-4 md:flex-row md:items-end">
                                <div class="flex-1">
                                    <input
                                        type="file"
                                        @input="handleFileInput"
                                        accept=".pdf,.txt"
                                        class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 file:mr-4 file:rounded-md file:border-0 file:bg-slate-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-100 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        :disabled="isUploading"
                                    />
                                    <p class="mt-2 text-sm text-slate-600">
                                        PDF and TXT files only. Max 10 MB.
                                    </p>

                                    <div v-if="uploadForm.errors.file" class="mt-2 text-sm text-red-600">
                                        {{ uploadForm.errors.file }}
                                    </div>
                                </div>

                                <div class="md:w-32">
                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-not-allowed disabled:opacity-75"
                                        :disabled="isUploading || !uploadForm.file"
                                    >
                                        <span v-if="!isUploading">Upload</span>
                                        <span v-else>Uploading...</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Progress bar -->
                            <div v-if="isUploading" class="mt-6">
                                <div class="flex items-center justify-between text-sm text-slate-600">
                                    <span>Processing...</span>
                                    <span>{{ uploadProgress }}%</span>
                                </div>
                                <div class="mt-2 h-2 w-full rounded-full bg-slate-200">
                                    <div
                                        class="h-2 rounded-full bg-primary-600 transition-all duration-300"
                                        :style="{ width: uploadProgress + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Documents List -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 sm:p-8">
                        <div v-if="props.documents.data.length > 0">
                            <h3 class="text-lg font-semibold text-slate-900">Your Documents</h3>

                            <div class="mt-6 overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead>
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600">
                                                    Document
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600">
                                                    Size
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600">
                                                    Status
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600">
                                                    Uploaded
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-600">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 bg-white">
                                            <tr v-for="document in props.documents.data" :key="document.id" class="hover:bg-slate-50">
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-slate-900">
                                                        {{ document.title }}
                                                    </div>
                                                    <div class="text-sm text-slate-600">
                                                        {{ document.original_filename }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                                    {{ formatFileSize(document.file_size) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                        :class="getStatusClass(document.processing_status)"
                                                    >
                                                        {{ document.processing_status.charAt(0).toUpperCase() + document.processing_status.slice(1) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                                    {{ formatDate(document.created_at) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <Link
                                                        :href="route('documents.show', document.id)"
                                                        class="text-primary-600 hover:text-primary-700 mr-4"
                                                    >
                                                        View
                                                    </Link>
                                                    <button
                                                        @click="confirmDeletion(document)"
                                                        class="text-rose-600 hover:text-rose-700"
                                                    >
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-6 flex items-center justify-between">
                                <div class="flex-1 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-slate-700">
                                            Showing
                                            <span class="font-medium">{{ props.documents.from ?? 0 }}</span>
                                            to
                                            <span class="font-medium">{{ props.documents.to ?? 0 }}</span>
                                            of
                                            <span class="font-medium">{{ props.documents.total }}</span>
                                            results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex -space-x-px rounded-lg shadow-sm" aria-label="Pagination">
                                            <template v-for="(link, index) in props.documents.links" :key="index">
                                                <span
                                                    v-if="link.url === null"
                                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-300 cursor-not-allowed rounded-lg"
                                                    v-html="link.label"
                                                ></span>
                                                <a
                                                    v-else-if="link.active"
                                                    :href="link.url || '#'"
                                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 border border-primary-300 hover:bg-primary-100 rounded-lg"
                                                    v-html="link.label"
                                                ></a>
                                                <a
                                                    v-else
                                                    :href="link.url || '#'"
                                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg"
                                                    v-html="link.label"
                                                ></a>
                                            </template>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="rounded-xl border border-dashed border-slate-300 p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-slate-900">No documents</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Get started by uploading your first document.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="confirmingDeletion" @close="confirmingDeletion = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Delete document?</h2>
                
                <p v-if="documentToDelete" class="mt-2 text-sm text-slate-600">
                    Are you sure you want to delete the document "{{ documentToDelete.title }}"? This action cannot be undone.
                </p>
                
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50"
                        @click="confirmingDeletion = false"
                    >
                        Cancel
                    </button>
                    
                    <button
                        type="button"
                        class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600"
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