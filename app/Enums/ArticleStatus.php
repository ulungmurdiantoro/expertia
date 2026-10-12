<?php

namespace App\Enums;

/**
 * Nilai kolom articles.status. Tidak dipasang sebagai cast di model agar nilai lama
 * yang tidak dikenal di database tidak membuat halaman error; pakai ->value saat membandingkan.
 */
enum ArticleStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
