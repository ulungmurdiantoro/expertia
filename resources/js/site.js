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
export const rubrics = [
    {
        slug: 'riset-inovasi',
        name: 'Riset & Inovasi',
        description: 'Temuan penelitian, paten, dan inovasi teknologi yang diterjemahkan menjadi bacaan yang jelas tanpa kehilangan ketepatannya.',
    },
    {
        slug: 'perspektif-pakar',
        name: 'Perspektif Pakar',
        description: 'Analisis, studi kasus, dan wawasan profesional dari mereka yang memahami persoalannya dari dekat.',
    },
    {
        slug: 'profil-pakar',
        name: 'Profil Pakar',
        description: 'Perjalanan, karya, dan pemikiran akademisi, peneliti, inventor, dan praktisi di balik sebuah keahlian.',
    },
    {
        slug: 'kebijakan-solusi',
        name: 'Kebijakan & Solusi',
        description: 'Membaca isu publik dan tantangan industri berdasarkan bukti, lalu menimbang jalan keluarnya.',
    },
];

// Tanggal dari server berformat "Y-m-d H:i:s"; Safari butuh "T" agar bisa diparse.
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
