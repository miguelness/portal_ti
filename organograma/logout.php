<?php
// Logout do Organograma: encerra sessão e redireciona para TI
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa variáveis de sessão utilizadas no portal
unset($_SESSION['logged_in']);
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['nome']);

// Destroi a sessão
session_destroy();

// Redireciona para o site de TI
$dest = 'https://ti.grupobarao.com.br/';
header('Location: ' . $dest);
exit;
?>
