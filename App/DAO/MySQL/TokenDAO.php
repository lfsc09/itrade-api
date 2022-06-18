<?php

namespace App\DAO\MySQL;

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
    public function search_token($id_usuario)
    {
        $statement = $this->pdo->prepare('SELECT expira_em,token FROM token WHERE id_usuario = :id_usuario AND expira_em >= NOW()');
        $statement->bindParam(':id_usuario', $id_usuario);
        $statement->execute();
        $token = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $token[0] ?? NULL;
    }

    /**
     * Guarda no DB o novo Token gerado do Usuário.
     * 
     */
    public function new_token($data)
    {
        $statement = $this->pdo->prepare('INSERT INTO token (criado_em,expira_em,token,id_usuario,ip,user_agent) VALUES (:criado_em,:expira_em,:token,:id_usuario,:ip,:user_agent)');
        $statement->bindParam(':criado_em', $data['criado_em']);
        $statement->bindParam(':expira_em', $data['expira_em']);
        $statement->bindParam(':token', $data['token']);
        $statement->bindParam(':id_usuario', $data['id_usuario']);
        $statement->bindParam(':ip', $data['ip']);
        $statement->bindParam(':user_agent', $data['user_agent']);
        $statement->execute();
    }
}
