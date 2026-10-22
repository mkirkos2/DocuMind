<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="w-full max-w-md space-y-6">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Sign in to your account
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Enter your credentials to access your dashboard
                </p>
            </div>

            <div v-if="status" class="rounded-lg bg-emerald-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-emerald-800">
                            {{ status }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="email" value="Email address" class="text-slate-700" />

                    <div class="mt-2">
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full rounded-lg border-slate-300 text-slate-900 placeholder-slate-500 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="you@example.com"
                        />
                    </div>

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <InputLabel for="password" value="Password" class="text-slate-700" />
                        
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-primary-600 hover:text-primary-700"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <div class="mt-2">
                        <TextInput
                            id="password"
                            type="password"
                            class="block w-full rounded-lg border-slate-300 text-slate-900 placeholder-slate-500 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                    </div>

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-slate-600">Remember me</span>
                </div>

                <div>
                    <PrimaryButton
                        class="w-full justify-center rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm"
                        :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Signing in...</span>
                        <span v-else>Sign in</span>
                    </PrimaryButton>
                </div>
            </form>
            
            <div class="text-center text-sm text-slate-600">
                Don't have an account?
                <Link
                    :href="route('register')"
                    class="font-medium text-primary-600 hover:text-primary-700"
                >
                    Sign up
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
