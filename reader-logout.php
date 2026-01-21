<?php
// reader-logout.php
session_start();
session_unset();     // limpa variáveis
session_destroy();   // encerra sessão
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
