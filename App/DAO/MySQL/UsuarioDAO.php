<?php

namespace App\DAO\MySQL;

class UsuarioDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Listar Usuários.
     */
    public function list()
    {
        $usuarios = $this->pdo->query('SELECT * FROM usuario')->fetchAll(\PDO::FETCH_ASSOC);
        return $usuarios;
    }

    /**
     * Busca o Usuário pela coluna $usuario.
     * 
     */
    public function auth_usuario(string $usuario)
    {
        $statement = $this->pdo->prepare('SELECT id,usuario,nome,senha FROM usuario WHERE usuario = :usuario');
        $statement->bindParam(':usuario', $usuario);
        $statement->execute();
        $usuario = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $usuario[0] ?? NULL;
    }

    public function new_usuario($data)
    {
        return password_hash($data, PASSWORD_ARGON2I);
    }
}
