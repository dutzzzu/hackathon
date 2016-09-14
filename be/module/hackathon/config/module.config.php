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
            'hackathon.rest.subcategories' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/subcategories[/:subcategories_id]',
                    'defaults' => [
                        'controller' => 'hackathon\\V1\\Rest\\Subcategories\\Controller',
                    ],
                ],
            ],
            'hackathon.rest.places' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/places[/:places_id]',
                    'defaults' => [
                        'controller' => 'hackathon\\V1\\Rest\\Places\\Controller',
                    ],
                ],
            ],
            'hackathon.rest.user-selection' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/user_selection[/:user_selection_id]',
                    'defaults' => [
                        'controller' => 'hackathon\\V1\\Rest\\UserSelection\\Controller',
                    ],
                ],
            ],
        ],
    ],
    'zf-versioning' => [
        'uri' => [
            0 => 'hackathon.rest.user',
            2 => 'hackathon.rest.subcategories',
            4 => 'hackathon.rest.places',
            5 => 'hackathon.rest.user-selection',
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
        'hackathon\\V1\\Rest\\Subcategories\\Controller' => [
            'listener' => 'hackathon\\V1\\Rest\\Subcategories\\SubcategoriesResource',
            'route_name' => 'hackathon.rest.subcategories',
            'route_identifier_name' => 'subcategories_id',
            'collection_name' => 'subcategories',
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
            'entity_class' => \hackathon\V1\Rest\Subcategories\SubcategoriesEntity::class,
            'collection_class' => \hackathon\V1\Rest\Subcategories\SubcategoriesCollection::class,
            'service_name' => 'subcategories',
        ],
        'hackathon\\V1\\Rest\\Places\\Controller' => [
            'listener' => 'hackathon\\V1\\Rest\\Places\\PlacesResource',
            'route_name' => 'hackathon.rest.places',
            'route_identifier_name' => 'places_id',
            'collection_name' => 'places',
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
            'page_size' => '25',
            'page_size_param' => 'pagesizeparameter',
            'entity_class' => \hackathon\V1\Rest\Places\PlacesEntity::class,
            'collection_class' => \hackathon\V1\Rest\Places\PlacesCollection::class,
            'service_name' => 'places',
        ],
        'hackathon\\V1\\Rest\\UserSelection\\Controller' => [
            'listener' => 'hackathon\\V1\\Rest\\UserSelection\\UserSelectionResource',
            'route_name' => 'hackathon.rest.user-selection',
            'route_identifier_name' => 'user_selection_id',
            'collection_name' => 'user_selection',
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
            'entity_class' => \hackathon\V1\Rest\UserSelection\UserSelectionEntity::class,
            'collection_class' => \hackathon\V1\Rest\UserSelection\UserSelectionCollection::class,
            'service_name' => 'user_selection',
        ],
    ],
    'zf-content-negotiation' => [
        'controllers' => [
            'hackathon\\V1\\Rest\\User\\Controller' => 'HalJson',
            'hackathon\\V1\\Rest\\Subcategories\\Controller' => 'HalJson',
            'hackathon\\V1\\Rest\\Places\\Controller' => 'HalJson',
            'hackathon\\V1\\Rest\\UserSelection\\Controller' => 'HalJson',
        ],
        'accept_whitelist' => [
            'hackathon\\V1\\Rest\\User\\Controller' => [
                0 => 'application/vnd.hackathon.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ],
            'hackathon\\V1\\Rest\\Subcategories\\Controller' => [
                0 => 'application/vnd.hackathon.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ],
            'hackathon\\V1\\Rest\\Places\\Controller' => [
                0 => 'application/vnd.hackathon.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ],
            'hackathon\\V1\\Rest\\UserSelection\\Controller' => [
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
            'hackathon\\V1\\Rest\\Subcategories\\Controller' => [
                0 => 'application/vnd.hackathon.v1+json',
                1 => 'application/json',
            ],
            'hackathon\\V1\\Rest\\Places\\Controller' => [
                0 => 'application/vnd.hackathon.v1+json',
                1 => 'application/json',
            ],
            'hackathon\\V1\\Rest\\UserSelection\\Controller' => [
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
            \hackathon\V1\Rest\Subcategories\SubcategoriesEntity::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'hackathon.rest.subcategories',
                'route_identifier_name' => 'subcategories_id',
                'hydrator' => \Zend\Hydrator\ArraySerializable::class,
            ],
            \hackathon\V1\Rest\Subcategories\SubcategoriesCollection::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'hackathon.rest.subcategories',
                'route_identifier_name' => 'subcategories_id',
                'is_collection' => true,
            ],
            \hackathon\V1\Rest\Places\PlacesEntity::class => [
                'entity_identifier_name' => 'ID',
                'route_name' => 'hackathon.rest.places',
                'route_identifier_name' => 'places_id',
                'hydrator' => \Zend\Hydrator\ArraySerializable::class,
            ],
            \hackathon\V1\Rest\Places\PlacesCollection::class => [
                'entity_identifier_name' => 'ID',
                'route_name' => 'hackathon.rest.places',
                'route_identifier_name' => 'places_id',
                'is_collection' => true,
            ],
            \hackathon\V1\Rest\UserSelection\UserSelectionEntity::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'hackathon.rest.user-selection',
                'route_identifier_name' => 'user_selection_id',
                'hydrator' => \Zend\Hydrator\ArraySerializable::class,
            ],
            \hackathon\V1\Rest\UserSelection\UserSelectionCollection::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'hackathon.rest.user-selection',
                'route_identifier_name' => 'user_selection_id',
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
            'hackathon\\V1\\Rest\\Subcategories\\SubcategoriesResource' => [
                'adapter_name' => 'hackathon',
                'table_name' => 'subcategories',
                'hydrator_name' => \Zend\Hydrator\ArraySerializable::class,
                'controller_service_name' => 'hackathon\\V1\\Rest\\Subcategories\\Controller',
                'entity_identifier_name' => 'id',
            ],
            'hackathon\\V1\\Rest\\Places\\PlacesResource' => [
                'adapter_name' => 'hackathon',
                'table_name' => 'places',
                'hydrator_name' => \Zend\Hydrator\ArraySerializable::class,
                'controller_service_name' => 'hackathon\\V1\\Rest\\Places\\Controller',
                'entity_identifier_name' => 'ID',
                'table_service' => 'hackathon\\V1\\Rest\\Places\\PlacesResource\\Table',
            ],
            'hackathon\\V1\\Rest\\UserSelection\\UserSelectionResource' => [
                'adapter_name' => 'hackathon',
                'table_name' => 'user_selection',
                'hydrator_name' => \Zend\Hydrator\ArraySerializable::class,
                'controller_service_name' => 'hackathon\\V1\\Rest\\UserSelection\\Controller',
                'entity_identifier_name' => 'id',
            ],
        ],
    ],
    'zf-mvc-auth' => [
        'authorization' => [
            'hackathon\\V1\\Rest\\User\\Controller' => [
                'collection' => [
                    'GET' => false,
                    'POST' => false,
                    'PUT' => false,
                    'PATCH' => false,
                    'DELETE' => false,
                ],
                'entity' => [
                    'GET' => false,
                    'POST' => false,
                    'PUT' => false,
                    'PATCH' => false,
                    'DELETE' => false,
                ],
            ],
        ],
    ],
];
