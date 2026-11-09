@php
    $heading = $display['grouped'] ? 'h3' : 'h2';
    $showTitle = $member->title && !$member->badges->contains('title', $member->title);
@endphp
<article class="card staff-card">
    <div class="card-body staff-card-body">
        <header class="staff-card-heading">
            <div class="staff-card-portrait">
                @if ($member->avatar)
                    <img class="staff-card-avatar object-fit-cover" src="{{ $member->avatar }}" alt=""
                        width="{{ $display['avatar_size'] }}" height="{{ $display['avatar_size'] }}" loading="lazy"
                        referrerpolicy="no-referrer">
                @else
                    <span class="staff-card-avatar d-grid bg-body-tertiary text-body-secondary"
                        aria-hidden="true"><i class="bi bi-person fs-2"></i></span>
                @endif
            </div>
            <div class="staff-card-identity">
                <{{ $heading }} class="h5 mb-1">{{ $member->name }}</{{ $heading }}>
                @if ($showTitle)
                    <p class="small text-body-secondary mb-0">{{ $member->title }}</p>
                @endif
                @if ($member->badges->isNotEmpty())
                    <div class="staff-card-badges d-flex flex-wrap gap-1 mt-2">
                        @foreach ($member->badges as $badge)
                            <span class="badge text-wrap" style="background-color: {{ $badge->backgroundColor() }}; color: {{ $badge->foregroundColor() }}">{{ $badge->title }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </header>
        @if ($display['show_biography'] && $member->biography)
            <div class="staff-card-biography prose text-body-secondary">
                {!! $member->biography !!}
            </div>
        @endif
    </div>
    @if ($member->website || $member->social_links)
        <div class="card-footer staff-card-links d-flex flex-wrap gap-2">
            @if ($member->website)
                <a class="small text-decoration-none" href="{{ $member->website }}" target="_blank" rel="noopener noreferrer">
                    {{ __('staff::messages.common.website') }}<i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i></a>
            @endif
            @foreach ($member->social_links as $link)
                <a class="small text-decoration-none" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-{{ $link['icon'] }} me-1" aria-hidden="true"></i>{{ $link['label'] }}</a>
            @endforeach
        </div>
    @endif
</article>
