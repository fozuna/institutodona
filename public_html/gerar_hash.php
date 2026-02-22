<?php
$pass = $_POST['pass'] ?? $_GET['pass'] ?? '';
if ($pass !== '') {
    echo password_hash($pass, PASSWORD_DEFAULT);
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Gerar Hash de Senha</title>
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body class="p-6">
  <div class="bg-white shadow rounded p-4 max-w-md mx-auto">
    <h1 class="text-xl font-semibold mb-3">Gerar Hash de Senha</h1>
    <form method="post" class="space-y-3">
      <div>
        <label class="block text-sm">Senha</label>
        <input type="text" name="pass" class="border rounded p-2 w-full" required />
      </div>
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">Gerar</button>
    </form>
    <div class="mt-3 text-sm text-gray-600">Também funciona via GET: ?pass=SuaSenha</div>
  </div>
</body>
</html>

