<template>
    <footer class="mt-12 border-t border-zinc-200 bg-white">
        <div class="mx-auto grid w-full max-w-screen-2xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:px-8">
            <section>
                <h3 class="text-sm font-semibold uppercase tracking-[0.15em] text-[#1BD6FF]">Editorial Policies</h3>
                <ul class="mt-4 space-y-2 text-sm text-zinc-600">
                    <li><a href="#" class="hover:text-[#0f5f74]">Standar komunitas</a></li>
                    <li><a href="#" class="hover:text-[#0f5f74]">Panduan republikasi</a></li>
                    <li><a href="#" class="hover:text-[#0f5f74]">Analisis data</a></li>
                    <li><a href="#" class="hover:text-[#0f5f74]">Umpan web kami</a></li>
                    <li><a href="#" class="hover:text-[#0f5f74]">Dapatkan newsletter</a></li>
                </ul>
            </section>

            <section>
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.15em] text-[#E80EB5]">Tentang kami</h3>
                    <div class="flex items-center gap-2 text-sm">
                        <a
                            href="https://instagram.com/expertia.id"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-icon-btn"
                            aria-label="Instagram Expertia"
                        >
                            <i class="fa-brands fa-instagram text-[15px]"></i>
                        </a>
                        <a
                            href="mailto:expertia.id@gmail.com"
                            class="social-icon-btn"
                            aria-label="Email Expertia"
                        >
                            <i class="fa-solid fa-envelope text-[15px]"></i>
                        </a>
                        <a
                            href="https://wa.me/62811399534"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-icon-btn"
                            aria-label="WhatsApp Expertia"
                        >
                            <i class="fa-brands fa-whatsapp text-[15px]"></i>
                        </a>
                    </div>
                </div>
                <ul class="mt-4 space-y-2 text-sm text-zinc-600">
                    <li><a href="#" class="hover:text-[#ca0a9d]">Tim kami</a></li>
                    <li><a href="#" class="hover:text-[#ca0a9d]">Program kami</a></li>
                    <li><a href="#" class="hover:text-[#ca0a9d]">Blog kami</a></li>
                    <li><a href="#" class="hover:text-[#ca0a9d]">Mitra dan donor</a></li>
                    <li><a href="#" class="hover:text-[#ca0a9d]">Informasi untuk media</a></li>
                    <li><a href="#" class="hover:text-[#ca0a9d]">Hubungi kami</a></li>
                </ul>
            </section>
        </div>

        <div class="border-t border-zinc-200 bg-zinc-50">
            <div class="mx-auto flex w-full max-w-screen-2xl flex-col gap-3 px-4 py-4 text-xs text-zinc-500 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <a href="#" class="hover:text-zinc-700">Kebijakan privasi</a>
                    <a href="#" class="hover:text-zinc-700">Syarat dan ketentuan</a>
                    <a href="#" class="hover:text-zinc-700">Koreksi</a>
                    <a href="#" class="hover:text-zinc-700">Pedoman media siber</a>
                </div>
                <p>Hak cipta &copy; 2026, expertia.id</p>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.social-icon-btn {
    display: inline-flex;
    height: 2.25rem;
    width: 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid #18181b !important;
    background: #ffffff !important;
    color: #18181b !important;
    transition: all 180ms ease;
}

.social-icon-btn i {
    color: currentColor !important;
}

.social-icon-btn:hover,
.social-icon-btn:focus-visible,
.social-icon-btn:active {
    background: #18181b !important;
    color: #ffffff !important;
    border-color: #18181b !important;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(24, 24, 27, 0.22);
}
</style>
