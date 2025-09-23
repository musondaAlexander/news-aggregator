<?php
require_once 'config.php';
require_once 'NewsAPI.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articleId = intval($_POST['article_id'] ?? 0);
    if ($articleId > 0) {
        $api = new NewsAPI();
        $ok = $api->trackView($articleId);
        header('Content-Type: application/json');
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
