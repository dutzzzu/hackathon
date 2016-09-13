<?php
return [
    'service_manager' => [
        'factories' => [],
    ],
    'router' => [
        'routes' => [
            'hackathon.rest.user' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/user[/:user_id]',
                    'defaults' => [
                        'controller' => 'hackathon\\V1\\Rest\\User\\Controller',
                    ],
                ],
            ],
        ],
    ],
    'zf-versioning' => [
        'uri' => [
            0 => 'hackathon.rest.user',
        ],
    ],
    'zf-rest' => [
        'hackathon\\V1\\Rest\\User\\Controller' => [
            'listener' => 'hackathon\\V1\\Rest\\User\\UserResource',
            'route_name' => 'hackathon.rest.user',
            'route_identifier_name' => 'user_id',
            'collection_name' => 'user',
            'entity_http_methods' => [
                0 => 'GET',
                1 => 'PATCH',
                2 => 'PUT',
                3 => 'DELETE',
            ],
            'collection_http_methods' => [
                0 => 'GET',
                1 => 'POST',
            ],
            'collection_query_whitelist' => [],
            'page_size' => 25,
            'page_size_param' => null,
            'entity_class' => \hackathon\V1\Rest\User\UserEntity::class,
            'collection_class' => \hackathon\V1\Rest\User\UserCollection::class,
            'service_name' => 'user',
        ],
    ],
    'zf-content-negotiation' => [
        'controllers' => [
            'hackathon\\V1\\Rest\\User\\Controller' => 'HalJson',
        ],
        'accept_whitelist' => [
            'hackathon\\V1\\Rest\\User\\Controller' => [
                0 => 'application/vnd.hackathon.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ],
        ],
        'content_type_whitelist' => [
            'hackathon\\V1\\Rest\\User\\Controller' => [
                0 => 'application/vnd.hackathon.v1+json',
                1 => 'application/json',
            ],
        ],
    ],
    'zf-hal' => [
        'metadata_map' => [
            \hackathon\V1\Rest\User\UserEntity::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'hackathon.rest.user',
                'route_identifier_name' => 'user_id',
                'hydrator' => \Zend\Hydrator\ArraySerializable::class,
            ],
            \hackathon\V1\Rest\User\UserCollection::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'hackathon.rest.user',
                'route_identifier_name' => 'user_id',
                'is_collection' => true,
            ],
        ],
    ],
    'zf-apigility' => [
        'db-connected' => [
            'hackathon\\V1\\Rest\\User\\UserResource' => [
                'adapter_name' => 'hackathon',
                'table_name' => 'user',
                'hydrator_name' => \Zend\Hydrator\ArraySerializable::class,
                'controller_service_name' => 'hackathon\\V1\\Rest\\User\\Controller',
                'entity_identifier_name' => 'id',
                'table_service' => 'hackathon\\V1\\Rest\\User\\UserResource\\Table',
            ],
        ],
    ],
];
