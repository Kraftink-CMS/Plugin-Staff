<?php

namespace KraftInk\Plugins\Staff\Services\Imports\KraftInk;

use App\Kraft\Migration\ImportContext;
use App\Kraft\Migration\ImportedHtml;
use App\Kraft\Migration\MappingRegistry;
use App\Kraft\NavigationTargets;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use KraftInk\Plugins\Staff\Models\Member;
use KraftInk\Plugins\Staff\Models\RoleListing;
use RuntimeException;

final class Mappings
{
    public function register(MappingRegistry $registry): void
    {
        $registry->register('kraftink', 'staff__documents', $this->document(...), 55, 'staff', [
            'staff__document_revisions' => [],
            'staff__documents' => [],
        ]);
        $registry->register(
            'kraftink',
            'staff__document_revisions',
            $this->revision(...),
            60,
            'staff',
            dependencies: ['staff__documents', 'users'],
        );
        $registry->register(
            'kraftink',
            'staff__members',
            $this->member(...),
            55,
            'staff',
            ['staff__members' => []],
            dependencies: ['users'],
        );
        $registry->register('kraftink', 'staff__roles', $this->role(...), 55, 'staff', dependencies: ['roles']);
        $registry->register(
            'kraftink',
            'staff__member_roles',
            $this->memberRole(...),
            60,
            'staff',
            dependencies: ['staff__members', 'staff__roles'],
        );
    }

    private function locale(array $row): string
    {
        return array_key_exists($row['locale'] ?? '', config('kraft.locales'))
            ? $row['locale']
            : config('imports.locale');
    }

    private function data(array $row, ImportContext $context): array
    {
        return [
            'locale' => $this->locale($row),
            'slug' => substr(Str::slug($row['slug'] ?? ($row['title'] ?? 'document')) ?: 'document', 0, 130).
                '-import-'.
                $row['id'].
                '-'.
                substr($context->runId, 0, 8),
            'title' => strip_tags($row['title']),
            'body' => ImportedHtml::clean($row['body'] ?? ''),
            'category' => isset($row['category']) ? strip_tags($row['category']) : null,
            'position' => max(0, (int) ($row['position'] ?? 0)),
            'permission' => 'imports.review',
            'published' => false,
        ];
    }

    private function document(array $row, ImportContext $context): array
    {
        return [
            'target_table' => 'staff__documents',
            'target_id' => $context->insert(
                'staff__documents',
                $this->data($row, $context) + [
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => now(),
                ],
            ),
        ];
    }

    private function revision(array $row, ImportContext $context): array
    {
        $snapshot = json_decode($row['snapshot'], true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($snapshot) || !isset($snapshot['title'])) {
            throw new RuntimeException('Invalid imported staff revision.');
        }
        $snapshot['id'] = $row['document_id'];

        return [
            'target_table' => 'staff__document_revisions',
            'target_id' => $context->insert('staff__document_revisions', [
                'document_id' => $context->required('staff__documents', $row['document_id']),
                'user_id' => empty($row['user_id']) ? null : $context->required('users', $row['user_id']),
                'snapshot' => json_encode($this->data($snapshot, $context), JSON_THROW_ON_ERROR),
                'created_at' => $row['created_at'] ?? now(),
            ]),
        ];
    }

    private function member(array $row, ImportContext $context): array
    {
        $website = $row['website'] ?? null;
        $user = empty($row['user_id']) ? null : $context->required('users', $row['user_id']);
        if (!empty($row['user_id']) && ($context->metadata('users', $row['user_id'])['preserved_local'] ?? false)) {
            $user = null;
        }
        $links = json_decode($row['social_links'] ?? '[]', true, flags: JSON_THROW_ON_ERROR);
        $links = Validator::make(['social_links' => $links], Member::socialLinkRules())->validated()['social_links'];

        return [
            'target_table' => 'staff__members',
            'target_id' => $context->insert('staff__members', [
                'user_id' => $user,
                'locale' => $this->locale($row),
                'name' => strip_tags($row['name']),
                'title' => strip_tags($row['title']),
                'biography' => ImportedHtml::clean($row['biography'] ?? ''),
                'avatar' => null,
                'website' => is_string($website) &&
                    in_array(strtolower(parse_url($website, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true) &&
                    NavigationTargets::validUrl($website)
                        ? $website
                        : null,
                'social_links' => $links ? json_encode($links, JSON_THROW_ON_ERROR) : null,
                'position' => max(0, (int) ($row['position'] ?? 0)),
                'published' => false,
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ]),
            'metadata' => ['avatar' => 'archive_only'],
        ];
    }

    private function role(array $row, ImportContext $context): array
    {
        $role = $row['role_id'] === null ? null : $context->required('roles', $row['role_id']);
        $key = ['role_id' => $role, 'locale' => $this->locale($row)];
        $existing = $role === null ? null : $context->target->table('staff__roles')->where($key)->value('id');
        $color = $row['color'] ?? RoleListing::DEFAULT_COLOR;
        $id =
            $existing ??
            $context->insert('staff__roles', $key + [
                'title' => strip_tags($row['title']),
                'position' => max(0, (int) ($row['position'] ?? 0)),
                'color' => preg_match(RoleListing::COLOR_PATTERN, $color) ? strtolower($color) : RoleListing::DEFAULT_COLOR,
            ]);

        return ['target_table' => 'staff__roles', 'target_id' => $id];
    }

    private function memberRole(array $row, ImportContext $context): array
    {
        return [
            'target_table' => 'staff__member_roles',
            'target_id' => $context->insert('staff__member_roles', [
                'member_id' => $context->required('staff__members', $row['member_id']),
                'role_listing_id' => $context->required('staff__roles', $row['role_listing_id']),
            ]),
        ];
    }
}
