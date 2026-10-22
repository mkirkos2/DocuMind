<script setup lang="ts">
import DocuMindLogo from '@/Components/DocuMindLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-background-light pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link 
                href="/" 
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-600"
            >
                <DocuMindLogo class="h-6 w-6 text-white" />
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-8 shadow-sm sm:max-w-md sm:rounded-xl sm:border sm:border-slate-200">
            <slot />
        </div>
    </div>
</template>
