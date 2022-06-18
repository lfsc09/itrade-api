<?php

namespace src;

function slimContainerConfig(): \Slim\Container
{
    $config = [
        'settings' => [
            'displayErrorDetails' => getenv('DISPLAY_ERRORS_DETAILS')
        ]
    ];
    return new \Slim\Container($config);
}
