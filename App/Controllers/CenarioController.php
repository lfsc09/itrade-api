<?php

namespace App\Controllers;

use App\DAO\MySQL\CenarioDAO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;

final class CenarioController
{
    public function list_suggest(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getQueryParams();
        $treated_data = [
            'place' => $fetched_data['place'] ?? NULL,
            'filters' => $fetched_data['filters'] ?? [],
            'id_dataset' => $fetched_data['id_dataset'] ?? NULL,
            'id_usuario' => $id_usuario
        ];
        $cenarioDAO = new CenarioDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $suggestion ] = $cenarioDAO->list_suggestion(...$treated_data);
        if ($status === 1)
            $response = $response->withJson(['suggestion' => $suggestion], 200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function list_datarows(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getParsedBody();
        $treated_data = [
            'id_dataset' => $fetched_data['id_dataset'] ?? NULL,
            'id_usuario' => $id_usuario
        ];
        $cenarioDAO = new CenarioDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $datarows ] = $cenarioDAO->list_datarows(...$treated_data);
        if ($status === 1)
            $response = $response->withJson(['datarows' => $datarows], 200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function manage_cenario(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getParsedBody();
        $cenarioDAO = new CenarioDAO();
        [ 'status' => $status, 'error' => $error ] = $cenarioDAO->manage_cenario($fetched_data, $id_usuario);
        if ($status === 1) {
            [ 'status' => $status, 'error' => $error, 'data' => $datarows ] = $cenarioDAO->list_datarows($fetched_data['id_dataset'], $id_usuario);
            if ($status === 1)
                $response = $response->withJson(['datarows' => $datarows], 200);
            else
                $response = $response->withStatus(500)->write($error);
        }
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }
}
