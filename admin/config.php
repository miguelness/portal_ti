<?php
// admin/config.php
$host = 'localhost'; // ou IP do servidor MySQL
$dbname = 'portal';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// Garante sessão ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega acessos do usuário
if (!isset($user_accesses)) {
    $user_accesses = [];
    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT a.access_name FROM user_access ua
                               JOIN accesses a ON ua.access_id = a.id
                               WHERE ua.user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_accesses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Define categorias permitidas com base nos acessos
$categorias_disponiveis = [];

if (in_array('Feeds TI', $user_accesses)) {
    $categorias_disponiveis[] = 'Maxtrade';
    $categorias_disponiveis[] = 'Portal';
}

if (in_array('Feeds RH', $user_accesses)) {
    $categorias_disponiveis[] = 'RH';
}

$categorias_disponiveis = array_unique($categorias_disponiveis);

?>
