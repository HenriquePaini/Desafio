<?php 


// Importação de Bibliotecas:
include "./lib.php";

// Conexão com o banco da clínica fictícia:
/*$connMedical = mysqli_connect("db", "root", "356241", "MedicalChallenge")
or die("Não foi possível conectar ao servidor MySQL: MedicalChallenge\n");

if ($connMedical->connect_error) {
    die("Erro de conexão: " . $connMedical->connect_error);
}
echo "Conectado com sucesso!\n";
*/
// Conexão com o banco temporário:
// Conexão com o banco temporário:
$connTemp = mysqli_connect("db", "root", "356241", "0temp")
  or die("Não foi possível conectar ao servidor MySQL: 0temp\n");

if ($connTemp->connect_error) {
    die("Erro de conexão: " . $connTemp->connect_error);
}
echo "Conectado com sucesso ao banco de dados 0temp!\n";
error_log("Conectado ao banco de dados 0temp");

// Desativar o autocommit e iniciar a transação
$connTemp->autocommit(false);
$connTemp->query("START TRANSACTION");

// Informações de Início da Migração:
echo "Início da Migração: " . dateNow() . ".\n\n";

$meu_array = array();  // Declarando a variável do tipo array

// Abrir o arquivo CSV para leitura
$file = fopen('pacientes.csv', 'r');

if ($file === false) {
    die('Não foi possível abrir o arquivo.');
}

echo "Arquivo CSV aberto com sucesso.\n";

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

echo "Dados do arquivo CSV lidos com sucesso.\n";

// Preparar a consulta SQL para inserir dados
$sql_1 = "INSERT INTO convenios (id, nome, descricao)
          VALUES (?, ?, ?)
          ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao)";

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

echo "Consultas preparadas com sucesso.\n";

foreach ($meu_array as $linha) {
    // Converter 'sexo_pac' para 'Masculino' ou 'Feminino'
    $sexo = strtoupper(substr($linha['sexo_pac'], 0, 1));
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
    $stmt_1->bind_param(
        'iss',
        $linha['id_conv'],
        $linha['convenio'],
        $linha['obs_clinicas']
    );

    // Executar inserção na tabela convenios
    if (!$stmt_1->execute()) {
        echo "Erro ao inserir os dados do convênio do paciente " . $linha['nome_paciente'] . ": " . $stmt_1->error . "<br>";
        error_log("Erro ao inserir dados do convênio: " . $stmt_1->error);
        $connTemp->rollback();  // Desfazer as mudanças em caso de erro
        continue;
    }

    // Bind dos parâmetros para a tabela pacientes
    $stmt_2->bind_param(
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
    if (!$stmt_2->execute()) {
        echo "Erro ao inserir os dados do paciente " . $linha['nome_paciente'] . ": " . $stmt_2->error . "<br>";
        error_log("Erro ao inserir dados do paciente: " . $stmt_2->error);
        $connTemp->rollback();  // Desfazer as mudanças em caso de erro
    } else {
        echo "Dados inseridos com sucesso para o paciente " . $linha['nome_paciente'] . ".<br>";
    }
}

// Confirmar as mudanças no banco de dados
if (!$connTemp->commit()) {
    echo "Erro ao realizar o commit: " . $connTemp->error . "<br>";
    error_log("Erro ao realizar o commit: " . $connTemp->error);
} else {
    echo "Commit realizado com sucesso.<br>";
}

$stmt_1->close();
$stmt_2->close();
$connTemp->close();

echo "Fim da Migração: " . dateNow() . ".\n";


?>