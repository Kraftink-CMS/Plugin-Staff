<?php

return [
    'title' => 'Team display',
    'description' => 'Customize the member cards on the team page.',
    'fields' => [
        'layout' => 'Layout',
        'columns' => 'Desktop columns',
        'alignment' => 'Text alignment',
        'avatar_size' => 'Avatar size in pixels',
        'grouped' => 'Group members by their first displayed rank',
        'show_biography' => 'Show biographies',
    ],
    'columns_help' => 'The layout uses fewer columns on smaller screens.',
    'layout' => [
        'cards' => 'Standard cards',
        'compact' => 'Compact cards with round avatars',
        'wide' => 'Horizontal cards',
        'carousel' => 'Carousel',
    ],
    'alignment' => ['start' => 'Left', 'center' => 'Center', 'end' => 'Right'],
    'other' => 'Other members',
    'carousel_label' => 'Team member carousel',
    'previous' => 'Previous profiles',
    'next' => 'Next profiles',
];
