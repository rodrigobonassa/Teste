<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['senha'] === 'escala') {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    }
    $erro = true;
}
if (isset($_SESSION['admin'])) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Escala · Login</title>
<style>
:root{--bg:#f8f7f4;--card:#fff;--border:#e5e3de;--text:#1a1a18;--muted:#888;--accent:#4f46e5;--accent-light:#eeedfe;--danger:#dc2626;--radius:12px}
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:2.5rem 2rem;width:100%;max-width:360px;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.logo{text-align:center;margin-bottom:2rem}
.logo-icon{width:52px;height:52px;background:var(--accent-light);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px}
.logo h1{font-size:20px;font-weight:600;color:var(--text)}
.logo p{font-size:13px;color:var(--muted);margin-top:4px}
label{display:block;font-size:13px;font-weight:500;color:var(--text);margin-bottom:6px}
input[type=password]{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:15px;outline:none;transition:border .15s;background:#fff;color:var(--text)}
input[type=password]:focus{border-color:var(--accent)}
.erro{background:#fef2f2;border:1px solid #fecaca;color:var(--danger);font-size:13px;padding:10px 12px;border-radius:8px;margin-top:12px}
button{width:100%;margin-top:16px;padding:11px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:500;cursor:pointer;transition:background .15s}
button:hover{background:#4338ca}
.view-link{text-align:center;margin-top:1.25rem;font-size:13px;color:var(--muted)}
.view-link a{color:var(--accent);text-decoration:none;font-weight:500}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">📅</div>
    <h1>Escala de Trabalho</h1>
    <p>Área administrativa</p>
  </div>
  <form method="POST">
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" autofocus placeholder="••••••••">
    <?php if (!empty($erro)): ?>
      <div class="erro">Senha incorreta. Tente novamente.</div>
    <?php endif; ?>
    <button type="submit">Entrar</button>
  </form>
  <div class="view-link">
    <a href="index.php">Ver escala sem login →</a>
  </div>
</div>
</body>
</html>
