<?php
// Wrapper to expose the NewsAPI class from the api folder
require_once __DIR__ . '/api/NewsApi.php';

// Backwards-compatible: if the class name differs, ensure alias
if (!class_exists('NewsAPI') && class_exists('NewsApi')) {
    class_alias('NewsApi', 'NewsAPI');
}

?>
