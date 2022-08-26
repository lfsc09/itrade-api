<?php

namespace App\DAO\MySQL;

class UsuarioDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Verifica e retorna os dados que serão usados na autenticação do Usuário
     * 
     * @param array fetched_data = [
     *      'usuario'  => @var string Usuário do Sistema
     *      'password' => @var string Senha do Usuário
     * ]
     */
    private function auth_usuario__fetchedData($fetched_data = [])
    {
        // Itens Obrigatórios
        if (empty($fetched_data))
            return ['status' => 0, 'error' => 'Sem dados passados', 'treated_data' => NULL];
        if (!isset($fetched_data['usuario']) || $fetched_data['usuario'] === '')
            return ['status' => 0, 'error' => 'Usuário é obrigatório', 'treated_data' => NULL];
        if (!isset($fetched_data['password']) || $fetched_data['password'] === '')
            return ['status' => 0, 'error' => 'Senha é obrigatória', 'treated_data' => NULL];

        // Trata dados
        $treated_data = [
            'usuario' => $fetched_data['usuario'],
            'password' => $fetched_data['password']
        ];

        return ['status' => 1, 'error' => '', 'treated_data' => $treated_data];
    }

    /**
     * Retorna os dados do usuario passado para fins de autenticação e criação do Token.
     */
    public function auth_usuario($fetched_data = [])
    {
        [
            'status' => $status,
            'error' => $error,
            'treated_data' => [
                'usuario' => $usuario,
                'password' => $password
            ]
        ] = $this->auth_usuario__fetchedData($fetched_data);

        if ($status === 0)
            return ['status' => 0, 'error' => $error, 'data' => NULL];
        
        $statement = $this->pdo->prepare('SELECT id,usuario,nome,senha FROM usuario WHERE usuario = :usuario');
        $statement->bindValue(':usuario', $usuario, $this->bindValue_Type($usuario));
        $statement->execute();
        $usuario = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($usuario === false || $password !== $usuario['senha'])
            return ['status' => 0, 'error' => 'Credenciais Inexistentes', 'data' => NULL];

        return ['status' => 1, 'error' => '', 'data' => $usuario];
    }

}
