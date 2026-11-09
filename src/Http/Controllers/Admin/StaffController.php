<?php

namespace KraftInk\Plugins\Staff\Http\Controllers\Admin;

use App\Kraft\System\TableQuery;
use App\Kraft\Html;
use App\Kraft\Media\ImageUrl;
use App\Models\Accounts\Role;
use App\Models\Accounts\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use KraftInk\Plugins\Staff\Models\Member;
use KraftInk\Plugins\Staff\Models\RoleListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffController
{
    public function admin(Request $request): View
    {
        $data = $request->validate([
            'tab' => ['nullable', Rule::in(['manual', 'automatic'])],
            'q' => 'nullable|string|max:100',
            'badge' => 'nullable|integer|min:1',
        ]);
        $tab = $data['tab'] ?? 'manual';
        $modal = $request->old('_modal');
        if (is_string($modal) && str_starts_with($modal, 'staff-role-')) {
            $tab = 'automatic';
        }
        $members = TableQuery::paginate(
            Member::query()
                ->with('badges')
                ->when($request->filled('q'), fn ($query) => $query->whereLike('name', '%'.$data['q'].'%'))
                ->when(isset($data['badge']), fn ($query) => $query->whereHas(
                    'badges',
                    fn ($badges) => $badges->whereKey($data['badge'])->whereColumn('staff__roles.locale', 'staff__members.locale'),
                ))
                ->orderBy('position')
                ->orderBy('name')
                ->orderBy('id'),
            ['name' => 'name', 'title' => 'title', 'locale' => 'locale', 'published' => 'published'],
            pageName: 'members_page',
            search: [],
        )
            ->withQueryString()
            ->appends(['tab' => 'manual']);

        return view('staff::admin.members.index', [
            'members' => $members,
            'automaticRoles' => TableQuery::paginate(
                RoleListing::query()
                    ->leftJoin('roles', 'roles.id', '=', 'staff__roles.role_id')
                    ->select('staff__roles.*')
                    ->orderBy('staff__roles.position')
                    ->orderBy('staff__roles.id'),
                [
                    'role' => 'roles.name',
                    'title' => 'staff__roles.title',
                    'locale' => 'staff__roles.locale',
                    'position' => 'staff__roles.position',
                ],
                pageName: 'roles_page',
                search: ['staff__roles.title', 'roles.name'],
            )
                ->withQueryString()
                ->appends(['tab' => 'automatic']),
            'roles' => Role::query()->orderByDesc('position')->get(),
            'badges' => RoleListing::query()->orderBy('locale')->orderBy('position')->orderBy('id')->get(),
            'tab' => $tab,
        ]);
    }

    public function edit(Request $request, ?int $id = null): JsonResponse|View
    {
        if ($request->boolean('account_lookup')) {
            $data = $request->validate(['q' => 'nullable|string|max:100']);

            return response()->json([
                'success' => true,
                'data' => User::query()
                    ->where('account_type', 'human')
                    ->whereNull('anonymized_at')
                    ->where('import_locked', false)
                    ->when($request->filled('q'), fn ($query) => $query->whereLike('username', '%'.$data['q'].'%'))
                    ->orderBy('username')
                    ->limit(30)
                    ->get(['id', 'username']),
            ]);
        }
        $member = $id ? Member::query()->find($id) : null;
        if ($id && !$member) {
            abort(404);
        }
        $users = User::query()
            ->where('account_type', 'human')
            ->whereNull('anonymized_at')
            ->where('import_locked', false)
            ->orderBy('username')
            ->limit(30)
            ->get(['id', 'username']);
        if (
            $member?->user_id &&
            !$users->contains('id', $member->user_id) &&
            ($selected = User::query()
                ->where('account_type', 'human')
                ->find($member->user_id, ['id', 'username']))
        ) {
            $users->prepend($selected);
        }

        return view('staff::admin.members.edit', [
            'member' => $member,
            'users' => $users,
            'badges' => RoleListing::query()->orderBy('locale')->orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function save(Request $request, ?int $id = null): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('account_type', 'human')],
            'name' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:120'],
            'locale' => ['required', Rule::in(array_keys(config('kraft.locales')))],
            'biography' => ['nullable', 'string', 'max:10000'],
            'avatar' => ['nullable', 'string', 'max:2000', new ImageUrl()],
            'website' => ['nullable', 'url:https', 'max:2000'],
            'position' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'published' => ['sometimes', 'boolean'],
            'badges_present' => ['sometimes', 'boolean'],
            'badge_ids' => ['sometimes', 'array', 'list', 'max:100'],
            'badge_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'social_links_present' => ['sometimes', 'boolean'],
        ] + Member::socialLinkRules());
        $syncBadges = $request->boolean('badges_present') || array_key_exists('badge_ids', $data);
        $badgeIds = $data['badge_ids'] ?? [];
        if ($request->boolean('social_links_present')) {
            $data['social_links'] ??= [];
        }
        unset($data['badges_present'], $data['badge_ids'], $data['social_links_present']);
        $data['biography'] = Html::clean($data['biography'] ?? '');
        $data['published'] = $request->boolean('published');
        DB::transaction(function () use ($id, $data, $syncBadges, $badgeIds): void {
            $member = $id ? Member::query()->lockForUpdate()->findOrFail($id) : new Member();
            $selected = $badgeIds;
            if (!$syncBadges && $member->exists && $member->locale !== $data['locale']) {
                $selected = $member->badges()->pluck('staff__roles.id')->all();
            }
            if ($selected !== [] && RoleListing::query()
                ->whereKey($selected)
                ->where('locale', $data['locale'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id'])->count() !== count($selected)
            ) {
                throw ValidationException::withMessages(['badge_ids' => __('staff::profiles.badge_locale_error')]);
            }
            $member->fill($data)->save();
            if ($syncBadges) {
                $member->badges()->sync($selected);
            }
        }, 3);

        return redirect()
            ->route('plugins.staff.admin.members.index')
            ->with('status', __('staff::messages.common.saved'));
    }

    public function destroy(int $id): RedirectResponse
    {
        Member::query()->where('id', $id)->delete();

        return back();
    }

    public function saveRole(Request $request, ?int $id = null): RedirectResponse
    {
        $data = $request->validate([
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'locale' => ['required', Rule::in(array_keys(config('kraft.locales')))],
            'title' => ['required', 'string', 'max:120'],
            'position' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'color' => ['sometimes', 'required', 'string', RoleListing::COLOR_RULE],
        ]);
        $data['role_id'] ??= null;
        try {
            DB::transaction(function () use ($id, $data): void {
                if ($id) {
                    $badge = RoleListing::query()->lockForUpdate()->findOrFail($id);
                    if ($badge->locale !== $data['locale'] && $badge->members()->exists()) {
                        throw ValidationException::withMessages(['locale' => __('staff::profiles.assigned_locale_error')]);
                    }
                    $badge->fill($data)->save();
                } else {
                    RoleListing::query()->create($data);
                }
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'role_id' => __('staff::messages.directory.role_exists'),
            ]);
        }

        return redirect()
            ->route('plugins.staff.admin.members.index', ['tab' => 'automatic'])
            ->with('status', __('staff::messages.common.saved'));
    }

    public function deleteRole(int $id): RedirectResponse
    {
        RoleListing::query()->where('id', $id)->delete();

        return redirect()
            ->route('plugins.staff.admin.members.index', ['tab' => 'automatic'])
            ->with('status', __('staff::messages.common.deleted'));
    }
}
