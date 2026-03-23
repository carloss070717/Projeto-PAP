<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/pdo.php';
require_once __DIR__ . '/../includes/emprestimo_notifier.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Acesso negado.\n";
    exit(1);
}

$limite = 200;
if (PHP_SAPI === 'cli' && isset($argv[1])) {
    $arg = filter_var($argv[1], FILTER_VALIDATE_INT);
    if (is_int($arg) && $arg > 0) {
        $limite = $arg;
    }
}

try {
    $resumo = app_notifier_enviar_atrasos_pendentes($pdo, $limite);

    echo '[ESTEL SGP] Notificação de atrasos' . PHP_EOL;
    echo 'Candidatos: ' . (int) $resumo['candidatos'] . PHP_EOL;
    echo 'Enviados: ' . (int) $resumo['enviados'] . PHP_EOL;
    echo 'Falhas: ' . (int) $resumo['falhas'] . PHP_EOL;

    foreach ($resumo['detalhes'] as $linha) {
        $id = (int) ($linha['emprestimo_id'] ?? 0);
        $status = (string) ($linha['status'] ?? 'erro');
        $mensagem = (string) ($linha['mensagem'] ?? '');
        echo '#'.$id.' ['.$status.'] '.$mensagem . PHP_EOL;
    }

    exit(((int) $resumo['falhas']) > 0 ? 2 : 0);
} catch (Throwable $e) {
    $mensagem = trim($e->getMessage()) !== '' ? trim($e->getMessage()) : 'Erro inesperado.';
    fwrite(STDERR, '[ESTEL SGP] Erro: ' . $mensagem . PHP_EOL);
    exit(1);
}

