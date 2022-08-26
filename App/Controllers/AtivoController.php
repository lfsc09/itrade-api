<?php

namespace App\Controllers;

use App\DAO\MySQL\AtivoDAO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;

final class AtivoController
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
        $ativoDAO = new AtivoDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $suggestion ] = $ativoDAO->list_suggestion(...$treated_data);
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
        $ativoDAO = new AtivoDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $datagrid ] = $ativoDAO->list_datagrid(...$treated_data);
        if ($status === 1)
            $response = $response->withJson(['datagrid' => $datagrid], 200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function list_edit(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_ativo = $request->getAttribute('route')->getArgument('id_ativo');
        $ativoDAO = new AtivoDAO();
        [ 'data' => $ativo ] = $ativoDAO->list_edit($id_ativo, $id_usuario);
        $response = $response->withJson(['ativo' => $ativo], 200);
        return $response;
    }

    public function new_ativo(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getParsedBody();
        $ativoDAO = new AtivoDAO();
        [ 'status' => $status, 'error' => $error ] = $ativoDAO->new_ativo($fetched_data, $id_usuario);
        if ($status === 1)
            $response = $response->withStatus(200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function edit_ativo(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_ativo = $request->getAttribute('route')->getArgument('id_ativo');
        $fetched_data = $request->getParsedBody();
        $ativoDAO = new AtivoDAO();
        [ 'status' => $status, 'error' => $error ] = $ativoDAO->edit_ativo($fetched_data, $id_ativo, $id_usuario);
        if ($status === 1){
            [ 'data' => $ativo ] = $ativoDAO->list_edit($id_ativo, $id_usuario);
            $response = $response->withJson(['ativo' => $ativo], 200);
        }
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function delete_ativo(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_ativo = $request->getAttribute('route')->getArgument('id_ativo');
        $ativoDAO = new AtivoDAO();
        [ 'status' => $status, 'error' => $error ] = $ativoDAO->delete_ativo($id_ativo, $id_usuario);
        if ($status === 1)
            $response = $response->withStatus(200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }
}
