<?php

namespace App\Controllers;

use App\DAO\MySQL\DashboardDAO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;

final class DashboardController
{
    /**
     * Step 1, retorna os Datasets que o usuario tem acesso (Utilizados nos Filtros)
     */
    public function step1(Request $request, Response $response, array $args)
    {
        $id_usuario = $request->getAttribute('token')['id'];
        $dashboardDAO = new DashboardDAO();
        // Datasets que o usuario tem acesso
        [ 'data' => $datasets ] = $dashboardDAO->list__dataset($id_usuario);
        $response = $response->withJson(['datasets' => $datasets], 200);
        return $response;
    }
}
