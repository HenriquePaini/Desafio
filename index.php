<?php 

//ini_set('display_errors',1);
// Importação de Bibliotecas:
include __DIR__ . "/lib.php";

// Conexão com o banco da clínica fictícia (MedicalChallenge):
$connMedical = mysqli_connect("db_medical", "root", "356241", "MedicalChallenge")
or die("Não foi possível conectar ao servidor MySQL: MedicalChallenge\n");

if ($connMedical->connect_error) {
    die("Erro de conexão: " . $connMedical->connect_error);
}
echo "Conectado com sucesso ao banco MedicalChallenge!\n";

// Conexão com o banco temporário (0temp):
$connTemp = mysqli_connect("db_temp", "root", "356241", "0temp")
  or die("Não foi possível conectar ao servidor MySQL: 0temp\n");

if ($connTemp->connect_error) {
    die("Erro de conexão: " . $connTemp->connect_error);
}
echo "Conectado com sucesso ao banco 0temp!\n";

// Informações de Inicio da Migração:
echo "Início da Migração: " . dateNow() . ".\n\n";

// Buscar os dados dos arquivos CSV pacientes
echo ret_dados_pacien($connTemp);

echo "passou 1\n";

// Buscar os dados dos arquivos CSV agendamentos
echo ret_dados_agend($connTemp);

echo "passou 2\n";

// Migrar os dados dos pacientes e agendamentos para as tabelas do banco fictício 
echo migra_dados($connTemp, $connMedical);

echo "passou 3\n";

// Encerrando as conexões:
$connMedical->close();
$connTemp->close();

// Informações de Fim da Migração:
echo "Fim da Migração: " . dateNow() . ".\n";

?>
