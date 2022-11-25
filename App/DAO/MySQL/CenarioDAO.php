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
     * Verifica e retorna os dados para serem inseridos (Novo Cenario)
     * 
     * @param array fetched_data = [
     *      'id_dataset'          => @var int Id do Dataset, a quem pertence os cenarios
     *      'cenarios_delete'     => @var Array Array de ids de cenarios a serem excluidos
     *      'cenarios_create'     => @var Array de [
     *          'nome'        => @var string Nome do Cenário
     *          'observacoes' => @var Array de [
     *              'ref'  => @var int Numero de referencia da Observação
     *              'nome' => @var string Nome da Observação
     *          ]
     *      ]
     *      'cenarios_update'     => @var Array de [
     *          'id'         => @var int Id do cenário sendo alterado
     *          'nome'       => @var string Novo nome do cenário (OPCIONAL)
     *          'obs_delete' => @var Array de ids de observações a serem excluidas
     *          'obs_create' => @var Array de [
     *              'ref'  => @var int Numero de referencia da nova Observação
     *              'nome' => @var string Nome da Observação
     *          ]
     *          'obs_update' => @var Array de [
     *              'id'   => @var int Id da observação sendo alterada
     *              'ref'  => @var int Novo ref da observação (OPCIONAL)
     *              'nome' => @var string Novo nome da observação (OPCIONAL)
     *          ]
     *      ]
     * ]
     */
    private function manage_cenario__fetchedData($fetched_data = [])
    {
        // Itens Obrigatórios
        if (empty($fetched_data))
            return ['status' => 0, 'error' => 'Sem dados passados', 'treated_data' => NULL];
        if (!isset($fetched_data['id_dataset']) || $fetched_data['id_dataset'] === '')
            return ['status' => 0, 'error' => 'Dataset deve ser informado', 'treated_data' => NULL];
            
        // Trata dados
        $treated_data = [
            'id_dataset' => $fetched_data['id_dataset'],
            'cenarios_delete' => $fetched_data['cenarios_delete'] ?? [],
            'cenarios_create' => $fetched_data['cenarios_create'] ?? [],
            'cenarios_update' => $fetched_data['cenarios_update'] ?? []
        ];
        
        if (empty($treated_data['cenarios_delete']) && empty($treated_data['cenarios_create']) && empty($treated_data['cenarios_update']))
            return ['status' => 0, 'error' => 'Nada a fazer', 'treated_data' => NULL];

        return ['status' => 1, 'error' => '', 'treated_data' => $treated_data];
    }

    /**
     * Recebe dados dos Cenários para Criar / Alterar / Remover.
     * 
     * Os cenários e suas observações são sempre excluidos, e recriados para evitar erros de duplicação.
     */
    public function manage_cenario($fetched_data = [], $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->manage_cenario__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        $id_dataset = $fetched_data['id_dataset'];

        // Checa para ver se o usuario tem acesso ao Dataset (E é o criador)
        $statement = $this->pdo->prepare("SELECT rvd.id_usuario_criador FROM rv__dataset__usuario rvd_u INNER JOIN rv__dataset rvd ON rvd_u.id_dataset=rvd.id WHERE rvd_u.id_dataset = :id_dataset AND rvd_u.id_usuario = :id_usuario");
        $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $id_criador__raw = $statement->fetch(\PDO::FETCH_ASSOC);
        $id_criador = $id_criador__raw['id_usuario_criador'] ?: -1;
        if ($id_criador !== $id_usuario)
            return ['status' => 0, 'error' => 'Apenas o criador pode modificar os Cenários'];

        // Pega todos os cenarios e observações atuais do Dataset
        $statement = $this->pdo->prepare("SELECT rvc.* FROM rv__cenario rvc WHERE rvc.id_dataset = :id_dataset");
        $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
        $statement->execute();
        $cenarios_atuais = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $statement_o = $this->pdo->prepare("SELECT rvco.* FROM rv__cenario_obs rvco WHERE rvco.id_cenario = :id_cenario");
            $statement_o->bindValue(':id_cenario', $row['id'], $this->bindValue_Type($row['id']));
            $statement_o->execute();

            $observacoes_atuais = [];
            while ($row_o = $statement->fetch(\PDO::FETCH_ASSOC))
                $observacoes_atuais[$row_o['id']] = [...$row_o];
            
            $cenarios_atuais[$row['id']] = [
                ...$row,
                'observacoes' => $observacoes_atuais
            ];
        }

        // Cenários a serem excluidos
        foreach ($fetched_data['cenarios_delete'] as $id_cenario_del) {
            unset($cenarios_atuais[$id_cenario_del]);
        }

        // Cenários a serem alterados
        foreach ($fetched_data['cenarios_update'] as $cenario_update) {
            // Há alteração de nome do Cenário
            if (array_key_exists('nome', $cenario_update)) {
                // Verifica nome duplicado
                foreach ($cenarios_atuais as $cenario_atual) {
                    if ($cenario_update['nome'] === $cenario_atual['nome'])
                        return ['status' => 0, 'error' => "O nome '{$cenario_update['nome']}' já existe em outro Cenário"];
                }
                $cenarios_atuais[$cenario_update['id']]['nome'] = $cenario_update['nome'];
            }

            // Há observações a serem removidas
            if (array_key_exists('obs_delete', $cenario_update)) {
                foreach ($cenario_update['obs_delete'] as $id_obs_del)
                    unset($cenarios_atuais[$cenario_update['id']]['observacoes'][$id_obs_del]);
            }
            // Há alterações nas observações
            if (array_key_exists('obs_update', $cenario_update)) {
                foreach ($cenario_update['obs_update'] as $obs_update) {
                    // Verifica ref ou nome duplicado
                    foreach ($cenarios_atuais[$cenario_update['id']]['observacoes'] as $obs_atual) {
                        if (array_key_exists('ref', $obs_update)) {
                            if ($obs_update['ref'] === $obs_atual['ref'])
                                return ['status' => 0, 'error' => "A ref '{$obs_update['ref']}' já é usada em outra Observação"];
                        }
                        if (array_key_exists('nome', $obs_update)) {
                            if ($obs_update['nome'] === $obs_atual['nome'])
                                return ['status' => 0, 'error' => "O nome '{$obs_update['nome']}' já é usada em outra Observação"];
                        }
                    }
                    // Há alteração de ref da Observação
                    if (array_key_exists('ref', $obs_update))
                        $cenarios_atuais[$cenario_update['id']]['observacoes'][$obs_update['id']]['ref'] = $obs_update['ref'];
                    // Há alteração de nome da Observação
                    if (array_key_exists('nome', $obs_update))
                        $cenarios_atuais[$cenario_update['id']]['observacoes'][$obs_update['id']]['nome'] = $obs_update['nome'];
                }
            }
            // Há novas observações
            if (array_key_exists('obs_create', $cenario_update)) {
                foreach ($cenario_update['obs_create'] as $obs_create) {
                    // Verifica ref ou nome duplicado
                    foreach ($cenarios_atuais[$cenario_update['id']]['observacoes'] as $obs_atual) {
                        if ($obs_create['ref'] === $obs_atual['ref'])
                            return ['status' => 0, 'error' => "A ref '{$obs_create['ref']}' já é usada em outra Observação"];
                        if ($obs_create['nome'] === $obs_atual['nome'])
                            return ['status' => 0, 'error' => "O nome '{$obs_create['nome']}' já é usada em outra Observação"];
                    }
                    $cenarios_atuais[$cenario_update['id']]['observacoes'][] = [
                        'ref' => $obs_create['ref'],
                        'nome' => $obs_create['nome']
                    ];
                }
            }
        }

        // Cenários a serem criados
        foreach ($fetched_data['cenarios_create'] as $cenario_create) {
            // Verifica nome duplicado
            foreach ($cenarios_atuais as $cenario_atual) {
                if ($cenario_create['nome'] === $cenario_atual['nome'])
                    return ['status' => 0, 'error' => "O nome '{$cenario_create['nome']}' já existe em outro Cenário"];
            }

            $novas_obs = [];
            foreach ($cenario_create['observacoes'] as $obs_to_create) {
                // Verifica refs e nomes duplicados
                foreach ($novas_obs as $nova_obs) {
                    if ($obs_to_create['ref'] === $nova_obs['ref'])
                        return ['status' => 0, 'error' => "A ref '{$obs_to_create['ref']}' já é usada em outra Observação"];
                    if ($obs_to_create['nome'] === $nova_obs['nome'])
                        return ['status' => 0, 'error' => "O nome '{$obs_to_create['nome']}' já é usada em outra Observação"]; 
                }
                $novas_obs[] = [
                    'ref' => $obs_to_create['ref'],
                    'nome' => $obs_to_create['nome']
                ];
            }

            $cenarios_atuais[] = [
                'nome' => $cenario_create['nome'],
                'observacoes' => $novas_obs
            ];
        }

        try {
            $this->pdo->beginTransaction();
            // Apaga os cenarios para recria-los
            $statement = $this->pdo->prepare("DELETE rvc,rvco FROM rv__cenario rvc LEFT JOIN rv__cenario_obs rvco ON rvc.id=rvco.id_cenario WHERE rvc.id_dataset = :id_dataset");
            $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
            $statement->execute();
            // Inserir os cenarios tratados
            foreach ($cenarios_atuais as $cenario_atual) {
                $id_cenario = NULL;
                // Re-insere um cenario já existente com as novas infos
                if (array_key_exists('id', $cenario_atual)) {
                    $id_cenario = $cenario_atual['id'];
                    $statement = $this->pdo->prepare("INSERT INTO rv__cenario (id,id_dataset,nome) VALUES (:id, :id_dataset, :nome)");
                    $statement->bindValue(':id', $id_cenario, $this->bindValue_Type($id_cenario));
                    $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
                    $statement->bindValue(':nome', $cenario_atual['nome'], $this->bindValue_Type($cenario_atual['nome']));
                    $statement->execute();
                }
                // É um novo cenario
                else {
                    $statement = $this->pdo->prepare("INSERT INTO rv__cenario (id_dataset,nome) VALUES (:id_dataset, :nome)");
                    $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
                    $statement->bindValue(':nome', $cenario_atual['nome'], $this->bindValue_Type($cenario_atual['nome']));
                    $statement->execute();
                    $id_cenario = $this->pdo->lastInsertId();
                }
                // Insere observações
                if (!empty($cenario_atual['observacoes'])) {
                    foreach ($cenario_atual['observacoes'] as $obs_atual) {
                        // Re-insere uma observação já existente com novas infos
                        if (array_key_exists('id', $obs_atual)) {
                            $statement = $this->pdo->prepare("INSERT INTO rv__cenario_obs (id,id_cenario,ref,nome) VALUES (:id, :id_cenario, :ref, :nome)");
                            $statement->bindValue(':id', $obs_atual['id'], $this->bindValue_Type($obs_atual['id']));
                            $statement->bindValue(':id_cenario', $id_cenario, $this->bindValue_Type($id_cenario));
                            $statement->bindValue(':ref', $obs_atual['ref'], $this->bindValue_Type($obs_atual['ref']));
                            $statement->bindValue(':nome', $obs_atual['nome'], $this->bindValue_Type($obs_atual['nome']));
                            $statement->execute();
                        }
                        // É uma nova observação
                        else {
                            $statement = $this->pdo->prepare("INSERT INTO rv__cenario_obs (id_cenario,ref,nome) VALUES (:id_cenario, :ref, :nome)");
                            $statement->bindValue(':id_cenario', $id_cenario, $this->bindValue_Type($id_cenario));
                            $statement->bindValue(':ref', $obs_atual['ref'], $this->bindValue_Type($obs_atual['ref']));
                            $statement->bindValue(':nome', $obs_atual['nome'], $this->bindValue_Type($obs_atual['nome']));
                            $statement->execute();
                        }
                    }
                }
            }
            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (\PDOException $exception) {
            $this->pdo->rollBack();
            if (in_array($exception->getCode(), [1062, 23000]))
                $error = 'Algum cenário ou observação já está cadastrado';
            else
                $error = 'Erro ao fazer estas mudanças';
            return ['status' => 0, 'error' => $error];
        }
    }
}
