<?php
// conexao.php

// Dados de conexão
$servername = "localhost";  // geralmente localhost ou 127.0.0.1
$username   = "root";       // usuário padrão do XAMPP
$password   = "";           // em geral, o root do XAMPP não tem senha
$dbname     = "portal";  // nome do banco de dados que você criou

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica se ocorreu algum erro na conexão
if ($conn->connect_errno) {
    echo "Falha na conexão com o MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error;
    exit();
}

// Define o charset como UTF-8 (caso queira acentuação/utf8)
mysqli_set_charset($conn, "utf8");
?>
