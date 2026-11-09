<?php

namespace KraftInk\Plugins\Staff\Services;

use App\Kraft\Settings;

class DisplaySettings
{
    public const array LAYOUTS = ['cards', 'compact', 'wide', 'carousel'];

    public const array ALIGNMENTS = ['start', 'center', 'end'];

    public const int MAX_COLUMNS = 6;

    public const int MIN_AVATAR_SIZE = 48;

    public const int MAX_AVATAR_SIZE = 240;

    private const string KEY = 'staff.display';

    private const array DEFAULTS = [
        'layout' => 'cards',
        'columns' => 3,
        'alignment' => 'start',
        'avatar_size' => 72,
        'grouped' => false,
        'show_biography' => true,
    ];

    public function __construct(private readonly Settings $settings)
    {
    }

    public function get(): array
    {
        $display = $this->settings->get(self::KEY);

        return self::normalize(is_array($display) ? $display : []);
    }

    public function save(array $display): void
    {
        $this->settings->set(self::KEY, self::normalize($display));
    }

    private static function normalize(array $display): array
    {
        $display = array_replace(self::DEFAULTS, $display);
        $columns = filter_var($display['columns'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => self::MAX_COLUMNS],
        ]);
        $avatarSize = filter_var($display['avatar_size'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MIN_AVATAR_SIZE, 'max_range' => self::MAX_AVATAR_SIZE],
        ]);

        return [
            'layout' => in_array($display['layout'], self::LAYOUTS, true) ? $display['layout'] : self::DEFAULTS['layout'],
            'columns' => $columns === false ? self::DEFAULTS['columns'] : $columns,
            'alignment' => in_array($display['alignment'], self::ALIGNMENTS, true)
                ? $display['alignment']
                : self::DEFAULTS['alignment'],
            'avatar_size' => $avatarSize === false ? self::DEFAULTS['avatar_size'] : $avatarSize,
            'grouped' => filter_var($display['grouped'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? self::DEFAULTS['grouped'],
            'show_biography' => filter_var($display['show_biography'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                ?? self::DEFAULTS['show_biography'],
        ];
    }
}
