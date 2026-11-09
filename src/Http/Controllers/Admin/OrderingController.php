<?php

namespace KraftInk\Plugins\Staff\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use KraftInk\Plugins\Staff\Models\Member;
use KraftInk\Plugins\Staff\Models\RoleListing;

class OrderingController
{
    public function edit(Request $request, string $kind): View
    {
        $locales = config('kraft.locales');
        $data = $request->validate(['locale' => ['nullable', 'string', Rule::in(array_keys($locales))]]);
        $locale = $data['locale'] ?? app()->getLocale();
        $query = $this->items($kind, $locale);
        if ($kind === 'roles') {
            $query->with('role:id,name');
        }
        $items = $query->get();

        return view('staff::admin.ordering.edit', [
            'kind' => $kind,
            'locale' => $locale,
            'locales' => $locales,
            'items' => $items,
            'revision' => $this->revision($kind, $locale, $items),
        ]);
    }

    public function update(Request $request, string $kind): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('kraft.locales')))],
            'revision' => ['required', 'string', 'size:64', 'alpha_num:ascii'],
            'order' => ['required', 'string', 'json'],
        ]);
        $order = json_decode($data['order']);
        if (!is_array($order)) {
            throw ValidationException::withMessages(['order' => __('staff::ordering.invalid_order')]);
        }
        $order = array_map(fn ($id) => is_int($id) || is_string($id) ? (string) $id : '', $order);
        $locale = $data['locale'];

        DB::transaction(function () use ($kind, $locale, $data, $order) {
            $items = $this->items($kind, $locale)->lockForUpdate()->get();
            if (!hash_equals($this->revision($kind, $locale, $items), $data['revision'])) {
                throw ValidationException::withMessages(['revision' => __('staff::ordering.stale')]);
            }
            $expected = array_map('strval', $items->modelKeys());
            if (count($order) !== count($expected) || array_diff($expected, $order) !== []) {
                throw ValidationException::withMessages(['order' => __('staff::ordering.invalid_order')]);
            }
            if ($order === $expected) {
                return;
            }
            $items = $items->keyBy('id');
            foreach ($order as $position => $id) {
                $item = $items->get($id);
                if ($item->position !== $position) {
                    $item->update(['position' => $position]);
                }
            }
        }, 3);

        return redirect()
            ->route('plugins.staff.admin.ordering.edit', ['kind' => $kind, 'locale' => $locale])
            ->with('status', __('staff::ordering.saved'));
    }

    private function items(string $kind, string $locale): Builder
    {
        $query = $kind === 'members'
            ? Member::query()->select(['id', 'name', 'title', 'position'])
            : RoleListing::query()->select(['id', 'role_id', 'title', 'position']);

        return $query->where('locale', $locale)
            ->orderBy('position')
            ->when($kind === 'members', fn (Builder $query) => $query->orderBy('name'))
            ->orderBy('id');
    }

    private function revision(string $kind, string $locale, Collection $items): string
    {
        $positions = $items->map(fn (Member|RoleListing $item) => [$item->id, $item->position])->values()->all();

        return hash('sha256', json_encode([$kind, $locale, $positions], JSON_THROW_ON_ERROR));
    }
}
