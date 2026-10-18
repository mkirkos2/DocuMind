<script setup lang="ts">
import { computed, ref, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface SourceReference {
    document_id: number;
    document_title: string;
    chunk_id: number;
    chunk_order: number;
    page_number: number | null;
}

interface AnswerData {
    answer: string;
    sources: SourceReference[];
}

interface ChatMessage {
    role: 'user' | 'assistant';
    content: string;
    sources?: SourceReference[];
}

interface ApiSuccess {
    success: true;
    data: AnswerData;
}

interface ApiError {
    error?: string;
    message?: string;
}

const question = ref('');
const messages = ref<ChatMessage[]>([]);
const loading = ref(false);
const errorMessage = ref('');
const chatContainer = ref<HTMLElement | null>(null);

const canSubmit = computed(
    () => question.value.trim().length > 0
        && question.value.length <= 2000
        && !loading.value,
);

async function scrollToBottom() {
    await nextTick();

    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
}

async function submitQuestion() {
    const text = question.value.trim();

    if (!text || text.length > 2000 || loading.value) {
        return;
    }

    errorMessage.value = '';
    messages.value.push({ role: 'user', content: text });
    question.value = '';
    loading.value = true;

    await scrollToBottom();

    try {
        const response = await axios.post<ApiSuccess>(
            '/question-answer',
            { question: text },
            { headers: { Accept: 'application/json' } },
        );

        messages.value.push({
            role: 'assistant',
            content: response.data.data.answer,
            sources: response.data.data.sources ?? [],
        });
    } catch (error: unknown) {
        const apiError = error as {
            response?: { data?: ApiError };
        };

        errorMessage.value =
            apiError.response?.data?.message
            ?? 'Δεν ήταν δυνατή η λήψη απάντησης. Δοκίμασε ξανά.';

        messages.value.push({
            role: 'assistant',
            content: 'Δεν μπόρεσα να απαντήσω σε αυτή την ερώτηση.',
            sources: [],
        });
    } finally {
        loading.value = false;
        await scrollToBottom();
    }
}
</script>

<template>
    <Head title="AI Chat" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                AI Chat
            </h2>
        </template>

        <div class="mx-auto flex max-w-5xl flex-col px-4 py-6 sm:px-6 lg:px-8">
            <div
                ref="chatContainer"
                class="mb-4 flex h-[55vh] min-h-80 flex-col gap-4 overflow-y-auto rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
                aria-live="polite"
            >
                <div v-if="messages.length === 0" class="m-auto max-w-md text-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Ρώτησε τα έγγραφά σου
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Κάνε μια ερώτηση σχετικά με τα έγγραφα που έχεις ανεβάσει και επεξεργαστεί.
                    </p>
                </div>

                <div
                    v-for="(message, index) in messages"
                    :key="index"
                    class="max-w-[90%] whitespace-pre-wrap rounded-lg p-3 sm:max-w-[80%]"
                    :class="message.role === 'user'
                        ? 'self-end bg-indigo-600 text-white'
                        : 'self-start bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-gray-100'"
                >
                    <p class="mb-1 text-xs font-semibold opacity-75">
                        {{ message.role === 'user' ? 'Εσύ' : 'DocuMind AI' }}
                    </p>

                    <p>{{ message.content }}</p>

                    <div
                        v-if="message.role === 'assistant' && message.sources?.length"
                        class="mt-3 border-t border-gray-300 pt-2 dark:border-gray-600"
                    >
                        <p class="mb-2 text-xs font-semibold">Πηγές</p>

                        <ul class="space-y-2">
                            <li
                                v-for="(source, sourceIndex) in message.sources"
                                :key="`${source.document_id}-${source.chunk_id}-${sourceIndex}`"
                                class="text-xs"
                            >
                                <p class="font-medium">{{ source.document_title }}</p>
                                <p class="opacity-75">
                                    Chunk #{{ source.chunk_id }}
                                    <span v-if="source.page_number">
                                        · Σελίδα {{ source.page_number }}
                                    </span>
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>

                <p v-if="loading" class="self-start text-sm text-gray-500 dark:text-gray-400">
                    Η AI επεξεργάζεται την ερώτησή σου…
                </p>
            </div>

            <form class="space-y-2" @submit.prevent="submitQuestion">
                <label for="question" class="sr-only">Η ερώτησή σου</label>

                <textarea
                    id="question"
                    v-model="question"
                    rows="3"
                    maxlength="2000"
                    placeholder="Γράψε την ερώτησή σου..."
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    :disabled="loading"
                    @keydown.enter.exact.prevent="submitQuestion"
                />

                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ question.length }}/2000 χαρακτήρες
                    </p>

                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ loading ? 'Αναμονή...' : 'Αποστολή' }}
                    </button>
                </div>

                <p
                    v-if="errorMessage"
                    role="alert"
                    class="text-sm text-red-600 dark:text-red-400"
                >
                    {{ errorMessage }}
                </p>
            </form>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Τα μηνύματα διατηρούνται προσωρινά μόνο σε αυτή τη σελίδα.
                Οι προηγούμενες ερωτήσεις δεν χρησιμοποιούνται ως πλαίσιο από την AI.
            </p>
        </div>
    </AuthenticatedLayout>
</template>