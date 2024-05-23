<?php

use function src\slimContainerConfig;
use App\Controllers\{ AuthController, UsuarioController, DashboardController, DatasetController, AtivoController, GerenciamentoController, CenarioController, OperacaoController };
use Tuupola\Middleware\JwtAuthentication;
use Tuupola\Middleware\CorsMiddleware;

$app = new \Slim\App(slimContainerConfig());

$app->add(new Tuupola\Middleware\CorsMiddleware([
    "origin" => ["itrade-dongs.com.br", "192.168.*"],
    "methods" => ["OPTIONS", "GET", "POST", "PUT", "PATCH", "DELETE"],
    "headers.allow" => ["Content-Type", "Authorization"],
    "headers.expose" => [],
    "credentials" => false,
    "cache" => 86400,
]));

/****************
 * ROTA DE LOGIN
 ****************/
$app->post('/auth', AuthController::class . ':authenticate');

/********************
 * ROTAS DE USUARIOS
 ********************/
$app->group('', function () use ($app) {
    $app->post('/usuario/novo', UsuarioController::class . ':new_usuario');
})
->add(new JwtAuthentication([
    'secret' => getenv('JWT_SECRET_KEY'),
    'secure' => getenv('JWT_SECURE')
]));

/***************************************************************
 *                           DAYTRADE
 ***************************************************************/

/*********************
 * ROTAS DE DASHBOARD
 *********************/
$app->group('', function () use ($app) {
    $app->get('/dash/load_datasets', DashboardController::class . ':load_datasets');
    $app->post('/dash/load_datasets__info', DashboardController::class . ':load_datasets__info');
})
->add(new JwtAuthentication([
    'secret' => getenv('JWT_SECRET_KEY'),
    'secure' => getenv('JWT_SECURE')
]));

/********************
 * ROTAS DE DATASETS
 ********************/
$app->group('', function () use ($app) {
    $app->get('/dataset/list_suggest', DatasetController::class . ':list_suggest');
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

/******************
 * ROTAS DE ATIVOS
 ******************/
$app->group('', function () use ($app) {
    $app->get('/ativo/list_suggest', AtivoController::class . ':list_suggest');
    $app->post('/ativo/list_datagrid', AtivoController::class . ':list_datagrid');
    /**
     * TODO: Fazer Middleware para permissões
     */
    $app->get('/ativo/list_edita/{id_ativo}', AtivoController::class . ':list_edit');
    $app->post('/ativo/novo', AtivoController::class . ':new_ativo');
    $app->put('/ativo/edita/{id_ativo}', AtivoController::class . ':edit_ativo');
    $app->delete('/ativo/deleta/{id_ativo}', AtivoController::class . ':delete_ativo');
})
->add(new JwtAuthentication([
    'secret' => getenv('JWT_SECRET_KEY'),
    'secure' => getenv('JWT_SECURE')
]));

/**************************
 * ROTAS DE GERENCIAMENTOS
 **************************/
$app->group('', function () use ($app) {
    $app->get('/gerenciamento/list_suggest', GerenciamentoController::class . ':list_suggest');
    $app->post('/gerenciamento/list_datagrid', GerenciamentoController::class . ':list_datagrid');
    /**
     * TODO: Fazer Middleware para permissões
     */
    $app->get('/gerenciamento/list_edita/{id_gerenciamento}', GerenciamentoController::class . ':list_edit');
    $app->post('/gerenciamento/novo', GerenciamentoController::class . ':new_gerenciamento');
    $app->put('/gerenciamento/edita/{id_gerenciamento}', GerenciamentoController::class . ':edit_gerenciamento');
    $app->delete('/gerenciamento/deleta/{id_gerenciamento}', GerenciamentoController::class . ':delete_gerenciamento');
})
->add(new JwtAuthentication([
    'secret' => getenv('JWT_SECRET_KEY'),
    'secure' => getenv('JWT_SECURE')
]));

/********************
 * ROTAS DE CENARIOS
 ********************/
$app->group('', function () use ($app) {
    $app->get('/cenario/list_suggest', CenarioController::class . ':list_suggest');
    $app->post('/cenario/list_datarows', CenarioController::class . ':list_datarows');
    /**
     * TODO: Fazer Middleware para permissões
     */
    $app->post('/cenario/gerencia', CenarioController::class . ':manage_cenario');
})
->add(new JwtAuthentication([
    'secret' => getenv('JWT_SECRET_KEY'),
    'secure' => getenv('JWT_SECURE')
]));

/*********************
 * ROTAS DE OPERAÇÕES
 *********************/
$app->group('', function () use ($app) {
    $app->post('/operacao/load_datasets__info', OperacaoController::class . ':load_datasets__info');
    /**
     * TODO: Fazer Middleware para permissões
     */
    $app->post('/operacao/novo', OperacaoController::class . ':new_operacao');
    $app->delete('/operacao/deleta/{ids_operacao}', OperacaoController::class . ':delete_operacao');
})
->add(new JwtAuthentication([
    'secret' => getenv('JWT_SECRET_KEY'),
    'secure' => getenv('JWT_SECURE')
]));

$app->run();
