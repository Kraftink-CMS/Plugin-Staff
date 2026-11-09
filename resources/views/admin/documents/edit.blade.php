@extends('admin.layouts.layout')
@section('title', $document ? $document->title : __('staff::messages.common.create'))
@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('plugins.staff.admin.documents.index') }}">
        {{ __('staff::messages.common.editorial') }}</a>
    @if ($document)
        <a class="btn btn-outline-secondary"
            href="{{ route('plugins.staff.admin.documents.revisions.index', $document->id) }}">{{ __('staff::messages.common.revisions') }}</a>
    @endif
@endsection
@section('content')
    @php
        $value = fn($key, $fallback = null) => old($key, $document?->{$key} ?? $fallback);
    @endphp
    <form data-ajax="true" @unless ($document) data-redirect-url="response" @endunless method="post" action="{{ $document ? route('plugins.staff.admin.documents.update', $document->id) : route('plugins.staff.admin.documents.store') }}">
        @csrf
        @if ($document)
            @method('PUT')
        @endif
        <div class="row g-4">
            <div class="col-lg-8">
                <section class="card h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="staff_title">{{ __('staff::messages.common.title') }}</label>
                            <input class="form-control" id="staff_title" name="title" value="{{ $value('title') }}"
                                maxlength="180" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="staff_slug">{{ __('staff::messages.directory.address') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">/staff/</span>
                                <input class="form-control" id="staff_slug" name="slug" value="{{ $value('slug') }}"
                                    pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="180" required>
                            </div>
                        </div>
                        <div>
                            <label class="form-label" for="staff_body">{{ __('staff::messages.common.body') }}</label>
                            <textarea class="form-control" id="staff_body" name="body" rows="18" data-editor="tinymce">{{ $value('body') }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-lg-4">
                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('staff::messages.directory.publication') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="staff_locale">{{ __('staff::messages.common.language') }}</label>
                            <select class="form-select" id="staff_locale" name="locale">
                                @foreach (config('kraft.locales') as $code => $label)
                                    <option value="{{ $code }}" @selected($value('locale', app()->getLocale()) === $code)>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="staff_category">{{ __('staff::messages.common.category') }}</label>
                            <input class="form-control" id="staff_category" name="category"
                                value="{{ $value('category') }}" maxlength="120">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="staff_position">{{ __('staff::messages.common.position') }}</label>
                            <input class="form-control" id="staff_position" name="position" type="number" min="0"
                                max="100000" value="{{ $value('position', 0) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="staff_permission">{{ __('staff::messages.common.permission') }}</label>
                            <input class="form-control" id="staff_permission" name="permission"
                                value="{{ $value('permission') }}" maxlength="100" pattern="[A-Za-z0-9_.-]+">
                        </div>
                        <input type="hidden" name="published" value="0">
                        <div class="form-check">
                            <input class="form-check-input" id="staff_published" name="published" type="checkbox"
                                value="1" @checked($value('published', false))>
                            <label class="form-check-label"
                                for="staff_published">{{ __('staff::messages.common.published') }}</label>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary" type="submit">{{ __('staff::messages.common.save') }}</button>
                    </div>
                </section>
            </div>
        </div>
    </form>
@endsection
