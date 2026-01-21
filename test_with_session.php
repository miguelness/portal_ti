<?php
session_start();

// Simular uma sessão de usuário logado
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['nome'] = 'Usuário Teste';

echo "Sessão criada. Testando API...\n";

// Dados de teste
$data = [
    'nome' => 'Teste com Sessão',
    'empresa' => 'Empresa Teste',
    'setor' => 'TI',
    'email' => 'teste.sessao@empresa.com',
    'ramal' => '8888',
    'status' => 'ativo'
];

// Configurar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/colaboradores.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n$response\n";
?>