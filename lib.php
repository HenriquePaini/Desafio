    <?php
    /*
    Biblioteca de Funções.
        Você pode separar funções muito utilizadas nesta biblioteca, evitando replicação de código.
    */

    //Criada a função para recuperar os dados do paciente.
    function ret_dados_pacien($connTemp) {
        $vetor = array();  // Declarando a variável do tipo array para armazenamento temporário

        // Abrir o CSV para leitura
        $arquivo = fopen('pacientes.csv', 'r');
        
        //Verificando se a abertura do csv deu certo
        if ($arquivo === false) {
            die('Não foi possível abrir o arquivo pacientes.');
        } /*else {
            echo "deu boa";
        }*/

        // Ler a primeira linha para obter os nomes das colunas
        $coluna = fgetcsv($arquivo, 0, ";");

        // Verifica se foi possível ler a primeira linha do csv 
        if ($coluna === false) {
            fclose($arquivo);
            die('Não foi possível ler a linha de cabeçalhos.');
        }

        // Ler as linhas restantes do arquivo, continuando até que todas as linhas sejam lidas 
        while (($linha = fgetcsv($arquivo, 0, ";")) !== false) {
            $vet = array();
            foreach ($coluna as $i => $column) {
                $vet[$column] = isset($linha[$i]) ? $linha[$i] : '';
            }
            $vetor[] = $vet;
        }

        fclose($arquivo);

        // Preparar a consulta SQL para inserir dados na tabela convenios

        $sql_1 = "INSERT INTO convenios (id, nome, descricao)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao)";// Evitar duplicatas

        // Preparar a consulta SQL para inserir dados na tabela convenios
        $sql_2 = "INSERT INTO pacientes (id, nome, sexo, nascimento, cpf, rg, id_convenio)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    nome=VALUES(nome), sexo=VALUES(sexo), nascimento=VALUES(nascimento), cpf=VALUES(cpf), rg=VALUES(rg), id_convenio=VALUES(id_convenio)";


        $insert_1 = $connTemp->prepare($sql_1);
        $insert_2 = $connTemp->prepare($sql_2);

        if (!$insert_1) {
            die("Erro ao preparar a consulta de convenios: " . $connTemp->error);
        }

        if (!$insert_2) {
            die("Erro ao preparar a consulta de pacientes: " . $connTemp->error);
        }

        foreach ($vetor as $linha) {
            // Converter 'sexo_pac' para 'Masculino' ou 'Feminino'
            $sexo = strtoupper(substr($linha['sexo_pac'], 0, 1));  // Pega a primeira letra e converte para maiúscula
            if ($sexo === 'M') {
                $sexo = 'Masculino';
            } elseif ($sexo === 'F') {
                $sexo = 'Feminino';
            } else {
                echo "Valor inválido de sexo encontrado para o paciente " . $linha['nome_paciente'] . ": " . $linha['sexo_pac'] . "<br>";
                continue;
            }

            // Converter a data de nascimento do formato dd/mm/yyyy para yyyy-mm-dd
            $nascimento = DateTime::createFromFormat('d/m/Y', $linha['nasc_paciente']);
            if ($nascimento === false) {
                echo "Data inválida para o paciente " . $linha['nome_paciente'] . ": " . $linha['nasc_paciente'] . "<br>";
                continue;
            }
            $nascimento_formatado = $nascimento->format('Y-m-d');

            // Bind dos parâmetros para a tabela convenios
            $insert_1->bind_param(
                'iss',
                $linha['id_conv'],
                $linha['convenio'],
                $linha['obs_clinicas']
            );
            //echo "passou";

            // Executar inserção na tabela convenios
            if (!$insert_1->execute()) {
                echo "Erro ao inserir os dados do convênio do paciente " . $linha['nome_paciente'] . ": " . $insert_1->error . "<br>";
                continue; 
            } /*else {
                echo "passou1";
            }*/

            // Bind dos parâmetros para a tabela pacientes
            $insert_2->bind_param(
                'isssssi',
                $linha['cod_paciente'],
                $linha['nome_paciente'],
                $sexo,  
                $nascimento_formatado,  
                $linha['cpf_paciente'],
                $linha['rg_paciente'],
                $linha['id_conv']
            );

            // Executar inserção na tabela pacientes
            if (!$insert_2->execute()) {
                echo "Erro ao inserir os dados do paciente " . $linha['nome_paciente'] . ": " . $insert_2->error . "<br>";
            } 
        }

        $insert_1->close();
        $insert_2->close();
    };

    /*-------------------------*/

    function ret_dados_agend($connTemp){
        // Abrir o arquivo CSV para leitura
        $arquivo = fopen('agendamentos.csv', 'r');

        if ($arquivo === false) {
            die('Não foi possível abrir o arquivo agendamentos.');
        }

        // Ler a primeira linha para obter os nomes das colunas
        $coluna = fgetcsv($arquivo, 0, ";");

        if ($coluna === false) {
            fclose($arquivo);
            die('Não foi possível ler a linha de cabeçalhos.');
        }

        // Ler as linhas restantes
        while (($linha = fgetcsv($arquivo, 0, ";")) !== false) {
            $vet = array();
            foreach ($coluna as $i => $column) {
                $vet[$column] = isset($linha[$i]) ? $linha[$i] : '';
            }
            $vetor[] = $vet;
        }

        fclose($arquivo);

        // Preparar a consulta SQL para inserir dados na tabela procedimentos
        $sql_1 = "INSERT INTO procedimentos (nome, descricao)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao)";

        // Preparar a consulta SQL para inserir dados na tabela procedimentos
        $sql_2 = "INSERT INTO profissionais (id, nome)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE nome=VALUES(nome)";

        // Preparar a consulta SQL para inserir dados na tabela procedimentos
        $sql_3 = "INSERT INTO agendamentos (id, id_paciente, id_profissional, dh_inicio, dh_fim, id_convenio, id_procedimento, observacoes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE id_convenio=VALUES(id_convenio), id_procedimento=VALUES(id_procedimento), observacoes=VALUES(observacoes)";

        //Método prepare utilizado para ler as os "$sql_?" e substitua os "?"
        $insert_1 = $connTemp->prepare($sql_1);
        $insert_2 = $connTemp->prepare($sql_2);
        $insert_3 = $connTemp->prepare($sql_3);

        // Verifica se a "preparação" foi bem-sucedida
        if (!$insert_1) {
            die("Erro ao preparar a consulta de procedimentos: " . $connTemp->error);
        }

        if (!$insert_2) {
            die("Erro ao preparar a consulta de profissionais: " . $connTemp->error);
        }

        if (!$insert_3) {
            die("Erro ao preparar a consulta de agendamentos: " . $connTemp->error);
        }

        foreach ($vetor as $linha) {

            // Converter a data de data do formato dd/mm/yyyy para yyyy-mm-dd hh24:mi:ss
            $data_inicio = $linha['dia'] . ' ' . $linha['hora_inicio'];
            $data_fim = $linha['dia'] . ' ' . $linha['hora_fim'];

            // Converter a data de data do formato dd/mm/yyyy H:i:s para yyyy-mm-dd H:i:s
            $dh_inicio = DateTime::createFromFormat('d/m/Y H:i:s', $data_inicio);
            if ($dh_inicio === false) {
                echo "Data inválida: " . $data_inicio . "<br>";
                continue;
            }
            $vd_data_inic = $dh_inicio->format('Y-m-d H:i:s');

            $dh_fim = DateTime::createFromFormat('d/m/Y H:i:s', $data_fim);
            if ($dh_fim === false) {
                echo "Data inválida: " . $data_fim . "<br>";
                continue;
            }
            $vd_data_fim = $dh_fim->format('Y-m-d H:i:s');

            // Bind dos parâmetros para a tabela procedimentos
            $insert_1->bind_param(
                'ss',
                $linha['procedimento'],
                $linha['descricao']
            );

            if (!$insert_1->execute()) {
                echo "Erro ao inserir os dados do procedimento " . $linha['procedimento'] . ": " . $insert_1->error . "<br>";
                continue;  
            }

            // Recuperar o ID gerado para o procedimento
            
            $procedimento_id = $connTemp->insert_id;

            // Bind dos parâmetros para a tabela profissionais
            // O método "bind_param" é usado para vincular variáveis aos parâmetros da consulta sql preparada.
            $insert_2->bind_param(
                'is',
                $linha['cod_medico'],
                $linha['medico']
            );
            //Verifica se a inserção dos dados foi bem-sucedida
            if (!$insert_2->execute()) {
                echo "Erro ao inserir os dados do profissional " . $linha['nome'] . ": " . $insert_2->error . "<br>";
                continue;  
            }

            // Bind dos parâmetros para a tabela agendamentos
            $insert_3->bind_param(
                'iiissiis',
                $linha['cod_agendamento'],
                $linha['cod_paciente'],
                $linha['cod_medico'],
                $vd_data_inic,
                $vd_data_fim,
                $linha['cod_convenio'],
                $procedimento_id,
                $linha['observacoes']
            );
            if (!$insert_3->execute()) {
                echo "Erro ao inserir os dados do agendamento " . $linha['id'] . ": " . $insert_3->error . "<br>";
                continue;  
            }

        }
        
        //fecha os objetos utilizados
        $insert_1->close();
        $insert_2->close();
        $insert_3->close();
    };

    function dateNow(){
    date_default_timezone_set('America/Sao_Paulo');
    return date('d-m-Y \à\s H:i:s');
    };

#=======================================================
function ret_dados_pacientes($connTemp) {
    // Consulta os dados dos pacientes na tabela temporária
    $consulta = "SELECT id
                      , nome
                      , sexo
                      , nascimento
                      , cpf
                      , rg
                      , id_convenio
                      , cod_referencia 
                   FROM pacientes";
    $resultado = $connTemp->query($consulta);

    if ($resultado->n_linhas > 0) { 
        // Cria um array para armazenar os dados dos pacientes
        $pacientes = [];
        while ($linhas = $resultado->fetch_assoc()) {
            $pacientes[] = $linhas;
        }
        return $pacientes;
    } else {
        return [];
    }
}

function ret_dados_convenios($connTemp) {
    $consulta = "SELECT id
                      , nome
                      , descricao 
                   FROM convenios";
    $resultado = $connTemp->query($consulta);

    if ($resultado->n_linhas > 0) {
        $convenios = [];
        while ($linhas = $resultado->fetch_assoc()) {
            $convenios[] = $linhas;
        }
        return $convenios;
    } else {
        return [];
    }
}

function ret_dados_procedimentos($connTemp) {
    $consulta = "SELECT id
                      , nome
                      , descricao 
                   FROM procedimentos";
    $resultado = $connTemp->query($consulta);

    if ($resultado->n_linhas > 0) {
        $procedimentos = [];
        while ($linhas = $resultado->fetch_assoc()) {
            $procedimentos[] = $linhas;
        }
        return $procedimentos;
    } else {
        return [];
    }
}

function ret_dados_profissionais($connTemp) {
    $consulta = "SELECT id
                      , nome
                      , crm 
                   FROM profissionais";
    $resultado = $connTemp->query($consulta);

    if ($resultado->n_linhas > 0) {
        $profissionais = [];
        while ($linhas = $resultado->fetch_assoc()) {
            $profissionais[] = $linhas;
        }
        return $profissionais;
    } else {
        return [];
    }
}

function ret_dados_agenda($connTemp) {
    $consulta = "SELECT id
                      , id_paciente
                      , id_profissional
                      , dh_inicio
                      , dh_fim
                      , id_convenio
                      , id_procedimento
                      , observacoes 
                   FROM agendamentos";
    $resultado = $connTemp->query($consulta);

    if ($resultado->n_linhas > 0) {
        $agendamentos = [];
        while ($linhas = $resultado->fetch_assoc()) {
            $agendamentos[] = $linhas;
        }
        return $agendamentos;
    } else {
        return [];
    }
}

#==========================================================
function migra_dados($connTemp, $connMedical) {
    // Inicia uma transação
    $connMedical->begin_transaction();

    try {
        // Migração dos convênios: Obtém os dados dos convênios.
        //Faz a conexão com o banco temporário para buscar os dados 
        $convenios = ret_dados_convenios($connTemp);
        foreach ($convenios as $linha) {
            // Checar se o convênio já existe no banco de produção
            $verif_query = "SELECT id 
                              FROM convenios 
                             WHERE id = ?";
            $verif_insert = $connMedical->prepare($verif_query);

            if (!$verif_insert) {
                throw new Exception("Erro ao preparar a consulta select de convênios: " . $connMedical->error);
            }

            $verif_insert->bind_param("i", $linha['id']);
            $verif_insert->execute();
            $verif_insert->store_result();

            //Verifica se o número de linhas retornadas pela consulta é zero
            if ($verif_insert->num_rows === 0) {
                // Convênio não existe, então faz o INSERT
                $sql_insert = "INSERT INTO convenios (id, nome, descricao) VALUES (?, ?, ?)";
                $insere = $connMedical->prepare($sql_insert);

                if (!$insere) {
                    throw new Exception("Erro ao preparar a consulta INSERT de convênios: " . $connMedical->error);
                }

                $insere->bind_param("iss"
                                   , $linha['id']
                                   , $linha['nome']
                                   , $linha['descricao']);

                //Executa o insert com os dados passados pela consulta
                $insere->execute();
            }

            $verif_insert->close();
        }

        // Migração dos pacientes
        //Faz a conexão com o banco temporário para buscar os dados 
        $pacientes = ret_dados_pacientes($connTemp);
        foreach ($pacientes as $linha) {
            $verif_query = "SELECT id 
                              FROM pacientes 
                             WHERE id = ?";
            $verif_insert = $connMedical->prepare($verif_query);

            if (!$verif_insert) {
                throw new Exception("Erro ao preparar a consulta SELECT de pacientes: " . $connMedical->error);
            }
            //Passa o parametro para a consulta
            $verif_insert->bind_param("i", $linha['id']);
            //Executa a consulta preparada com os parâmetros vinculados.
            $verif_insert->execute();
            //Armzaena os dados da consulta
            $verif_insert->store_result();

            //Verifica se o número de linhas retornadas pela consulta é zero
            if ($verif_insert->num_rows === 0) {
                // Faz a inserção do paciente
                $sql_insert = "INSERT INTO pacientes (id, nome, sexo, nascimento, cpf, rg, id_convenio, cod_referencia) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $insere = $connMedical->prepare($sql_insert);

                if (!$insere) {
                    throw new Exception("Erro ao preparar a consulta INSERT de pacientes: " . $connMedical->error);
                }

                $insere->bind_param("issssssi", 
                    $linha['id'], 
                    $linha['nome'], 
                    $linha['sexo'], 
                    $linha['nascimento'],  
                    $linha['cpf'], 
                    $linha['rg'], 
                    $linha['id_convenio'], 
                    $linha['cod_referencia']
                );

                //Executa o insert com os dados passados pela consulta
                $insere->execute();
            }

            $verif_insert->close();
        }

        // Migração dos procedimentos
        //Faz a conexão com o banco temporário para buscar os dados 
        $procedimentos = ret_dados_procedimentos($connTemp);
        foreach ($procedimentos as $linha) {
            $verif_query = "SELECT id 
                              FROM procedimentos
                             WHERE id = ?";
            $verif_insert = $connMedical->prepare($verif_query);

            if (!$verif_insert) {
                throw new Exception("Erro ao preparar a consulta SELECT de procedimentos: " . $connMedical->error);
            }

            //Passa o parametro para a consulta
            $verif_insert->bind_param("i", $linha['id']);
            //Executa a consulta preparada com os parâmetros vinculados.
            $verif_insert->execute();
            //Armzaena os dados da consulta
            $verif_insert->store_result();

            //Verifica se o número de linhas retornadas pela consulta é zero
            if ($verif_insert->num_rows === 0) {
                $sql_insert = "INSERT INTO procedimentos (id, nome, descricao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao)";
                $insere = $connMedical->prepare($sql_insert);

                if (!$insere) {
                    throw new Exception("Erro ao preparar a consulta INSERT de procedimentos: " . $connMedical->error);
                }

                $insere->bind_param("iss"
                                   , $linha['id']
                                   , $linha['nome']
                                   , $linha['descricao']);
                
                //Executa o insert com os dados passados pela consulta
                $insere->execute();
            }

            $verif_insert->close();
        }

        // Migração dos profissionais
        //Faz a conexão com o banco temporário para buscar os dados 
        $profissionais = ret_dados_profissionais($connTemp);
        foreach ($profissionais as $linha) {
            $verif_query = "SELECT id FROM profissionais WHERE id = ?";
            $verif_insert = $connMedical->prepare($verif_query);

            if (!$verif_insert) {
                throw new Exception("Erro ao preparar a consulta SELECT de profissionais: " . $connMedical->error);
            }
            //Passa o parametro para a consulta
            $verif_insert->bind_param("i", $linha['id']);
            //Executa a consulta preparada com os parâmetros vinculados.
            $verif_insert->execute();
            //Armzaena os dados da consulta
            $verif_insert->store_result();

            //Verifica se o número de linhas retornadas pela consulta é zero
            if ($verif_insert->num_rows === 0) {
                $sql_insert = "INSERT INTO profissionais (id, nome, crm) VALUES (?, ?, ?)";
                $insere = $connMedical->prepare($sql_insert);

                if (!$insere) {
                    throw new Exception("Erro ao preparar a consulta INSERT de profissionais: " . $connMedical->error);
                }

                $insere->bind_param("iss"
                                   , $linha['id']
                                   , $linha['nome']
                                   , $linha['crm']);

                //Executa o insert com os dados passados pela consulta
                $insere->execute();
            }

            $verif_insert->close();
        }

        // Migração dos agendamentos
        //Faz a conexão com o banco temporário para buscar os dados 
        $agendamentos = ret_dados_agenda($connTemp);
        foreach ($agendamentos as $linha) {
            $verif_query = "SELECT id FROM agendamentos WHERE id = ?";
            $verif_insert = $connMedical->prepare($verif_query);

            if (!$verif_insert) {
                throw new Exception("Erro ao preparar a consulta SELECT de agendamentos: " . $connMedical->error);
            }
            //Passa o parametro para a consulta
            $verif_insert->bind_param("i", $linha['id']);
            //Executa a consulta preparada com os parâmetros vinculados.
            $verif_insert->execute();
            //Armzaena os dados da consulta
            $verif_insert->store_result();

            //Verifica se o número de linhas retornadas pela consulta é zero
            if ($verif_insert->num_rows === 0) {
                $sql_insert = "INSERT INTO agendamentos (id, id_paciente, id_profissional, dh_inicio, dh_fim, id_convenio, id_procedimento, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $insere = $connMedical->prepare($sql_insert);

                if (!$insere) {
                    throw new Exception("Erro ao preparar a consulta INSERT de agendamentos: " . $connMedical->error);
                }

                $insere->bind_param("iisssiis", 
                    $linha['id'], 
                    $linha['id_paciente'], 
                    $linha['id_profissional'], 
                    $linha['dh_inicio'], 
                    $linha['dh_fim'], 
                    $linha['id_convenio'], 
                    $linha['id_procedimento'], 
                    $linha['observacoes']
                );

                //Executa o insert com os dados passados pela consulta
                $insere->execute();
            }
            //Fecha a operação
            $verif_insert->close();
        }

        // Confirma a transação após a migração
        $connMedical->commit();

    } catch (Exception $e) {
        // Reverte a transação em caso de erro
        $connMedical->rollback();
        echo "Erro durante a migração: " . $e->getMessage();
    }
}

?>
