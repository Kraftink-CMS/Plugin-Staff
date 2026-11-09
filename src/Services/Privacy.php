<?php

namespace KraftInk\Plugins\Staff\Services;

use App\Events\AccountDeleting;
use App\Events\ExportingPersonalData;
use App\Kraft\Extensions\Contracts\PrivacyHandler;
use KraftInk\Plugins\Staff\Models\DocumentRevision;
use KraftInk\Plugins\Staff\Models\Member;
use KraftInk\Plugins\Staff\Models\MemberRole;

class Privacy implements PrivacyHandler
{
    public function export(ExportingPersonalData $event): void
    {
        $canManage = $event->user->hasPermission('staff.manage');
        $event->data['staff'] = [
            'revisions' => DocumentRevision::query()
                ->where('user_id', $event->user->id)
                ->orderBy('id')
                ->select(['id', 'document_id', 'snapshot', 'created_at'])
                ->cursor()
                ->map(function ($revision) use ($canManage) {
                    $revision->snapshot = $canManage ? $revision->snapshot : null;

                    return $revision;
                }),
        ];
        $event->data['staff']['profiles'] = Member::query()
            ->where('user_id', $event->user->id)
            ->with(['badges' => fn ($query) => $query->select(['staff__roles.id', 'staff__roles.title'])])
            ->select([
                'id',
                'locale',
                'name',
                'title',
                'biography',
                'avatar',
                'website',
                'social_links',
                'published',
                'created_at',
                'updated_at',
            ])
            ->lazyById();
    }

    public function anonymize(AccountDeleting $event): void
    {
        DocumentRevision::query()
            ->where('user_id', $event->user->id)
            ->orderBy('id')
            ->select(['id', 'document_id'])
            ->chunkById(100, function ($revisions) {
                foreach ($revisions as $revision) {
                    DocumentRevision::query()
                        ->where('id', $revision->id)
                        ->update([
                            'user_id' => null,
                            'snapshot' => json_encode(
                                ['anonymized' => true, 'document_id' => $revision->document_id],
                                JSON_THROW_ON_ERROR,
                            ),
                        ]);
                }
            });
        $profiles = Member::query()->where('user_id', $event->user->id);
        MemberRole::query()->whereIn('member_id', (clone $profiles)->select('id'))->delete();
        $profiles
            ->update([
                'user_id' => null,
                'name' => $event->replacementName,
                'title' => '',
                'biography' => null,
                'avatar' => null,
                'website' => null,
                'social_links' => null,
                'published' => false,
                'updated_at' => now(),
            ]);
    }
}
