<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout variant="wide">
        <Head title="Log in" />

        <section class="mx-auto w-full max-w-screen-2xl rounded-xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <h1 class="font-serif text-3xl font-bold text-zinc-900 sm:text-4xl">Masuk ke Expertia</h1>
                <Link :href="route('register')" class="text-sm text-[#0f5f74] underline hover:text-[#1BD6FF]">
                    Belum punya akun?
                </Link>
            </div>

            <div class="mt-8 grid gap-8 lg:grid-cols-[1.15fr_40px_0.85fr] lg:items-start">
                <div>
                    <div v-if="status" class="mb-4 rounded-md bg-green-50 px-3 py-2 text-sm font-medium text-green-700">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <InputLabel for="email" value="Surel *" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="your.email@address.com"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Kata sandi *" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <label class="flex items-center">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                <span class="ms-2 text-sm text-zinc-600">Remember me</span>
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm text-zinc-600 underline hover:text-zinc-900"
                            >
                                Forgot your password?
                            </Link>
                        </div>

                        <div class="pt-1">
                            <PrimaryButton
                                class="w-full justify-center"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Log in
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="hidden h-full w-px bg-zinc-200 lg:block"></div>

                <div class="space-y-4">
                    <button type="button" class="flex w-full items-center gap-3 rounded-md border border-zinc-300 px-4 py-3 text-left font-semibold text-zinc-800">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-white text-[#db4437]">
                            <i class="fa-brands fa-google"></i>
                        </span>
                        Masuk dengan Google
                    </button>
                    <button type="button" class="flex w-full items-center gap-3 rounded-md border border-zinc-300 px-4 py-3 text-left font-semibold text-zinc-800">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-[#1877f2] text-white">
                            <i class="fa-brands fa-facebook-f"></i>
                        </span>
                        Masuk dengan Facebook
                    </button>
                    <button type="button" class="flex w-full items-center gap-3 rounded-md border border-zinc-300 px-4 py-3 text-left font-semibold text-zinc-800">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-[#0a66c2] text-white">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </span>
                        Masuk dengan LinkedIn
                    </button>
                    <button type="button" class="flex w-full items-center gap-3 rounded-md border border-zinc-300 px-4 py-3 text-left font-semibold text-zinc-800">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-black text-white">
                            <i class="fa-brands fa-x-twitter"></i>
                        </span>
                        Masuk dengan X
                    </button>
                    <p class="pt-2 text-sm text-zinc-500">
                        Kami memerlukan alamat surel untuk masuk melalui media sosial.
                    </p>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>
