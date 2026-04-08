<?php
require_once 'config.php';
try {
    $check = $pdo->prepare("SELECT COUNT(*) FROM accesses WHERE access_name = ?");
    $check->execute(['Visualizar Organograma']);
    if ($check->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO accesses (access_name, description) VALUES (?, ?)")
            ->execute(['Visualizar Organograma', 'Permissão para visualizar o organograma público']);
        echo "✓ Acesso 'Visualizar Organograma' adicionado.\n";
    } else {
        echo "✓ Acesso 'Visualizar Organograma' já existe.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
