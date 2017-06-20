<?php


return [
    'endpoint' => [
        'localhost' => [
            'host' => env('SOLR_HOST', '192.168.3.190'),
            'port' => env('SOLR_PORT', '8983'),
            'path' => env('SOLR_PATH', '/solr/'),
            'core' => env('SOLR_CORE', 'new_core'),
        ]
    ]
];
