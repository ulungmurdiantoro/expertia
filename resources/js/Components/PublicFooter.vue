<script setup>
import { contact } from '@/site';
import { Link } from '@inertiajs/vue3';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="site-footer">
        <div class="wrap">
            <div class="foot-grid">
                <div class="foot-brand">
                    <span class="wordmark">expertia</span>
                    <p>Media kepakaran Indonesia.<br />Dari Kepakaran Menuju Dampak Nyata.</p>
                </div>
                <div>
                    <h2>JELAJAHI</h2>
                    <ul>
                        <li><Link :href="route('articles.index')">Artikel</Link></li>
                        <li><Link :href="route('articles.index', { category: 'riset-inovasi' })">Riset &amp; Inovasi</Link></li>
                        <li><Link :href="route('articles.index', { category: 'perspektif-pakar' })">Perspektif Pakar</Link></li>
                    </ul>
                </div>
                <div>
                    <h2>EXPERTIA</h2>
                    <ul>
                        <li><Link :href="route('tentang')">Tentang Expertia</Link></li>
                        <li><Link :href="route('kontributor')">Jadi Kontributor</Link></li>
                        <li><Link :href="route('kontak')">Kontak &amp; Kolaborasi</Link></li>
                        <li><Link :href="route('login')">Masuk (kontributor)</Link></li>
                    </ul>
                </div>
                <div>
                    <h2>KEBIJAKAN</h2>
                    <ul>
                        <li><Link :href="route('kebijakan.editorial')">Kebijakan Editorial</Link></li>
                        <li><Link :href="route('kebijakan.privasi')">Kebijakan Privasi</Link></li>
                    </ul>
                </div>
                <div>
                    <h2>HUBUNGI KAMI</h2>
                    <ul>
                        <li>
                            <a :href="contact.whatsappUrl" target="_blank" rel="noopener">WhatsApp {{ contact.whatsappNumber }}</a><br />
                            <span>{{ contact.whatsappName }}</span>
                        </li>
                        <li><a :href="contact.instagramUrl" target="_blank" rel="noopener">Instagram {{ contact.instagramHandle }}</a></li>
                        <li><span>{{ contact.email }}</span></li>
                    </ul>
                </div>
            </div>
            <div class="foot-bottom">
                <span>© {{ year }} Expertia.id · Dikelola oleh PT Padma Nusa Akademi</span>
                <span>Pandangan dalam Perspektif Pakar adalah pandangan penulis.</span>
            </div>
        </div>
    </footer>
</template>
