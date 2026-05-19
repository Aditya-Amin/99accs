<?php

return [

    'valorant' => [
        // Skin checker service endpoint — set KAWITOYS_ENDPOINT in .env
        // The exact POST path (e.g. /api/check) must be confirmed from the service docs.
        'checker_endpoint'    => env('KAWITOYS_ENDPOINT', 'https://kawitoys.shop/'),
        'checker_user_field'  => env('KAWITOYS_USER_FIELD', 'username'),
        'checker_pass_field'  => env('KAWITOYS_PASS_FIELD', 'password'),
        'checker_proxy_field' => env('KAWITOYS_PROXY_FIELD', 'proxy'),
        'checker_timeout'     => (int) env('KAWITOYS_TIMEOUT', 60),

        // Public catalog API — free, no auth required
        'api_base'  => env('VALORANT_API_BASE', 'https://valorant-api.com/v1'),
        'cache_ttl' => (int) env('GAME_API_CACHE_TTL', 86400), // 24 hours

        // Riot entitlement TypeID → item category
        // Update if kawitoys uses different UUIDs
        'type_ids' => [
            'skin_level'       => env('VALORANT_TYPE_SKIN_LEVEL',   'e7c63390-eda7-46e0-bb7a-a6abdacd2433'),
            'agent'            => env('VALORANT_TYPE_AGENT',         '01bb38e1-da47-4e6a-9b3d-945fe4655707'),
            'buddy_level'      => env('VALORANT_TYPE_BUDDY_LEVEL',   'dd3bf334-87f3-40bd-b043-682a57a8dc3a'),
            'buddy_equippable' => env('VALORANT_TYPE_BUDDY_EQ',      '4e60e748-bce6-4faa-9327-ebbe6089d5fe'),
            'spray'            => env('VALORANT_TYPE_SPRAY',         'd5f120f8-ff8c-4aac-92ea-f2b5acbe9475'),
            'player_card'      => env('VALORANT_TYPE_PLAYER_CARD',   '3f296c07-64c3-494c-923b-fe692a4fa1bd'),
            'player_title'     => env('VALORANT_TYPE_PLAYER_TITLE',  'de7caa6b-adf7-4588-bbd1-143831e786c6'),
        ],
    ],

    'fortnite' => [
        // Configured when Fortnite importer is built
    ],

    'legends' => [
        // Configured when Legends importer is built
    ],

];
