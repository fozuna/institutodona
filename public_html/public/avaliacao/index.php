<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/../avaliacoes.php';
if ($query !== '') {
    $target .= '?' . $query;
}
header('Location: ' . $target, true, 302);
exit;
