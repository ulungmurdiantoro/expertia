<script setup>
import PublicRubricMark from '@/Components/PublicRubricMark.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { articleKicker, contact, formatDate, rubrics } from '@/site';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    featured: {
        type: Object,
        default: null,
    },
    secondary: {
        type: Object,
        default: null,
    },
    latest: {
        type: Array,
        default: () => [],
    },
    rubricCounts: {
        type: [Object, Array],
        default: () => ({}),
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const hideImage = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <Head :title="meta.title || 'Expertia.id — Media Kepakaran Indonesia'" />

    <PublicLayout>
        <section class="section hero">
            <div class="wrap">
                <div class="hero-main">
                    <p class="eyebrow">Media Kepakaran Indonesia</p>
                    <h1>Pengetahuan yang Layak Dikenal Lebih Luas.</h1>
                    <p class="sub">
                        Menghadirkan riset, paten, inovasi, dan perspektif para pakar untuk membantu masyarakat, industri,
                        serta pengambil keputusan memahami persoalan secara lebih mendalam.
                    </p>
                    <div class="actions">
                        <Link class="btn btn-navy" :href="route('articles.index')">Jelajahi Artikel</Link>
                        <Link class="btn btn-line" :href="route('kontributor')">Jadi Kontributor</Link>
                    </div>
                </div>
                <ol class="flow" aria-label="Dari pengetahuan ke dampak">
                    <li><span>01</span>Pengetahuan</li>
                    <li><span>02</span>Kepakaran</li>
                    <li class="last"><span>03</span>Dampak<em>Dari Kepakaran Menuju Dampak Nyata.</em></li>
                </ol>
            </div>
        </section>

        <section v-if="featured" class="section home-featured" aria-labelledby="pilihan-h">
            <div class="wrap">
                <div class="section-head">
                    <h2 id="pilihan-h" class="h2">Artikel Pilihan</h2>
                    <Link class="link-arrow" :href="route('articles.index')">Lihat semua artikel →</Link>
                </div>
                <div class="featured">
                    <article class="feat-main is-overlay">
                        <!-- Gambar terbesar di atas lipatan (LCP): dimuat lebih dulu, tidak lazy. -->
                        <img v-if="featured.thumbnail_url" :src="featured.thumbnail_url" alt="" fetchpriority="high" decoding="async" @error="hideImage" />
                        <div class="feat-copy">
                            <p class="eyebrow card-meta">
                                <PublicRubricMark :slug="featured.category?.slug" />{{ articleKicker(featured) }}
                            </p>
                            <h3>
                                <Link :href="route('articles.show', featured.slug)">{{ featured.title }}</Link>
                            </h3>
                            <p v-if="featured.excerpt" class="excerpt">{{ featured.excerpt }}</p>
                            <p class="byline">
                                <strong>{{ featured.author?.name || 'Redaksi Expertia' }}</strong> · {{ formatDate(featured.published_at) }}
                            </p>
                        </div>
                    </article>

                    <div class="feat-side">
                        <article v-if="secondary" class="side-card">
                            <Link
                                v-if="secondary.thumbnail_url"
                                :href="route('articles.show', secondary.slug)"
                                tabindex="-1"
                                aria-hidden="true"
                            >
                                <div class="cover light">
                                    <img :src="secondary.thumbnail_url" alt="" loading="lazy" @error="hideImage" />
                                </div>
                            </Link>
                            <p class="eyebrow card-meta">
                                <PublicRubricMark :slug="secondary.category?.slug" />{{ articleKicker(secondary) }}
                            </p>
                            <h3>
                                <Link :href="route('articles.show', secondary.slug)">{{ secondary.title }}</Link>
                            </h3>
                            <p class="byline">
                                <strong>{{ secondary.author?.name || 'Redaksi Expertia' }}</strong> · {{ formatDate(secondary.published_at) }}
                            </p>
                        </article>
                        <aside class="side-note">
                            <p>Riset yang baik tidak seharusnya berhenti di jurnal.</p>
                            <p>Kirimkan karya Anda. Editor kami membantu mengolahnya menjadi tulisan populer.</p>
                            <Link class="link-arrow" :href="route('kontributor')">Pelajari program kontributor →</Link>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="latest.length" class="section on-paper" aria-labelledby="terbaru-h">
            <div class="wrap">
                <div class="section-head">
                    <h2 id="terbaru-h" class="h2">Terbaru</h2>
                    <Link class="link-arrow" :href="route('articles.index')">Semua artikel →</Link>
                </div>
                <div class="latest-grid is-staggered">
                    <article v-for="story in latest" :key="story.id" class="side-card">
                        <Link
                            v-if="story.thumbnail_url"
                            :href="route('articles.show', story.slug)"
                            tabindex="-1"
                            aria-hidden="true"
                        >
                            <div class="cover light">
                                <img :src="story.thumbnail_url" alt="" loading="lazy" @error="hideImage" />
                            </div>
                        </Link>
                        <p class="eyebrow card-meta">
                            <PublicRubricMark :slug="story.category?.slug" />{{ articleKicker(story) }}
                        </p>
                        <h3>
                            <Link :href="route('articles.show', story.slug)">{{ story.title }}</Link>
                        </h3>
                        <p class="byline">
                            <strong>{{ story.author?.name || 'Redaksi Expertia' }}</strong> · {{ formatDate(story.published_at) }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section on-tint" aria-labelledby="rubrik-h">
            <div class="wrap">
                <h2 id="rubrik-h" class="h2">Jelajahi Kepakaran</h2>
                <p class="lead">Empat rubrik untuk memahami persoalan dari sudut pandang orang yang menekuninya.</p>
                <div class="rubrics is-cards">
                    <template v-for="rubric in rubrics" :key="rubric.slug">
                        <Link
                            v-if="rubricCounts[rubric.slug] > 0"
                            class="rubric"
                            :href="route('articles.index', { category: rubric.slug })"
                        >
                            <PublicRubricMark :slug="rubric.slug" :size="14" />
                            <h3>{{ rubric.name }}</h3>
                            <p>{{ rubric.description }}</p>
                            <p class="status"><span class="count">{{ rubricCounts[rubric.slug] }}</span> artikel →</p>
                        </Link>
                        <div v-else class="rubric is-soon">
                            <PublicRubricMark :slug="rubric.slug" :size="14" />
                            <h3>{{ rubric.name }}</h3>
                            <p>{{ rubric.description }}</p>
                            <p class="status soon">Segera hadir</p>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <section class="section program" aria-labelledby="program-h">
            <div class="wrap">
                <div class="copy">
                    <p class="eyebrow">Program Kontributor Perdana</p>
                    <h2 id="program-h">Punya Riset, Paten, atau Kepakaran? Kami Bantu Publikasikan. <em>Gratis.</em></h2>
                    <p class="body">
                        Anda tidak perlu menulis dari nol. Kirimkan tautan jurnal, informasi paten, studi kasus, atau kajian Anda.
                        Tim editor Expertia mengolahnya menjadi artikel populer dan carousel Instagram, lalu meminta persetujuan
                        Anda sebelum terbit.
                    </p>
                    <div class="actions">
                        <a class="btn btn-ivory" :href="contact.registrationUrl" target="_blank" rel="noopener">Daftar Gratis sebagai Kontributor</a>
                        <Link class="text" :href="route('kontributor')">Pelajari programnya</Link>
                    </div>
                    <p class="fine">
                        Setiap pendaftaran melalui kurasi editorial. Pertanyaan? WhatsApp
                        <a :href="contact.whatsappUrl" target="_blank" rel="noopener">{{ contact.whatsappNumber }}</a>
                        ({{ contact.whatsappName }}).
                    </p>
                </div>
                <ul class="benefits is-boxes">
                    <li class="label">Yang Anda terima</li>
                    <li>Satu artikel populer dari materi sumber Anda</li>
                    <li>Satu carousel Instagram</li>
                    <li>Profil dan bidang kepakaran Anda</li>
                    <li>Terbit di expertia.id dan Instagram {{ contact.instagramHandle }}</li>
                </ul>
            </div>
        </section>

        <section class="section about is-dark" aria-labelledby="tentang-h">
            <div class="wrap">
                <div class="col">
                    <h2 id="tentang-h" class="eyebrow">Tentang Expertia</h2>
                    <p class="statement">
                        Pengetahuan memberi dampak lebih besar ketika dapat diakses, dipahami, dan digunakan. Expertia menjembatani
                        dunia akademik, profesional, industri, dan kebijakan publik melalui publikasi yang kredibel dan relevan.
                    </p>
                    <p style="margin-top: 24px">
                        <Link class="link-arrow" :href="route('kebijakan.editorial')">Baca kebijakan editorial →</Link>
                    </p>
                </div>
                <div class="col principles">
                    <div>
                        <h3>Berbasis bukti</h3>
                        <p>Setiap tulisan bertumpu pada riset, data, atau pengalaman yang dapat ditelusuri.</p>
                    </div>
                    <div>
                        <h3>Kritis pada masalah, konstruktif pada solusi</h3>
                        <p>Kami tidak berhenti pada kritik, tetapi menimbang jalan keluar.</p>
                    </div>
                    <div>
                        <h3>Independen dalam menilai</h3>
                        <p>Tidak berpihak pada pemerintah maupun oposisi. Konten kerja sama selalu diberi label.</p>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
