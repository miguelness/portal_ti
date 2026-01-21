<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../conexao.php';

function createExcerpt($content, $length = 150) {
    $text = strip_tags($content);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

try {
    $article_id = filter_input(INPUT_GET, 'article_id', FILTER_VALIDATE_INT);
    
    if (!$article_id || $article_id <= 0) {
        echo json_encode([]);
        exit;
    }
    
    // Get current article's title for better matching
    $stmt = $conn->prepare("SELECT titulo FROM noticias WHERE id = ?");
    $stmt->bind_param('i', $article_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentArticle = $result->fetch_assoc();
    $stmt->close();
    
    if (!$currentArticle) {
        echo json_encode([]);
        exit;
    }
    
    // Extract keywords from current article title for better matching
    $titleWords = explode(' ', strtolower($currentArticle['titulo']));
    $titleWords = array_filter($titleWords, function($word) {
        return strlen($word) > 3; // Only words longer than 3 characters
    });
    
    $relatedPosts = [];
    
    if (!empty($titleWords)) {
        // Find posts with similar words in title (limit to 3 keywords for performance)
        $keywords = array_slice($titleWords, 0, 3);
        $likeConditions = [];
        $types = 'i'; // for article_id
        $params = [$article_id];
        
        foreach ($keywords as $word) {
            $likeConditions[] = "titulo LIKE ?";
            $types .= 's';
            $params[] = "%$word%";
        }
        
        if (!empty($likeConditions)) {
            $sql = "
                 SELECT id, titulo as title, conteudo as content, imagem as image, 
                        data_publicacao as created_at, 0 as views
                 FROM noticias 
                 WHERE id != ? AND (" . implode(' OR ', $likeConditions) . ")
                 ORDER BY data_publicacao DESC
                 LIMIT 6
             ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $relatedPosts[] = $row;
            }
            $stmt->close();
        }
    }
    
    // If we don't have enough related posts, get recent popular posts
    if (count($relatedPosts) < 3) {
        $needed = 6 - count($relatedPosts);
        $excludeIds = array_column($relatedPosts, 'id');
        $excludeIds[] = $article_id;
        
        $placeholders = str_repeat('?,', count($excludeIds) - 1) . '?';
        $types = str_repeat('i', count($excludeIds)) . 'i';
        $params = array_merge($excludeIds, [$needed]);
        
        $sql = "
             SELECT id, titulo as title, conteudo as content, imagem as image, 
                    data_publicacao as created_at, 0 as views
             FROM noticias 
             WHERE id NOT IN ($placeholders)
             ORDER BY data_publicacao DESC
             LIMIT ?
         ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $relatedPosts[] = $row;
        }
        $stmt->close();
    }
    
    // Format the response
    $formattedPosts = array_map(function($post) {
        return [
            'id' => (int)$post['id'],
            'title' => htmlspecialchars($post['title']),
            'excerpt' => createExcerpt($post['content']),
            'image' => $post['image'],
            'created_at' => $post['created_at'],
            'views' => (int)$post['views']
        ];
    }, array_slice($relatedPosts, 0, 6)); // Limit to 6 posts
    
    echo json_encode($formattedPosts);
    
} catch (Exception $e) {
    error_log("Error in related_posts.php: " . $e->getMessage());
    echo json_encode([]);
}

$conn->close();
?>