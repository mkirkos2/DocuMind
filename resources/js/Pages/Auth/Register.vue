<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="w-full max-w-md space-y-6">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Create your account
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Get started with DocuMind today
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="name" value="Full name" class="text-slate-700" />

                    <div class="mt-2">
                        <TextInput
                            id="name"
                            type="text"
                            class="block w-full rounded-lg border-slate-300 text-slate-900 placeholder-slate-500 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="John Doe"
                        />
                    </div>

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Email address" class="text-slate-700" />

                    <div class="mt-2">
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full rounded-lg border-slate-300 text-slate-900 placeholder-slate-500 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="you@example.com"
                        />
                    </div>

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <InputLabel for="password" value="Password" class="text-slate-700" />
                    </div>

                    <div class="mt-2">
                        <TextInput
                            id="password"
                            type="password"
                            class="block w-full rounded-lg border-slate-300 text-slate-900 placeholder-slate-500 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                    </div>

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirm password" class="text-slate-700" />

                    <div class="mt-2">
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="block w-full rounded-lg border-slate-300 text-slate-900 placeholder-slate-500 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                    </div>

                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>

                <div>
                    <PrimaryButton
                        class="w-full justify-center rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm"
                        :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Creating account...</span>
                        <span v-else>Create account</span>
                    </PrimaryButton>
                </div>
            </form>
            
            <div class="text-center text-sm text-slate-600">
                Already have an account?
                <Link
                    :href="route('login')"
                    class="font-medium text-primary-600 hover:text-primary-700"
                >
                    Sign in
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
