<?php

namespace App\DAO\MySQL;

class DatasetDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retorna a lista de Datasets
     */
    public function list()
    {
        $datasets = $this->pdo->query('SELECT * FROM rv__dataset')->fetchAll(\PDO::FETCH_ASSOC);
        return $datasets;
    }

    /**
     * Retornar dados dos Datasets para uso no DataGrid.
     * 
     * @param page       : Index da pagina a ser recuperada [(@param page * @param pageSize * 1) ... (@param pageSize)]
     * @param pageSize   : Quantidade de linhas a serem retornadas
     * @param filters    : Array com os filtros a serem aplicados como SEARCH
     * @param sorting    : Array com as colunas para fazer SORT
     * @param id_usuario : Id do usuario logado a fazer esta requisição
     */
    public function list_datagrid($page, $pageSize, $filters, $sorting, $id_usuario)
    {
        $offset = $page * $pageSize;

        /**
         * DATASET TABLE
         */
        $queryParams = [];
        $selectClause = ['rvd.*', 'crvo.qtd_ops'];

        // Prepara o SEARCHING
        $whereClause = [
            "rvd_u.id_usuario = {$id_usuario}"
        ];
        foreach ($filters as $col_name => $values){
            $colClause = [];
            if ($col_name === 'nome'){
                foreach ($values as $i => $value){
                    $colClause[] = "rvd.{$col_name} LIKE %:{$col_name}_{$i}%";
                    $queryParams[":{$col_name}_{$i}"] = $value;
                }
            }
            else{
                foreach ($values as $i => $value){
                    $colClause[] = "rvd.{$col_name} = :{$col_name}_{$i}";
                    $queryParams[":{$col_name}_{$i}"] = $value;
                }
            }
            $whereClause[] = '(' . implode(' OR ', $colClause) . ')';
        }
        $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
        
        // Capturar a quantidade total (Com os filtros aplicados)
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM rv__dataset rvd INNER JOIN rv__dataset__usuario rvd_u ON rvd.id=rvd_u.id_dataset {$whereSQL}");
        $statement->execute($queryParams);
        $total_rows = $statement->fetchColumn();

        // Prepara o SORTING
        $sortClause = [];
        foreach ($sorting as $column){
            if ($column['field'] === 'qtd_ops')
                $sortClause[] = "crvo.{$column['field']} {$column['sort']}";
            else
                $sortClause[] = "rvd.{$column['field']} {$column['sort']}";
        }
        $sortSQL = !empty($sortClause) ? 'ORDER BY ' . implode(', ', $sortClause) : '';

        // Capturar as rows para serem mostradas
        $queryParams[':pageSize'] = $pageSize;
        $queryParams[':offset'] = $offset;
        $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
        $selectSQL = implode(',', $selectClause);
        $statement = $this->pdo->prepare("SELECT {$selectSQL} FROM rv__dataset rvd INNER JOIN rv__dataset__usuario rvd_u ON rvd.id=rvd_u.id_dataset LEFT JOIN (SELECT COUNT(_rvo.id) AS qtd_ops, _rvo.id_dataset FROM rv__operacoes _rvo GROUP BY _rvo.id_dataset) crvo ON rvd.id=crvo.id_dataset {$whereSQL} {$sortSQL} LIMIT :offset,:pageSize");
        foreach ($queryParams as $name => $value)
            $statement->bindValue($name, $value, $this->bindValue_Type($value));
        $statement->execute();
        
        $datasets = [];

        /**
         * USUARIOS TABLE
         */
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $statement_u = $this->pdo->prepare('SELECT u.id,u.usuario,u.nome FROM rv__dataset__usuario rvd_u INNER JOIN usuario u ON rvd_u.id_usuario=u.id WHERE rvd_u.id_dataset = :id_dataset');
            $statement_u->bindValue(':id_dataset', $row['id'], $this->bindValue_Type($row['id']));
            $statement_u->execute();
            $usuarios_dataset = [];
            while ($row_u = $statement_u->fetch(\PDO::FETCH_ASSOC)) {
                $usuarios_dataset[] = [
                    'usuario' => $row_u['usuario'],
                    'nome' => $row_u['nome'],
                    'criador' => $row['id_usuario_criador'] == $row_u['id'] ? 1 : 0
                ];
            }
            $datasets[] = [
                'id' => $row['id'],
                'sou_criador' => ($row['id_usuario_criador'] == $id_usuario) ? 1 : 0,
                'nome' => $row['nome'],
                'data_criacao' => $row['data_criacao'],
                'data_atualizacao' => $row['data_atualizacao'],
                'situacao' => (int) $row['situacao'],
                'tipo' => (int) $row['tipo'],
                'usuarios' => $usuarios_dataset,
                'qtd_ops' => !is_null($row['qtd_ops']) ? (int) $row['qtd_ops'] : 0
            ];
        }

        return ['data' => ['rowCount' => $total_rows, 'rows' => $datasets]];
    }

    /**
     * Retornar dados do Dataset para Edição
     */
    public function list_edit($id_dataset, $id_usuario)
    {
        $selectClause = ['rvd.id', 'rvd.nome', 'rvd.situacao', 'rvd.tipo', 'rvd.observacao', 'IFNULL(crvd_u.usuarios, "") AS usuarios'];
        $selectSQL = implode(',', $selectClause);
        $statement = $this->pdo->prepare("SELECT {$selectSQL} FROM rv__dataset rvd INNER JOIN rv__dataset__usuario rvd_u ON rvd.id=rvd_u.id_dataset LEFT JOIN (SELECT _rvd_u.id_dataset, GROUP_CONCAT(_rvd_u.id_usuario SEPARATOR ';') AS usuarios FROM rv__dataset__usuario _rvd_u WHERE _rvd_u.id_dataset = :id_dataset AND _rvd_u.id_usuario != :id_usuario GROUP BY _rvd_u.id_dataset) crvd_u ON rvd.id=crvd_u.id_dataset WHERE rvd.id = :id_dataset AND rvd_u.id_usuario = :id_usuario");
        $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $dataset = $statement->fetch(\PDO::FETCH_ASSOC);
        return ['data' => $dataset ?: NULL];
    }

    /**
     * Verifica e retorna os dados para serem inseridos (Novo Dataset)
     * 
     * @param array fetched_data = [
     *      'nome'       => @var string Nome do Dataset
     *      'situacao'   => @var int Situação do Dataset
     *      'tipo'       => @var int Tipo do Dataset
     *      'usuarios'   => @var array Usuários com acesso ao Dataset
     *      'observacao' => @var string Observações do Dataset
     * ]
     */
    private function new_dataset__fetchedData($fetched_data)
    {
        // Itens Obrigatórios
        if (empty($fetched_data))
            return ['status' => 0, 'error' => 'Sem dados passados', 'treated_data' => NULL];
        if (!isset($fetched_data['nome']) || $fetched_data['nome'] === '')
            return ['status' => 0, 'error' => 'Nome é obrigatório', 'treated_data' => NULL];
        if (!isset($fetched_data['situacao']) || $fetched_data['situacao'] === '')
            return ['status' => 0, 'error' => 'Situação é obrigatória', 'treated_data' => NULL];
        if (!isset($fetched_data['tipo']) || $fetched_data['tipo'] === '')
            return ['status' => 0, 'error' => 'Tipo é obrigatório', 'treated_data' => NULL];

        // Trata dados
        $treated_data = [
            'nome' => $fetched_data['nome'],
            'situacao' => (int) $fetched_data['situacao'],
            'tipo' => (int) $fetched_data['tipo'],
            'usuarios' => [],
            'observacao' => !isset($fetched_data['observacao']) ? '' : $fetched_data['observacao']
        ];

        // Trata array de usuarios para pegar apenas o ID deles
        if (isset($fetched_data['usuarios']) && is_array($fetched_data['usuarios']))
            $treated_data['usuarios'] = array_map(fn ($usuario) => $usuario['id'], $fetched_data['usuarios']);
        
        return ['status' => 1, 'error' => '', 'treated_data' => $treated_data];
    }

    /**
     * Recebe dados de Dataset para criar um novo
     */
    public function new_dataset($fetched_data, $id_usuario)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->new_dataset__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];
        return ['status' => 1, 'error' => ''];
    }
}
