<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/includes/escala.php';

$msg = '';

// ── Salvar alterações ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = carregarDados();
    $semPost = (int)($_POST['sem'] ?? 0);

    // Salvar status de cada célula
    foreach (FUNCIONARIOS as $nome) {
        for ($d = 0; $d < 7; $d++) {
            $chave = "{$semPost}_{$nome}_{$d}";
            $inputKey = "status_{$nome}_{$d}";
            if (isset($_POST[$inputKey])) {
                $val = $_POST[$inputKey];
                $padrao = statusPadrao($nome, $semPost, $d);
                if ($val === $padrao) {
                    unset($dados['overrides'][$chave]);
                } else {
                    $dados['overrides'][$chave] = $val;
                }
            }
        }
        // Observação
        $obsChave = "{$semPost}_{$nome}";
        $obsVal = trim($_POST["obs_{$nome}"] ?? '');
        if ($obsVal === '') {
            unset($dados['obs'][$obsChave]);
        } else {
            $dados['obs'][$obsChave] = $obsVal;
        }
    }
    salvarDados($dados);
    $msg = 'Escala salva com sucesso!';
}

// ── Resetar semana ─────────────────────────────────────────────────────────────
if (isset($_GET['reset'])) {
    $semReset = (int)$_GET['reset'];
    $dados = carregarDados();
    foreach (FUNCIONARIOS as $nome) {
        for ($d = 0; $d < 7; $d++) {
            unset($dados['overrides']["{$semReset}_{$nome}_{$d}"]);
        }
        unset($dados['obs']["{$semReset}_{$nome}"]);
    }
    salvarDados($dados);
    header("Location: admin.php?sem=$semReset&msg=reset");
    exit;
}

$semIdx = isset($_GET['sem']) ? (int)$_GET['sem'] : semanaAtual();
if (isset($_GET['msg']) && $_GET['msg'] === 'reset') $msg = 'Semana resetada para o padrão.';

$escala = getEscalaSemana($semIdx);
$dom = domingoSemana($semIdx);

$statusOpts = [
    'dom'  => 'Domingo',
    'sab'  => 'Sábado',
    'trb'  => 'Trabalha',
    'flg'  => 'Folga',
    'off'  => 'Folga FDS',
];

function badgeAdmin(string $s): string {
    $map = ['dom'=>'dom','sab'=>'sab','trb'=>'trb','flg'=>'flg','off'=>'off'];
    $labels = ['dom'=>'Dom','sab'=>'Sáb','trb'=>'TRB','flg'=>'FLG','off'=>'—'];
    $cls = $map[$s] ?? 'off';
    return "<span class=\"badge $cls\">{$labels[$cls]}</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · Escala</title>
<style>
:root{
  --bg:#f8f7f4;--card:#fff;--border:#e5e3de;--text:#1a1a18;--muted:#6b6b68;
  --accent:#4f46e5;--accent-hover:#4338ca;--accent-light:#eeedfe;
  --dom-bg:#eeedfe;--dom-fg:#3c3489;
  --sab-bg:#e6f1fb;--sab-fg:#0c447c;
  --trb-bg:#eaf3de;--trb-fg:#27500a;
  --flg-bg:#f1efe8;--flg-fg:#5f5e5a;
  --off-bg:#f8f7f4;--off-fg:#aaa;
  --success-bg:#f0fdf4;--success-fg:#15803d;--success-border:#bbf7d0;
  --danger:#dc2626;--danger-bg:#fef2f2;--danger-border:#fecaca;
  --radius:14px;--radius-sm:8px
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--text);min-height:100vh}
header{background:var(--card);border-bottom:1px solid var(--border);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.header-left{display:flex;align-items:center;gap:10px}
.header-left .icon{font-size:20px}
.header-left h1{font-size:16px;font-weight:600}
.header-right{display:flex;align-items:center;gap:8px}
.btn{padding:8px 16px;border-radius:var(--radius-sm);font-size:13px;font-weight:500;cursor:pointer;border:1px solid var(--border);text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:background .15s;background:var(--card);color:var(--text)}
.btn:hover{background:var(--bg)}
.btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}
.btn-primary:hover{background:var(--accent-hover)}
.btn-danger{color:var(--danger);border-color:#fecaca}
.btn-danger:hover{background:var(--danger-bg)}
.btn-sm{padding:5px 10px;font-size:12px}
nav-week{display:flex;align-items:center;gap:8px}
.nav{display:flex;align-items:center;gap:6px;margin:0 auto}
.nav a{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);text-decoration:none;font-size:16px;transition:background .15s}
.nav a:hover{background:var(--bg)}
.nav .semana-label{font-size:13px;font-weight:500;min-width:140px;text-align:center;color:var(--muted)}
.content{max-width:760px;margin:0 auto;padding:20px 16px}
.msg{padding:12px 16px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;border:1px solid}
.msg.ok{background:var(--success-bg);color:var(--success-fg);border-color:var(--success-border)}
.func-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px;overflow:hidden}
.func-header{padding:14px 16px 10px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border);background:#fafafa}
.avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:13px;flex-shrink:0}
.func-name{font-weight:600;font-size:15px}
.func-sub{font-size:12px;color:var(--muted);margin-top:1px}
.dias-grid{display:grid;grid-template-columns:repeat(7,1fr)}
.dia-cell{border-right:1px solid var(--border);padding:10px 6px 8px;text-align:center}
.dia-cell:last-child{border-right:none}
.dia-cell.wknd{background:#fafafa}
.dia-cell.modified{background:#fffbeb}
.dia-label{font-size:10px;color:var(--muted);font-weight:500;margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px}
.dia-date{font-size:10px;color:var(--muted);margin-top:5px}
.badge{display:inline-block;padding:3px 7px;border-radius:5px;font-size:11px;font-weight:600}
.badge.dom{background:var(--dom-bg);color:var(--dom-fg)}
.badge.sab{background:var(--sab-bg);color:var(--sab-fg)}
.badge.trb{background:var(--trb-bg);color:var(--trb-fg)}
.badge.flg{background:var(--flg-bg);color:var(--flg-fg)}
.badge.off{background:var(--off-bg);color:var(--off-fg)}
select.dia-select{width:100%;padding:4px 2px;border:1px solid var(--border);border-radius:5px;font-size:11px;background:var(--card);color:var(--text);cursor:pointer;margin-top:4px}
select.dia-select:focus{outline:none;border-color:var(--accent)}
.obs-row{padding:10px 16px;border-top:1px solid var(--border);display:flex;align-items:center;gap:8px}
.obs-row label{font-size:12px;color:var(--muted);white-space:nowrap;font-weight:500}
.obs-row input{flex:1;padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:var(--card);color:var(--text)}
.obs-row input:focus{outline:none;border-color:var(--accent)}
.form-actions{margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.modified-dot{display:inline-block;width:6px;height:6px;background:#f59e0b;border-radius:50%;margin-left:4px;vertical-align:middle}
</style>
</head>
<body>
<header>
  <div class="header-left">
    <span class="icon">📅</span>
    <div>
      <h1>Escala · Admin</h1>
    </div>
  </div>
  <div class="nav">
    <a href="?sem=<?= $semIdx - 1 ?>">‹</a>
    <span class="semana-label"><?= labelSemana($semIdx) ?></span>
    <a href="?sem=<?= $semIdx + 1 ?>">›</a>
  </div>
  <div class="header-right">
    <a href="index.php?sem=<?= $semIdx ?>" class="btn btn-sm" target="_blank">👁 Ver público</a>
    <a href="?sem=<?= semanaAtual() ?>" class="btn btn-sm">Hoje</a>
    <a href="logout.php" class="btn btn-sm">Sair</a>
  </div>
</header>

<div class="content">
  <?php if ($msg): ?>
  <div class="msg ok">✓ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="sem" value="<?= $semIdx ?>">

    <?php
    $dados = carregarDados();
    $cores = ['Emily'=>['bg'=>'#eeedfe','fg'=>'#534ab7'],'Rafaela'=>['bg'=>'#faeeda','fg'=>'#854f0b'],'Alisson'=>['bg'=>'#e1f5ee','fg'=>'#0f6e56'],'Julia'=>['bg'=>'#faece7','fg'=>'#993c1d']];
    foreach (FUNCIONARIOS as $nome):
        $info = $escala[$nome];
        $cor = $cores[$nome];
        $iniciais = strtoupper(substr($nome,0,1)) . strtoupper(substr($nome,1,1));
        $temObs = !empty($info['obs']);
    ?>
    <div class="func-card">
      <div class="func-header">
        <div class="avatar" style="background:<?= $cor['bg'] ?>;color:<?= $cor['fg'] ?>"><?= $iniciais ?></div>
        <div>
          <div class="func-name"><?= $nome ?></div>
          <div class="func-sub">Clique no turno para alterar</div>
        </div>
      </div>
      <div class="dias-grid">
        <?php for ($d = 0; $d < 7; $d++):
          $isWknd = ($d === 0 || $d === 6);
          $dataTs = $dom + $d * 86400;
          $current = $info['dias'][$d];
          $padrao = statusPadrao($nome, $semIdx, $d);
          $isModified = isset($dados['overrides']["{$semIdx}_{$nome}_{$d}"]);
        ?>
        <div class="dia-cell<?= $isWknd ? ' wknd' : '' ?><?= $isModified ? ' modified' : '' ?>">
          <div class="dia-label">
            <?= DIAS_NOMES[$d] ?><?php if ($isModified) echo '<span class="modified-dot" title="Alterado"></span>'; ?>
          </div>
          <select name="status_<?= $nome ?>_<?= $d ?>" class="dia-select">
            <?php foreach ($statusOpts as $val => $label): ?>
              <option value="<?= $val ?>"<?= $current === $val ? ' selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
          <div class="dia-date"><?= date('d/m', $dataTs) ?></div>
        </div>
        <?php endfor; ?>
      </div>
      <div class="obs-row">
        <label for="obs_<?= $nome ?>">💬 Obs:</label>
        <input type="text" id="obs_<?= $nome ?>" name="obs_<?= $nome ?>" value="<?= htmlspecialchars($info['obs']) ?>" placeholder="Observação para <?= $nome ?> (opcional)">
      </div>
    </div>
    <?php endforeach; ?>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">💾 Salvar semana</button>
      <a href="?sem=<?= $semIdx ?>&reset=<?= $semIdx ?>" class="btn btn-danger btn-sm" onclick="return confirm('Resetar semana para o padrão?')">↺ Resetar para padrão</a>
      <span style="font-size:12px;color:var(--muted);margin-left:4px">
        <span class="modified-dot"></span> = dia alterado manualmente
      </span>
    </div>
  </form>
</div>
</body>
</html>
