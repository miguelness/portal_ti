<?php
session_start();
session_unset();
session_destroy();
header('Location: login.php'); // Redireciona para a página de login
exit();
?>
