<?php

namespace App\Enums;

/**
 * Nilai kolom articles.editorial_status (posisi artikel di alur review redaksi).
 */
enum EditorialStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RevisionRequested = 'revision_requested';
}
