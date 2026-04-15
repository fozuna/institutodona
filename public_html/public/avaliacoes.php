<?php
require dirname(__DIR__, 2) . '/app/autoload.php';

$controller = new \App\Controllers\PublicAvaliacoesController();
$controller->handle();
