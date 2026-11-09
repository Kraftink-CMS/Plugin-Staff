@extends('admin.layouts.layout')
@section('title', __('staff::ordering.title.' . $kind))
@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('plugins.staff.admin.members.index', ['tab' => $kind === 'roles' ? 'automatic' : 'manual']) }}">
        {{ __('common.actions.back') }}
    </a>
@endsection
@section('content')
    <section class="card">
        <div class="card-body">
            <form method="get" action="{{ route('plugins.staff.admin.ordering.edit', ['kind' => $kind]) }}" class="row row-cols-auto g-2 align-items-end">
                <div class="col">
                    <label for="staff-order-locale" class="form-label">{{ __('common.labels.language') }}</label>
                    <select id="staff-order-locale" class="form-select" name="locale">
                        @foreach ($locales as $code => $language)
                            <option value="{{ $code }}" @selected($code === $locale)>{{ $language }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col">
                    <button class="btn btn-outline-secondary" type="submit">{{ __('staff::ordering.show_language') }}</button>
                </div>
            </form>
        </div>
        @if ($items->isEmpty())
            <div class="card-body pt-0">
                <p class="text-body-secondary mb-0">{{ __('staff::ordering.empty') }}</p>
            </div>
        @else
            <form action="{{ route('plugins.staff.admin.ordering.update', ['kind' => $kind]) }}" method="post"
                data-staff-ordering data-move-message="{{ __('staff::ordering.moved') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="locale" value="{{ $locale }}">
                <input type="hidden" name="revision" value="{{ $revision }}">
                <input type="hidden" name="order" value="{{ json_encode(array_map('strval', $items->modelKeys()), JSON_THROW_ON_ERROR) }}">
                <div class="card-body pt-0">
                    @if ($items->count() > 1)
                        <p class="text-body-secondary d-none" data-order-controls>{{ __('staff::ordering.help') }}</p>
                        <noscript><p class="text-body-secondary">{{ __('staff::ordering.javascript_required') }}</p></noscript>
                    @endif
                    <ol class="list-group list-group-numbered" data-order-list>
                        @foreach ($items as $item)
                            @php
                                $label = $kind === 'members' ? $item->name : $item->title;
                                $detail = $kind === 'members' ? $item->title : $item->role?->name;
                            @endphp
                            <li class="list-group-item d-flex align-items-center gap-2" data-order-id="{{ $item->id }}">
                                @if ($items->count() > 1)
                                    <span class="staff-order-handle text-body-secondary d-none" draggable="true" data-order-handle data-order-controls aria-hidden="true">
                                        <i class="bi bi-grip-vertical fs-5"></i>
                                    </span>
                                @endif
                                <div class="flex-grow-1 text-break">
                                    <div class="fw-semibold" data-order-label>{{ $label }}</div>
                                    @if ($detail)
                                        <div class="small text-body-secondary">{{ $detail }}</div>
                                    @endif
                                </div>
                                @if ($items->count() > 1)
                                    <div class="btn-group flex-shrink-0 d-none" data-order-controls>
                                        <button class="btn btn-outline-secondary" type="button" data-order-move="up"
                                            aria-label="{{ __('staff::ordering.move_up', ['name' => $label]) }}">
                                            <i class="bi bi-arrow-up" aria-hidden="true"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" type="button" data-order-move="down"
                                            aria-label="{{ __('staff::ordering.move_down', ['name' => $label]) }}">
                                            <i class="bi bi-arrow-down" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                    <p class="visually-hidden" role="status" data-order-status></p>
                </div>
                @if ($items->count() > 1)
                    <div class="card-footer d-none" data-order-controls>
                        <button class="btn btn-primary" type="submit" disabled>{{ __('common.actions.save') }}</button>
                    </div>
                @endif
            </form>
        @endif
    </section>
@endsection
@if ($items->count() > 1)
    @push('head')
        <link rel="stylesheet" href="{{ route('site.extensions.assets.show', ['id' => 'staff', 'asset' => 'ordering.css']) }}">
    @endpush
    @push('scripts')
        <script type="module" src="{{ route('site.extensions.assets.show', ['id' => 'staff', 'asset' => 'ordering.js']) }}"></script>
    @endpush
@endif
