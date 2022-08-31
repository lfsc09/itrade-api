<?php

namespace App\Controllers;

use App\DAO\MySQL\GerenciamentoDAO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;

final class GerenciamentoController
{
    public function list_suggest(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getQueryParams();
        $treated_data = [
            'place' => $fetched_data['place'] ?? NULL,
            'filters' => $fetched_data['filters'] ?? [],
            'id_usuario' => $id_usuario
        ];
        $gerenciamentoDAO = new GerenciamentoDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $suggestion ] = $gerenciamentoDAO->list_suggestion(...$treated_data);
        if ($status === 1)
            $response = $response->withJson(['suggestion' => $suggestion], 200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function list_datagrid(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getParsedBody();
        $treated_data = [
            'page' => $fetched_data['page'] ?? 0,
            'pageSize' => $fetched_data['pageSize'] ?? 0,
            'filters' => $fetched_data['filters'] ?? [],
            'sorting' => $fetched_data['sorting'] ?? [],
            'id_usuario' => $id_usuario
        ];
        $gerenciamentoDAO = new GerenciamentoDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $datagrid ] = $gerenciamentoDAO->list_datagrid(...$treated_data);
        if ($status === 1)
            $response = $response->withJson(['datagrid' => $datagrid], 200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function list_edit(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_gerenciamento = $request->getAttribute('route')->getArgument('id_gerenciamento');
        $gerenciamentoDAO = new GerenciamentoDAO();
        [ 'data' => $gerenciamento ] = $gerenciamentoDAO->list_edit($id_gerenciamento, $id_usuario);
        $response = $response->withJson(['gerenciamento' => $gerenciamento], 200);
        return $response;
    }

    public function new_gerenciamento(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getParsedBody();
        $gerenciamentoDAO = new GerenciamentoDAO();
        [ 'status' => $status, 'error' => $error ] = $gerenciamentoDAO->new_gerenciamento($fetched_data, $id_usuario);
        if ($status === 1)
            $response = $response->withStatus(200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function edit_gerenciamento(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_gerenciamento = $request->getAttribute('route')->getArgument('id_gerenciamento');
        $fetched_data = $request->getParsedBody();
        $gerenciamentoDAO = new GerenciamentoDAO();
        [ 'status' => $status, 'error' => $error ] = $gerenciamentoDAO->edit_gerenciamento($fetched_data, $id_gerenciamento, $id_usuario);
        if ($status === 1){
            [ 'data' => $gerenciamento ] = $gerenciamentoDAO->list_edit($id_gerenciamento, $id_usuario);
            $response = $response->withJson(['gerenciamento' => $gerenciamento], 200);
        }
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function delete_gerenciamento(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_gerenciamento = $request->getAttribute('route')->getArgument('id_gerenciamento');
        $gerenciamentoDAO = new GerenciamentoDAO();
        [ 'status' => $status, 'error' => $error ] = $gerenciamentoDAO->delete_gerenciamento($id_gerenciamento, $id_usuario);
        if ($status === 1)
            $response = $response->withStatus(200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }
}
