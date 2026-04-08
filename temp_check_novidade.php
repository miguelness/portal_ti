<?php
require_once 'c:/Xampp/htdocs/portal/conexao.php';
$res = $conn->query("SELECT id, titulo, is_novidade FROM menu_links WHERE is_novidade=1");
if ($res) {
    print_r($res->fetch_all(MYSQLI_ASSOC));
}
