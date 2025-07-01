<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Regras de Permissões e Proteções
    |--------------------------------------------------------------------------
    */

    /**
     * Lista de nomes de GRUPOS que são considerados protegidos.
     * Lemos uma string do .env e a convertemos em um array.
     */
    'protected_groups' => env('PROTECTED_GROUPS') ? explode(',', env('PROTECTED_GROUPS')) : [],

    /**
     * Lista de nomes de USUÁRIOS (username) que são considerados protegidos.
     */
    'protected_usernames' => env('PROTECTED_USERNAMES') ? explode(',', env('PROTECTED_USERNAMES')) : [],
];