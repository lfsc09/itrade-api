<?php

namespace App\DAO\MySQL;

use PDOException;

class DatasetDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retornar de sugestão de datasets para um Autocomplete, dependendo do @param field passado e seu @param value.
     * 
     * @param place      : Informa o local que está requisitando, podendo mudar as informações a serem retornadas
     * @param filters    : Filtros utilizados na busca
     * @param id_usuario : Id do usuario logado a fazer esta requisição
     */
    public function list_suggestion($place, $filters, $id_usuario)
    {
        if (is_null($place) || !is_array($filters))
            return ['status' => 0, 'error' => 'Dados não passados corretamente', 'data' => NULL];
            
        $queryParams = [];

        // Inicia o SEARCHING
        $whereClause = [];
        
        // SEMPRE fazer as queries especificas para o MODULO__LOCAL__INPUT fazendo a requisição (SEM generalizações no código)
        switch ($place) {
            // Apenas os Datasets que o usuario é criador
            case 'cenario__picker__nome':
                $whereClause[] = "rvd.id_usuario_criador = {$id_usuario}";
                $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
                $statement = $this->pdo->prepare("SELECT rvd.id,rvd.nome,DATE_FORMAT(rvd.data_atualizacao, '%d/%m/%Y %H:%i:%s') as data_atualizacao FROM rv__dataset rvd {$whereSQL} ORDER BY rvd.data_atualizacao DESC");
                break;
            // Apenas os Datasets que o usuario é criador
            case 'operacoes_novo__picker__nome':
                $whereClause[] = "rvd.id_usuario_criador = {$id_usuario}";
                $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
                $statement = $this->pdo->prepare("SELECT rvd.id,rvd.nome,DATE_FORMAT(rvd.data_atualizacao, '%d/%m/%Y %H:%i:%s') as data_atualizacao FROM rv__dataset rvd {$whereSQL} ORDER BY rvd.data_atualizacao DESC");
                break;
            default:
                return ['status' => 0, 'error' => 'Dados não passados corretamente', 'data' => NULL];
        }
        foreach ($queryParams as $name => $value)
            $statement->bindValue($name, $value, $this->bindValue_Type($value));
        $statement->execute();
        $suggests = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return ['status' => 1, 'error' => '', 'data' => $suggests];
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
        if (!is_numeric($page) || !is_numeric($pageSize) || !is_array($filters) || !is_array($sorting))
            return ['status' => 0, 'error' => 'Dados não passados corretamente', 'data' => NULL];

        $offset = $page * $pageSize;

        /**
         * DATASET INFO
         */
        $queryParams = [];
        $selectClause = ['rvd.*', 'crvo.qtd_ops'];

        // Prepara o SEARCHING
        // 'compare' : Tipo de comparação LIKE ou =
        // 'strict'  : Use para apendar % no inicio e/ou final (Ou vazio se não for necessário)
        $validFilterColumns = [
            'tipo' => [
                'col' => 'rvd.nome',
                'compare' => '=',
                'strict' => ['start' => '', 'end' => '']
            ],
            'situacao' => [
                'col' => 'rvd.situacao',
                'compare' => '=',
                'strict' => ['start' => '', 'end' => '']
            ]
        ];
        $whereClause = [
            "rvd_u.id_usuario = {$id_usuario}"
        ];
        foreach ($filters as $col_name => $values){
            $colClause = [];
             if (!array_key_exists($col_name, $validFilterColumns))
                return ['status' => 0, 'error' => "'{$col_name}' não é valido", 'data' => NULL];
            foreach ($values as $i => $value){
                $colClause[] = $validFilterColumns[$col_name]['col'] . ' ' . $validFilterColumns[$col_name]['compare'] . ' ' . ":{$col_name}_{$i}";
                $queryParams[":{$col_name}_{$i}"] = $validFilterColumns[$col_name]['strict']['start'] . $value . $validFilterColumns[$col_name]['strict']['end'];
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
         * USUARIOS INFO
         */
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $statement_u = $this->pdo->prepare("SELECT u.id,u.usuario,u.nome FROM rv__dataset__usuario rvd_u INNER JOIN usuario u ON rvd_u.id_usuario=u.id WHERE rvd_u.id_dataset = :id_dataset");
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

        return ['status' => 1, 'error' => '', 'data' => ['rowCount' => $total_rows, 'rows' => $datasets]];
    }

    /**
     * Retornar dados do Dataset para Edição
     */
    public function list_edit($id_dataset = -1, $id_usuario = -1)
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
     * Retorna a lista de usuarios no sistema (Para DatasetController::list_edita)
     * 
     * @param id_usuario : Id do usuario fazendo a requisição, para não ser retornado na lista
     */
    public function list_edit__usuario($id_usuario = -1)
    {
        $statement = $this->pdo->prepare("SELECT id,usuario,nome FROM usuario WHERE id != :id_usuario");
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $usuarios = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return ['data' => $usuarios];
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
    private function new_dataset__fetchedData($fetched_data = [])
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
    public function new_dataset($fetched_data = [], $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->new_dataset__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        try {
            $this->pdo->beginTransaction();
            // Cria o Dataset
            $statement = $this->pdo->prepare("INSERT INTO rv__dataset (id_usuario_criador,nome,situacao,tipo,observacao) VALUES (:id_usuario_criador, :nome, :situacao, :tipo, :observacao)");
            $statement->bindValue(':id_usuario_criador', $id_usuario, $this->bindValue_Type($id_usuario));
            $statement->bindValue(':nome', $treated_data['nome'], $this->bindValue_Type($treated_data['nome']));
            $statement->bindValue(':situacao', $treated_data['situacao'], $this->bindValue_Type($treated_data['situacao']));
            $statement->bindValue(':tipo', $treated_data['tipo'], $this->bindValue_Type($treated_data['tipo']));
            $statement->bindValue(':observacao', $treated_data['observacao'], $this->bindValue_Type($treated_data['observacao']));
            $statement->execute();

            $id_dataset = $this->pdo->lastInsertId();
            // Adiciona os usuarios com acesso ao Dataset (Próprio usuario incluso)
            // Coloca por padrão o id do usuario criador
            $treated_data['usuarios'][] = $id_usuario;
            $insertClause = [];
            $queryParams = [':id_dataset' => $id_dataset];
            foreach ($treated_data['usuarios'] as $i => $id_usuario){
                $insertClause[] = "(:id_dataset, :id_usuario_{$i})";
                $queryParams[":id_usuario_{$i}"] = $id_usuario;
            }
            $insertSQL = implode(',', $insertClause);
            $statement = $this->pdo->prepare("INSERT INTO rv__dataset__usuario (id_dataset,id_usuario) VALUES {$insertSQL}");
            foreach ($queryParams as $name => $value)
                $statement->bindValue($name, $value, $this->bindValue_Type($value));
            $statement->execute();
            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (\PDOException $exception) {
            $this->pdo->rollBack();
            if (in_array($exception->getCode(), [1062, 23000]))
                $error = 'Dataset já cadastrado';
            else
                $error = 'Erro ao cadastrar este Dataset';
            return ['status' => 0, 'error' => $error];
        }
    }

    /**
     * Verifica e retorna os dados para serem editados (Edição do Dataset)
     * 
     * @param array fetched_data = [
     *      'nome'       => @var string Nome do Dataset
     *      'situacao'   => @var int Situação do Dataset
     *      'tipo'       => @var int Tipo do Dataset
     *      'usuarios'   => @var array Usuários com acesso ao Dataset
     *      'observacao' => @var string Observações do Dataset
     * ]
     */
    private function edit_dataset__fetchedData($fetched_data = [])
    {
        // Itens Obrigatórios
        if (empty($fetched_data))
            return ['status' => 0, 'error' => 'Sem dados passados', 'treated_data' => NULL];

        $treated_data = [];

        // Trata dados
        if (isset($fetched_data['nome']) && $fetched_data['nome'] !== '')
            $treated_data['nome'] = $fetched_data['nome'];
        if (isset($fetched_data['situacao']) && $fetched_data['situacao'] !== '')
            $treated_data['situacao'] = (int) $fetched_data['situacao'];
        if (isset($fetched_data['tipo']) && $fetched_data['tipo'] !== '')
            $treated_data['tipo'] = (int) $fetched_data['tipo'];
        if (isset($fetched_data['observacao']) && $fetched_data['observacao'] !== '')
            $treated_data['observacao'] = $fetched_data['observacao'];

        // Trata array de usuarios para pegar apenas o ID deles
        if (isset($fetched_data['usuarios']) && is_array($fetched_data['usuarios']))
            $treated_data['usuarios'] = array_map(fn ($usuario) => $usuario['id'], $fetched_data['usuarios']);
        
        return ['status' => 1, 'error' => '', 'treated_data' => $treated_data];
    }

    /**
     * Recebe dados do Dataset para editar
     */
    public function edit_dataset($fetched_data = [], $id_dataset = -1, $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->edit_dataset__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        // Checa para ver se o usuario tem acesso ao Dataset (E é o criador)
        $statement = $this->pdo->prepare("SELECT rvd.id_usuario_criador FROM rv__dataset__usuario rvd_u INNER JOIN rv__dataset rvd ON rvd_u.id_dataset=rvd.id WHERE rvd_u.id_dataset = :id_dataset AND rvd_u.id_usuario = :id_usuario");
        $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $id_criador__raw = $statement->fetch(\PDO::FETCH_ASSOC);
        $id_criador = $id_criador__raw['id_usuario_criador'] ?: -1;
        if ($id_criador !== $id_usuario)
            return ['status' => 0, 'error' => 'Apenas o criador pode alterar este Dataset'];

        try {
            $this->pdo->beginTransaction();
            // Atualiza os usuarios com acesso ao Dataset
            if (isset($treated_data['usuarios'])){
                // Remove o acesso de todos
                $statement = $this->pdo->prepare("DELETE FROM rv__dataset__usuario WHERE id_dataset = :id_dataset");
                $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
                $statement->execute();
                // Coloca por padrão o id do usuario criador
                $treated_data['usuarios'][] = $id_usuario;
                // Adiciona os usuarios com acesso ao Dataset (Próprio usuario incluso)
                $insertClause = [];
                $queryParams = [':id_dataset' => $id_dataset];
                foreach ($treated_data['usuarios'] as $i => $id_usuario){
                    $insertClause[] = "(:id_dataset, :id_usuario_{$i})";
                    $queryParams[":id_usuario_{$i}"] = $id_usuario;
                }
                $insertSQL = implode(',', $insertClause);
                $statement = $this->pdo->prepare("INSERT INTO rv__dataset__usuario (id_dataset,id_usuario) VALUES {$insertSQL}");
                foreach ($queryParams as $name => $value)
                    $statement->bindValue($name, $value, $this->bindValue_Type($value));
                $statement->execute();
            }

            // Edita o Dataset
            if (isset($treated_data['nome']) || isset($treated_data['situacao']) || isset($treated_data['tipo']) || isset($treated_data['observacao']) || isset($treated_data['usuarios'])){
                $updateClause = ['data_atualizacao = NOW()'];
                $queryParams = [
                    ':id_dataset' => $id_dataset,
                    ':id_usuario_criador' => $id_criador
                ];
                foreach ($treated_data as $name => $value){
                    // Pula usuarios com acesso, pois é em outra query
                    if ($name !== 'usuarios'){
                        $updateClause[] = "{$name} = :{$name}";
                        $queryParams[":{$name}"] = $value;
                    }
                }
                $updateSQL = implode(', ', $updateClause);
                $statement = $this->pdo->prepare("UPDATE rv__dataset SET {$updateSQL} WHERE id = :id_dataset AND id_usuario_criador = :id_usuario_criador");
                foreach ($queryParams as $name => $value)
                    $statement->bindValue($name, $value, $this->bindValue_Type($value));
                $statement->execute();
            }

            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (\PDOException $exception) {
            $this->pdo->rollBack();
            if (in_array($exception->getCode(), [1062, 23000]))
                $error = 'Um dataset com esse nome já está cadastrado';
            else
                $error = 'Erro ao editar este Dataset';
            return ['status' => 0, 'error' => $error];
        }
    }

    /**
     * Delete um Dataset, apagando tambem os cenários relacionados e as operações
     */
    public function delete_dataset($id_dataset = -1, $id_usuario = -1)
    {
        // Checa para ver se o usuario tem acesso ao Dataset (E é o criador)
        $statement = $this->pdo->prepare("SELECT rvd.id_usuario_criador FROM rv__dataset__usuario rvd_u INNER JOIN rv__dataset rvd ON rvd_u.id_dataset=rvd.id WHERE rvd_u.id_dataset = :id_dataset AND rvd_u.id_usuario = :id_usuario");
        $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $id_criador__raw = $statement->fetch(\PDO::FETCH_ASSOC);
        $id_criador = $id_criador__raw['id_usuario_criador'] ?: -1;
        if ($id_criador !== $id_usuario)
            return ['status' => 0, 'error' => 'Apenas o criador pode deletar este Dataset'];

        try {
            $this->pdo->beginTransaction();
            // Remove o Dataset
            $statement = $this->pdo->prepare("DELETE rvd,rvdu FROM rv__dataset rvd LEFT JOIN rv__dataset__usuario rvdu ON rvd.id=rvdu.id_dataset WHERE rvd.id = :id_dataset");
            $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
            $statement->execute();
            // Remove os Cenários
            $statement = $this->pdo->prepare("DELETE rvc,rvco FROM rv__cenario rvc LEFT JOIN rv__cenario_obs rvco ON rvc.id=rvco.id_cenario WHERE rvc.id_dataset = :id_dataset");
            $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
            $statement->execute();
            // Remove as operações
            $statement = $this->pdo->prepare("DELETE FROM rv__operacoes WHERE id_dataset = :id_dataset");
            $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
            $statement->execute();
            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (\PDOException $exception) {
            $this->pdo->rollBack();
            return ['status' => 0, 'error' => 'Erro ao deletar este Dataset'];
        }
    }
}
