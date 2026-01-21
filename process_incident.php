<?php
// process_incident.php
// Aqui você inclui a conexão com seu banco:
require 'conexao.php'; // Ajuste para o nome correto do seu arquivo de conexão

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportedBy     = $_POST['reportedBy']     ?? '';
    $location       = $_POST['location']       ?? '';
    $typeOfIssue    = $_POST['typeOfIssue']    ?? '';
    $severityLevel  = $_POST['severityLevel']  ?? '';
    $description    = $_POST['description']    ?? '';
    $additionalInfo = $_POST['additionalInfo'] ?? '';

    $sql = "INSERT INTO incidents_reports (reported_by, location, type_of_issue, severity_level, description, additional_info)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss",
        $reportedBy,
        $location,
        $typeOfIssue,
        $severityLevel,
        $description,
        $additionalInfo
    );

    if ($stmt->execute()) {
        echo "OK"; // ou pode exibir algo mais elaborado
    } else {
        echo "Erro: " . $stmt->error;
    }
}
