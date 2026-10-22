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
    <Head title="AI Assistant" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-primary-600">AI ASSISTANT</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Ρώτησε τα έγγραφά σου
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Κάνε μια ερώτηση σχετικά με τα έγγραφα που έχεις ανεβάσει και επεξεργαστεί.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div
                        ref="chatContainer"
                        class="flex h-[60vh] min-h-96 flex-col gap-5 overflow-y-auto p-5"
                        aria-live="polite"
                    >
                        <div v-if="messages.length === 0" class="m-auto max-w-md text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary-50">
                                <svg viewBox="0 0 24 24" class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M8.5 9a3.5 3.5 0 0 1 7 0c0 2.5-3.5 2.5-3.5 5" stroke-linecap="round" />
                                    <circle cx="12" cy="18" r=".8" fill="currentColor" />
                                    <circle cx="12" cy="12" r="9" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-slate-900">
                                Ρώτησε τα έγγραφά σου
                            </h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Κάνε μια ερώτηση σχετικά με τα έγγραφα που έχεις ανεβάσει και επεξεργαστεί.
                            </p>
                        </div>

                        <div
                            v-for="(message, index) in messages"
                            :key="index"
                            class="max-w-[90%] whitespace-pre-wrap sm:max-w-[85%]"
                            :class="message.role === 'user'
                                ? 'self-end'
                                : 'self-start'"
                        >
                            <div
                                class="rounded-xl px-4 py-3"
                                :class="message.role === 'user'
                                    ? 'bg-primary-600 text-white'
                                    : 'bg-slate-100 text-slate-900'"
                            >
                                <p class="mb-1 text-xs font-semibold opacity-80">
                                    {{ message.role === 'user' ? 'Εσύ' : 'DocuMind AI' }}
                                </p>

                                <p class="leading-relaxed">{{ message.content }}</p>
                            </div>

                            <div
                                v-if="message.role === 'assistant' && message.sources?.length"
                                class="mt-3 rounded-lg border border-slate-200 bg-white p-3"
                            >
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-600">Πηγές</p>

                                <ul class="space-y-2">
                                    <li
                                        v-for="(source, sourceIndex) in message.sources"
                                        :key="`${source.document_id}-${source.chunk_id}-${sourceIndex}`"
                                        class="text-xs"
                                    >
                                        <p class="font-medium text-slate-900">{{ source.document_title }}</p>
                                        <p class="text-slate-600">
                                            Τμήμα #{{ source.chunk_order + 1 }}
                                            <span v-if="source.page_number">
                                                · Σελίδα {{ source.page_number }}
                                            </span>
                                        </p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div v-if="loading" class="self-start">
                            <div class="flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-3">
                                <div class="flex gap-1">
                                    <div class="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.3s]"></div>
                                    <div class="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.15s]"></div>
                                    <div class="h-2 w-2 animate-bounce rounded-full bg-slate-400"></div>
                                </div>
                                <span class="text-sm text-slate-600">Η AI επεξεργάζεται την ερώτησή σου…</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form class="space-y-3" @submit.prevent="submitQuestion">
                    <div class="relative overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm transition focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500">
                        <label for="question" class="sr-only">Η ερώτησή σου</label>
                        <textarea
                            id="question"
                            v-model="question"
                            rows="3"
                            maxlength="2000"
                            placeholder="Γράψε την ερώτησή σου..."
                            class="block w-full resize-none border-0 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-500 focus:ring-0"
                            :disabled="loading"
                            @keydown.enter.exact.prevent="submitQuestion"
                        />
                        
                        <div class="absolute inset-x-0 bottom-0 flex items-center justify-between border-t border-slate-200 bg-white/80 px-4 py-3 backdrop-blur-sm">
                            <p class="text-xs text-slate-500">
                                {{ question.length }}/2000 χαρακτήρες
                            </p>
                            
                            <button
                                type="submit"
                                :disabled="!canSubmit"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span v-if="!loading">Αποστολή</span>
                                <span v-else>Αναμονή...</span>
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="errorMessage"
                        role="alert"
                        class="text-sm text-red-600"
                    >
                        {{ errorMessage }}
                    </p>
                    
                    <p class="text-xs text-slate-500">
                        Τα μηνύματα διατηρούνται προσωρινά μόνο σε αυτή τη σελίδα.
                        Οι προηγούμενες ερωτήσεις δεν χρησιμοποιούνται ως πλαίσιο από την AI.
                    </p>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>