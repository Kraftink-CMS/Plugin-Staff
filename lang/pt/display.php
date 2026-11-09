<?php

return [
    'title' => 'Visualização da equipe',
    'description' => 'Personalize os cartões dos membros na página da equipe.',
    'fields' => [
        'layout' => 'Layout',
        'columns' => 'Colunas no computador',
        'alignment' => 'Alinhamento do texto',
        'avatar_size' => 'Tamanho dos avatares em pixels',
        'grouped' => 'Agrupar membros pelo primeiro cargo exibido',
        'show_biography' => 'Exibir biografias',
    ],
    'columns_help' => 'O layout usa menos colunas em telas menores.',
    'layout' => [
        'cards' => 'Cartões padrão',
        'compact' => 'Cartões compactos com avatares redondos',
        'wide' => 'Cartões horizontais',
        'carousel' => 'Carrossel',
    ],
    'alignment' => ['start' => 'Esquerda', 'center' => 'Centralizado', 'end' => 'Direita'],
    'other' => 'Outros membros',
    'carousel_label' => 'Carrossel dos membros da equipe',
    'previous' => 'Perfis anteriores',
    'next' => 'Próximos perfis',
];
