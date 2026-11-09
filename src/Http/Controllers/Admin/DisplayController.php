<?php

namespace KraftInk\Plugins\Staff\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use KraftInk\Plugins\Staff\Services\DisplaySettings;

class DisplayController
{
    public function edit(DisplaySettings $settings): View
    {
        return view('staff::admin.display.edit', ['display' => $settings->get()]);
    }

    public function update(Request $request, DisplaySettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'layout' => ['required', Rule::in(DisplaySettings::LAYOUTS)],
            'columns' => ['required', 'integer', 'between:1,'.DisplaySettings::MAX_COLUMNS],
            'alignment' => ['required', Rule::in(DisplaySettings::ALIGNMENTS)],
            'avatar_size' => ['required', 'integer', 'min:'.DisplaySettings::MIN_AVATAR_SIZE, 'max:'.DisplaySettings::MAX_AVATAR_SIZE],
            'grouped' => ['required', 'boolean'],
            'show_biography' => ['required', 'boolean'],
        ], [], __('staff::display.fields'));
        $settings->save($data);

        return redirect()->route('plugins.staff.admin.display.edit')->with('status', __('staff::messages.common.saved'));
    }
}
