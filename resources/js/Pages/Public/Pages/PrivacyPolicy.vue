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
    <Head :title="meta.title || 'Kebijakan Privasi — Expertia.id'" />

    <PublicLayout>
        <div class="page-head">
            <div class="wrap">
                <p class="eyebrow">Kebijakan Privasi</p>
                <h1>Data Anda kami gunakan seperlunya, dan tidak kami tampilkan</h1>
                <p class="updated">Berlaku sejak Oktober 2026</p>
            </div>
        </div>

        <div class="wrap">
            <div class="prose" style="padding-bottom: 56px">
                <p>
                    Kebijakan ini menjelaskan data pribadi yang dikumpulkan Expertia.id, cara kami menggunakannya, dan hak Anda
                    atas data tersebut, sesuai Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.
                </p>

                <h2>Data yang kami kumpulkan</h2>
                <ul>
                    <li>
                        <strong>Dari calon kontributor:</strong> nama, gelar, institusi, jabatan, bidang kepakaran, email, nomor
                        WhatsApp, tautan profil profesional, dan materi yang Anda kirimkan melalui formulir pendaftaran.
                    </li>
                    <li><strong>Dari pengguna yang membuat akun:</strong> nama, email, dan kata sandi terenkripsi.</li>
                    <li><strong>Dari pembaca:</strong> data teknis kunjungan seperti halaman yang dibuka dan jumlah tayangan artikel.</li>
                </ul>

                <h2>Untuk apa data digunakan</h2>
                <ul>
                    <li>Menghubungi Anda terkait kurasi, penyuntingan, dan persetujuan naskah.</li>
                    <li>Memverifikasi identitas dan afiliasi kontributor.</li>
                    <li>Menyusun profil publik kontributor yang telah disetujui.</li>
                    <li>Memahami artikel mana yang dibaca agar redaksi dapat meningkatkan kualitas konten.</li>
                </ul>

                <h2>Yang ditampilkan ke publik</h2>
                <p>
                    Profil kontributor hanya memuat nama, foto (jika Anda berikan), afiliasi, jabatan, bidang kepakaran, dan
                    daftar karya. <strong>Nomor telepon dan email tidak pernah ditampilkan.</strong>
                </p>

                <h2>Berbagi data</h2>
                <p>
                    Kami tidak menjual data pribadi. Data hanya dapat diakses tim redaksi Expertia dan penyedia layanan teknis yang
                    membantu kami mengoperasikan website dan formulir, dengan kewajiban menjaga kerahasiaannya.
                </p>

                <h2>Penyimpanan</h2>
                <p>
                    Data kontributor disimpan selama Anda masih terdaftar atau selama diperlukan untuk keperluan redaksi. Data
                    pendaftar yang tidak lolos kurasi dihapus paling lambat 12 bulan setelah keputusan kurasi, kecuali Anda meminta
                    untuk tetap dihubungi.
                </p>

                <h2>Hak Anda</h2>
                <p>
                    Anda dapat meminta salinan, perbaikan, atau penghapusan data pribadi Anda, serta menarik persetujuan penggunaan
                    data. Kirim permintaan melalui WhatsApp
                    <a :href="contact.whatsappUrl" target="_blank" rel="noopener">{{ contact.whatsappNumber }}</a>
                    ({{ contact.whatsappName }}) atau email {{ contact.email }}.
                </p>

                <h2>Perubahan kebijakan</h2>
                <p>Kebijakan ini dapat diperbarui. Tanggal berlaku di bagian atas halaman menunjukkan versi terbaru.</p>
                <p>Pengendali data: PT Padma Nusa Akademi, pengelola Expertia.id.</p>
            </div>
        </div>
    </PublicLayout>
</template>
