<?php
require_once 'config.php';
require_once __DIR__ . '/api/NewsApi.php';
require_once __DIR__ . '/api/Auth.php';

header('Content-Type: application/json');

$db = Database::getConnection();
$out = ['success' => true];

// Require admin auth
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

try {
    // Get columns first to adapt to actual schema
    $columns = [];
    try {
        $cols = $db->query("SHOW COLUMNS FROM articles")->fetchAll();
        $out['columns'] = $cols;
        foreach ($cols as $c) $columns[] = $c['Field'];
    } catch (Exception $e) {
        $out['columns_error'] = $e->getMessage();
    }

    // Total articles
    $stmt = $db->query("SELECT COUNT(*) as total FROM articles");
    $out['total_articles'] = (int) $stmt->fetch()['total'];

    // Build select list from available columns
    $wanted = ['id','title','url','published_at','source_name','url_to_image','description'];
    $selectCols = array_values(array_intersect($wanted, $columns));
    if (empty($selectCols)) {
        $out['recent'] = [];
    } else {
        $sql = "SELECT " . implode(', ', $selectCols) . " FROM articles ORDER BY published_at DESC LIMIT 10";
        $stmt2 = $db->query($sql);
        $out['recent'] = $stmt2->fetchAll();
    }

    try {
        $create = $db->query("SHOW CREATE TABLE articles")->fetch();
        $out['create'] = $create;
    } catch (Exception $e) {
        $out['create_error'] = $e->getMessage();
    }

    echo json_encode($out, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

exit;
