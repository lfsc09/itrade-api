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

    public function list_edit(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_cenario = $request->getAttribute('route')->getArgument('id_cenario');
        $cenarioDAO = new CenarioDAO();
        [ 'data' => $cenario ] = $cenarioDAO->list_edit($id_cenario, $id_usuario);
        $response = $response->withJson(['cenario' => $cenario], 200);
        return $response;
    }

    public function new_cenario(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getParsedBody();
        $cenarioDAO = new CenarioDAO();
        [ 'status' => $status, 'error' => $error ] = $cenarioDAO->new_cenario($fetched_data, $id_usuario);
        if ($status === 1)
            $response = $response->withStatus(200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function edit_cenario(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_cenario = $request->getAttribute('route')->getArgument('id_cenario');
        $fetched_data = $request->getParsedBody();
        $cenarioDAO = new CenarioDAO();
        [ 'status' => $status, 'error' => $error ] = $cenarioDAO->edit_cenario($fetched_data, $id_cenario, $id_usuario);
        if ($status === 1){
            [ 'data' => $cenario ] = $cenarioDAO->list_edit($id_cenario, $id_usuario);
            $response = $response->withJson(['cenario' => $cenario], 200);
        }
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function delete_cenario(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_cenario = $request->getAttribute('route')->getArgument('id_cenario');
        $cenarioDAO = new CenarioDAO();
        [ 'status' => $status, 'error' => $error ] = $cenarioDAO->delete_cenario($id_cenario, $id_usuario);
        if ($status === 1)
            $response = $response->withStatus(200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }
}
