<?php
echo "=== Teste da API de Colaboradores ===\n";

// Dados de teste
$dados_teste = [
    'nome' => 'Teste Usuario',
    'empresa' => 'Empresa Teste',
    'setor' => 'TI',
    'email' => 'teste@teste.com',
    'ramal' => '1234',
    'telefone' => '11999999999',
    'status' => 'ativo'
];

// Simular requisição POST
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

echo "Enviando dados para: $url\n";
echo "Dados: " . $json_data . "\n\n";

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    echo "Erro cURL: " . curl_error($ch) . "\n";
} else {
    echo "Código HTTP: $http_code\n";
    echo "Resposta: $response\n";
}

curl_close($ch);
?>