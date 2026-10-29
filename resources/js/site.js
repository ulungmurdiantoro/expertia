// Data situs publik Expertia yang dipakai di banyak halaman.
// Ganti email di sini bila email berdomain sudah aktif.
export const contact = {
    whatsappNumber: '0811399534',
    whatsappUrl: 'https://wa.me/62811399534',
    whatsappName: 'Kurniawan Eka',
    email: 'expertia.id@gmail.com',
    instagramHandle: '@expertia.id',
    instagramUrl: 'https://www.instagram.com/expertia.id/',
    registrationUrl: 'https://tinyurl.com/joinexpertia',
};

// Rubrik mengikuti slug kategori. Rubrik tanpa artikel terbit tampil "Segera hadir".
// `tone` adalah warna penanda rubrik (kotak kecil di label, tab, dan daftar).
export const rubrics = [
    {
        slug: 'riset-inovasi',
        name: 'Riset & Inovasi',
        description: 'Temuan penelitian, paten, dan inovasi teknologi yang diterjemahkan menjadi bacaan yang jelas tanpa kehilangan ketepatannya.',
        tone: '#18243D',
    },
    {
        slug: 'perspektif-pakar',
        name: 'Perspektif Pakar',
        description: 'Analisis, studi kasus, dan wawasan profesional dari mereka yang memahami persoalannya dari dekat.',
        tone: '#9A7395',
    },
    {
        slug: 'profil-pakar',
        name: 'Profil Pakar',
        description: 'Perjalanan, karya, dan pemikiran akademisi, peneliti, inventor, dan praktisi di balik sebuah keahlian.',
        tone: '#39305E',
    },
    {
        slug: 'kebijakan-solusi',
        name: 'Kebijakan & Solusi',
        description: 'Membaca isu publik dan tantangan industri berdasarkan bukti, lalu menimbang jalan keluarnya.',
        tone: '#C4B8C6',
    },
];

export const rubricTone = (slug) => rubrics.find((rubric) => rubric.slug === slug)?.tone || '#A8B0BD';

// "Jabatan · Institusi", bagian yang kosong dilewati.
export const authorRole = (author) => [author?.position, author?.institution].filter(Boolean).join(' · ');

export const initials = (name) =>
    String(name || '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0].toUpperCase())
        .join('');

// Zona waktu tetap: halaman dirender di server (UTC) lalu di browser pembaca; tanpa
// zona yang sama, tanggal artikel yang terbit dekat tengah malam bisa berbeda.
export const SITE_TIME_ZONE = 'Asia/Jakarta';

// Tanggal dari server berformat ISO 8601 (atau "Y-m-d H:i:s" pada data lama;
// Safari butuh "T" agar bisa diparse).
export const formatDate = (value) => {
    if (!value) {
        return '';
    }

    const date = new Date(String(value).replace(' ', 'T'));

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: SITE_TIME_ZONE,
    }).format(date);
};

export const readingMinutes = (html) => {
    const words = String(html || '')
        .replace(/<[^>]+>/g, ' ')
        .split(/\s+/)
        .filter(Boolean).length;

    return Math.max(1, Math.round(words / 200));
};

// "Riset & Inovasi · Ekonomi" — kategori lalu tag pertama.
export const articleKicker = (article) =>
    [article?.category?.name, article?.tags?.[0]?.name].filter(Boolean).join(' · ');
