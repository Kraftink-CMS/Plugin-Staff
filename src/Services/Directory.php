<?php

namespace KraftInk\Plugins\Staff\Services;

use App\Kraft\Auth\Avatars;
use App\Kraft\Auth\PermissionAccess;
use App\Kraft\Auth\RoleAssignments;
use App\Models\Accounts\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use KraftInk\Plugins\Staff\Models\Member;
use KraftInk\Plugins\Staff\Models\RoleListing;

class Directory
{
    public function members(
        ?string $search = null,
        ?User $user = null,
        int $perPage = 24,
        ?int $page = null,
        bool $grouped = false,
    ): LengthAwarePaginator {
        $eligible = $this->eligibleRoles();
        $query = $this->query($search, $user, $eligible, $grouped);
        if ($grouped) {
            $query
                ->leftJoin('staff__roles as primary_role', 'primary_role.id', '=', 'directory.primary_role_id')
                ->orderByRaw('CASE WHEN primary_role.id IS NULL THEN 1 ELSE 0 END')
                ->orderBy('primary_role.position')
                ->orderBy('primary_role.id');
        }
        $members = $query
            ->orderBy('directory.position')
            ->orderBy('directory.name')
            ->orderBy('directory.source')
            ->orderBy('directory.id')
            ->paginate(max(1, min(24, $perPage)), ['directory.*'], 'members_page', $page)
            ->withQueryString();

        $manualIds = $members->getCollection()->where('source', 'manual')->pluck('id');
        $automaticIds = $members->getCollection()->where('source', 'automatic')->pluck('user_id');
        $profiles = Member::query()
            ->whereKey($manualIds->all())
            ->with(['badges' => fn ($query) => $query->where('staff__roles.locale', app()->getLocale())])
            ->get(['id', 'social_links'])
            ->keyBy('id');
        $automaticBadges = RoleListing::query()
            ->joinSub($eligible, 'matches', 'matches.listing_id', '=', 'staff__roles.id')
            ->whereIn('matches.user_id', $automaticIds)
            ->orderBy('staff__roles.position')
            ->orderBy('staff__roles.id')
            ->get(['staff__roles.*', 'matches.user_id'])
            ->groupBy('user_id');
        $portraits = [];
        foreach ($members as $member) {
            if (!$member->avatar && $member->user_id) {
                $account = new User();
                $account->setRawAttributes([
                    'id' => $member->user_id,
                    'username' => $member->user_username,
                    'avatar_path' => $member->avatar_path,
                ]);
                $account->exists = true;
                $portraits[$member->user_id] = $account;
            }
        }
        $avatars = app(Avatars::class);
        $avatars->prepare($portraits);
        $members->through(function ($member) use ($portraits, $avatars, $profiles, $automaticBadges) {
            if (!$member->avatar && isset($portraits[$member->user_id])) {
                $member->avatar = $avatars->url($portraits[$member->user_id]);
            }
            $profile = $profiles->get($member->id);
            $member->badges = ($member->source === 'manual' ? $profile?->badges : $automaticBadges->get($member->user_id))
                ?? collect();
            $member->social_links = $member->source === 'manual' ? ($profile?->social_links ?? []) : [];
            unset($member->avatar_path, $member->user_username);

            return $member;
        });

        return $members;
    }

    public function hasMembers(?User $user): bool
    {
        return $this->query(null, $user, $this->eligibleRoles())->exists();
    }

    private function query(?string $search, ?User $user, Builder $eligible, bool $grouped = false): Builder
    {
        $manual = Member::query()
            ->from('staff__members as members')
            ->toBase()
            ->leftJoin('users', 'users.id', '=', 'members.user_id')
            ->where('members.locale', app()->getLocale())
            ->where('members.published', true)
            ->where(function ($query) {
                $query->whereNull('members.user_id')->orWhere(fn ($query) => $this->active($query));
            });
        $canView = app(PermissionAccess::class)->allows($user, 'staff.view');
        if (!$canView) {
            $manual->whereRaw('1 = 0');
        }
        $manualIds = (clone $manual)->whereNotNull('members.user_id')->select('members.user_id');
        if ($search !== null && $search !== '') {
            $manual->where(
                fn ($query) => $query
                    ->whereLike('members.name', '%'.$search.'%')
                    ->orWhereLike('members.title', '%'.$search.'%')
                    ->orWhereLike('members.biography', '%'.$search.'%'),
            );
        }
        $manual
            ->select([
                'members.id',
                'members.user_id',
                'members.name',
                'members.title',
                'members.biography',
                'members.avatar',
                'members.website',
                'members.position',
                'users.avatar_path',
                'users.username as user_username',
            ])
            ->selectRaw("'manual' AS source");
        $automatic = DB::query()
            ->fromSub($eligible, 'eligible')
            ->join('users', 'users.id', '=', 'eligible.user_id')
            ->whereNotIn('users.id', $manualIds)
            ->whereNotExists(function ($query) use ($eligible) {
                $query
                    ->fromSub($eligible, 'higher')
                    ->selectRaw('1')
                    ->whereColumn('higher.user_id', 'eligible.user_id')
                    ->where(
                        fn ($query) => $query
                            ->whereColumn('higher.position', '<', 'eligible.position')
                            ->orWhere(
                                fn ($query) => $query
                                    ->whereColumn('higher.position', 'eligible.position')
                                    ->whereColumn('higher.listing_id', '<', 'eligible.listing_id'),
                            ),
                    );
            });
        $this->active($automatic);
        if (!$canView) {
            $automatic->whereRaw('1 = 0');
        }
        if ($search !== null && $search !== '') {
            $automatic->where(
                fn ($query) => $query
                    ->whereLike('users.username', '%'.$search.'%')
                    ->orWhereLike('eligible.title', '%'.$search.'%'),
            );
        }
        $automatic
            ->select(['users.id', 'users.id as user_id', 'users.username', 'eligible.title'])
            ->selectRaw(
                "NULL AS biography, NULL AS avatar, NULL AS website, eligible.position, users.avatar_path, users.username AS user_username, 'automatic' AS source",
            )
            ->distinct();
        if ($grouped) {
            $manual->selectSub(
                RoleListing::query()
                    ->join('staff__member_roles as badges', 'badges.role_listing_id', '=', 'staff__roles.id')
                    ->whereColumn('badges.member_id', 'members.id')
                    ->whereColumn('staff__roles.locale', 'members.locale')
                    ->orderBy('staff__roles.position')
                    ->orderBy('staff__roles.id')
                    ->select('staff__roles.id')
                    ->limit(1),
                'primary_role_id',
            );
            $automatic->addSelect('eligible.listing_id as primary_role_id');
        }

        return DB::query()->fromSub($manual->unionAll($automatic), 'directory');
    }

    private function eligibleRoles(): Builder
    {
        $roleIds = RoleListing::query()
            ->where('locale', app()->getLocale())
            ->whereNotNull('role_id')
            ->pluck('role_id')
            ->all();

        return DB::query()
            ->fromSub(app(RoleAssignments::class)->query($roleIds), 'assignments')
            ->join('staff__roles as listings', 'listings.role_id', '=', 'assignments.role_id')
            ->where('listings.locale', app()->getLocale())
            ->select(['assignments.user_id', 'listings.id as listing_id', 'listings.title', 'listings.position'])
            ->distinct();
    }

    private function active(Builder $query): Builder
    {
        return $query
            ->where('users.account_type', 'human')
            ->where('users.banned_permanently', false)
            ->whereNull('users.anonymized_at')
            ->where('users.import_locked', false)
            ->where(fn ($query) => $query->whereNull('users.banned_until')->orWhere('users.banned_until', '<=', now()));
    }
}
