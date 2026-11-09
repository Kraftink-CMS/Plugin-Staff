<?php

namespace KraftInk\Plugins\Staff\Services\Imports\MineWeb;

use App\Kraft\Html;
use App\Kraft\Migration\ImportContext;
use App\Kraft\Migration\MappingRegistry;
use App\Kraft\NavigationTargets;

final class Mappings
{
    public function register(MappingRegistry $registry): void
    {
        $registry->register('mineweb', 'staff__staffs', $this->member(...), 50, 'staff', [
            'staff__members' => ['locale' => [config('imports.locale')]],
        ]);
    }

    private function member(array $row, ImportContext $context): array
    {
        $name = strip_tags((string) $row['username']);
        $matches = [];
        foreach ($context->sourceRows('users') ?? [] as $user) {
            if (mb_strtolower((string) ($user['pseudo'] ?? ($user['name'] ?? ''))) === mb_strtolower($name)) {
                $matches[] =
                    $context->metadata('users', $user['id'])['preserved_local'] ?? false
                        ? null
                        : $context->mapped('users', $user['id']);
            }
        }
        $links = [];
        foreach (
            ['facebook', 'reddit', 'twitter', 'googleplus', 'youtube', 'weibo', 'github', 'instagram'] as $network
        ) {
            $url = $row[$network.'_url'] ?? null;
            if (
                is_string($url) &&
                in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true) &&
                NavigationTargets::validUrl($url)
            ) {
                $links[] =
                    '<a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'">'.ucfirst($network).'</a>';
            }
        }

        return $this->writeMember($context, [
            'user_id' => count($matches) === 1 ? $matches[0] : null,
            'name' => $name,
            'title' => (string) ($row['rank'] ?? ''),
            'biography' => implode(' · ', $links),
            'position' => $row['order'] ?? 0,
            'created_at' => $row['created_at'] ?? now(),
            'updated_at' => $row['updated_at'] ?? now(),
        ]);
    }

    private function writeMember(ImportContext $context, array $member): array
    {
        return [
            'target_table' => 'staff__members',
            'target_id' => $context->insert('staff__members', [
                'user_id' => $member['user_id'],
                'locale' => config('imports.locale'),
                'name' => mb_substr(strip_tags($member['name']), 0, 120),
                'title' => mb_substr(strip_tags($member['title']), 0, 120),
                'biography' => Html::clean($member['biography']),
                'avatar' => null,
                'website' => null,
                'position' => max(0, (int) $member['position']),
                'published' => false,
                'created_at' => $member['created_at'],
                'updated_at' => $member['updated_at'],
            ]),
        ];
    }
}
