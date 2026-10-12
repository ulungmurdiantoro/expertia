<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { contact } from '@/site';
import { Head } from '@inertiajs/vue3';

defineProps({
    meta: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <Head :title="meta.title || 'Kebijakan Editorial — Expertia.id'" />

    <PublicLayout>
        <div class="page-head">
            <div class="wrap">
                <p class="eyebrow">Kebijakan Editorial</p>
                <h1>Kredibilitas adalah dasar dari semua yang kami terbitkan</h1>
                <p class="sub">Kebijakan ini menjelaskan cara kami memeriksa kontributor, menyunting naskah, dan memperbaiki kesalahan.</p>
                <p class="updated">Berlaku sejak Oktober 2026</p>
            </div>
        </div>

        <div class="wrap">
            <div class="prose" style="padding-bottom: 56px">
                <h2>1. Verifikasi kepakaran</h2>
                <p>
                    Sebelum diberi status <strong>Kontributor Terverifikasi</strong>, kami memeriksa identitas, afiliasi
                    institusi, rekam jejak, dan materi pendukung kontributor. Status ini menandakan bahwa kami telah melakukan
                    pemeriksaan tersebut, bukan sertifikasi atas kepakaran seseorang.
                </p>

                <h2>2. Verifikasi karya</h2>
                <p>
                    Kami memeriksa referensi penelitian, status paten, dan klaim penting dalam setiap naskah sesuai kebutuhan.
                    Setiap artikel yang bersumber dari penelitian mencantumkan sumbernya.
                </p>

                <h2>3. Penggunaan AI</h2>
                <p>
                    Kami dapat menggunakan AI untuk membantu penyuntingan, penyederhanaan bahasa, penyusunan struktur, dan desain
                    visual. Kami tidak menggunakan AI untuk membuat data, kutipan, temuan, atau referensi. Setiap fakta diperiksa
                    oleh editor.
                </p>

                <h2>4. Persetujuan penulis</h2>
                <p>Substansi akhir setiap naskah mendapat persetujuan kontributor sebelum terbit.</p>

                <h2>5. Hak cipta dan izin</h2>
                <p>
                    Hak cipta atas karya asli tetap pada kontributor. Kontributor memberi Expertia izin noneksklusif untuk
                    menerbitkan naskah di kanal Expertia. Kutipan dari karya lain disertai sumber, dan gambar mencantumkan asal
                    serta lisensinya.
                </p>

                <h2>6. Pemisahan jenis konten</h2>
                <p>
                    Setiap tulisan diberi label sesuai jenisnya: Riset &amp; Inovasi, Perspektif Pakar, Profil Pakar, Kebijakan
                    &amp; Solusi, atau Konten Kerja Sama. Konten berbayar atau sponsor selalu diberi label
                    <strong>Kerja Sama</strong> dan tidak memengaruhi penilaian redaksi atas tulisan lain.
                </p>

                <h2>7. Independensi</h2>
                <p>
                    Expertia tidak berafiliasi dengan partai politik dan tidak mengambil posisi pro maupun anti pemerintah.
                    Pandangan dalam Perspektif Pakar adalah pandangan kontributor.
                </p>

                <h2>8. Privasi kontributor</h2>
                <p>
                    Nomor telepon dan email kontributor tidak pernah ditampilkan di website. Profil publik hanya memuat nama,
                    afiliasi, bidang kepakaran, dan daftar karya.
                </p>

                <h2 id="koreksi">9. Koreksi</h2>
                <p>
                    Jika Anda menemukan kesalahan, hubungi redaksi melalui WhatsApp
                    <a :href="contact.whatsappUrl" target="_blank" rel="noopener">{{ contact.whatsappNumber }}</a>
                    ({{ contact.whatsappName }}) atau email {{ contact.email }} dengan menyebutkan judul artikel dan bagian yang
                    keliru. Kami meninjau setiap laporan. Kesalahan fakta diperbaiki dan dicatat di akhir artikel beserta tanggal
                    koreksinya. Artikel tidak dihapus diam-diam.
                </p>

                <h2>10. Hak jawab</h2>
                <p>
                    Pihak yang disebut dalam tulisan dapat mengajukan hak jawab melalui kontak yang sama. Kami menanganinya sesuai
                    Pedoman Pemberitaan Media Siber yang ditetapkan
                    <a href="https://dewanpers.or.id" target="_blank" rel="noopener">Dewan Pers</a>.
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
