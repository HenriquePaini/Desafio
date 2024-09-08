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
$connTemp = mysqli_connect("db", "root", "356241", "0temp")
  or die("Não foi possível conectar ao servidor MySQL: 0temp\n");

if ($connTemp->connect_error) {
    die("Erro de conexão: " . $connTemp->connect_error);
}
echo "Conectado com sucesso!\n";

// Informações de Inicio da Migração:
echo "Início da Migração: " . dateNow() . ".\n\n";

echo ret_dados_pacien($connTemp);


echo "====================================================\n";

echo ret_dados_agend($connTemp);

// Encerrando as conexões:
//$connMedical->close();
$connTemp->close();

// Informações de Fim da Migração:
echo "Fim da Migração: " . dateNow() . ".\n";

?>
