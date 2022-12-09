<?php

namespace App\DAO\MySQL;

use PDOException;

class DashboardDAO extends Connection
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retornar todos os datasets que o usuario tem acesso (Sendo criador ou não).
     * 
     * @param id_usuario : Id do usuario logado a fazer esta requisição
     */
    public function list__dataset($id_usuario)
    {
        $statement = $this->pdo->prepare("SELECT id,id_usuario_criador,nome,DATE_FORMAT(data_atualizacao, '%d/%m/%Y %H:%i:%s') as data_atualizacao FROM rv__dataset rvd INNER JOIN rv__dataset__usuario rvd_u ON rvd.id=rvd_u.id_dataset WHERE rvd_u.id_usuario = :id_usuario ORDER BY rvd.data_atualizacao DESC");
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $datasets = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return ['data' => $datasets];
    }

    /**
     * Retornar a lista de cenarios e observações dos datasets passados no filtro.
     * 
     * @param filters    : Filtros passados para consulta de operações
     * @param id_usuario : Id do usuario logado a fazer a requisição
     */
    public function list__cenario($cenarios_encontrados, $filters, $id_usuario)
    {
        if (!is_array($filters['dataset']) || empty($filters['dataset']))
            return ['status' => 0, 'error' => 'Dados não passados corretamente', 'data' => NULL];

        $queryParams = [];

        // Prepara o SEARCHING
        $whereClause = [
            "rvd_u.id_usuario = {$id_usuario}",
        ];
        $datasetClause = [];
        foreach ($filters['dataset'] as $i => $value){
            $datasetClause[] = "rvc.id_dataset = :id_dataset_{$i}";
            $queryParams[":id_dataset_{$i}"] = $value;
        }
        $whereClause[] = '(' . implode(' OR ', $datasetClause) . ')';
        $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';

        // Captura os cenários
        $statement = $this->pdo->prepare("SELECT rvc.*,rvd.nome AS dataset FROM rv__cenario rvc INNER JOIN rv__dataset__usuario rvd_u ON rvc.id_dataset=rvd_u.id_dataset INNER JOIN rv__dataset rvd ON rvc.id_dataset=rvd.id {$whereSQL} ORDER BY rvc.nome");
        foreach ($queryParams as $name => $value)
            $statement->bindValue($name, $value, $this->bindValue_Type($value));
        $statement->execute();

        $cenarios = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $treated_cenarios = [];
        // Captura as observações
        if (!empty($cenarios)){
            $whereClause = array_reduce($cenarios, function ($t, $c) {
                if (!in_array($c['id'], $t))
                    $t[] = "rvco.id_cenario = '{$c['id']}'";
                return $t;
            }, []);
            $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' OR ', $whereClause) : '';
    
            $statement = $this->pdo->prepare("SELECT rvco.* FROM rv__cenario_obs rvco {$whereSQL} ORDER BY rvco.id_cenario,rvco.ref");
            $statement->execute();
            $observacoes = $statement->fetchAll(\PDO::FETCH_ASSOC);

            for ($c=0, $tc=0; $c < count($cenarios); $c++){
                if (array_key_exists($cenarios[$c]['nome'], $cenarios_encontrados)){
                    $treated_cenarios[$tc] = [ ...$cenarios[$c] ];
                    $treated_cenarios[$tc]['observacoes'] = [];
                    foreach ($observacoes as $obs){
                        if ($treated_cenarios[$tc]['id'] === $obs['id_cenario'])
                            $treated_cenarios[$tc]['observacoes'][] = ['id' => $obs['id'], 'ref' => $obs['ref'], 'nome' => $obs['nome']];
                    }
                    $tc++;
                }
            }
        }

        return $treated_cenarios;
    }

    /**
     * Retornar as operações dos datasets passados nos filtros.
     * Retornar tambem os 'ativos' e 'gerenciamentos' dessas operações
     * 
     * @param filters    : Filtros passados para consulta de operações
     * @param id_usuario : Id do usuario logado a fazer a requisição
     */
    public function list__operacoes($filters, $id_usuario)
    {
        if (!is_array($filters['dataset']) || empty($filters['dataset']))
            return ['status' => 0, 'error' => 'Dados não passados corretamente', 'data' => NULL];

        $operacoes = [];
        $gerenciamentos = [];
        $ativos = [];
        $cenarios = [];
        $originalInfo = [
            'dias' => []
        ];

        $queryParams = [];

        // Prepara o SEARCHING
        // 'compare' : Tipo de comparação LIKE ou =
        // 'strict'  : Use para apendar % no inicio e/ou final (Ou vazio se não for necessário)
        $validFilterColumns = [
            'dataset' => [
                'col' => 'rvo.id_dataset',
                'compare' => '=',
                'strict' => ['start' => '', 'end' => '']
            ],
        ];
        $whereClause = [
            "rvd_u.id_usuario = {$id_usuario}",
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

        // Captura as operações
        $statement = $this->pdo->prepare("SELECT rvo.* FROM rv__operacoes rvo INNER JOIN rv__dataset__usuario rvd_u ON rvo.id_dataset=rvd_u.id_dataset {$whereSQL} ORDER BY rvo.data ASC,rvo.hora ASC");
        foreach ($queryParams as $name => $value)
            $statement->bindValue($name, $value, $this->bindValue_Type($value));
        $statement->execute();

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            if (!in_array($row['gerenciamento'], $gerenciamentos))
                $gerenciamentos[] = $row['gerenciamento'];
            if (!in_array($row['ativo'], $ativos))
                $ativos[] = $row['ativo'];
            if (!array_key_exists($row['cenario'], $cenarios))
                $cenarios[$row['cenario']] = NULL;
            $originalInfo['dias'][$row['data']] = 0;
            $operacoes[] = [
                'id' => $row['id'],
                'sequencia' => $row['sequencia'],
                'gerenciamento' => $row['gerenciamento'],
                //Chuncho pra não dar erro com Timezone no JS
                'data' => "{$row['data']} 00:00:00",
                'ativo' => $row['ativo'],
                'op' => (int) $row['op'],
                'cts' => (int) $row['cts'],
                'hora' => $row['hora'],
                'resultado' => (float) $row['resultado'],
                'retorno_risco' => (float) $row['retorno_risco'],
                'cenario' => $row['cenario'],
                'observacoes' => $row['observacoes'],
                'erro' => (int) $row['erro'],
                'ativo_custo' => (float) $row['ativo_custo'],
                'ativo_valor_tick' => (float) $row['ativo_valor_tick'],
                'ativo_pts_tick' => (float) $row['ativo_pts_tick']
            ];
        }

        $operacoes_qtd = count($operacoes);
        $originalInfo['date_inicial'] = ($operacoes_qtd) ? $operacoes[0]["data"] : date("Y-m-d h:i:s");
        $originalInfo['date_final'] = ($operacoes_qtd) ? $operacoes[$operacoes_qtd - 1]["data"] : date("Y-m-d h:i:s");

        array_walk($gerenciamentos, fn (&$r, $i) => $r = ['value' => $i, 'label' => $r]);
        array_walk($ativos, fn (&$r, $i) => $r = ['value' => $i, 'label' => $r]);

        return ['operacoes' => $operacoes, 'gerenciamentos' => $gerenciamentos, 'ativos' => $ativos, 'cenarios_encontrados' => $cenarios, 'originalInfo' => $originalInfo];
    }
}
