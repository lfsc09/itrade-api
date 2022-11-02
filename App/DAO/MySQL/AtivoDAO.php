<?php

namespace App\DAO\MySQL;

use PDOException;

class AtivoDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retornar de sugestão de ativos para um Autocomplete, dependendo do @param field passado e seu @param value.
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
        $whereClause = [
            "rva.id_usuario = {$id_usuario}"
        ];
        
        // SEMPRE fazer as queries especificas para o MODULO__LOCAL__INPUT fazendo a requisição (SEM generalizações no código)
        switch ($place) {
            case 'ativo__filter__nome':
                // Prepara o SEARCHING
                if (!array_key_exists('nome', $filters))
                    return ['status' => 0, 'error' => 'Dados não passados corretamente', 'data' => NULL];
                $whereClause[] = "rva.nome LIKE :nome";
                $queryParams[':nome'] = "%{$filters['nome']}%";
                $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
                $statement = $this->pdo->prepare("SELECT rva.nome AS value,rva.nome AS label FROM rv__ativo rva {$whereSQL}");
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
     * Retornar dados dos Ativos (USADO APENAS POR OUTROS CONTROLLERS)
     * 
     * @param id_usuario : Id do usuario logado a fazer esta requisição
     */
    public function list_data($id_usuario)
    {
        $selectClause = ['rva.id', 'rva.nome', 'rva.custo', 'rva.valor_tick', 'rva.pts_tick'];

        // Prepara o SEARCHING
        $whereClause = [
            "rva.id_usuario = {$id_usuario}",
        ];
        $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
        
        // Capturar as rows para serem mostradas
        $selectSQL = implode(',', $selectClause);
        $statement = $this->pdo->prepare("SELECT {$selectSQL} FROM rv__ativo rva {$whereSQL} ORDER BY rva.nome ASC");
        $statement->execute();
        $ativos = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return ['status' => 1, 'error' => '', 'data' => $ativos];
    }

    /**
     * Retornar dados dos Ativos para uso no DataGrid.
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
         * ATIVO TABLE
         */
        $queryParams = [];
        $selectClause = ['rva.*'];

        // Prepara o SEARCHING
        // 'compare' : Tipo de comparação LIKE ou =
        // 'strict'  : Use para apendar % no inicio e/ou final (Ou vazio se não for necessário)
        $validFilterColumns = [
            'nome' => [
                'col' => 'rva.nome',
                'compare' => 'LIKE',
                'strict' => ['start' => '%', 'end' => '%']
            ]
        ];
        $whereClause = [
            "rva.id_usuario = {$id_usuario}"
        ];
        foreach ($filters as $col_name => $values) {
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
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM rv__ativo rva {$whereSQL}");
        $statement->execute($queryParams);
        $total_rows = $statement->fetchColumn();

        // Prepara o SORTING
        $sortClause = [];
        foreach ($sorting as $column) {
            $sortClause[] = "rva.{$column['field']} {$column['sort']}";
        }
        $sortSQL = !empty($sortClause) ? 'ORDER BY ' . implode(', ', $sortClause) : '';

        // Capturar as rows para serem mostradas
        $queryParams[':pageSize'] = $pageSize;
        $queryParams[':offset'] = $offset;
        $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
        $selectSQL = implode(',', $selectClause);
        $statement = $this->pdo->prepare("SELECT {$selectSQL} FROM rv__ativo rva {$whereSQL} {$sortSQL} LIMIT :offset,:pageSize");
        foreach ($queryParams as $name => $value)
            $statement->bindValue($name, $value, $this->bindValue_Type($value));
        $statement->execute();
        $ativos = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return ['status' => 1, 'error' => '', 'data' => ['rowCount' => $total_rows, 'rows' => $ativos]];
    }

    /**
     * Retornar dados do Ativo para Edição
     */
    public function list_edit($id_ativo = -1, $id_usuario = -1)
    {
        $selectClause = ['rva.id', 'rva.nome', 'rva.custo', 'rva.valor_tick', 'rva.pts_tick'];
        $selectSQL = implode(',', $selectClause);
        $statement = $this->pdo->prepare("SELECT {$selectSQL} FROM rv__ativo rva WHERE rva.id = :id_ativo AND rva.id_usuario = :id_usuario");
        $statement->bindValue(':id_ativo', $id_ativo, $this->bindValue_Type($id_ativo));
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $ativo = $statement->fetch(\PDO::FETCH_ASSOC);
        return ['data' => $ativo ?: NULL];
    }

    /**
     * Verifica e retorna os dados para serem inseridos (Novo Ativo)
     * 
     * @param array fetched_data = [
     *      'nome'       => @var string Nome do Ativo
     *      'custo'      => @var float Custo por contrato do ativo
     *      'valor_tick' => @var float Valor monetário de cada tick do ativo
     *      'pts_tick'   => @var float Quantidade de pts a cada tick do ativo
     * ]
     */
    private function new_ativo__fetchedData($fetched_data = [])
    {
        // Itens Obrigatórios
        if (empty($fetched_data))
            return ['status' => 0, 'error' => 'Sem dados passados', 'treated_data' => NULL];
        if (!isset($fetched_data['nome']) || $fetched_data['nome'] === '')
            return ['status' => 0, 'error' => 'Nome é obrigatório', 'treated_data' => NULL];
        if (!isset($fetched_data['custo']) || $fetched_data['custo'] === '')
            return ['status' => 0, 'error' => 'Custo é obrigatório', 'treated_data' => NULL];
        if (!isset($fetched_data['valor_tick']) || $fetched_data['valor_tick'] === '')
            return ['status' => 0, 'error' => 'Valor do tick é obrigatório', 'treated_data' => NULL];
        if (!isset($fetched_data['pts_tick']) || $fetched_data['pts_tick'] === '')
            return ['status' => 0, 'error' => 'Pts por tick é obrigatório', 'treated_data' => NULL];

        // Trata dados
        $treated_data = [
            'nome' => $fetched_data['nome'],
            'custo' => (float) $fetched_data['custo'],
            'valor_tick' => (float) $fetched_data['valor_tick'],
            'pts_tick' => (float) $fetched_data['pts_tick']
        ];

        return ['status' => 1, 'error' => '', 'treated_data' => $treated_data];
    }

    /**
     * Recebe dados de Ativo para criar um novo
     */
    public function new_ativo($fetched_data = [], $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->new_ativo__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        try {
            $this->pdo->beginTransaction();
            // Cria o Ativo
            $statement = $this->pdo->prepare('INSERT INTO rv__ativo (id_usuario,nome,custo,valor_tick,pts_tick) VALUES (:id_usuario, UPPER(:nome), :custo, :valor_tick, :pts_tick)');
            $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
            $statement->bindValue(':nome', $treated_data['nome'], $this->bindValue_Type($treated_data['nome']));
            $statement->bindValue(':custo', $treated_data['custo'], $this->bindValue_Type($treated_data['custo']));
            $statement->bindValue(':valor_tick', $treated_data['valor_tick'], $this->bindValue_Type($treated_data['valor_tick']));
            $statement->bindValue(':pts_tick', $treated_data['pts_tick'], $this->bindValue_Type($treated_data['pts_tick']));
            $statement->execute();

            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (\PDOException $exception) {
            $this->pdo->rollBack();
            if (in_array($exception->getCode(), [1062, 23000]))
                $error = 'Ativo já cadastrado';
            else
                $error = 'Erro ao cadastrar este Ativo';
            return ['status' => 0, 'error' => $error];
        }
    }

    /**
     * Verifica e retorna os dados para serem editados (Edição do Ativo)
     * 
     * @param array fetched_data = [
     *      'nome'       => @var string Nome do Ativo
     *      'custo'      => @var float Custo por contrato do ativo
     *      'valor_tick' => @var float Valor monetário de cada tick do ativo
     *      'pts_tick'   => @var float Quantidade de pts a cada tick do ativo
     * ]
     */
    private function edit_ativo__fetchedData($fetched_data = [])
    {
        // Itens Obrigatórios
        if (empty($fetched_data))
            return ['status' => 0, 'error' => 'Sem dados passados', 'treated_data' => NULL];

        $treated_data = [];

        // Trata dados
        if (isset($fetched_data['nome']) && $fetched_data['nome'] !== '')
            $treated_data['nome'] = $fetched_data['nome'];
        if (isset($fetched_data['custo']) && $fetched_data['custo'] !== '')
            $treated_data['custo'] = (float) $fetched_data['custo'];
        if (isset($fetched_data['valor_tick']) && $fetched_data['valor_tick'] !== '')
            $treated_data['valor_tick'] = (float) $fetched_data['valor_tick'];
        if (isset($fetched_data['pts_tick']) && $fetched_data['pts_tick'] !== '')
            $treated_data['pts_tick'] = (float) $fetched_data['pts_tick'];

        return ['status' => 1, 'error' => '', 'treated_data' => $treated_data];
    }

    /**
     * Recebe dados do Ativo para editar
     */
    public function edit_ativo($fetched_data = [], $id_ativo = -1, $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->edit_ativo__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        try {
            $this->pdo->beginTransaction();
            // Edita o Ativo
            if (isset($treated_data['nome']) || isset($treated_data['custo']) || isset($treated_data['valor_tick']) || isset($treated_data['pts_tick'])){
                $updateClause = [];
                $queryParams = [
                    ':id_ativo' => $id_ativo,
                    ':id_usuario' => $id_usuario
                ];
                foreach ($treated_data as $name => $value){
                    $updateClause[] = "{$name} = :{$name}";
                    $queryParams[":{$name}"] = $value;
                }
                $updateSQL = implode(', ', $updateClause);
                $statement = $this->pdo->prepare("UPDATE rv__ativo SET {$updateSQL} WHERE id=:id_ativo AND id_usuario=:id_usuario");
                foreach ($queryParams as $name => $value)
                    $statement->bindValue($name, $value, $this->bindValue_Type($value));
                $statement->execute();
            }

            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (PDOException $exception) {
            $this->pdo->rollBack();
            if (in_array($exception->getCode(), [1062, 23000]))
                $error = 'Um ativo com esse nome já está cadastrado';
            else
                $error = 'Erro ao editar este Ativo';
            return ['status' => 0, 'error' => $error];
        }
    }

    /**
     * Delete um Ativo
     */
    public function delete_ativo($id_ativo = -1, $id_usuario = -1)
    {

        try {
            $this->pdo->beginTransaction();
            $queryParams = [
                ':id_ativo' => $id_ativo,
                ':id_usuario' => $id_usuario
            ];
            $statement = $this->pdo->prepare("DELETE FROM rv__ativo WHERE id=:id_ativo AND id_usuario=:id_usuario");
            foreach ($queryParams as $name => $value)
                $statement->bindValue($name, $value, $this->bindValue_Type($value));
            $statement->execute();

            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (PDOException $exception) {
            $this->pdo->rollBack();
            return ['status' => 0, 'error' => 'Erro ao deletar este Ativo'];
        }
    }
}
