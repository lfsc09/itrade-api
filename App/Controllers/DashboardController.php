<?php

namespace App\Controllers;

use App\DAO\MySQL\DashboardDAO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;

final class DashboardController
{
    public function load_datasets(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $dashboardDAO = new DashboardDAO();
        // Datasets que o usuario tem acesso
        [ 'data' => $datasets ] = $dashboardDAO->list__dataset($id_usuario);
        $response = $response->withJson(['datasets' => $datasets], 200);
        return $response;
    }

    public function load_datasets__info(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getParsedBody();
        $treated_data = [
            'filters' => $fetched_data['filters'] ?? [],
            'id_usuario' => $id_usuario
        ];
        $dashboardDAO = new DashboardDAO();
        // Operações dos Datasets que o usuario tem acesso
        [ 'operacoes' => $operacoes, 'gerenciamentos' => $gerenciamentos, 'ativos' => $ativos, 'cenarios_encontrados' => $cenarios_encontrados, 'originalInfo' => $originalInfo ] = $dashboardDAO->list__operacoes(...$treated_data);
        $cenarios = $dashboardDAO->list__cenario($cenarios_encontrados, ...$treated_data);
        $response = $response->withJson(['operacoes' => $operacoes, 'ativos' => $ativos, 'gerenciamentos' => $gerenciamentos, 'originalInfo' => $originalInfo, 'cenarios' => $cenarios], 200);
        return $response;
    }
}
