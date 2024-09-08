<?php
/*
  Biblioteca de Funções.
    Você pode separar funções muito utilizadas nesta biblioteca, evitando replicação de código.
*/

function ret_dados_pacien($connTemp) {
    $meu_array = array();  // Declarando a variável do tipo array

    // Abrir o arquivo CSV para leitura
    $file = fopen('pacientes.csv', 'r');

    if ($file === false) {
        die('Não foi possível abrir o arquivo.');
    }

    // Ler a primeira linha para obter os nomes das colunas
    $columns = fgetcsv($file, 0, ";");

    if ($columns === false) {
        fclose($file);
        die('Não foi possível ler a linha de cabeçalhos.');
    }

    // Ler as linhas restantes
    while (($linha = fgetcsv($file, 0, ";")) !== false) {
        $row = array();
        foreach ($columns as $index => $column) {
            $row[$column] = isset($linha[$index]) ? $linha[$index] : '';
        }
        $meu_array[] = $row;
    }

    fclose($file);

    // Preparar a consulta SQL para inserir dados

    $sql_1 = "INSERT INTO convenios (id, nome, descricao)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao)"; // Evitar duplicatas

    $sql_2 = "INSERT INTO pacientes (id, nome, sexo, nascimento, cpf, rg, id_convenio)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                nome=VALUES(nome), sexo=VALUES(sexo), nascimento=VALUES(nascimento), cpf=VALUES(cpf), rg=VALUES(rg), id_convenio=VALUES(id_convenio)";


    $stmt_1 = $connTemp->prepare($sql_1);
    $stmt_2 = $connTemp->prepare($sql_2);

    if (!$stmt_1) {
        die("Erro ao preparar a consulta de convenios: " . $connTemp->error);
    }

    if (!$stmt_2) {
        die("Erro ao preparar a consulta de pacientes: " . $connTemp->error);
    }

    foreach ($meu_array as $linha) {
        // Converter 'sexo_pac' para 'Masculino' ou 'Feminino'
        $sexo = strtoupper(substr($linha['sexo_pac'], 0, 1));  // Pega a primeira letra e converte para maiúscula
        if ($sexo === 'M') {
            $sexo = 'Masculino';
        } elseif ($sexo === 'F') {
            $sexo = 'Feminino';
        } else {
            echo "Valor inválido de sexo encontrado para o paciente " . $linha['nome_paciente'] . ": " . $linha['sexo_pac'] . "<br>";
            continue;  // Ignorar esta entrada e passar para a próxima
        }

        // Converter a data de nascimento do formato dd/mm/yyyy para yyyy-mm-dd
        $nascimento = DateTime::createFromFormat('d/m/Y', $linha['nasc_paciente']);
        if ($nascimento === false) {
            echo "Data inválida para o paciente " . $linha['nome_paciente'] . ": " . $linha['nasc_paciente'] . "<br>";
            continue;
        }
        $nascimento_formatado = $nascimento->format('Y-m-d');

        // Bind dos parâmetros para a tabela convenios
        $stmt_1->bind_param(
            'iss',
            $linha['id_conv'],
            $linha['convenio'],
            $linha['obs_clinicas']
        );

        // Executar inserção na tabela convenios
        if (!$stmt_1->execute()) {
            echo "Erro ao inserir os dados do convênio do paciente " . $linha['nome_paciente'] . ": " . $stmt_1->error . "<br>";
            continue;  // Pular para o próximo se falhar na inserção de convênios
        }

        // Bind dos parâmetros para a tabela pacientes
        $stmt_2->bind_param(
            'isssssi',
            $linha['cod_paciente'],
            $linha['nome_paciente'],
            $sexo,  // valor ajustado
            $nascimento_formatado,  // formato yyyy-mm-dd
            $linha['cpf_paciente'],
            $linha['rg_paciente'],
            $linha['id_conv']
        );

        // Executar inserção na tabela pacientes
        if (!$stmt_2->execute()) {
            echo "Erro ao inserir os dados do paciente " . $linha['nome_paciente'] . ": " . $stmt_2->error . "<br>";
        } else {
            echo "Dados inseridos com sucesso para o paciente " . $linha['nome_paciente'] . ".<br>";
        }
    }

    $stmt_1->close();
    $stmt_2->close();
};





/*
function ret_dados_pacien(){
 
    $meu_array = array();  /* Declarando a variável do tipo array 
    $file = fopen('pacientes.csv', 'r');
    while(($linha = fgetcsv($file)) !== false){
    
        $meu_array[] = $linha;
    
    }
    fclose($file);
    print_r ($meu_array);

};
*/
function dateNow(){
  date_default_timezone_set('America/Sao_Paulo');
  return date('d-m-Y \à\s H:i:s');
};