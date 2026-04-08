<?php
// Logout do Organograma: encerra sessão e redireciona para TI
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa variáveis de sessão utilizadas no portal organograma isoladas
unset($_SESSION['org_logged_in']);
unset($_SESSION['org_user_id']);
unset($_SESSION['org_username']);
unset($_SESSION['org_nome']);

// NOTA: não chamamos session_destroy() aqui para não quebrar a sessão do painel Admin do Portal caso ativo.

// Redireciona para o site de TI
$dest = 'https://ti.grupobarao.com.br/';
header('Location: ' . $dest);
exit;
?>
