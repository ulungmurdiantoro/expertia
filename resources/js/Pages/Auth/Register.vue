<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    registrationType: {
        type: String,
        default: 'user',
    },
    submitRoute: {
        type: String,
        default: 'register.store',
    },
    title: {
        type: String,
        default: 'Daftar Akun User',
    },
    subtitle: {
        type: String,
        default: 'Mulai sebagai user. Anda bisa berlangganan konten premium dan ajukan upgrade menjadi author dari akun Anda.',
    },
    expertiseOptions: {
        type: Array,
        default: () => [],
    },
    switchLabel: {
        type: String,
        default: 'Daftar langsung sebagai Author',
    },
    switchRoute: {
        type: String,
        default: 'register.author',
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    gender: '',
    whatsapp_number: '',
    institution: '',
    institution_unit: '',
    linkedin_url: '',
    expertise_topics: [],
    expertise_other: '',
});

const isAuthor = computed(() => props.registrationType === 'author');
const authorStep = ref(1);
const authorSteps = [
    { id: 1, label: 'Akun' },
    { id: 2, label: 'Profil' },
    { id: 3, label: 'Kepakaran' },
];
const localErrors = ref({});

const setLocalError = (field, message) => {
    localErrors.value[field] = message;
};

const clearLocalErrors = () => {
    localErrors.value = {};
};

const validateAuthorStep = (step) => {
    clearLocalErrors();

    if (step === 1) {
        if (!form.name) setLocalError('name', 'Nama wajib diisi.');
        if (!form.email) setLocalError('email', 'Email wajib diisi.');
        if (!form.password) setLocalError('password', 'Password wajib diisi.');
        if (!form.password_confirmation) setLocalError('password_confirmation', 'Konfirmasi password wajib diisi.');
    }

    if (step === 2) {
        if (!form.gender) setLocalError('gender', 'Gender wajib dipilih.');
        if (!form.whatsapp_number) setLocalError('whatsapp_number', 'Nomor Whatsapp wajib diisi.');
        if (!form.institution) setLocalError('institution', 'Asal instansi wajib diisi.');
        if (!form.institution_unit) setLocalError('institution_unit', 'Unit kerja wajib diisi.');
    }

    if (step === 3) {
        if (form.expertise_topics.length === 0 && !form.expertise_other) {
            setLocalError('expertise_topics', 'Pilih minimal satu kepakaran atau isi Other.');
        }
    }

    return Object.keys(localErrors.value).length === 0;
};

const nextAuthorStep = () => {
    if (!validateAuthorStep(authorStep.value)) {
        return;
    }

    authorStep.value = Math.min(3, authorStep.value + 1);
};

const prevAuthorStep = () => {
    clearLocalErrors();
    authorStep.value = Math.max(1, authorStep.value - 1);
};

const submit = () => {
    if (isAuthor.value && !validateAuthorStep(authorStep.value)) {
        return;
    }

    form.post(route(props.submitRoute), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout variant="wide">
        <Head :title="title" />

        <section v-if="!isAuthor" class="mx-auto w-full max-w-screen-2xl rounded-xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <h1 class="font-serif text-3xl font-bold text-zinc-900 sm:text-4xl">{{ title }}</h1>
                <Link :href="route('login')" class="text-sm text-[#0f5f74] underline hover:text-[#1BD6FF]">
                    Sudah punya akun?
                </Link>
            </div>

            <div v-if="subtitle" class="mt-2 text-sm text-zinc-600">{{ subtitle }}</div>

            <div class="mt-8 grid gap-8 lg:grid-cols-[1.15fr_40px_0.85fr] lg:items-start">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="name" value="Nama lengkap *" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Nama depan dan belakang"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Surel *" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
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
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Konfirmasi Kata sandi *" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-1">
                        <Link :href="route(switchRoute)" class="text-sm text-zinc-600 underline hover:text-zinc-900">
                            {{ switchLabel }}
                        </Link>
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Daftar
                        </PrimaryButton>
                    </div>
                </form>

                <div class="hidden h-full w-px bg-zinc-200 lg:block"></div>

                <div class="space-y-4">
                    <button type="button" class="flex w-full items-center gap-3 rounded-md border border-zinc-300 px-4 py-3 text-left font-semibold text-zinc-800">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-white text-[#db4437]">
                            <i class="fa-brands fa-google"></i>
                        </span>
                        Mendaftar dengan Google
                    </button>
                    <button type="button" class="flex w-full items-center gap-3 rounded-md border border-zinc-300 px-4 py-3 text-left font-semibold text-zinc-800">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-[#1877f2] text-white">
                            <i class="fa-brands fa-facebook-f"></i>
                        </span>
                        Mendaftar dengan Facebook
                    </button>
                    <button type="button" class="flex w-full items-center gap-3 rounded-md border border-zinc-300 px-4 py-3 text-left font-semibold text-zinc-800">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-[#0a66c2] text-white">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </span>
                        Mendaftar dengan LinkedIn
                    </button>
                    <button type="button" class="flex w-full items-center gap-3 rounded-md border border-zinc-300 px-4 py-3 text-left font-semibold text-zinc-800">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-black text-white">
                            <i class="fa-brands fa-x-twitter"></i>
                        </span>
                        Mendaftar dengan X
                    </button>
                    <p class="pt-2 text-sm text-zinc-500">
                        Kami memerlukan alamat surel untuk masuk melalui media sosial.
                    </p>
                </div>
            </div>
        </section>

        <section v-else class="mx-auto w-full max-w-5xl rounded-xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#0f5f74]">Registrasi Author</p>
                    <h1 class="mt-2 font-serif text-2xl font-bold leading-tight text-zinc-900 sm:text-3xl">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-2 max-w-3xl text-sm leading-relaxed text-zinc-600">{{ subtitle }}</p>
                </div>
                <Link :href="route('login')" class="text-sm text-[#0f5f74] underline hover:text-[#1BD6FF]">
                    Sudah punya akun?
                </Link>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-2">
                <div
                    v-for="step in authorSteps"
                    :key="step.id"
                    class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.08em]"
                    :class="step.id === authorStep ? 'border-[#1BD6FF] bg-[#1BD6FF]/15 text-[#0f5f74]' : 'border-zinc-300 bg-white text-zinc-500'"
                >
                    <span>{{ step.id }}</span>
                    <span>{{ step.label }}</span>
                </div>
            </div>

            <form @submit.prevent="submit" class="mt-6">
                <div v-if="authorStep === 1" class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="name" value="Nama" />
                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus autocomplete="name" />
                        <InputError class="mt-2" :message="form.errors.name || localErrors.name" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="email" value="Email Aktif" />
                        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="username" />
                        <InputError class="mt-2" :message="form.errors.email || localErrors.email" />
                    </div>
                    <div>
                        <InputLabel for="password" value="Password" />
                        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required autocomplete="new-password" />
                        <InputError class="mt-2" :message="form.errors.password || localErrors.password" />
                    </div>
                    <div>
                        <InputLabel for="password_confirmation" value="Konfirmasi Password" />
                        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" required autocomplete="new-password" />
                        <InputError class="mt-2" :message="form.errors.password_confirmation || localErrors.password_confirmation" />
                    </div>
                </div>

                <div v-if="authorStep === 2" class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="gender" value="Gender (Pria/Wanita)" />
                        <select
                            id="gender"
                            v-model="form.gender"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option disabled value="">Pilih gender</option>
                            <option value="Pria">Pria</option>
                            <option value="Wanita">Wanita</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.gender || localErrors.gender" />
                    </div>
                    <div>
                        <InputLabel for="whatsapp_number" value="Nomor yang bisa dihubungi (Whatsapp)" />
                        <TextInput id="whatsapp_number" type="text" class="mt-1 block w-full" v-model="form.whatsapp_number" required placeholder="08xxxxxxxxxx" />
                        <InputError class="mt-2" :message="form.errors.whatsapp_number || localErrors.whatsapp_number" />
                    </div>
                    <div>
                        <InputLabel for="institution" value="Asal Instansi" />
                        <TextInput id="institution" type="text" class="mt-1 block w-full" v-model="form.institution" required />
                        <InputError class="mt-2" :message="form.errors.institution || localErrors.institution" />
                    </div>
                    <div>
                        <InputLabel for="institution_unit" value="Unit kerja Anda di Instansi" />
                        <TextInput id="institution_unit" type="text" class="mt-1 block w-full" v-model="form.institution_unit" required />
                        <InputError class="mt-2" :message="form.errors.institution_unit || localErrors.institution_unit" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="linkedin_url" value="LinkedIn (opsional)" />
                        <TextInput id="linkedin_url" type="url" class="mt-1 block w-full" v-model="form.linkedin_url" placeholder="https://www.linkedin.com/in/username" />
                        <InputError class="mt-2" :message="form.errors.linkedin_url" />
                    </div>
                </div>

                <div v-if="authorStep === 3" class="grid gap-5">
                    <div>
                        <InputLabel value="Kepakaran Anda *" />
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="option in expertiseOptions"
                                :key="option"
                                class="inline-flex items-center gap-2 text-sm text-gray-700"
                            >
                                <input
                                    v-model="form.expertise_topics"
                                    type="checkbox"
                                    :value="option"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                <span>{{ option }}</span>
                            </label>
                        </div>

                        <div class="mt-3">
                            <InputLabel for="expertise_other" value="Other:" />
                            <TextInput id="expertise_other" type="text" class="mt-1 block w-full" v-model="form.expertise_other" placeholder="Tulis kepakaran lain" />
                        </div>

                        <InputError class="mt-2" :message="form.errors.expertise_topics || form.errors.expertise_other || localErrors.expertise_topics" />
                    </div>
                </div>

                <div class="mt-7 flex items-center justify-between gap-3 border-t border-zinc-200 pt-5">
                    <div class="flex items-center gap-3">
                        <Link :href="route(switchRoute)" class="text-sm text-zinc-600 underline hover:text-zinc-900">
                            {{ switchLabel }}
                        </Link>
                        <button
                            v-if="authorStep > 1"
                            type="button"
                            class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50"
                            @click="prevAuthorStep"
                        >
                            Kembali
                        </button>
                    </div>

                    <div>
                        <button
                            v-if="authorStep < 3"
                            type="button"
                            class="rounded-md border border-[#1BD6FF] bg-[#1BD6FF] px-4 py-2 text-sm font-semibold text-[#0f5f74] hover:bg-[#16c4ea]"
                            @click="nextAuthorStep"
                        >
                            Lanjut
                        </button>
                        <PrimaryButton
                            v-else
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Daftar Author
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </section>
    </GuestLayout>
</template>
