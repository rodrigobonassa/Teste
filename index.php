<?php
require_once __DIR__ . '/includes/escala.php';

$semIdx = isset($_GET['sem']) ? (int)$_GET['sem'] : semanaAtual();
$escala = getEscalaSemana($semIdx);
$dom = domingoSemana($semIdx);

function badgePublico(string $s): string {
    return match($s) {
        'dom'  => '<span class="badge dom">Domingo</span>',
        'sab'  => '<span class="badge sab">Sábado</span>',
        'trb'  => '<span class="badge trb">Trabalha</span>',
        'flg'  => '<span class="badge flg">Folga</span>',
        'off'  => '<span class="badge off">—</span>',
        default => '<span class="badge off">—</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Escala de Trabalho</title>
<style>
:root{
  --bg:#f8f7f4;--card:#fff;--border:#e5e3de;--text:#1a1a18;--muted:#6b6b68;
  --accent:#4f46e5;--accent-light:#eeedfe;
  --dom-bg:#eeedfe;--dom-fg:#3c3489;
  --sab-bg:#e6f1fb;--sab-fg:#0c447c;
  --trb-bg:#eaf3de;--trb-fg:#27500a;
  --flg-bg:#f1efe8;--flg-fg:#5f5e5a;
  --off-bg:#f8f7f4;--off-fg:#aaa;
  --radius:14px;--radius-sm:8px
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--text);min-height:100vh}
header{background:var(--card);border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.header-title{display:flex;align-items:center;gap:10px}
.header-title .icon{font-size:22px}
.header-title h1{font-size:17px;font-weight:600}
.header-title p{font-size:12px;color:var(--muted);margin-top:2px}
.nav{display:flex;align-items:center;gap:8px}
.nav a{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);text-decoration:none;font-size:16px;transition:background .15s}
.nav a:hover{background:var(--bg)}
.nav .semana{font-size:13px;font-weight:500;padding:0 4px;min-width:130px;text-align:center;color:var(--muted)}
.content{max-width:680px;margin:0 auto;padding:20px 16px}
.cards{display:flex;flex-direction:column;gap:12px}
.func-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.func-header{padding:14px 16px 10px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border)}
.avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:14px;flex-shrink:0}
.func-name{font-weight:600;font-size:15px}
.func-sub{font-size:12px;color:var(--muted);margin-top:1px}
.dias-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:0}
.dia-cell{padding:10px 4px 8px;text-align:center;border-right:1px solid var(--border)}
.dia-cell:last-child{border-right:none}
.dia-cell.wknd{background:#fafafa}
.dia-label{font-size:10px;color:var(--muted);font-weight:500;margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px}
.dia-date{font-size:10px;color:var(--muted);margin-top:4px}
.badge{display:inline-block;padding:3px 7px;border-radius:5px;font-size:11px;font-weight:600;white-space:nowrap}
.badge.dom{background:var(--dom-bg);color:var(--dom-fg)}
.badge.sab{background:var(--sab-bg);color:var(--sab-fg)}
.badge.trb{background:var(--trb-bg);color:var(--trb-fg)}
.badge.flg{background:var(--flg-bg);color:var(--flg-fg)}
.badge.off{background:var(--off-bg);color:var(--off-fg)}
.obs-box{padding:10px 16px;font-size:12px;color:var(--muted);border-top:1px solid var(--border);background:#fafafa;font-style:italic}
.legend{margin-top:20px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px}
.legend h3{font-size:12px;font-weight:600;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px}
.legend-items{display:flex;flex-wrap:wrap;gap:8px}
.login-link{text-align:center;margin-top:16px;font-size:12px;color:var(--muted)}
.login-link a{color:var(--accent);text-decoration:none}
.avatars{background:linear-gradient(135deg,#eeedfe,#e6f1fb)}
<?php
$avatarCores = ['Emily'=>'#eeedfe;color:#534ab7','Rafaela'=>'#faeeda;color:#854f0b','Alisson'=>'#e1f5ee;color:#0f6e56','Julia'=>'#faece7;color:#993c1d'];
foreach ($avatarCores as $n => $c) echo ".av-{$n}{background:#{$c}}";
// fix: rewrite properly
?>
</style>
</head>
<body>
<header>
  <div class="header-title">
    <span class="icon">📅</span>
    <div>
      <h1>Escala de Trabalho</h1>
      <p>Visualização da semana</p>
    </div>
  </div>
  <div class="nav">
    <a href="?sem=<?= $semIdx - 1 ?>" title="Semana anterior">‹</a>
    <span class="semana"><?= labelSemana($semIdx) ?></span>
    <a href="?sem=<?= $semIdx + 1 ?>" title="Próxima semana">›</a>
  </div>
</header>

<div class="content">
  <div class="cards">
    <?php
    $cores = ['Emily' => ['bg'=>'#eeedfe','fg'=>'#534ab7'], 'Rafaela' => ['bg'=>'#faeeda','fg'=>'#854f0b'], 'Alisson' => ['bg'=>'#e1f5ee','fg'=>'#0f6e56'], 'Julia' => ['bg'=>'#faece7','fg'=>'#993c1d']];
    foreach (FUNCIONARIOS as $nome):
        $info = $escala[$nome];
        $cor = $cores[$nome];
        $iniciais = strtoupper(substr($nome, 0, 1)) . (strlen($nome) > 1 ? strtoupper(substr($nome, 1, 1)) : '');
    ?>
    <div class="func-card">
      <div class="func-header">
        <div class="avatar" style="background:<?= $cor['bg'] ?>;color:<?= $cor['fg'] ?>"><?= $iniciais ?></div>
        <div>
          <div class="func-name"><?= $nome ?></div>
          <div class="func-sub">
            <?php
            $fds = array_filter($info['dias'], fn($s) => $s === 'dom' || $s === 'sab');
            $folgaIdx = array_search('flg', $info['dias']);
            $partes = [];
            if (!empty($fds)) {
                $fdsNome = in_array('dom', $fds) ? 'Domingo' : 'Sábado';
                $partes[] = "Trabalha no $fdsNome";
            }
            if ($folgaIdx !== false) $partes[] = 'Folga na ' . DIAS_FULL[$folgaIdx];
            echo implode(' · ', $partes);
            ?>
          </div>
        </div>
      </div>
      <div class="dias-grid">
        <?php for ($d = 0; $d < 7; $d++):
          $isWknd = ($d === 0 || $d === 6);
          $dataTs = $dom + $d * 86400;
        ?>
        <div class="dia-cell<?= $isWknd ? ' wknd' : '' ?>">
          <div class="dia-label"><?= DIAS_NOMES[$d] ?></div>
          <?= badgePublico($info['dias'][$d]) ?>
          <div class="dia-date"><?= date('d/m', $dataTs) ?></div>
        </div>
        <?php endfor; ?>
      </div>
      <?php if ($info['obs']): ?>
      <div class="obs-box">💬 <?= htmlspecialchars($info['obs']) ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="legend">
    <h3>Legenda</h3>
    <div class="legend-items">
      <span class="badge dom">Domingo</span>
      <span class="badge sab">Sábado</span>
      <span class="badge trb">Trabalha</span>
      <span class="badge flg">Folga</span>
      <span class="badge off">Folga FDS</span>
    </div>
  </div>

  <div class="login-link"><a href="login.php">Área administrativa</a></div>
</div>
</body>
</html>
