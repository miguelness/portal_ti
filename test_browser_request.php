<?php
session_start();

// Simular sessão de usuário logado
$_SESSION['user_id'] = 1;
$_SESSION['nome'] = 'Teste Admin';

echo "=== Teste Simulando Requisição do Navegador ===\n";

// Dados de teste
$dados_teste = [
    'nome' => 'Teste Browser',
    'empresa' => 'Empresa Browser',
    'setor' => 'TI',
    'email' => 'browser@teste.com',
    'ramal' => '5678',
    'telefone' => '11888888888',
    'status' => 'ativo'
];

// Simular requisição POST com cookies de sessão
$url = 'http://localhost:8000/api/colaboradores.php';
$json_data = json_encode($dados_teste);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json_data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

// Adicionar headers do navegador
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_REFERER, 'http://localhost:8000/admin/colaboradores.php');

echo "Enviando dados para: $url\n";
echo "Dados: " . $json_data . "\n\n";

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    echo "Erro cURL: " . curl_error($ch) . "\n";
} else {
    echo "Código HTTP: $http_code\n";
    echo "Resposta: $response\n";
    
    // Tentar decodificar JSON para ver detalhes
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "\nDetalhes da resposta:\n";
        print_r($decoded);
    }
}

curl_close($ch);

// Limpar arquivo de cookies
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}
?>