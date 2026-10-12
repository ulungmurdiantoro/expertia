<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { contact } from '@/site';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    meta: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <Head :title="meta.title || 'Kontak — Expertia.id'" />

    <PublicLayout>
        <div class="page-head">
            <div class="wrap">
                <p class="eyebrow">Kontak &amp; Kolaborasi</p>
                <h1>Hubungi redaksi Expertia</h1>
                <p class="sub">Untuk pertanyaan program kontributor, koreksi artikel, kerja sama, atau undangan narasumber.</p>
            </div>
        </div>

        <div class="wrap">
            <div class="prose" style="padding-bottom: 56px">
                <div class="contact-card">
                    <div>
                        <p class="k">WhatsApp</p>
                        <p class="v">{{ contact.whatsappNumber }}</p>
                        <p class="n">
                            {{ contact.whatsappName }} ·
                            <a :href="contact.whatsappUrl" target="_blank" rel="noopener">Buka WhatsApp</a>
                        </p>
                    </div>
                    <div>
                        <p class="k">Email</p>
                        <p class="v">{{ contact.email }}</p>
                        <p class="n">Dibalas pada hari kerja</p>
                    </div>
                    <div>
                        <p class="k">Instagram</p>
                        <p class="v">{{ contact.instagramHandle }}</p>
                        <p class="n"><a :href="contact.instagramUrl" target="_blank" rel="noopener">Kunjungi profil</a></p>
                    </div>
                </div>

                <h2>Ingin menjadi kontributor?</h2>
                <p>Daftar langsung melalui formulir. Anda tidak perlu menulis artikel dari nol dan tidak perlu membuat akun.</p>
                <div class="actions">
                    <a class="btn btn-navy" :href="contact.registrationUrl" target="_blank" rel="noopener">Daftar Gratis sebagai Kontributor</a>
                    <Link class="btn btn-line" :href="route('kontributor')">Pelajari programnya</Link>
                </div>

                <h2>Melaporkan kesalahan</h2>
                <p>
                    Sebutkan judul artikel dan bagian yang keliru. Prosedurnya dijelaskan di
                    <Link :href="`${route('kebijakan.editorial')}#koreksi`">Kebijakan Editorial</Link>.
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
