@extends('admin.layouts.layout')
@section('title', __('staff::messages.common.directory'))
@section('content')
    <div data-hash-tabs data-hash-tabs-errors="{{ json_encode($errors->keys()) }}">
        <ul class="nav nav-tabs mb-3" role="tablist">
            @foreach (['manual', 'automatic'] as $panel)
                <li class="nav-item" role="presentation">
                    <button @class(['nav-link', 'active' => $tab === $panel]) id="staff-tab-{{ $panel }}" type="button" data-bs-toggle="tab"
                        data-bs-target="#staff-directory-{{ $panel }}" role="tab"
                        aria-controls="staff-directory-{{ $panel }}"
                        aria-selected="{{ $tab === $panel ? 'true' : 'false' }}">{{ $panel === 'manual' ? __('staff::messages.directory.manual') : __('staff::profiles.badges') }}</button>
                </li>
            @endforeach
        </ul>
        <div class="tab-content">
            <div @class(['tab-pane fade', 'show active' => $tab === 'manual']) id="staff-directory-manual" role="tabpanel"
                aria-labelledby="staff-tab-manual">
                <div id="staff-admin-members" data-ajax-pagination data-table tabindex="-1" class="vstack gap-4">
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="{{ route('plugins.staff.admin.members.create') }}">{{ __('staff::messages.common.add_member') }}</a>
                        <a class="btn btn-outline-secondary" href="{{ route('plugins.staff.admin.ordering.edit', ['kind' => 'members']) }}">{{ __('staff::ordering.action') }}</a>
                    </div>
                    <section class="card">
                        <div class="card-body">
                            <x-tables.controls :paginator="$members" class="mb-3" />
                            <form method="get" action="{{ route('plugins.staff.admin.members.index') }}" class="row g-2">
                                <input type="hidden" name="tab" value="manual">
                                <div class="col-md">
                                    <input type="search" class="form-control" name="q" value="{{ request('q') }}" maxlength="100"
                                        aria-label="{{ __('staff::messages.common.search') }}"
                                        placeholder="{{ __('staff::messages.common.member_name') }}">
                                </div>
                                @if ($badges->isNotEmpty())
                                    <div class="col-md">
                                        <select class="form-select" name="badge" aria-label="{{ __('staff::profiles.badge_filter') }}">
                                            <option value="">{{ __('staff::profiles.all_badges') }}</option>
                                            @foreach ($badges as $badge)
                                                <option value="{{ $badge->id }}" @selected((int) request('badge') === $badge->id)>{{ $badge->title }} ({{ strtoupper($badge->locale) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <noscript>
                                    <button class="btn btn-outline-secondary"
                                        type="submit">{{ __('staff::messages.common.search') }}</button>
                                </noscript>
                            </form>
                        </div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th scope="col" data-table-sort="name">{{ __('staff::messages.common.member_name') }}</th>
                                        <th scope="col" data-table-sort="title">{{ __('staff::messages.common.member_title') }}</th>
                                        <th scope="col">{{ __('staff::profiles.badges') }}</th>
                                        <th scope="col" data-table-sort="locale">{{ __('staff::messages.common.language') }}</th>
                                        <th scope="col" data-table-sort="published">{{ __('staff::messages.common.published') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($members as $member)
                                        <tr>
                                            <td>{{ $member->name }}</td>
                                            <td>{{ $member->title }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach ($member->badges as $badge)
                                                        <span class="badge" style="background-color: {{ $badge->backgroundColor() }}; color: {{ $badge->foregroundColor() }}">{{ $badge->title }}</span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>{{ strtoupper($member->locale) }}</td>
                                            <td><span
                                                    @class([
                                                        'badge',
                                                        'text-bg-success' => $member->published,
                                                        'text-bg-secondary' => !$member->published,
                                                    ])>{{ $member->published ? __('staff::messages.common.yes') : __('staff::messages.common.no') }}</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                        type="button"
                                                        data-bs-toggle="dropdown">{{ __('common.labels.actions') }}</button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item"
                                                                href="{{ route('plugins.staff.admin.members.edit', $member->id) }}">{{ __('common.actions.edit') }}</a>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item text-danger" type="button"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#staff-member-delete-{{ $member->id }}">{{ __('common.actions.delete') }}</button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-tables.empty :colspan="6" :message="__('staff::messages.directory.no_members')" :action="__('staff::messages.common.add_member')"
                                            :href="route('plugins.staff.admin.members.create')" />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $members->links() }}
                        </div>
                    </section>
                    @foreach ($members as $member)
                        <x-forms.modal :id="'staff-member-delete-' . $member->id" :title="__('common.actions.delete')" :action="route('plugins.staff.admin.members.destroy', $member->id)" method="DELETE" :submit="__('common.actions.delete')"
                            size="sm">
                            <p>{{ __('staff::messages.directory.delete_member') }}</p>
                            <strong>{{ $member->name }}</strong>
                        </x-forms.modal>
                    @endforeach
                </div>
            </div>
            <div @class(['tab-pane fade', 'show active' => $tab === 'automatic']) id="staff-directory-automatic" role="tabpanel"
                aria-labelledby="staff-tab-automatic">
                <div id="staff-admin-roles" data-ajax-pagination data-table tabindex="-1" class="vstack gap-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal"
                            data-bs-target="#staff-role-new">{{ __('staff::profiles.add_badge') }}</button>
                        <a class="btn btn-outline-secondary" href="{{ route('plugins.staff.admin.ordering.edit', ['kind' => 'roles']) }}">{{ __('staff::ordering.action') }}</a>
                    </div>
                    <section class="card">
                        <div class="card-body">
                            <x-tables.controls :paginator="$automaticRoles" class="mb-3" />
                            <p class="mb-0">{{ __('staff::profiles.badges_help') }}</p>
                        </div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th scope="col" data-table-sort="title">{{ __('staff::profiles.badge_name') }}</th>
                                        <th scope="col" data-table-sort="role">{{ __('staff::profiles.linked_rank') }}</th>
                                        <th scope="col" data-table-sort="locale">{{ __('staff::messages.common.language') }}</th>
                                        <th scope="col" data-table-sort="position">{{ __('staff::messages.common.position') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($automaticRoles as $item)
                                        <tr>
                                            <td><span class="badge" style="background-color: {{ $item->backgroundColor() }}; color: {{ $item->foregroundColor() }}">{{ $item->title }}</span></td>
                                            <td>{{ $roles->firstWhere('id', $item->role_id)?->name ?? __('staff::profiles.no_linked_rank') }}</td>
                                            <td>{{ strtoupper($item->locale) }}</td>
                                            <td>{{ $item->position }}</td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                        type="button"
                                                        data-bs-toggle="dropdown">{{ __('common.labels.actions') }}</button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <button class="dropdown-item" type="button" data-bs-toggle="modal"
                                                                data-bs-target="#staff-role-{{ $item->id }}">{{ __('common.actions.edit') }}</button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item text-danger" type="button"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#staff-role-delete-{{ $item->id }}">{{ __('common.actions.delete') }}</button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-tables.empty :colspan="5" :message="__('staff::profiles.no_badges')" :action="__('staff::profiles.add_badge')"
                                            modal="staff-role-new" />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $automaticRoles->links() }}
                        </div>
                    </section>
                    <x-forms.modal id="staff-role-new" :title="__('staff::profiles.add_badge')" :action="route('plugins.staff.admin.roles.store')">
                        @include('staff::admin.roles.partials.fields', ['listing' => null])
                    </x-forms.modal>
                    @foreach ($automaticRoles as $item)
                        <x-forms.modal :id="'staff-role-' . $item->id" :title="__('staff::profiles.edit_badge')" :action="route('plugins.staff.admin.roles.update', $item->id)" method="PUT">
                            @include('staff::admin.roles.partials.fields', ['listing' => $item])
                        </x-forms.modal>
                        <x-forms.modal :id="'staff-role-delete-' . $item->id" :title="__('common.actions.delete')" :action="route('plugins.staff.admin.roles.destroy', $item->id)" method="DELETE"
                            :submit="__('common.actions.delete')" size="sm">
                            <p>{{ __('staff::profiles.delete_badge') }}</p>
                            <strong>{{ $item->title }}</strong>
                        </x-forms.modal>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script type="module"
            src="{{ route('site.extensions.assets.show', ['id' => 'staff', 'asset' => 'editor.js']) }}">
        </script>
    @endpush
@endsection
