<?php

namespace App\DAO\MySQL;

use PDOException;

class GerenciamentoDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retornar de sugestão de gerenciamentos para um Autocomplete, dependendo do @param field passado e seu @param value.
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
            "rvg.id_usuario = {$id_usuario}"
        ];
        
        // SEMPRE fazer as queries especificas para o MODULO__LOCAL__INPUT fazendo a requisição (SEM generalizações no código)
        switch ($place) {
            case 'gerenciamento__filter__nome':
                // Prepara o SEARCHING
                if (!array_key_exists('nome', $filters))
                    return ['status' => 0, 'error' => 'Dados não passados corretamente', 'data' => NULL];
                $whereClause[] = "rvg.nome LIKE :nome";
                $queryParams[':nome'] = "%{$filters['nome']}%";
                $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
                $statement = $this->pdo->prepare("SELECT rvg.nome AS value,rvg.nome AS label FROM rv__gerenciamento rvg {$whereSQL}");
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
     * Retornar dados dos Gerenciamentos para uso no DataGrid.
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
        $selectClause = ['rvg.*'];

        // Prepara o SEARCHING
        // 'compare' : Tipo de comparação LIKE ou =
        // 'strict'  : Use para apendar % no inicio e/ou final (Ou vazio se não for necessário)
        $validFilterColumns = [
            'nome' => [
                'col' => 'rvg.nome',
                'compare' => 'LIKE',
                'strict' => ['start' => '%', 'end' => '%']
            ]
        ];
        $whereClause = [
            "rvg.id_usuario = {$id_usuario}"
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
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM rv__gerenciamento rvg {$whereSQL}");
        $statement->execute($queryParams);
        $total_rows = $statement->fetchColumn();

        // Prepara o SORTING
        $sortClause = [];
        foreach ($sorting as $column) {
            $sortClause[] = "rvg.{$column['field']} {$column['sort']}";
        }
        $sortSQL = !empty($sortClause) ? 'ORDER BY ' . implode(', ', $sortClause) : '';

        // Capturar as rows para serem mostradas
        $queryParams[':pageSize'] = $pageSize;
        $queryParams[':offset'] = $offset;
        $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
        $selectSQL = implode(',', $selectClause);
        $statement = $this->pdo->prepare("SELECT {$selectSQL} FROM rv__gerenciamento rvg {$whereSQL} {$sortSQL} LIMIT :offset,:pageSize");
        foreach ($queryParams as $name => $value)
            $statement->bindValue($name, $value, $this->bindValue_Type($value));
        $statement->execute();

        $gerenciamentos = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $acoes_decoded = json_decode($row['acoes'], TRUE);
            $escaladas_decoded = json_decode($row['escaladas'], TRUE);
            $acoes = [];
            for ($i=0; $i < count($acoes_decoded); $i++)
                $acoes[] = ['acao' => (int) $acoes_decoded[$i], 'escalada' => (int) $escaladas_decoded[$i]];
            $gerenciamentos[] = [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'acoes' => $acoes
            ];
        }

        return ['status' => 1, 'error' => '', 'data' => ['rowCount' => $total_rows, 'rows' => $gerenciamentos]];
    }

    /**
     * Retornar dados do Gerenciamento para Edição
     */
    public function list_edit($id_gerenciamento = -1, $id_usuario = -1)
    {
        $selectClause = ['rvg.id', 'rvg.nome', 'rvg.acoes', 'rvg.escaladas'];
        $selectSQL = implode(',', $selectClause);
        $statement = $this->pdo->prepare("SELECT {$selectSQL} FROM rv__gerenciamento rvg WHERE rvg.id = :id_gerenciamento AND rvg.id_usuario = :id_usuario");
        $statement->bindValue(':id_gerenciamento', $id_gerenciamento, $this->bindValue_Type($id_gerenciamento));
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row){
            $acoes_decoded = json_decode($row['acoes'], TRUE);
            $escaladas_decoded = json_decode($row['escaladas'], TRUE);
            $acoes = [];
            for ($i=0; $i < count($acoes_decoded); $i++)
                $acoes[] = ['key' => $i, 'acao' => (int) $acoes_decoded[$i], 'escalada' => (int) $escaladas_decoded[$i]];
            $gerenciamento = [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'acoes' => $acoes
            ];
            return ['data' => $gerenciamento];
        }
        else
            return ['data' => NULL];
    }

    /**
     * Verifica e retorna os dados para serem inseridos (Novo Gerenciamento)
     * 
     * @param array fetched_data = [
     *      'nome'      => @var string Nome do Gerenciamento
     *      'acoes'     => @var string Array stringified de ações de Gain/Loss em Scalps
     *      'escaladas' => @var string Array stringified de quantidade de escaladas das respectivas ações
     * ]
     */
    private function new_gerenciamento__fetchedData($fetched_data = [])
    {
        // Itens Obrigatórios
        if (empty($fetched_data))
            return ['status' => 0, 'error' => 'Sem dados passados', 'treated_data' => NULL];
        if (!isset($fetched_data['nome']) || $fetched_data['nome'] === '')
            return ['status' => 0, 'error' => 'Nome é obrigatório', 'treated_data' => NULL];
        if (!isset($fetched_data['acoes']) || $fetched_data['acoes'] === '')
            return ['status' => 0, 'error' => 'As ações são obrigatórias', 'treated_data' => NULL];
        if (!isset($fetched_data['escaladas']) || $fetched_data['escaladas'] === '')
            return ['status' => 0, 'error' => 'As escaladas são obrigatórias', 'treated_data' => NULL];

        // Trata dados
        $treated_data = [
            'nome' => $fetched_data['nome'],
            'acoes' => $fetched_data['acoes'],
            'escaladas' => $fetched_data['escaladas']
        ];

        return ['status' => 1, 'error' => '', 'treated_data' => $treated_data];
    }

    /**
     * Recebe dados de Gerenciamento para criar um novo
     */
    public function new_gerenciamento($fetched_data = [], $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->new_gerenciamento__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        try {
            $this->pdo->beginTransaction();
            // Cria o Gerenciamento
            $statement = $this->pdo->prepare('INSERT INTO rv__gerenciamento (id_usuario,nome,acoes,escaladas) VALUES (:id_usuario, UPPER(:nome), :acoes, :escaladas)');
            $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
            $statement->bindValue(':nome', $treated_data['nome'], $this->bindValue_Type($treated_data['nome']));
            $statement->bindValue(':acoes', $treated_data['acoes'], $this->bindValue_Type($treated_data['acoes']));
            $statement->bindValue(':escaladas', $treated_data['escaladas'], $this->bindValue_Type($treated_data['escaladas']));
            $statement->execute();

            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (\PDOException $exception) {
            $this->pdo->rollBack();
            if (in_array($exception->getCode(), [1062, 23000]))
                $error = 'Gerenciamento já cadastrado';
            else
                $error = 'Erro ao cadastrar este Gerenciamento';
            return ['status' => 0, 'error' => $error];
        }
    }

    /**
     * Verifica e retorna os dados para serem editados (Edição do Gerenciamento)
     * 
     * @param array fetched_data = [
     *      'nome'      => @var string Nome do Gerenciamento
     *      'acoes'     => @var string Array stringified de ações de Gain/Loss em Scalps
     *      'escaladas' => @var string Array stringified de quantidade de escaladas das respectivas ações
     * ]
     */
    private function edit_gerenciamento__fetchedData($fetched_data = [])
    {
        // Itens Obrigatórios
        if (empty($fetched_data))
            return ['status' => 0, 'error' => 'Sem dados passados', 'treated_data' => NULL];

        $treated_data = [];

        // Trata dados
        if (isset($fetched_data['nome']) && $fetched_data['nome'] !== '')
            $treated_data['nome'] = $fetched_data['nome'];
        if (isset($fetched_data['acoes']) && $fetched_data['acoes'] !== '')
            $treated_data['acoes'] = $fetched_data['acoes'];
        if (isset($fetched_data['escaladas']) && $fetched_data['escaladas'] !== '')
            $treated_data['escaladas'] = $fetched_data['escaladas'];

        return ['status' => 1, 'error' => '', 'treated_data' => $treated_data];
    }

    /**
     * Recebe dados do Gerenciamento para editar
     */
    public function edit_gerenciamento($fetched_data = [], $id_gerenciamento = -1, $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->edit_gerenciamento__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        try {
            $this->pdo->beginTransaction();
            // Edita o Gerenciamento
            if (isset($treated_data['nome']) || isset($treated_data['acoes']) || isset($treated_data['escaladas'])){
                $updateClause = [];
                $queryParams = [
                    ':id_gerenciamento' => $id_gerenciamento,
                    ':id_usuario' => $id_usuario
                ];
                foreach ($treated_data as $name => $value){
                    $updateClause[] = "{$name} = :{$name}";
                    $queryParams[":{$name}"] = $value;
                }
                $updateSQL = implode(', ', $updateClause);
                $statement = $this->pdo->prepare("UPDATE rv__gerenciamento SET {$updateSQL} WHERE id=:id_gerenciamento AND id_usuario=:id_usuario");
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
                $error = 'Um gerenciamento com esse nome já está cadastrado';
            else
                $error = 'Erro ao editar este Gerenciamento';
            return ['status' => 0, 'error' => $error];
        }
    }

    /**
     * Delete um Gerenciamento
     */
    public function delete_gerenciamento($id_gerenciamento = -1, $id_usuario = -1)
    {

        try {
            $this->pdo->beginTransaction();
            $queryParams = [
                ':id_gerenciamento' => $id_gerenciamento,
                ':id_usuario' => $id_usuario
            ];
            $statement = $this->pdo->prepare("DELETE FROM rv__gerenciamento WHERE id=:id_gerenciamento AND id_usuario=:id_usuario");
            foreach ($queryParams as $name => $value)
                $statement->bindValue($name, $value, $this->bindValue_Type($value));
            $statement->execute();

            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (PDOException $exception) {
            $this->pdo->rollBack();
            return ['status' => 0, 'error' => 'Erro ao deletar este Gerenciamento'];
        }
    }
}
