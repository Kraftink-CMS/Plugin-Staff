@use('KraftInk\Plugins\Staff\Models\Member')
@php($icon = is_string($link['icon'] ?? null) ? $link['icon'] : 'link-45deg')
<div class="row g-2 align-items-start" data-social-row>
    <label class="col-md-3">
        <span class="form-label d-block">{{ __('staff::profiles.link_label') }}</span>
        <input class="form-control" data-social-field="label" @if ($index !== null) name="social_links[{{ $index }}][label]" @endif value="{{ is_string($link['label'] ?? null) ? $link['label'] : '' }}" maxlength="80" required>
    </label>
    <label class="col-md">
        <span class="form-label d-block">{{ __('staff::profiles.link_url') }}</span>
        <input class="form-control" type="url" data-social-field="url" @if ($index !== null) name="social_links[{{ $index }}][url]" @endif value="{{ is_string($link['url'] ?? null) ? $link['url'] : '' }}" maxlength="2000" required>
    </label>
    @if (class_exists(\App\Kraft\ContentIcons::class))
        <div class="col-md-3">
            <span class="form-label d-block">{{ __('staff::profiles.link_icon') }}</span>
            <x-dynamic-component component="admin::icon-picker" :name="$index === null ? null : 'social_links[' . $index . '][icon]'"
                :value="$icon" :aria-label="__('staff::profiles.link_icon')" data-social-field="icon" required />
        </div>
    @else
        <label class="col-md-3">
            <span class="form-label d-block">{{ __('staff::profiles.link_icon') }}</span>
            <select class="form-select" data-social-field="icon" @if ($index !== null) name="social_links[{{ $index }}][icon]" @endif required>
                @if (!in_array($icon, Member::SOCIAL_ICONS, true))
                    <option value="{{ $icon }}" selected>{{ $icon }}</option>
                @endif
                @foreach (Member::SOCIAL_ICONS as $preset)
                    <option value="{{ $preset }}" @selected($icon === $preset)>{{ __('staff::profiles.icons.' . $preset) }}</option>
                @endforeach
            </select>
        </label>
    @endif
    <div class="col-md-auto">
        <div class="btn-group mt-md-4" role="group" aria-label="{{ __('common.labels.actions') }}">
            <button class="btn btn-outline-secondary" type="button" data-social-move="up" aria-label="{{ __('staff::profiles.move_link_up') }}" disabled>
                <i class="bi bi-arrow-up" aria-hidden="true"></i>
            </button>
            <button class="btn btn-outline-secondary" type="button" data-social-move="down" aria-label="{{ __('staff::profiles.move_link_down') }}" disabled>
                <i class="bi bi-arrow-down" aria-hidden="true"></i>
            </button>
            <button class="btn btn-outline-danger" type="button" data-social-remove aria-label="{{ __('staff::profiles.remove_link') }}" disabled>
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>
