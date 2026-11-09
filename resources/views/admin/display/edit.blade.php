@use('KraftInk\Plugins\Staff\Services\DisplaySettings')
@extends('admin.layouts.layout')
@section('title', __('staff::display.title'))
@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('plugins.staff.admin.members.index') }}">{{ __('staff::messages.common.directory') }}</a>
@endsection
@section('content')
    <form class="card" method="post" action="{{ route('plugins.staff.admin.display.update') }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            <p class="text-body-secondary">{{ __('staff::display.description') }}</p>
            <div class="row g-3">
                @foreach (['layout' => DisplaySettings::LAYOUTS, 'columns' => range(1, DisplaySettings::MAX_COLUMNS), 'alignment' => DisplaySettings::ALIGNMENTS] as $field => $options)
                    @php($selected = old($field, $display[$field]))
                    <div class="col-sm-6">
                        <label class="form-label" for="staff-display-{{ $field }}">{{ __('staff::display.fields.' . $field) }}</label>
                        <select class="form-select @error($field) is-invalid @enderror" id="staff-display-{{ $field }}" name="{{ $field }}"
                            @if ($field === 'columns') aria-describedby="staff-display-columns-help" @endif>
                            @foreach ($options as $option)
                                <option value="{{ $option }}" @selected(is_scalar($selected) && (string) $selected === (string) $option)>
                                    {{ is_int($option) ? $option : __('staff::display.' . $field . '.' . $option) }}
                                </option>
                            @endforeach
                        </select>
                        @if ($field === 'columns')
                            <div class="form-text" id="staff-display-columns-help">{{ __('staff::display.columns_help') }}</div>
                        @endif
                        @error($field)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
                <div class="col-sm-6">
                    @php($avatarSize = old('avatar_size', $display['avatar_size']))
                    <label class="form-label" for="staff-display-avatar-size">{{ __('staff::display.fields.avatar_size') }}</label>
                    <input class="form-control @error('avatar_size') is-invalid @enderror" id="staff-display-avatar-size"
                        name="avatar_size" type="number" min="{{ DisplaySettings::MIN_AVATAR_SIZE }}"
                        max="{{ DisplaySettings::MAX_AVATAR_SIZE }}" value="{{ is_scalar($avatarSize) ? $avatarSize : '' }}" required>
                    @error('avatar_size')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="mt-3">
                @foreach (['grouped', 'show_biography'] as $field)
                    <div class="form-check">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input class="form-check-input @error($field) is-invalid @enderror" type="checkbox"
                            id="staff-display-{{ $field }}" name="{{ $field }}" value="1"
                            @checked(old($field, $display[$field]))>
                        <label class="form-check-label" for="staff-display-{{ $field }}">{{ __('staff::display.fields.' . $field) }}</label>
                        @error($field)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary" type="submit">{{ __('staff::messages.common.save') }}</button>
        </div>
    </form>
@endsection
