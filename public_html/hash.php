<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p = $_POST['password'] ?? '';
    if ($p === '') { echo 'Informe uma senha.'; exit; }
    echo password_hash($p, PASSWORD_DEFAULT);
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<head><meta charset="utf-8"><title>Gerar hash</title></head>
<body style="font-family:system-ui;max-width:420px;margin:40px auto">
  <form method="post">
    <label>Senha</label>
    <input type="password" name="password" style="width:100%;padding:.5rem;margin:.5rem 0" />
    <button type="submit" style="padding:.5rem 1rem">Gerar</button>
  </form>
</body>
</html>