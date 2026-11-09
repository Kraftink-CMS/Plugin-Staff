<?php

return [
    'title' => 'Affichage de l’équipe',
    'description' => 'Personnalisez les fiches des membres sur la page de l’équipe.',
    'fields' => [
        'layout' => 'Disposition',
        'columns' => 'Colonnes sur ordinateur',
        'alignment' => 'Alignement du texte',
        'avatar_size' => 'Taille des avatars en pixels',
        'grouped' => 'Regrouper les membres par leur premier rang affiché',
        'show_biography' => 'Afficher les biographies',
    ],
    'columns_help' => 'La disposition utilise moins de colonnes sur les petits écrans.',
    'layout' => [
        'cards' => 'Fiches standard',
        'compact' => 'Fiches compactes avec avatars ronds',
        'wide' => 'Fiches horizontales',
        'carousel' => 'Carrousel',
    ],
    'alignment' => ['start' => 'Gauche', 'center' => 'Centré', 'end' => 'Droite'],
    'other' => 'Autres membres',
    'carousel_label' => 'Carrousel des membres de l’équipe',
    'previous' => 'Profils précédents',
    'next' => 'Profils suivants',
];
