<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
        meta: {
            from: number;
            to: number;
            total: number;
        };
    };
}

const props = defineProps<Props>();

const uploadForm = useForm({
    file: null as File | null,
});

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

const deleteDocument = (document: Document) => {
    if (confirm('Are you sure you want to delete this document?')) {
        useForm({}).delete(route('documents.destroy', document.id));
    }
};

const handleFileInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        uploadForm.file = target.files[0];
    }
};
</script>

<template>
    <Head title="Documents" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Documents
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Upload Form -->
                        <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h3 class="text-lg font-medium mb-4">Upload Document</h3>

                            <form @submit.prevent="submit">
                                <div class="flex flex-col md:flex-row gap-4">
                                    <div class="flex-1">
                                        <input
                                            type="file"
                                            @input="handleFileInput"
                                            accept=".pdf,.txt"
                                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                                            :disabled="isUploading"
                                        />
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                            PDF and TXT files only. Max 10 MB.
                                        </p>

                                        <div v-if="uploadForm.errors.file" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ uploadForm.errors.file }}
                                        </div>
                                    </div>

                                    <div class="md:w-32">
                                        <button
                                            type="submit"
                                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-150 ease-in-out"
                                            :disabled="isUploading || !uploadForm.file"
                                            :class="{ 'opacity-75 cursor-not-allowed': isUploading || !uploadForm.file }"
                                        >
                                            <span v-if="!isUploading">Upload</span>
                                            <span v-else>Uploading...</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Progress bar -->
                                <div v-if="isUploading" class="mt-4">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                        <div
                                            class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                            :style="{ width: uploadProgress + '%' }"
                                        ></div>
                                    </div>
                                    <div class="text-right text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ uploadProgress }}%
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Documents List -->
                        <div v-if="props.documents.data.length > 0">
                            <h3 class="text-lg font-medium mb-4">Your Documents</h3>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Document
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Size
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Uploaded
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="document in props.documents.data" :key="document.id">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ document.title }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ document.original_filename }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ formatFileSize(document.file_size) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                    :class="{
                                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100': document.processing_status === 'pending',
                                                        'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100': document.processing_status === 'processing',
                                                        'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': document.processing_status === 'completed',
                                                        'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100': document.processing_status === 'failed'
                                                    }"
                                                >
                                                    {{ document.processing_status.charAt(0).toUpperCase() + document.processing_status.slice(1) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ formatDate(document.created_at) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <Link
                                                    :href="route('documents.show', document.id)"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3"
                                                >
                                                    View
                                                </Link>
                                                <button
                                                    @click="deleteDocument(document)"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-6 flex items-center justify-between">
                                <div class="flex flex-1 justify-between sm:hidden">
                                    <a
                                        v-if="props.documents.links[0] && props.documents.links[0].url"
                                        :href="props.documents.links[0].url || '#'"
                                        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300"
                                    >
                                        Previous
                                    </a>
                                    <a
                                        v-if="props.documents.links[props.documents.links.length - 1] && props.documents.links[props.documents.links.length - 1].url"
                                        :href="props.documents.links[props.documents.links.length - 1].url || '#'"
                                        class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300"
                                    >
                                        Next
                                    </a>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            Showing
                                            <span class="font-medium">{{ props.documents?.meta?.from ?? 0 }}</span>
                                            to
                                            <span class="font-medium">{{ props.documents?.meta?.to ?? 0 }}</span>
                                            of
                                            <span class="font-medium">{{ props.documents?.meta?.total ?? 0 }}</span>
                                            results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <template v-for="(link, index) in props.documents.links" :key="index">
                                                <span
                                                    v-if="link.url === null"
                                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400"
                                                    v-html="link.label"
                                                ></span>
                                                <a
                                                    v-else-if="link.active"
                                                    :href="link.url || '#'"
                                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-300 hover:bg-blue-100 dark:bg-blue-900 dark:border-blue-700 dark:text-blue-100 dark:hover:bg-blue-800"
                                                    v-html="link.label"
                                                ></a>
                                                <a
                                                    v-else
                                                    :href="link.url || '#'"
                                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
                                                    v-html="link.label"
                                                ></a>
                                            </template>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No documents</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Get started by uploading your first document.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>