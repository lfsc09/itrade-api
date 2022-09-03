<?php

namespace App\Controllers;

use App\DAO\MySQL\DatasetDAO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;

final class DatasetController
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
        $datasetDAO = new DatasetDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $suggestion ] = $datasetDAO->list_suggestion(...$treated_data);
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
        $datasetDAO = new DatasetDAO();
        [ 'status' => $status, 'error' => $error, 'data' => $datagrid ] = $datasetDAO->list_datagrid(...$treated_data);
        if ($status === 1)
            $response = $response->withJson(['datagrid' => $datagrid], 200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function list_new(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $datasetDAO = new DatasetDAO();
        [ 'data' => $select_usuarios ] = $datasetDAO->list_edit__usuario($id_usuario);
        $response = $response->withJson(['dataset' => NULL, 'usuarios' => $select_usuarios], 200);
        return $response;
    }

    public function list_edit(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_dataset = $request->getAttribute('route')->getArgument('id_dataset');
        $datasetDAO = new DatasetDAO();
        [ 'data' => $dataset ] = $datasetDAO->list_edit($id_dataset, $id_usuario);
        [ 'data' => $select_usuarios ] = $datasetDAO->list_edit__usuario($id_usuario);
        $response = $response->withJson(['dataset' => $dataset, 'usuarios' => $select_usuarios], 200);
        return $response;
    }

    public function new_dataset(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $fetched_data = $request->getParsedBody();
        $datasetDAO = new DatasetDAO();
        [ 'status' => $status, 'error' => $error ] = $datasetDAO->new_dataset($fetched_data, $id_usuario);
        if ($status === 1)
            $response = $response->withStatus(200);
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }

    public function edit_dataset(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $id_dataset = $request->getAttribute('route')->getArgument('id_dataset');
        $fetched_data = $request->getParsedBody();
        $datasetDAO = new DatasetDAO();
        [ 'status' => $status, 'error' => $error ] = $datasetDAO->edit_dataset($fetched_data, $id_dataset, $id_usuario);
        if ($status === 1){
            [ 'data' => $dataset ] = $datasetDAO->list_edit($id_dataset, $id_usuario);
            $response = $response->withJson(['dataset' => $dataset], 200);
        }
        else
            $response = $response->withStatus(500)->write($error);
        return $response;
    }
}
