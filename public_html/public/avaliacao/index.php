<?php
require dirname(__DIR__, 3) . '/app/autoload.php';

(new \App\Controllers\AvaliacaoPublicaController())->handle();
