<?php

namespace App\Controllers;

use App\DAO\MySQL\TokenDAO;
use App\DAO\MySQL\UsuarioDAO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
use \Firebase\JWT\JWT;

final class AuthController
{
    public function login(Request $request, Response $response, array $args)
    {
        $fetched_data = $request->getParsedBody();

        //Busca o usuario e verifica a senha
        $usuarioDAO = new UsuarioDAO();
        $usuario = $usuarioDAO->auth_usuario($fetched_data['usuario']);

        if (is_null($usuario))
            return $response->withStatus(401);
            
        if (!password_verify($fetched_data['password'], $usuario['senha']))
            return $response->withStatus(401);

        $tokenDAO = new TokenDAO();
        
        //Busca um Token ativo do Usuário
        $tokenData = $tokenDAO->search_token($usuario['id']);

        //Se não tinha token ja ou se ele não é mais valido
        if (is_null($tokenData)){
            //Gera um novo Token e guarda
            $tokenData = [
                'criado_em' => (new \DateTime())->format('Y-m-d H:i:s'),
                'expira_em' => (new \DateTime())->modify('+1 day')->format('Y-m-d H:i:s'),
                'ip' => '',
                'id_usuario' => $usuario['id'],
                'user_agent' => ''
            ];
            $tokenPayload = [
                'id' => $tokenData['id_usuario'],
                'usuario' => $usuario['usuario'],
                'nome' => $usuario['nome'],
                'exp' => $tokenData['expira_em']
            ];
            $tokenData['token'] = JWT::encode($tokenPayload, getenv('JWT_SECRET_KEY'));
    
            $tokenDAO->new_token($tokenData);
        }

        $response = $response->withJson(['data' => $tokenData['token']], 200);
        return $response;
    }
}