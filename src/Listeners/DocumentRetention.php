<?php

namespace KraftInk\Plugins\Staff\Listeners;

use App\Events\PruningData;
use App\Kraft\System\RevisionRetention;
use KraftInk\Plugins\Staff\Models\DocumentRevision;

class DocumentRetention
{
    public function prune(PruningData $event): void
    {
        $event->add(
            'staff.history',
            app(RevisionRetention::class)->prune(DocumentRevision::query(), 'document_id', $event->dryRun),
        );
    }
}
