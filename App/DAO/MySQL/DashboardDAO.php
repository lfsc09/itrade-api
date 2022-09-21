<?php

namespace App\DAO\MySQL;

use PDOException;

class DashboardDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retornar todos os datasets que o usuario tem acesso (Sendo criador ou não).
     * 
     * @param id_usuario : Id do usuario logado a fazer esta requisição
     */
    public function list__dataset($id_usuario)
    {
        $statement = $this->pdo->prepare('SELECT id,id_usuario_criador,nome FROM rv__dataset rvd INNER JOIN rv__dataset__usuario rvd_u ON rvd.id=rvd_u.id_dataset WHERE rvd_u.id_usuario = :id_usuario ORDER BY data_atualizacao DESC');
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $datasets = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return ['data' => $datasets];
    }
}
