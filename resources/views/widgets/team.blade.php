<section class="card h-100">
    <div class="card-body">
        <h2 class="h5 card-title mb-3">{{ __('staff::widgets.team') }}</h2>
        <ul class="list-unstyled mb-0">
            @forelse ($members as $member)
                <li class="mb-2">
                    <span class="fw-semibold">{{ $member->name }}</span>
                    <span class="text-body-secondary">{{ $member->title }}</span>
                </li>
            @empty
                <li class="text-body-secondary">{{ __('staff::widgets.empty') }}</li>
            @endforelse
        </ul>
    </div>
</section>
