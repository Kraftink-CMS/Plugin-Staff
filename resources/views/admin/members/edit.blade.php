@extends('admin.layouts.layout')
@use('KraftInk\Plugins\Staff\Models\Member')
@section('title', $member ? __('staff::messages.directory.edit_member') . ' · ' . $member->name :
    __('staff::messages.common.add_member'))
@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('plugins.staff.admin.members.index') }}">
        {{ __('staff::messages.common.directory') }}</a>
@endsection
@section('content')
    @php
        $value = function ($field, $fallback = null) use ($member) {
            $fallback = $member?->{$field} ?? $fallback;
            $input = old($field, $fallback);

            return $input === null || is_scalar($input) ? $input : $fallback;
        };
        $badgeIds = old('badge_ids', old('badges_present') ? [] : ($member?->badges->modelKeys() ?? []));
        $badgeIds = is_array($badgeIds) ? array_map('intval', array_filter($badgeIds, 'is_numeric')) : [];
        $links = old('social_links', old('social_links_present') ? [] : ($member?->social_links ?? []));
        $links = is_array($links) ? array_values(array_filter(array_slice($links, 0, Member::MAX_SOCIAL_LINKS), 'is_array')) : [];
    @endphp
    <form class="vstack gap-4" data-staff-profile data-ajax="true" @unless ($member) data-redirect-url="response" @endunless method="post" action="{{ $member ? route('plugins.staff.admin.members.update', $member->id) : route('plugins.staff.admin.members.store') }}">
        @csrf
        @if ($member)
            @method('PUT')
        @endif
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">{{ __('staff::messages.directory.identity') }}</h2>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="member_name">{{ __('staff::messages.common.member_name') }}</label>
                        <input class="form-control" id="member_name" name="name" value="{{ $value('name') }}"
                            maxlength="120" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="member_title">{{ __('staff::messages.common.member_title') }}</label>
                        <input class="form-control" id="member_title" name="title" value="{{ $value('title') }}"
                            maxlength="120" required>
                    </div>
                </div>
                <div class="mb-4" data-staff-account>
                    <label class="form-label" for="account_search">{{ __('staff::messages.common.find_account') }}</label>
                    <div class="input-group mb-2">
                        <input class="form-control" id="account_search" type="search" maxlength="100" data-account-query>
                        <button class="btn btn-outline-secondary" type="button" data-account-search
                            data-url="{{ url()->current() }}"
                            data-error="{{ __('staff::messages.directory.search_error') }}">{{ __('common.actions.search') }}</button>
                    </div>
                    <label class="form-label" for="member_user">{{ __('staff::messages.common.linked_account') }}</label>
                    <select class="form-select" id="member_user" name="user_id" data-account-results>
                        <option value="">{{ __('staff::messages.directory.no_account') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) $value('user_id') === $user->id)>{{ $user->username }}
                                (#{{ $user->id }})
                            </option>
                        @endforeach
                    </select>
                    <p class="small text-danger mb-0" role="status" data-account-feedback></p>
                    <div class="form-text">
                        {{ __('staff::messages.directory.account_help') }}
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="member_bio">{{ __('staff::messages.common.biography') }}</label>
                    <textarea class="form-control" id="member_bio" name="biography" rows="12" maxlength="10000" data-editor="tinymce">{{ $value('biography') }}</textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-admin::media-picker name="avatar" :value="$member?->avatar" context="staff-image"
                            value-mode="url" id="member_avatar" :url-max-length="2000"
                            :label="__('staff::messages.common.avatar')" :help="__('staff::messages.directory.avatar_help')" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="member_website">{{ __('staff::messages.common.website') }}</label>
                        <input class="form-control" id="member_website" name="website" type="url"
                            value="{{ $value('website') }}" maxlength="2000" pattern="https://.*">
                    </div>
                </div>
                <div class="row g-3 mt-2 border-top pt-3">
                    <div class="col-md-4">
                        <label class="form-label" for="member_locale">{{ __('staff::messages.common.language') }}</label>
                        <select class="form-select" id="member_locale" name="locale" data-profile-locale>
                            @foreach (config('kraft.locales') as $code => $label)
                                <option value="{{ $code }}" @selected($value('locale', app()->getLocale()) === $code)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="member_position">{{ __('staff::messages.common.position') }}</label>
                        <input class="form-control" id="member_position" name="position" type="number" min="0"
                            max="2147483647" value="{{ $value('position', 0) }}" required>
                    </div>
                    <div class="col-md-4">
                        <input type="hidden" name="published" value="0">
                        <div class="form-check mt-md-4">
                            <input class="form-check-input" id="member_published" name="published" type="checkbox"
                                value="1" @checked($value('published', false))>
                            <label class="form-check-label"
                                for="member_published">{{ __('staff::messages.common.published') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">{{ __('staff::profiles.profile_details') }}</h2>
            </div>
            <div class="card-body">
                <input type="hidden" name="badges_present" value="1">
                <fieldset class="mb-4">
                    <legend class="h6">{{ __('staff::profiles.badges') }}</legend>
                    <p class="form-text">{{ __('staff::profiles.member_badges_help') }}</p>
                    <p class="small text-body-secondary mb-0" data-badge-empty @if ($badges->isNotEmpty()) hidden @endif>{{ __('staff::profiles.no_badges_for_locale') }}</p>
                    @foreach ($badges->groupBy('locale') as $locale => $choices)
                        <div data-badge-locale="{{ $locale }}" class="mb-3">
                            <p class="small text-body-secondary mb-1">{{ config('kraft.locales.' . $locale, $locale) }}</p>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ($choices as $badge)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="badge_ids[]" id="staff-badge-{{ $badge->id }}" value="{{ $badge->id }}" @checked(in_array($badge->id, $badgeIds, true))>
                                        <label class="form-check-label badge" for="staff-badge-{{ $badge->id }}" style="background-color: {{ $badge->backgroundColor() }}; color: {{ $badge->foregroundColor() }}">{{ $badge->title }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </fieldset>
                <fieldset data-social-links data-max-links="{{ Member::MAX_SOCIAL_LINKS }}">
                    <legend class="h6">{{ __('staff::profiles.social_links') }}</legend>
                    <input type="hidden" name="social_links_present" value="1">
                    <div class="vstack gap-3 mb-3" data-social-rows>
                        @foreach ($links as $index => $link)
                            @include('staff::admin.members.partials.social-link', ['link' => $link, 'index' => $index])
                        @endforeach
                    </div>
                    <template data-social-template>
                        @include('staff::admin.members.partials.social-link', ['link' => [], 'index' => null])
                    </template>
                    <button class="btn btn-outline-secondary" type="button" data-social-add disabled>
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('staff::profiles.add_link') }}
                    </button>
                    <p class="visually-hidden" role="status" data-social-status="{{ __('staff::profiles.link_position') }}"></p>
                </fieldset>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary" type="submit">{{ __('staff::messages.common.save') }}</button>
            </div>
        </section>
    </form>
    @push('scripts')
        <script type="module" src="{{ route('site.extensions.assets.show', ['id' => 'staff', 'asset' => 'editor.js']) }}"></script>
        <script type="module" src="{{ route('site.extensions.assets.show', ['id' => 'staff', 'asset' => 'profiles.js']) }}"></script>
    @endpush
@endsection
