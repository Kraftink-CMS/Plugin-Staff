@extends('admin.layouts.layout')
@section('title', __('staff::messages.common.editorial'))
@section('actions')
    <a class="btn btn-primary" href="{{ route('plugins.staff.admin.documents.create') }}">{{ __('staff::messages.common.create') }}</a>
@endsection
@section('content')
    <div id="staff-admin-documents" data-ajax-pagination data-table tabindex="-1" class="vstack gap-4">
        <section class="card">
            <div class="card-body">
                <x-tables.controls :paginator="$documents" class="mb-3" />
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="staff-document-state">{{ __('common.labels.publication') }}</label>
                        <select class="form-select" id="staff-document-state" name="state">
                            <option value="">{{ __('staff::messages.editor.all_states') }}</option>
                            @foreach (['draft', 'published'] as $state)
                                <option value="{{ $state }}" @selected(request('state') === $state)>{{ __('common.statuses.' . $state) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <noscript>
                        <div class="col-auto">
                            <button class="btn btn-outline-primary" type="submit">{{ __('site.content.filter') }}</button>
                        </div>
                    </noscript>
                </form>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="text-nowrap">
                        <tr>
                            <th scope="col" data-table-sort="title">{{ __('staff::messages.common.title') }}</th>
                            <th scope="col" data-table-sort="locale">{{ __('staff::messages.common.language') }}</th>
                            <th scope="col" data-table-sort="published">{{ __('common.labels.publication') }}</th>
                            <th class="text-end">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $item)
                            <tr>
                                <td>{{ $item->title }}</td>
                                <td>{{ strtoupper($item->locale) }}</td>
                                <td>
                                    <span @class(['badge', 'text-bg-success' => $item->published, 'text-bg-secondary' => !$item->published])>
                                        {{ __('common.statuses.' . ($item->published ? 'published' : 'draft')) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">{{ __('common.labels.actions') }}</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('plugins.staff.admin.documents.edit', $item->id) }}">
                                                    {{ __('common.actions.edit') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('plugins.staff.admin.documents.revisions.index', $item->id) }}">
                                                    {{ __('staff::messages.common.revisions') }}
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#staff-document-delete-{{ $item->id }}">
                                                    {{ __('common.actions.delete') }}
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-tables.empty :colspan="4" :message="__('staff::messages.directory.no_documents')" :action="__('staff::messages.common.create')" :href="route('plugins.staff.admin.documents.create')" />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $documents->links() }}
            </div>
        </section>
        @foreach ($documents as $item)
            <x-forms.modal :id="'staff-document-delete-' . $item->id" :title="__('common.actions.delete')" :action="route('plugins.staff.admin.documents.destroy', $item->id)" method="DELETE" :submit="__('common.actions.delete')" size="sm">
                <p>{{ __('staff::messages.directory.delete_document') }}</p>
                <strong>{{ $item->title }}</strong>
            </x-forms.modal>
        @endforeach
    </div>
@endsection
