<?php
// ─── Funcionários ────────────────────────────────────────────────────────────
define('FUNCIONARIOS', ['Emily', 'Rafaela', 'Alisson', 'Julia']);
// Pares de fim de semana: Emily+Julia (Dom semA), Rafaela+Alisson (Sab semA)
// Semana A: Emily=Dom, Julia=Dom, Rafaela=Sáb, Alisson=Sáb
// Semana B: Emily=Sáb, Julia=Sáb, Rafaela=Dom, Alisson=Dom

define('SEMANA_A', [
    'Emily'   => ['fds' => 'dom', 'folga' => 2], // Ter
    'Rafaela' => ['fds' => 'sab', 'folga' => 4], // Qui
    'Alisson' => ['fds' => 'sab', 'folga' => 5], // Sex
    'Julia'   => ['fds' => 'dom', 'folga' => 3], // Qua
]);
define('SEMANA_B', [
    'Emily'   => ['fds' => 'sab', 'folga' => 3], // Qua
    'Rafaela' => ['fds' => 'dom', 'folga' => 5], // Sex
    'Alisson' => ['fds' => 'dom', 'folga' => 4], // Qui
    'Julia'   => ['fds' => 'sab', 'folga' => 2], // Ter
]);

define('DATA_FILE', __DIR__ . '/../data/escala.json');
define('DIAS_NOMES', ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']);
define('DIAS_FULL', ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado']);

// Âncora: domingo 14/06/2026 = semana 0
define('ANCORA_TS', mktime(0,0,0,6,14,2026));

// ─── Funções de dados ─────────────────────────────────────────────────────────
function carregarDados(): array {
    if (!file_exists(DATA_FILE)) return ['overrides' => [], 'obs' => []];
    $json = file_get_contents(DATA_FILE);
    return json_decode($json, true) ?? ['overrides' => [], 'obs' => []];
}

function salvarDados(array $dados): void {
    if (!is_dir(dirname(DATA_FILE))) mkdir(dirname(DATA_FILE), 0755, true);
    file_put_contents(DATA_FILE, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ─── Lógica de escala ─────────────────────────────────────────────────────────
function semanaIndex(int $ts): int {
    $diff = $ts - ANCORA_TS;
    return (int)floor($diff / (7 * 86400));
}

function domingoSemana(int $semIdx): int {
    return ANCORA_TS + $semIdx * 7 * 86400;
}

function statusPadrao(string $nome, int $semIdx, int $dia): string {
    $pat = ($semIdx % 2 === 0) ? SEMANA_A[$nome] : SEMANA_B[$nome];
    if ($dia === 0) return $pat['fds'] === 'dom' ? 'dom' : 'off';
    if ($dia === 6) return $pat['fds'] === 'sab' ? 'sab' : 'off';
    if ($dia === 1) return 'trb'; // Segunda: sem folga
    if ($dia === $pat['folga']) return 'flg';
    return 'trb';
}

function getEscalaSemana(int $semIdx): array {
    $dados = carregarDados();
    $resultado = [];
    foreach (FUNCIONARIOS as $nome) {
        $dias = [];
        for ($d = 0; $d < 7; $d++) {
            $chave = "{$semIdx}_{$nome}_{$d}";
            $status = $dados['overrides'][$chave] ?? statusPadrao($nome, $semIdx, $d);
            $dias[$d] = $status;
        }
        $obs_chave = "{$semIdx}_{$nome}";
        $resultado[$nome] = [
            'dias' => $dias,
            'obs'  => $dados['obs'][$obs_chave] ?? '',
        ];
    }
    return $resultado;
}

function formatarData(int $ts): string {
    return date('d/m', $ts);
}

function labelSemana(int $semIdx): string {
    $dom = domingoSemana($semIdx);
    $sab = $dom + 6 * 86400;
    return formatarData($dom) . ' – ' . formatarData($sab);
}

function semanaAtual(): int {
    return semanaIndex(strtotime('sunday', strtotime('today')));
}
