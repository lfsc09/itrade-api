<?php

namespace App\Controllers;

use App\DAO\MySQL\UsuarioDAO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;

final class UsuarioController
{
    public function list(Request $request, Response $response, array $args)
    {
        $usuarioDAO = new UsuarioDAO();
        $usuarios = $usuarioDAO->list();
        $response = $response->withJson(['data' => $usuarios], 200);
        return $response;
    }

    public function new_usuario(Request $request, Response $response, array $args)
    {
        $fetched_data = $request->getParsedBody();
        $usuarioDAO = new UsuarioDAO();
        $novo_password = $usuarioDAO->new_usuario($fetched_data['password']);
        $response = $response->withJson(['data' => $novo_password], 200);
        return $response;
    }
}
