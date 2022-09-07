<?php

namespace App\DAO\MySQL;

use PDOException;

class CenarioDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retornar de sugestão de cenarios para um Autocomplete, dependendo do @param field passado e seu @param value.
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
            "rvc.id_usuario = {$id_usuario}"
        ];
        
        // SEMPRE fazer as queries especificas para o MODULO__LOCAL__INPUT fazendo a requisição (SEM generalizações no código)
        switch ($place) {
            case 'cenario__filter__nome':
                // Prepara o SEARCHING
                if (!array_key_exists('nome', $filters))
                    return ['status' => 0, 'error' => 'Dados não passados corretamente', 'data' => NULL];
                $whereClause[] = "rvc.nome LIKE :nome";
                $queryParams[':nome'] = "%{$filters['nome']}%";
                $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
                $statement = $this->pdo->prepare("SELECT rvc.nome AS value,rvc.nome AS label FROM rv__cenario rvc {$whereSQL}");
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
     * Retornar dados dos Cenarios para uso na Listagem.
     * 
     * @param id_dataset : Id do dataset que contem os possiveis cenarios
     * @param id_usuario : Id do usuario logado a fazer esta requisição
     */
    public function list_datarows($id_dataset, $id_usuario)
    {
        if (is_null($id_dataset))
            return ['status' => 0, 'error' => 'Dados não passados corretamente', 'data' => NULL];

        /**
         * CENARIO TABLE
         */
        $queryParams = [':id_dataset' => $id_dataset];
        $selectClause = ['rvc.id', 'rvc.nome'];

        // Prepara o SEARCHING
        $whereClause = [
            "rvd.id_usuario_criador = {$id_usuario}",
            "rvc.id_dataset = :id_dataset"
        ];
        $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
        
        // Capturar as rows para serem mostradas
        $selectSQL = implode(',', $selectClause);
        $statement = $this->pdo->prepare("SELECT {$selectSQL} FROM rv__cenario rvc INNER JOIN rv__dataset rvd ON rvc.id_dataset=rvd.id {$whereSQL} ORDER BY rvc.nome ASC");
        foreach ($queryParams as $name => $value)
            $statement->bindValue($name, $value, $this->bindValue_Type($value));
        $statement->execute();

        $cenarios = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $statement_o = $this->pdo->prepare("SELECT rvco.id,rvco.ref,rvco.nome FROM rv__cenario_obs rvco WHERE rvco.id_cenario = :id_cenario ORDER BY rvco.ref ASC");
            $statement_o->bindValue(':id_cenario', $row['id'], $this->bindValue_Type($row['id']));
            $statement_o->execute();
            
            $cenarios[] = [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'observacoes' => $statement_o->fetchAll(\PDO::FETCH_ASSOC)
            ];
        }

        return ['status' => 1, 'error' => '', 'data' => $cenarios];
    }

    /**
     * Retornar dados do Cenario para Edição
     */
    public function list_edit($id_cenario = -1, $id_usuario = -1)
    {
        $selectClause = ['rvc.id', 'rvc.nome', 'rvc.acoes', 'rvc.escaladas'];
        $selectSQL = implode(',', $selectClause);
        $statement = $this->pdo->prepare("SELECT {$selectSQL} FROM rv__cenario rvc WHERE rvc.id = :id_cenario AND rvc.id_usuario = :id_usuario");
        $statement->bindValue(':id_cenario', $id_cenario, $this->bindValue_Type($id_cenario));
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row){
            $acoes_decoded = json_decode($row['acoes'], TRUE);
            $escaladas_decoded = json_decode($row['escaladas'], TRUE);
            $acoes = [];
            for ($i=0; $i < count($acoes_decoded); $i++)
                $acoes[] = ['key' => $i, 'acao' => (int) $acoes_decoded[$i], 'escalada' => (int) $escaladas_decoded[$i]];
            $cenario = [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'acoes' => $acoes
            ];
            return ['data' => $cenario];
        }
        else
            return ['data' => NULL];
    }

    /**
     * Verifica e retorna os dados para serem inseridos (Novo Cenario)
     * 
     * @param array fetched_data = [
     *      'nome'      => @var string Nome do Cenario
     *      'acoes'     => @var string Array stringified de ações de Gain/Loss em Scalps
     *      'escaladas' => @var string Array stringified de quantidade de escaladas das respectivas ações
     * ]
     */
    private function new_cenario__fetchedData($fetched_data = [])
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
     * Recebe dados de Cenario para criar um novo
     */
    public function new_cenario($fetched_data = [], $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->new_cenario__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        try {
            $this->pdo->beginTransaction();
            // Cria o Cenario
            $statement = $this->pdo->prepare('INSERT INTO rv__cenario (id_usuario,nome,acoes,escaladas) VALUES (:id_usuario, UPPER(:nome), :acoes, :escaladas)');
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
                $error = 'Cenario já cadastrado';
            else
                $error = 'Erro ao cadastrar este Cenario';
            return ['status' => 0, 'error' => $error];
        }
    }

    /**
     * Verifica e retorna os dados para serem editados (Edição do Cenario)
     * 
     * @param array fetched_data = [
     *      'nome'      => @var string Nome do Cenario
     *      'acoes'     => @var string Array stringified de ações de Gain/Loss em Scalps
     *      'escaladas' => @var string Array stringified de quantidade de escaladas das respectivas ações
     * ]
     */
    private function edit_cenario__fetchedData($fetched_data = [])
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
     * Recebe dados do Cenario para editar
     */
    public function edit_cenario($fetched_data = [], $id_cenario = -1, $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->edit_cenario__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        try {
            $this->pdo->beginTransaction();
            // Edita o Cenario
            if (isset($treated_data['nome']) || isset($treated_data['acoes']) || isset($treated_data['escaladas'])){
                $updateClause = [];
                $queryParams = [
                    ':id_cenario' => $id_cenario,
                    ':id_usuario' => $id_usuario
                ];
                foreach ($treated_data as $name => $value){
                    $updateClause[] = "{$name} = :{$name}";
                    $queryParams[":{$name}"] = $value;
                }
                $updateSQL = implode(', ', $updateClause);
                $statement = $this->pdo->prepare("UPDATE rv__cenario SET {$updateSQL} WHERE id=:id_cenario AND id_usuario=:id_usuario");
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
                $error = 'Um cenario com esse nome já está cadastrado';
            else
                $error = 'Erro ao editar este Cenario';
            return ['status' => 0, 'error' => $error];
        }
    }

    /**
     * Delete um Cenario
     */
    public function delete_cenario($id_cenario = -1, $id_usuario = -1)
    {

        try {
            $this->pdo->beginTransaction();
            $queryParams = [
                ':id_cenario' => $id_cenario,
                ':id_usuario' => $id_usuario
            ];
            $statement = $this->pdo->prepare("DELETE FROM rv__cenario WHERE id=:id_cenario AND id_usuario=:id_usuario");
            foreach ($queryParams as $name => $value)
                $statement->bindValue($name, $value, $this->bindValue_Type($value));
            $statement->execute();

            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (PDOException $exception) {
            $this->pdo->rollBack();
            return ['status' => 0, 'error' => 'Erro ao deletar este Cenario'];
        }
    }
}
