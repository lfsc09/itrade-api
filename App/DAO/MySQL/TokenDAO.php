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
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $token = $statement->fetch(\PDO::FETCH_ASSOC);
        return ['data' => $token ?: NULL];
    }

    /**
     * Guarda no DB o novo Token gerado do Usuário.
     * 
     */
    public function new_token($data)
    {
        $statement = $this->pdo->prepare('INSERT INTO token (criado_em,expira_em,token,id_usuario,ip) VALUES (:criado_em,:expira_em,:token,:id_usuario,:ip)');
        $statement->bindValue(':criado_em', $data['criado_em']);
        $statement->bindValue(':expira_em', $data['expira_em']);
        $statement->bindValue(':token', $data['token']);
        $statement->bindValue(':id_usuario', $data['id_usuario']);
        $statement->bindValue(':ip', $data['ip']);
        $statement->execute();
    }
}
