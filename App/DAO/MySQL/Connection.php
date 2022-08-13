<?php

namespace App\DAO\MySQL;

abstract class Connection {
    /**
     * @var \PDO
     */
    protected $pdo;

    public function __construct() {
        $host = getenv('DB_HOSTNAME');
        $port = getenv('DB_PORT');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $dbname = getenv('DB_NAME');

        $this->pdo = new \PDO("mysql:host={$host};dbname={$dbname};port={$port}", $user, $pass);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Retorna o Type da variavel @var value para usar no \PDO::statement->bindValue
     */
    public function bindValue_Type($value){
        if (is_int($value))
            return \PDO::PARAM_INT;
        else if (is_bool($value))
            return \PDO::PARAM_BOOL;
        else if (is_null($value))
            return \PDO::PARAM_NULL;
        else
            return \PDO::PARAM_STR;
    }
}
