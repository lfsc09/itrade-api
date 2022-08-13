<?php

use function src\slimContainerConfig;
use App\Controllers\{ AuthController, UsuarioController, DatasetController };
use Tuupola\Middleware\JwtAuthentication;

$app = new \Slim\App(slimContainerConfig());

/**
 * ROTA DE LOGIN
 * 
 */
$app->post('/auth', AuthController::class . ':authenticate');

/**
 * ROTAS DE USUARIOS
 * 
 */
$app->group('', function () use ($app) {
    $app->post('/usuario/novo', UsuarioController::class . ':new_usuario');
})
->add(new JwtAuthentication([
    'secret' => getenv('JWT_SECRET_KEY'),
    'secure' => getenv('JWT_SECURE')
]));

/**
 * ROTAS DE DATASETS
 * 
 */
$app->group('', function () use ($app) {
    $app->post('/dataset/list_datagrid', DatasetController::class . ':list_datagrid');
    $app->get('/dataset/list_novo', DatasetController::class . ':list_new');
    /**
     * TODO: Fazer Middleware para permissões
     */
    $app->get('/dataset/list_edita/{id_dataset}', DatasetController::class . ':list_edit');
    $app->post('/dataset/novo', DatasetController::class . ':new_dataset');
    $app->put('/dataset/edita/{id_dataset}', DatasetController::class . ':edit_dataset');
    $app->delete('/dataset/deleta/{id_dataset}', DatasetController::class . ':delete_dataset');
})
->add(new JwtAuthentication([
    'secret' => getenv('JWT_SECRET_KEY'),
    'secure' => getenv('JWT_SECURE')
]));

$app->run();
