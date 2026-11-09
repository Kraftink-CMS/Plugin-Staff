@use('App\Kraft\SiteDates')
@extends('default.layouts.layout')
@section('content')
    <nav class="page-heading mb-4">
        <a class="text-decoration-none" href="{{ route('plugins.staff.public.documents.index') }}"><i
                class="bi bi-arrow-left me-2" aria-hidden="true"></i>{{ __('staff::messages.common.' . $plugin) }}</a>
    </nav>
    <article class="card card-body p-4 p-lg-5 mb-4">
        <h1 class="h2 mb-4">{{ $document->title }}</h1>
        <div class="prose">
            {!! $document->body !!}
        </div>
        <p class="small text-body-secondary border-top pt-3 mt-4 mb-0"><small>{{ __('staff::messages.common.updated') }}
                {{ app(SiteDates::class)->format($document->updated_at, 'd F Y') }}</small></p>
    </article>
@endsection
