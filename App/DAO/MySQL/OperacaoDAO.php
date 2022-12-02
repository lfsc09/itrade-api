<?php

namespace App\DAO\MySQL;

use PDOException;

class OperacaoDAO extends Connection
{

    /**
     * Verifica e retorna os dados para serem inseridos (Nova Operação)
     * 
     * @param array fetched_data = [
     *      'id_dataset'    => @var int Id do Dataset, a quem pertence as operações
     *      'operacoes'     => @var Array de [
     *          'gerenciamento'    => @var string Gerenciamento da Operação
     *          'data'             => @var date Data da Operação
     *          'ativo'            => @var string Ativo da Operação
     *          'op'               => @var string Op da Operação
     *          'cts'              => @var int Cts da Operação
     *          'hora'             => @var time Hora da Operação
     *          'erro'             => @var int Erro da Operação
     *          'resultado'        => @var decimal Resultado da Operação
     *          'retorno_risco'    => @var decimal Retorno pro Risco da Operação
     *          'cenario'          => @var string Cenario da Operação
     *          'observacoes'      => @var string Observações da Operação
     *          'ativo_custo'      => @var decimal Custo do Ativo
     *          'ativo_valor_tick' => @var decimal Valor do Tick do Ativo
     *          'ativo_pts_tick'   => @var decimal Pts do Tick do Ativo
     *      ]
     * ]
     */
    private function new_operacao__fetchedData($fetched_data = [])
    {
        // Itens Obrigatórios
        if (empty($fetched_data))
            return ['status' => 0, 'error' => 'Sem dados passados', 'treated_data' => NULL];
        if (!isset($fetched_data['id_dataset']) || $fetched_data['id_dataset'] === '')
            return ['status' => 0, 'error' => 'Dataset deve ser informado', 'treated_data' => NULL];
            
        // Trata dados
        $treated_data = [
            'id_dataset' => $fetched_data['id_dataset'],
            'operacoes' => $fetched_data['operacoes'] ?? [],
            'ativos' => $fetched_data['ativos'] ?? [],
        ];
        
        if (empty($treated_data['operacoes']))
            return ['status' => 0, 'error' => 'Nada a fazer', 'treated_data' => NULL];

        return ['status' => 1, 'error' => '', 'treated_data' => $treated_data];
    }

    /**
     * Recebe dados de Operações serem adicionadas
     */
    public function new_operacao($fetched_data = [], $id_usuario = -1)
    {
        [ 'status' => $status, 'error' => $error, 'treated_data' => $treated_data ] = $this->new_operacao__fetchedData($fetched_data);
        if ($status === 0)
            return ['status' => 0, 'error' => $error];

        $id_dataset = $fetched_data['id_dataset'];

        // Checa para ver se o usuario tem acesso ao Dataset (E é o criador)
        $statement = $this->pdo->prepare("SELECT rvd.id_usuario_criador,IFNULL(rvo.ult_seq+1,1) AS ult_seq FROM rv__dataset__usuario rvdu INNER JOIN rv__dataset rvd ON rvdu.id_dataset=rvd.id LEFT JOIN (SELECT id_dataset,MAX(sequencia) AS ult_seq FROM rv__operacoes WHERE id_dataset = :id_dataset) rvo ON rvdu.id_dataset=rvo.id_dataset WHERE rvdu.id_dataset = :id_dataset AND rvdu.id_usuario = :id_usuario");
        $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
        $statement->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
        $statement->execute();
        $sys_info = $statement->fetch(\PDO::FETCH_ASSOC);
        if (empty($sys_info) || $sys_info['id_usuario_criador'] !== $id_usuario)
            return ['status' => 0, 'error' => 'Apenas o criador pode modificar adicionar Operações'];

        try {
            $this->pdo->beginTransaction();
            // Adiciona automaticamente os ativos (Vindos do Upload de Arquivo)
            if (!empty($fetched_data['ativos'])) {
                foreach ($fetched_data['ativos'] as $ativo) {
                    $statement_a = $this->pdo->prepare("INSERT INTO rv__ativos (id_usuario,nome,custo,valor_tick,pts_tick) VALUES (:id_usuario, UPPER(:nome), :custo, :valor_tick, :pts_tick)");
                    $statement_a->bindValue(':id_usuario', $id_usuario, $this->bindValue_Type($id_usuario));
                    $statement_a->bindValue(':nome', $ativo['nome'], $this->bindValue_Type($ativo['nome']));
                    $statement_a->bindValue(':custo', $ativo['custo'], $this->bindValue_Type($ativo['custo']));
                    $statement_a->bindValue(':valor_tick', $ativo['valor_tick'], $this->bindValue_Type($ativo['valor_tick']));
                    $statement_a->bindValue(':pts_tick', $ativo['pts_tick'], $this->bindValue_Type($ativo['pts_tick']));
                    $statement_a->execute();
                }
            }
            
            $operacoes_ja_cadastradas = [];
            // Busca operacoes ja cadastradas para evitar duplicatas (id_dataset, data, ativo, op, hora, resultado, cenario, observacoes)
            $statement = $this->pdo->prepare("SELECT gerenciamento,data,ativo,op,hora,resultado,cenario,observacoes FROM rv__operacoes WHERE id_dataset = :id_dataset");
            $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
            $statement->execute();
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $key = "{$row['gerenciamento']}{$row['data']}{$row['ativo']}{$row['op']}{$row['hora']}" . rtrim((strpos($row['resultado'], '.') !== false ? rtrim($row['resultado'], '0') : $row['resultado']), '.') . "{$row['cenario']}{$row['observacoes']}";
                $operacoes_ja_cadastradas[$key] = NULL;
            }

            $ult_seq = $sys_info['ult_seq'];
            $block_size = 50;
            $block_i = 0;
            $insert_data = ['clause' => '', 'params' => []];
            foreach ($fetched_data['operacoes'] as $operacao) {
                // Se fechou o bloco
                if ($block_i === $block_size) {
                    // Faz a inserção do Bloco
                    $statement = $this->pdo->prepare("INSERT INTO rv__operacoes (id_dataset,id_usuario,sequencia,gerenciamento,data,ativo,op,cts,hora,erro,resultado,retorno_risco,cenario,observacoes,ativo_custo,ativo_valor_tick,ativo_pts_tick) VALUES {$insert_data['clause']}");
                    foreach ($insert_data['params'] as $name => $value)
                        $statement->bindValue($name, $value, $this->bindValue_Type($value));
                    $statement->execute();
                    // Reseta o Bloco de inserção
                    $block_i = 0;
                    $insert_data = ['clause' => '', 'params' => []];
                }
                $find_by_key = "{$operacao['gerenciamento']}{$operacao['data']}{$operacao['ativo']}{$operacao['op']}{$operacao['hora']}" . rtrim((strpos($operacao['resultado'], '.') !== false ? rtrim($operacao['resultado'], '0') : $operacao['resultado']), '.') . "{$operacao['cenario']}{$operacao['observacoes']}";
                if (!array_key_exists($find_by_key, $operacoes_ja_cadastradas)) {
                    $insert_data['clause'] .= ($insert_data['clause'] !== '' ? ',' : '') . "(" . 
                        ":id_dataset_{$block_i}" . 
                        ", :id_usuario_{$block_i}" . 
                        ", :sequencia_{$block_i}" . 
                        ", :gerenciamento_{$block_i}" . 
                        ", :data_{$block_i}" . 
                        ", :ativo_{$block_i}" . 
                        ", :op_{$block_i}" . 
                        ", :cts_{$block_i}" . 
                        ", :hora_{$block_i}" . 
                        ", :erro_{$block_i}" . 
                        ", :resultado_{$block_i}" . 
                        ", :retorno_risco_{$block_i}" . 
                        ", :cenario_{$block_i}" . 
                        ", :observacoes_{$block_i}" . 
                        ", :ativo_custo_{$block_i}" . 
                        ", :ativo_valor_tick_{$block_i}" . 
                        ", :ativo_pts_tick_{$block_i}" . 
                        ")";
                    $insert_data['params'] = array_merge($insert_data['params'], [
                        ":id_dataset_{$block_i}"       => $id_dataset,
                        ":id_usuario_{$block_i}"       => $id_usuario,
                        ":sequencia_{$block_i}"        => $ult_seq,
                        ":gerenciamento_{$block_i}"    => array_key_exists('gerenciamento', $operacao) ? $operacao['gerenciamento'] : '',
                        ":data_{$block_i}"             => array_key_exists('data', $operacao) ? $operacao['data'] : '',
                        ":ativo_{$block_i}"            => array_key_exists('ativo', $operacao) ? $operacao['ativo'] : '',
                        ":op_{$block_i}"               => array_key_exists('op', $operacao) ? $operacao['op'] : '',
                        ":cts_{$block_i}"              => array_key_exists('cts', $operacao) ? $operacao['cts'] : '1',
                        ":hora_{$block_i}"             => array_key_exists('hora', $operacao) ? $operacao['hora'] : '',
                        ":erro_{$block_i}"             => array_key_exists('erro', $operacao) ? $operacao['erro'] : '',
                        ":resultado_{$block_i}"        => array_key_exists('resultado', $operacao) ? $operacao['resultado'] : '',
                        ":retorno_risco_{$block_i}"    => array_key_exists('retorno_risco', $operacao) ? $operacao['retorno_risco'] : '',
                        ":cenario_{$block_i}"          => array_key_exists('cenario', $operacao) ? $operacao['cenario'] : '',
                        ":observacoes_{$block_i}"      => array_key_exists('observacoes', $operacao) ? $operacao['observacoes'] : '',
                        ":ativo_custo_{$block_i}"      => array_key_exists('ativo_custo', $operacao) ? $operacao['ativo_custo'] : '',
                        ":ativo_valor_tick_{$block_i}" => array_key_exists('ativo_valor_tick', $operacao) ? $operacao['ativo_valor_tick'] : '',
                        ":ativo_pts_tick_{$block_i}"   => array_key_exists('ativo_pts_tick', $operacao) ? $operacao['ativo_pts_tick'] : ''
                    ]);
                    $ult_seq++;
                    $block_i++;
                }
            }
            // Insere caso não tenha fechado o bloco
            if ($block_i > 0) {
                $statement = $this->pdo->prepare("INSERT INTO rv__operacoes (id_dataset,id_usuario,sequencia,gerenciamento,data,ativo,op,cts,hora,erro,resultado,retorno_risco,cenario,observacoes,ativo_custo,ativo_valor_tick,ativo_pts_tick) VALUES {$insert_data['clause']}");
                foreach ($insert_data['params'] as $name => $value)
                    $statement->bindValue($name, $value, $this->bindValue_Type($value));
                $statement->execute();
            }
            // Atualiza a data de atualização no dataset
            $statement = $this->pdo->prepare("UPDATE rv__dataset SET data_atualizacao=NOW() WHERE id = :id_dataset");
            $statement->bindValue(':id_dataset', $id_dataset, $this->bindValue_Type($id_dataset));
            $statement->execute();
            $this->pdo->commit();
            return ['status' => 1, 'error' => ''];
        }
        catch (\PDOException $exception) {
            $this->pdo->rollBack();
            if (in_array($exception->getCode(), [1062, 23000]))
                $error = 'Existem operações repetidas';
            else
                $error = 'Erro no cadastro de operações, nada foi feito';
            return ['status' => 0, 'error' => $error];
        }
    }
}