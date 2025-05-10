<?php

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'L5 Swagger UI',
            ],

            'routes' => [
                'api' => 'api/documentation',
            ],

            'paths' => [
                'docs' => storage_path('api-docs'),
                'base' => env('L5_SWAGGER_BASE_PATH', '/api'),
                'annotations' => [
                    base_path('app/Http/Controllers'),
                ],
                'docs_json' => 'api-docs.json',  // ✅ Add this line
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
            ],
        ],
    ],

    'routes' => [
        'api' => 'api/documentation',
        'docs' => 'docs',
        'oauth2_callback' => 'api/oauth2-callback',
        'middleware' => [
            'api' => [],
            'asset' => [],
            'docs' => [],
            'oauth2_callback' => [],
        ],
    ],

    'securityDefinitions' => [
        'securitySchemes' => [
            'BearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
                'description' => 'Enter JWT token in the format: Bearer {token}'
            ],
        ],
        'security' => [
            [
                'BearerAuth' => []
            ]
        ],
    ],
];
