@use('App\Kraft\Site\Seo')
@extends('default.layouts.layout')
@push('head')
    <link rel="stylesheet" href="{{ route('site.extensions.assets.show', ['id' => $plugin, 'asset' => 'display.css']) }}">
@endpush
@if ($display['layout'] === 'carousel')
    @push('scripts')
        <script type="module" src="{{ route('site.extensions.assets.show', ['id' => $plugin, 'asset' => 'display.js']) }}"></script>
    @endpush
@endif
@section('content')
    <section>
        <header class="page-heading">
            <h1 class="h2 mb-0">{{ __('staff::messages.common.staff') }}</h1>
        </header>
        <form method="get" class="card card-body site-panel mb-4" role="search">
            <label class="form-label" for="staff-search">{{ __('staff::messages.common.search') }}</label>
            <div class="input-group">
                <input class="form-control" id="staff-search" type="search" name="q" value="{{ request('q') }}"
                    maxlength="100">
                <button class="btn btn-primary" type="submit">{{ __('staff::messages.common.search') }}</button>
            </div>
            @if (request()->filled('q'))
                <a class="small mt-2 align-self-start" href="{{ route('plugins.staff.public.documents.index') }}">{{ __('staff::messages.common.reset_search') }}</a>
            @endif
        </form>
        <div id="staff-public-members" data-ajax-pagination tabindex="-1" class="vstack gap-4">
            @foreach ($memberGroups as $group)
                @continue($group->isEmpty())
                @php
                    $groupId = 'staff-member-group-' . $loop->iteration;
                    $carousel = $display['layout'] === 'carousel';
                @endphp
                <div @if ($carousel) data-staff-carousel @endif>
                    @if ($display['grouped'])
                        <h2 class="h4 section-heading mb-3" id="{{ $groupId }}">{{ $group->first()->badges->first()?->title ?? __('staff::display.other') }}</h2>
                    @endif
                    <div @class(['staff-profile-list', 'staff-layout-' . $display['layout'], 'text-' . $display['alignment']])
                        style="--staff-desktop-columns: {{ $display['columns'] }}; --staff-tablet-columns: {{ min(2, $display['columns']) }}; --staff-avatar-size: {{ $display['avatar_size'] }}px"
                        @if ($carousel)
                            id="{{ $groupId }}-track" data-staff-carousel-track role="region" tabindex="0"
                            @if ($display['grouped']) aria-labelledby="{{ $groupId }}"
                            @else aria-label="{{ __('staff::display.carousel_label') }}" @endif
                        @endif>
                        @foreach ($group as $member)
                            @include('staff::default.documents.member-card')
                        @endforeach
                    </div>
                    @if ($carousel)
                        <div class="mt-3" data-staff-carousel-controls hidden>
                            <div class="d-flex justify-content-between gap-2">
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-staff-carousel-direction="-1"
                                    aria-controls="{{ $groupId }}-track">{{ __('staff::display.previous') }}</button>
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-staff-carousel-direction="1"
                                    aria-controls="{{ $groupId }}-track">{{ __('staff::display.next') }}</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
            {{ $members->links() }}
        </div>
        <div id="staff-public-documents" data-ajax-pagination tabindex="-1" class="vstack gap-4">
            @forelse($documents->groupBy('category') as $category => $items)
                <section class="mb-5">
                    <h2 class="h4 section-heading mb-3">{{ $category ?: __('staff::messages.common.general') }}</h2>
                    <div class="row g-4">
                        @foreach ($items as $document)
                            <div class="col-md-6">
                                <article class="card content-card h-100">
                                    <div class="card-body p-4">
                                        <h3 class="h5"><a class="text-decoration-none"
                                                href="{{ route('plugins.staff.public.documents.show', $document->slug) }}">{{ $document->title }}</a>
                                        </h3>
                                        <p class="text-body-secondary mb-0">
                                            {{ Seo::excerpt($document->body, 170) }}</p>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                @if ($members->isEmpty())
                    <div class="card card-body empty-state text-center py-5">
                        <i class="bi bi-people fs-2 mb-3" aria-hidden="true"></i>
                        <p class="mb-0">{{ __(request()->filled('q') ? 'staff::messages.common.no_results' : 'staff::messages.common.empty') }}</p>
                    </div>
                @endif
            @endforelse
            {{ $documents->links() }}
        </div>
    </section>
@endsection
