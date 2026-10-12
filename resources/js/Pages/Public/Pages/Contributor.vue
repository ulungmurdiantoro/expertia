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

const faqs = [
    {
        q: 'Apakah program ini benar-benar gratis?',
        a: 'Ya. Kontributor perdana yang lolos kurasi tidak dikenakan biaya apa pun untuk penulisan artikel, desain carousel, maupun publikasi.',
    },
    {
        q: 'Apakah saya harus memiliki artikel jurnal?',
        a: 'Tidak. Kami juga menerima paten, studi kasus, hasil pengabdian masyarakat, kajian profesional, dan pengalaman yang terdokumentasi.',
    },
    {
        q: 'Apakah paten dapat dipublikasikan?',
        a: 'Dapat. Kami menulis tentang masalah yang dipecahkan, cara kerjanya secara umum, dan manfaatnya. Informasi yang masih rahasia atau belum terdaftar tidak kami muat tanpa izin Anda.',
    },
    {
        q: 'Apakah praktisi yang bukan dosen boleh mendaftar?',
        a: 'Boleh. Praktisi, konsultan, dan profesional industri dengan keahlian yang dapat ditunjukkan juga kami sambut.',
    },
    {
        q: 'Apakah saya harus mengirim artikel yang sudah jadi?',
        a: 'Tidak. Kirimkan materi sumbernya, dan editor kami menyusun naskahnya. Jika Anda sudah punya draf, silakan lampirkan.',
    },
    {
        q: 'Apakah semua karya pasti diterbitkan?',
        a: 'Tidak. Setiap pendaftaran melalui kurasi. Kami menilai kejelasan sumber, kesesuaian dengan rubrik, dan kepentingannya bagi pembaca.',
    },
    {
        q: 'Apakah hak cipta tetap milik saya?',
        a: 'Ya. Hak cipta atas karya asli tetap pada Anda. Anda memberi Expertia izin noneksklusif untuk menerbitkan artikel dan carousel di kanal Expertia, dengan nama Anda tercantum sebagai sumber.',
    },
    {
        q: 'Apakah Expertia menggunakan AI dalam proses editorial?',
        a: 'Ya, sebagai alat bantu untuk menyusun struktur, menyederhanakan bahasa, dan membuat desain. Setiap fakta diperiksa oleh editor dan disetujui oleh Anda. AI tidak kami gunakan untuk mengarang data, kutipan, atau temuan.',
    },
    {
        q: 'Bagaimana proses persetujuan publikasi?',
        a: 'Kami mengirimkan draf artikel dan carousel kepada Anda. Anda dapat meminta perbaikan substansi sebelum memberi persetujuan. Tanpa persetujuan Anda, naskah tidak akan terbit.',
    },
    {
        q: 'Apakah saya perlu membuat akun di website?',
        a: 'Tidak. Cukup isi formulir pendaftaran. Profil kontributor Anda kami siapkan setelah lolos kurasi.',
    },
    {
        q: 'Apakah nomor telepon saya akan ditampilkan?',
        a: 'Tidak. Nomor telepon dan email Anda hanya dipakai tim redaksi untuk berkoordinasi. Profil publik hanya memuat nama, afiliasi, bidang kepakaran, dan karya Anda.',
    },
    {
        q: 'Bagaimana jika ada kesalahan setelah artikel terbit?',
        a: 'Hubungi kami melalui WhatsApp atau email. Koreksi dilakukan sesuai Kebijakan Editorial dan dicatat di akhir artikel.',
    },
];
</script>

<template>
    <Head :title="meta.title || 'Jadi Kontributor — Expertia.id'" />

    <PublicLayout>
        <div class="page-head">
            <div class="wrap">
                <p class="eyebrow">Program Kontributor Perdana</p>
                <h1>Punya Riset, Paten, atau Kepakaran? Kami Bantu Publikasikan. Gratis.</h1>
                <p class="sub">
                    Banyak penelitian, invensi, dan pengalaman profesional yang berharga hanya dibaca oleh kalangan terbatas.
                    Melalui Program Kontributor Perdana, Expertia membantu Anda memperkenalkan karya tersebut kepada masyarakat,
                    industri, dan pengambil keputusan, dalam bahasa yang mudah dipahami.
                </p>
                <div class="actions">
                    <a class="btn btn-navy" :href="contact.registrationUrl" target="_blank" rel="noopener">Daftar Gratis sebagai Kontributor</a>
                    <a class="btn btn-line" :href="contact.whatsappUrl" target="_blank" rel="noopener">Tanya lewat WhatsApp</a>
                </div>
            </div>
        </div>

        <div class="wrap">
            <div class="prose">
                <h2>Mengapa bergabung dengan Expertia</h2>
                <div class="two-col">
                    <div>
                        <h3>Karya Anda dibaca lebih luas</h3>
                        <p>Kami menerjemahkan temuan ilmiah dan pengalaman profesional menjadi tulisan yang dapat dinikmati pembaca umum.</p>
                    </div>
                    <div>
                        <h3>Anda tidak menulis sendirian</h3>
                        <p>Editor kami menyusun naskah dari materi yang Anda kirim. Anda cukup memeriksa dan menyetujui.</p>
                    </div>
                    <div>
                        <h3>Substansi tetap milik Anda</h3>
                        <p>Tidak ada yang terbit tanpa persetujuan Anda, dan nama Anda tercantum sebagai sumber kepakaran.</p>
                    </div>
                    <div>
                        <h3>Profil yang dapat dirujuk</h3>
                        <p>Halaman kontributor Anda memuat afiliasi, bidang kepakaran, dan daftar karya di Expertia.</p>
                    </div>
                </div>

                <h2>Yang Anda terima</h2>
                <p>Bagi kontributor yang lolos kurasi, tanpa biaya:</p>
                <ol>
                    <li>Satu artikel populer berdasarkan materi sumber Anda.</li>
                    <li>Satu carousel Instagram yang merangkum karya Anda.</li>
                    <li>Halaman profil dengan afiliasi dan bidang kepakaran.</li>
                    <li>Publikasi di expertia.id dan Instagram {{ contact.instagramHandle }}.</li>
                </ol>

                <h2>Siapa yang dapat bergabung</h2>
                <ul>
                    <li>Dosen dan akademisi</li>
                    <li>Peneliti di lembaga riset, pemerintah, atau swasta</li>
                    <li>Inventor dan pemegang paten</li>
                    <li>Praktisi profesional</li>
                    <li>Pakar dengan karya, kajian, atau pengalaman yang terdokumentasi</li>
                </ul>

                <h2>Materi yang dapat dikirim</h2>
                <p>Anda cukup menyiapkan satu atau beberapa dari berikut ini:</p>
                <ul>
                    <li>Tautan artikel jurnal atau laporan penelitian</li>
                    <li>DOI atau referensi publikasi</li>
                    <li>Nomor dan informasi paten atau invensi</li>
                    <li>Studi kasus implementasi</li>
                    <li>Hasil pengabdian kepada masyarakat</li>
                    <li>Kajian atau laporan profesional</li>
                    <li>Materi presentasi atau referensi lain yang relevan</li>
                </ul>

                <h2>Bagaimana prosesnya</h2>
                <ol class="steps">
                    <li><strong>Daftar</strong>Isi formulir singkat dan lampirkan tautan atau berkas materi Anda.</li>
                    <li><strong>Kurasi</strong>Tim kami memeriksa identitas, afiliasi, dan kesesuaian materi dengan rubrik Expertia.</li>
                    <li><strong>Penyuntingan</strong>Editor menyusun artikel dan carousel dari materi Anda.</li>
                    <li><strong>Persetujuan</strong>Anda memeriksa naskah dan meminta perbaikan bila perlu.</li>
                    <li><strong>Terbit</strong>Artikel dan carousel dipublikasikan, lalu tautannya kami kirimkan kepada Anda.</li>
                </ol>
                <p>Kami menghubungi Anda melalui WhatsApp atau email dalam 7 hari kerja setelah pendaftaran diterima.</p>

                <h2>Ketentuan program</h2>
                <ul>
                    <li>Berlaku bagi kontributor perdana yang lolos kurasi selama periode soft launch.</li>
                    <li>Setiap kontributor menerima satu paket: satu artikel dan satu carousel.</li>
                    <li>
                        Materi adalah karya Anda sendiri atau karya yang Anda miliki izin untuk dipublikasikan. Untuk karya bersama,
                        cantumkan seluruh anggota tim.
                    </li>
                    <li>
                        Expertia dapat menolak atau menunda materi yang tidak sesuai dengan
                        <Link :href="route('kebijakan.editorial')">Kebijakan Editorial</Link>.
                    </li>
                    <li>Program ini tidak mensyaratkan pembelian layanan apa pun.</li>
                    <li>Publikasi di Expertia bukan sertifikasi atau pengakuan resmi atas kepakaran seseorang.</li>
                </ul>

                <h2 id="faq">Pertanyaan yang sering diajukan</h2>
                <div class="faq">
                    <details v-for="item in faqs" :key="item.q">
                        <summary>{{ item.q }}</summary>
                        <p>{{ item.a }}</p>
                    </details>
                </div>
            </div>
        </div>

        <div class="wrap">
            <div class="cta-band">
                <h2>Siap memperkenalkan karya Anda?</h2>
                <p>Pendaftaran hanya membutuhkan beberapa menit. Anda tidak perlu membuat akun.</p>
                <div class="actions">
                    <a class="btn btn-navy" :href="contact.registrationUrl" target="_blank" rel="noopener">Daftar Gratis sebagai Kontributor</a>
                    <span style="color: var(--ink-3); font-size: 15px">
                        atau WhatsApp <a :href="contact.whatsappUrl" target="_blank" rel="noopener">{{ contact.whatsappNumber }}</a>
                        ({{ contact.whatsappName }})
                    </span>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
