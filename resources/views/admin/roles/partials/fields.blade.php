@use('KraftInk\Plugins\Staff\Models\RoleListing')
@php
    $roleModal = $listing ? 'staff-role-' . $listing->id : 'staff-role-new';
    $roleOld = old('_modal') === $roleModal;
    $roleValue = function ($key, $fallback = null) use ($roleOld, $listing) {
        $fallback = $listing?->{$key} ?? $fallback;
        $input = $roleOld ? old($key, $fallback) : $fallback;

        return $input === null || is_scalar($input) ? $input : $fallback;
    };
    $roleColor = $roleValue('color', RoleListing::DEFAULT_COLOR);
    if (!is_string($roleColor) || !preg_match(RoleListing::COLOR_PATTERN, $roleColor)) {
        $roleColor = RoleListing::DEFAULT_COLOR;
    }
@endphp
<div class="mb-3">
    <label class="form-label" for="{{ $roleModal }}-role">{{ __('staff::profiles.linked_rank') }}</label>
    <select class="form-select" id="{{ $roleModal }}-role" name="role_id">
        <option value="">{{ __('staff::profiles.no_linked_rank') }}</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" @selected((int) $roleValue('role_id') === $role->id)>{{ $role->name }}</option>
        @endforeach
    </select>
    <div class="form-text">{{ __('staff::profiles.linked_rank_help') }}</div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md">
        <label class="form-label" for="{{ $roleModal }}-badge-title">{{ __('staff::profiles.badge_name') }}</label>
        <input class="form-control" id="{{ $roleModal }}-badge-title" name="title" maxlength="120"
            value="{{ $roleValue('title') }}" required>
    </div>
    <div class="col-md-auto">
        <label class="form-label" for="{{ $roleModal }}-color">{{ __('staff::profiles.badge_color') }}</label>
        <input class="form-control form-control-color" id="{{ $roleModal }}-color" name="color" type="color" value="{{ $roleColor }}" required>
    </div>
</div>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="{{ $roleModal }}-locale">{{ __('staff::messages.common.language') }}</label>
        <select class="form-select" id="{{ $roleModal }}-locale" name="locale">
            @foreach (config('kraft.locales') as $code => $label)
                <option value="{{ $code }}" @selected($roleValue('locale', app()->getLocale()) === $code)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $roleModal }}-position">{{ __('staff::messages.common.position') }}</label>
        <input class="form-control" id="{{ $roleModal }}-position" name="position" type="number" min="0"
            max="2147483647" value="{{ $roleValue('position', 0) }}" required>
    </div>
</div>
