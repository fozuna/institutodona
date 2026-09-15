<?php
// Correção crítica: certificados de treinamentos desconfigurados.
//
// Regressão introduzida no commit d2dde64 (29/05/2026): TreinamentoDocumentService::
// renderCertificatePdf() usava `.page`/`.certificate` com `width:100%;height:100%`
// ANINHADOS (cada um com seu próprio padding) + `page-break-inside:avoid`. O
// Dompdf resolvia essa cascata de porcentagens sem nenhuma altura de referência
// real em html/body de forma imprevisível, calculando uma caixa MAIOR que a
// própria página A4 paisagem (medido no diagnóstico: ~930x670pt para uma página
// de 841.89x595.28pt) - e como o bloco "não cabia" numa página segundo esse
// cálculo, `page-break-inside:avoid` empurrava o certificado inteiro para a
// página 2, deixando a página 1 completamente em branco e cortando o conteúdo
// nas bordas da página 2.
//
// Este teste chama o renderizador REAL de produção (TreinamentoDocumentService::
// renderCertificatePdf(), o mesmo usado por certificado() e certificadoLote() em
// TreinamentosController) e inspeciona a estrutura binária do PDF resultante -
// sem depender de bytes exatos (frágil), mas validando invariantes estruturais:
// exatamente 1 página, orientação paisagem, stream de conteúdo não vazio, e
// TODAS as coordenadas dos operadores de desenho/texto dentro dos limites reais
// do MediaBox da página (a evidência direta que comprovou a causa raiz no
// diagnóstico).
require __DIR__ . '/../autoload.php';

use App\Services\TreinamentoDocumentService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

/**
 * Extrai os objetos /Type /Page (não /Pages) de um PDF gerado pelo Dompdf/CPDF
 * e, para cada um, decodifica o stream de conteúdo (FlateDecode) e varre todas
 * as coordenadas usadas pelos operadores de desenho (m/l/re) e posicionamento
 * de texto (Td), retornando os limites [minX,maxX,minY,maxY] encontrados.
 * Parsing propositalmente simples (regex sobre a estrutura textual do PDF) -
 * suficiente para detectar regressões estruturais, sem reimplementar um parser
 * PDF completo.
 */
function inspectCertificatePdf(string $pdf): array
{
    if (strpos($pdf, '%PDF-') !== 0) {
        return ['valid' => false];
    }
    preg_match_all('/\/MediaBox\s*\[([^\]]+)\]/', $pdf, $mbMatches);
    $mediaBox = null;
    foreach ($mbMatches[1] as $raw) {
        $parts = array_map('floatval', preg_split('/\s+/', trim($raw)));
        if (count($parts) === 4) {
            $mediaBox = $parts;
            break;
        }
    }

    preg_match_all('/(\d+) 0 obj\s*<<([^>]*\/Type\s*\/Page[^s][^>]*)>>/s', $pdf, $pages, PREG_SET_ORDER);
    $pageObjNums = array_map(static fn(array $p): string => $p[1], $pages);

    $pageStreams = [];
    foreach ($pageObjNums as $num) {
        if (!preg_match('/' . $num . ' 0 obj(.*?)\/Contents\s+(\d+)\s+0\s+R/s', $pdf, $m)) {
            $pageStreams[$num] = ['bytes' => 0, 'minX' => null, 'maxX' => null, 'minY' => null, 'maxY' => null];
            continue;
        }
        $contentsObj = $m[2];
        if (!preg_match('/' . $contentsObj . ' 0 obj.*?stream\r?\n(.*?)endstream/s', $pdf, $sm)) {
            $pageStreams[$num] = ['bytes' => 0, 'minX' => null, 'maxX' => null, 'minY' => null, 'maxY' => null];
            continue;
        }
        $raw = $sm[1];
        $decoded = @gzuncompress($raw);
        if ($decoded === false) {
            $decoded = @gzinflate(substr($raw, 2));
        }
        if ($decoded === false) {
            $decoded = $raw;
        }
        $decoded = (string)$decoded;

        $textBlocks = preg_match_all('/BT(.*?)ET/s', $decoded);

        preg_match_all('/(-?\d+\.\d+) (-?\d+\.\d+) (?:m|l|re) ?/', $decoded, $coordsML);
        preg_match_all('/(-?\d+\.\d+) (-?\d+\.\d+) Td/', $decoded, $coordsTd);
        $allX = array_map('floatval', array_merge($coordsML[1] ?? [], $coordsTd[1] ?? []));
        $allY = array_map('floatval', array_merge($coordsML[2] ?? [], $coordsTd[2] ?? []));

        $pageStreams[$num] = [
            'bytes' => strlen($decoded),
            'text_blocks' => $textBlocks,
            'minX' => empty($allX) ? null : min($allX),
            'maxX' => empty($allX) ? null : max($allX),
            'minY' => empty($allY) ? null : min($allY),
            'maxY' => empty($allY) ? null : max($allY),
        ];
    }

    return [
        'valid' => true,
        'media_box' => $mediaBox,
        'page_count' => count($pageObjNums),
        'pages' => $pageStreams,
    ];
}

function assertSinglePagePortraitSafe(string $label, string $pdf): array
{
    $info = inspectCertificatePdf($pdf);
    if (empty($info['valid'])) {
        failFast("$label: PDF inválido (não começa com %PDF-).");
    }
    if ((int)$info['page_count'] !== 1) {
        failFast("$label: esperado exatamente 1 página, obtido " . $info['page_count'] . '.');
    }
    $mb = $info['media_box'];
    if ($mb === null) {
        failFast("$label: MediaBox não encontrado.");
    }
    [$x0, $y0, $x1, $y1] = $mb;
    $width = $x1 - $x0;
    $height = $y1 - $y0;
    if ($width <= $height) {
        failFast("$label: página não está em paisagem (largura=$width altura=$height).");
    }
    // A4 paisagem ~ 841.89 x 595.28pt (297x210mm), com folga para arredondamento.
    if (abs($width - 841.89) > 2.0 || abs($height - 595.28) > 2.0) {
        failFast("$label: dimensões fora do esperado para A4 paisagem (obtido {$width}x{$height}).");
    }

    $page = array_values($info['pages'])[0] ?? null;
    if ($page === null || $page['bytes'] <= 0) {
        failFast("$label: stream de conteúdo da página está vazio (página em branco).");
    }
    if (($page['text_blocks'] ?? 0) < 5) {
        failFast("$label: poucos blocos de texto (" . ($page['text_blocks'] ?? 0) . ") - conteúdo principal parece ausente.");
    }
    foreach (['minX' => 0, 'maxX' => 0, 'minY' => 0, 'maxY' => 0] as $key => $_) {
        if ($page[$key] === null) {
            failFast("$label: não foi possível extrair coordenadas de desenho da página.");
        }
    }
    if ($page['minX'] < $x0 - 0.5 || $page['maxX'] > $x1 + 0.5) {
        failFast("$label: geometria excede a largura da página (X entre {$page['minX']} e {$page['maxX']}, MediaBox 0..$width).");
    }
    if ($page['minY'] < $y0 - 0.5 || $page['maxY'] > $y1 + 0.5) {
        failFast("$label: geometria excede a altura da página (Y entre {$page['minY']} e {$page['maxY']}, MediaBox 0..$height).");
    }

    return $info;
}

$treinamento = [
    'template_certificado' => '',
    'assinatura_responsavel' => 'João Pedro Catrinck',
];

// Cenário 1: equivalente ao certificado real relatado como defeituoso.
$agendaReal = [
    'id' => 20,
    'data' => '2026-07-29 06:00:00',
    'data_fim' => '2026-07-29 07:00:00',
    'treinamento_nome' => 'Treinamento de Manual de Processo Contabilidade',
    'carga_horaria' => '1.00',
    'instrutor' => 'João Pedro Catrinck',
    'responsavel_nome' => 'João Pedro Catrinck',
];
$participantReal = [
    'colaborador_id' => 859,
    'colaborador_nome' => 'GABRIEL PEREIRA FIGUEIREDO',
    'certificado_numero' => 'CERT-20-859-1785770784',
    'certificado_codigo' => '8BC37048370E9B70256E',
];

$service = new TreinamentoDocumentService();
$pdfReal = $service->renderCertificatePdf($treinamento, $agendaReal, $participantReal);
assertSinglePagePortraitSafe('Cenário real (Gabriel Pereira Figueiredo)', $pdfReal);
ok('Cenário real: 1 página A4 paisagem, sem página em branco, geometria dentro do MediaBox');

// Cenário 2: nomes/títulos significativamente mais longos que o caso real -
// participante, treinamento e instrutor com nomes muito extensos, carga
// horária de 3 dígitos - exatamente o tipo de conteúdo que a correção precisa
// suportar sem gerar overflow nem uma segunda página.
$agendaStress = [
    'id' => 21,
    'data' => '2026-07-29 06:00:00',
    'data_fim' => '2026-07-29 07:00:00',
    'treinamento_nome' => 'Treinamento Avançado e Completo de Integração de Processos Administrativos, Financeiros, Contábeis e de Compliance Corporativo Multissetorial',
    'carga_horaria' => '120.50',
    'instrutor' => 'Professor Doutor Bartholomeu Nepomuceno da Silva Albuquerque Filho de Vasconcelos e Andrade Neto',
    'responsavel_nome' => 'Professor Doutor Bartholomeu Nepomuceno da Silva Albuquerque Filho de Vasconcelos e Andrade Neto',
];
$participantStress = [
    'colaborador_id' => 999,
    'colaborador_nome' => 'Maria Eduarda Vasconcelos de Albuquerque Pereira dos Santos Nascimento Figueiredo',
    'certificado_numero' => 'CERT-21-999-1785999999',
    'certificado_codigo' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
];
$pdfStress = $service->renderCertificatePdf($treinamento, $agendaStress, $participantStress);
assertSinglePagePortraitSafe('Cenário de estresse (nomes muito longos)', $pdfStress);
ok('Cenário de estresse: nomes muito longos continuam em 1 página, sem overflow');

// Cenário 3: apenas o nome do treinamento é excepcionalmente longo (isolando
// especificamente o texto que, no relato original, chegava a cortar na
// margem direita), mantendo os demais campos curtos.
$agendaTreinamentoLongo = [
    'id' => 22,
    'data' => '2026-08-01 08:00:00',
    'data_fim' => '2026-08-01 09:00:00',
    'treinamento_nome' => 'Treinamento de Integração, Segurança do Trabalho, Compliance e Processos Internos da Área Administrativo-Financeira',
    'carga_horaria' => '2.00',
    'instrutor' => 'Ana Silva',
    'responsavel_nome' => 'Ana Silva',
];
$participantCurto = [
    'colaborador_id' => 1,
    'colaborador_nome' => 'João Silva',
    'certificado_numero' => 'CERT-22-1-1786000000',
    'certificado_codigo' => 'ABC123',
];
$pdfTreinamentoLongo = $service->renderCertificatePdf($treinamento, $agendaTreinamentoLongo, $participantCurto);
assertSinglePagePortraitSafe('Cenário nome de treinamento longo', $pdfTreinamentoLongo);
ok('Cenário nome de treinamento longo: quebra naturalmente, sem cortar/ultrapassar a página');

echo "Treinamentos certificado PDF layout regression tests passed.\n";
