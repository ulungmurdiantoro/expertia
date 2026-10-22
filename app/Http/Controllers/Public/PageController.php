<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\PageMeta;
use App\Support\StructuredData;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function contributor(): Response
    {
        return $this->render(
            'Public/Pages/Contributor',
            'Jadi Kontributor — Expertia.id',
            'Program Kontributor Perdana Expertia: riset, paten, atau kepakaran Anda kami bantu publikasikan sebagai artikel populer dan carousel Instagram, gratis.',
        );
    }

    public function about(): Response
    {
        return $this->render(
            'Public/Pages/About',
            'Tentang Expertia — Expertia.id',
            'Expertia menjembatani dunia akademik, profesional, industri, dan kebijakan publik melalui publikasi kepakaran yang kredibel dan relevan.',
            'AboutPage',
        );
    }

    public function editorialPolicy(): Response
    {
        return $this->render(
            'Public/Pages/EditorialPolicy',
            'Kebijakan Editorial — Expertia.id',
            'Cara Expertia memverifikasi kontributor dan karya, menggunakan AI, meminta persetujuan penulis, dan memperbaiki kesalahan.',
        );
    }

    public function privacyPolicy(): Response
    {
        return $this->render(
            'Public/Pages/PrivacyPolicy',
            'Kebijakan Privasi — Expertia.id',
            'Data yang dikumpulkan Expertia.id, cara penggunaannya, dan hak Anda. Nomor telepon dan email kontributor tidak pernah ditampilkan.',
        );
    }

    public function contact(): Response
    {
        return $this->render(
            'Public/Pages/Contact',
            'Kontak — Expertia.id',
            'Hubungi redaksi Expertia.id untuk program kontributor, koreksi, dan kerja sama: WhatsApp 0811399534 (Kurniawan Eka).',
            'ContactPage',
        );
    }

    private function render(string $component, string $title, string $description, string $schemaType = 'WebPage'): Response
    {
        return Inertia::render($component, [
            'meta' => PageMeta::make(
                $title,
                $description,
                jsonLd: [
                    StructuredData::webPage($schemaType, $title, $description),
                    StructuredData::organization(),
                ],
            ),
        ]);
    }
}
