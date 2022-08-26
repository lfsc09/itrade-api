<?php

namespace App\DAO\MySQL;

use PDOException;

class TokenDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Busca um Token válido do Usuário.
     * 
     */
    public function search_token($id_usuario = -1)
    {
        $statement = $this->pdo->prepare('SELECT expira_em,token FROM token WHERE id_usuario = :id_usuario AND expira_em >= NOW()');
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $token = $statement->fetch(\PDO::FETCH_ASSOC);
        return ['data' => $token ?: NULL];
    }

    /**
     * Guarda no DB o novo Token gerado do Usuário.
     * 
     */
    public function new_token($fetched_data = [])
    {
        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare('INSERT INTO token (criado_em,expira_em,token,id_usuario,ip) VALUES (:criado_em,:expira_em,:token,:id_usuario,:ip)');
            $statement->bindValue(':criado_em', $fetched_data['criado_em']);
            $statement->bindValue(':expira_em', $fetched_data['expira_em']);
            $statement->bindValue(':token', $fetched_data['token']);
            $statement->bindValue(':id_usuario', $fetched_data['id_usuario']);
            $statement->bindValue(':ip', $fetched_data['ip']);
            $statement->execute();

            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (\PDOException $exception) {
            $this->pdo->rollBack();
            return ['status' => 0, 'error' => 'Erro ao gerar um novo Token'];
        }
    }
}
