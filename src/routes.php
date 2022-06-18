<?php

use function src\slimContainerConfig;
use App\Controllers\{ AuthController, UsuarioController };
use Tuupola\Middleware\JwtAuthentication;

$app = new \Slim\App(slimContainerConfig());

/**
 * ROTA DE LOGIN
 * 
 */
$app->post('/login', AuthController::class . ':login');

/**
 * ROTAS DE USUARIOS
 * 
 */
$app->group('', function () use ($app) {
    $app->get('/usuario', UsuarioController::class . ':list');
    $app->post('/novo_usuario', UsuarioController::class . ':new_usuario');
})
->add(new JwtAuthentication([
    'secret' => getenv('JWT_SECRET_KEY'),
    'secure' => getenv('JWT_SECURE'),
    'attribute' => 'jwt'
]));

$app->run();
