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
    <Head :title="meta.title || 'Tentang Expertia — Expertia.id'" />

    <PublicLayout>
        <div class="page-head">
            <div class="wrap">
                <p class="eyebrow">Tentang Expertia</p>
                <h1>Pengetahuan yang Layak Dikenal Lebih Luas</h1>
            </div>
        </div>

        <div class="wrap">
            <div class="prose">
                <p>
                    Setiap tahun, banyak penelitian selesai dikerjakan, invensi didaftarkan, dan pengalaman profesional terkumpul
                    di Indonesia. Sebagian besar hanya dibaca oleh kalangan terbatas: tersimpan di jurnal, laporan, atau ingatan
                    orang yang mengerjakannya.
                </p>
                <p>
                    Expertia hadir karena pengetahuan dan kepakaran memiliki dampak yang lebih besar ketika dapat diakses,
                    dipahami, dan digunakan oleh masyarakat.
                </p>

                <h2>Apa yang kami lakukan</h2>
                <p>
                    Kami mengolah hasil riset, paten, inovasi, kajian, dan pengalaman para pakar menjadi tulisan yang jelas tanpa
                    mengorbankan ketepatannya. Kami juga memperkenalkan orang-orang di balik karya tersebut: perjalanan, pemikiran,
                    dan kontribusinya.
                </p>
                <p>
                    Tujuan kami sederhana: menjembatani dunia akademik, profesional, industri, dan kebijakan publik melalui
                    publikasi yang kredibel dan relevan.
                </p>

                <h2>Cara kami bekerja</h2>
                <div class="two-col">
                    <div>
                        <h3>Dari sumber yang dapat ditelusuri</h3>
                        <p>Setiap tulisan merujuk pada penelitian, data, atau pengalaman yang dapat diperiksa.</p>
                    </div>
                    <div>
                        <h3>Bersama para pakarnya</h3>
                        <p>Naskah disusun bersama kontributor dan terbit setelah mendapat persetujuan mereka.</p>
                    </div>
                    <div>
                        <h3>Kritis, konstruktif, independen</h3>
                        <p>
                            Kami tidak mengambil posisi pro maupun anti pemerintah. Kami menyajikan bukti, menimbang pilihan, dan
                            membedakan dengan jelas antara analisis, opini, dan konten kerja sama.
                        </p>
                    </div>
                </div>
                <blockquote>Critical on problems. Constructive on solutions. Independent in judgment.</blockquote>

                <h2>Untuk siapa Expertia</h2>
                <p>
                    Untuk pembaca umum yang ingin memahami persoalan lebih dalam. Untuk pelaku industri yang mencari solusi
                    berbasis bukti. Untuk pengambil keputusan yang membutuhkan sudut pandang para ahli. Dan untuk para pakar yang
                    ingin karyanya memberi manfaat lebih luas.
                </p>

                <h2>Pengelola</h2>
                <p>
                    Expertia.id dikelola oleh PT Padma Nusa Akademi. Untuk urusan redaksi dan kolaborasi, hubungi
                    {{ contact.whatsappName }} melalui WhatsApp
                    <a :href="contact.whatsappUrl" target="_blank" rel="noopener">{{ contact.whatsappNumber }}</a>
                    atau email {{ contact.email }}.
                </p>
                <p style="font-family: var(--serif); font-size: 24px; color: var(--navy); margin-top: 36px">
                    Dari Kepakaran Menuju Dampak Nyata.
                </p>
                <div class="actions" style="margin-bottom: 56px">
                    <Link class="btn btn-navy" :href="route('articles.index')">Jelajahi Artikel</Link>
                    <Link class="btn btn-line" :href="route('kontributor')">Jadi Kontributor</Link>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
