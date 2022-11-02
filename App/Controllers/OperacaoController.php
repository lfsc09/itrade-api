<?php

namespace App\Controllers;

use App\DAO\MySQL\OperacaoDAO;
use App\DAO\MySQL\AtivoDAO;
use App\DAO\MySQL\CenarioDAO;
use App\DAO\MySQL\GerenciamentoDAO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;

final class OperacaoController
{
    public function load_datasets__info(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getParsedBody();
        $treated_data = [
            'id_dataset' => $fetched_data['id_dataset'] ?? NULL,
            'id_usuario' => $id_usuario
        ];
        $ativoDAO = new AtivoDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $ativos ] = $ativoDAO->list_data($treated_data['id_usuario']);
        if (count($ativos) === 0)
            return $response->withStatus(500)->write('Deve ter Ativos cadastrados');
        $gerenciamentoDAO = new GerenciamentoDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $gerenciamentos ] = $gerenciamentoDAO->list_data($treated_data['id_usuario']);
        if (count($gerenciamentos) === 0)
            return $response->withStatus(500)->write('Deve ter Gerenciamentos cadastrados');
        $cenarioDAO = new CenarioDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $cenarios ] = $cenarioDAO->list_datarows(...$treated_data);
        if (count($cenarios) === 0)
            return $response->withStatus(500)->write('Deve ter Cenários cadastrados');

        $response = $response->withJson(['ativos' => $ativos, 'gerenciamentos' => $gerenciamentos, 'cenarios' => $cenarios], 200);
        return $response;
    }
}