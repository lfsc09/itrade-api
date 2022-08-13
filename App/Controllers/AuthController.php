<?php

namespace App\Controllers;

use App\DAO\MySQL\TokenDAO;
use App\DAO\MySQL\UsuarioDAO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response as Response;
use Firebase\JWT\JWT;

final class AuthController
{
    public function authenticate(Request $request, Response $response, array $args)
    {
        $fetched_data = $request->getParsedBody();

        // Busca o usuario e verifica a senha
        $usuarioDAO = new UsuarioDAO();
        [ 'status' => $statusAuth, 'error' => $error, 'data' => $usuario ] = $usuarioDAO->auth_usuario($fetched_data);

        if ($statusAuth === 0)
            return $response->withStatus(401);

        $tokenDAO = new TokenDAO();

        // Busca um Token ativo do Usuário
        [ 'data' => $tokenData ] = $tokenDAO->search_token($usuario['id']);

        // Se não tinha token ja ou se ele não é mais valido
        if (is_null($tokenData)) {
            // Gera um novo Token e guarda
            $tokenData = [
                'criado_em' => (new \DateTime())->format('Y-m-d H:i:s'),
                'expira_em' => (new \DateTime())->modify('+1 day')->format('Y-m-d H:i:s'),
                'ip' => '',
                'id_usuario' => $usuario['id']
            ];
            $tokenPayload = [
                'id' => $tokenData['id_usuario'],
                'usuario' => $usuario['usuario'],
                'nome' => $usuario['nome'],
                'iat' => strtotime($tokenData['criado_em']),
                'exp' => strtotime($tokenData['expira_em'])
            ];
            $tokenData['token'] = JWT::encode($tokenPayload, getenv('JWT_SECRET_KEY'));

            $tokenDAO->new_token($tokenData);
        }

        $response = $response->withJson(['token' => $tokenData['token']], 200);
        return $response;
    }
}
