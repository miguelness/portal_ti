<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../conexao.php';

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

try {
    // Create comments table if it doesn't exist
    $createTable = "
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_article_id (article_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $conn->query($createTable);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle comment submission
        $article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $comment = sanitizeInput($_POST['comment'] ?? '');
        
        $errors = [];
        
        // Validation
        if (!$article_id || $article_id <= 0) {
            $errors[] = 'ID do artigo inválido';
        }
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Nome deve ter pelo menos 2 caracteres';
        }
        
        if (empty($email) || !validateEmail($email)) {
            $errors[] = 'Email inválido';
        }
        
        if (empty($comment) || strlen($comment) < 10) {
            $errors[] = 'Comentário deve ter pelo menos 10 caracteres';
        }
        
        if (strlen($comment) > 2000) {
            $errors[] = 'Comentário muito longo (máximo 2000 caracteres)';
        }
        
        // Check if article exists
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM noticias WHERE id = ?");
            $stmt->bind_param('i', $article_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if (!$result->fetch_assoc()) {
                $errors[] = 'Artigo não encontrado';
            }
            $stmt->close();
        }
        
        // Basic spam prevention
        if (empty($errors)) {
            $spamWords = ['viagra', 'casino', 'lottery', 'winner', 'click here'];
            $commentLower = strtolower($comment);
            foreach ($spamWords as $spamWord) {
                if (strpos($commentLower, $spamWord) !== false) {
                    $errors[] = 'Comentário contém conteúdo não permitido';
                    break;
                }
            }
        }
        
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }
        
        // Insert comment
        $stmt = $conn->prepare("INSERT INTO comments (article_id, name, email, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isss', $article_id, $name, $email, $comment);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Comentário adicionado com sucesso!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao salvar comentário'
            ]);
        }
        $stmt->close();
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle comment retrieval
        $article_id = filter_input(INPUT_GET, 'article_id', FILTER_VALIDATE_INT);
        
        if (!$article_id || $article_id <= 0) {
            echo json_encode([]);
            exit;
        }
        
        // Check if article exists
        $stmt = $conn->prepare("SELECT id FROM noticias WHERE id = ?");
        $stmt->bind_param('i', $article_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            echo json_encode([]);
            exit;
        }
        $stmt->close();
        
        // Get comments
        $stmt = $conn->prepare("
            SELECT name, comment, created_at 
            FROM comments 
            WHERE article_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param('i', $article_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = [
                'name' => htmlspecialchars($row['name']),
                'comment' => htmlspecialchars($row['comment']),
                'created_at' => $row['created_at']
            ];
        }
        $stmt->close();
        
        echo json_encode($comments);
    }
    
} catch (Exception $e) {
    error_log("Error in comments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}

$conn->close();
?>