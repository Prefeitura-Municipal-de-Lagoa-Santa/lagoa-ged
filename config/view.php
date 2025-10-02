<?php

return [
    // Caminhos onde o Laravel vai procurar as views
    'paths' => [
        resource_path('views'),
    ],

    // Caminho onde as views compiladas serão armazenadas
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        storage_path('framework/views')
    ),
];
