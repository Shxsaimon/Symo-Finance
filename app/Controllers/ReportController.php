<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Relatórios Analíticos e Exportação CSV (ReportController)
 *
 * Fornece a visão analítica e sintética das movimentações financeiras
 * e exportação estruturada em CSV com codificação UTF-8 com BOM.
 */
class ReportController extends Controller
{
    private Report $reportModel;
    private Account $accountModel;
    private Category $categoryModel;
    private PaymentMethod $paymentMethodModel;
    private CreditCard $creditCardModel;

    public function __construct()
    {
        $this->reportModel = new Report();
        $this->accountModel = new Account();
        $this->categoryModel = new Category();
        $this->paymentMethodModel = new PaymentMethod();
        $this->creditCardModel = new CreditCard();
    }

    /**
     * Exibe a interface de relatórios analíticos com filtros e tabelas.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $filters = $this->extractFilters();

        $dadosAnaliticos = $this->reportModel->getDetailedReport($filters);
        $resumo = $this->reportModel->getSummary($filters);
        $dadosAgrupados = $this->reportModel->getGroupedByCategory($filters);

        $contas = $this->accountModel->getActiveAccounts();
        $categorias = $this->categoryModel->getActiveCategories();
        $meiosPagamento = $this->paymentMethodModel->getActivePaymentMethods();
        $cartoes = $this->creditCardModel->getAllWithSummary();

        $this->render('reports/index', [
            'title'           => 'Relatórios Analíticos — Symo',
            'filters'         => $filters,
            'dadosAnaliticos' => $dadosAnaliticos,
            'resumo'          => $resumo,
            'dadosAgrupados'  => $dadosAgrupados,
            'contas'          => $contas,
            'categorias'      => $categorias,
            'meiosPagamento'  => $meiosPagamento,
            'cartoes'         => $cartoes,
        ], 'layouts/main');
    }

    /**
     * Exporta os dados do relatório para arquivo CSV estruturado com delimitador ";" e BOM UTF-8.
     *
     * @param bool $exit Se true, encerra o script após o envio do arquivo
     * @return void
     */
    public function exportCsv(bool $exit = true): void
    {
        AuthHelper::requireLogin();

        $filters = $this->extractFilters();

        $dados = $this->reportModel->getDetailedReport($filters);
        $resumo = $this->reportModel->getSummary($filters);

        $timestamp = date('Y-m-d_His');
        $filename = "symo_relatorio_financeiro_{$timestamp}.csv";

        // Headers HTTP para Forçar Download
        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        $output = fopen('php://output', 'w');
        if ($output === false) {
            if ($exit) {
                exit;
            }
            return;
        }

        // 1. Gravação do Byte Order Mark (BOM) UTF-8 para compatibilidade perfeita com Microsoft Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $delimiter = ';';

        // 2. Metadados do Relatório no Cabeçalho
        fputcsv($output, ['SYMO — RELATÓRIO FINANCEIRO ANALÍTICO'], $delimiter);
        fputcsv($output, ['Gerado em:', date('d/m/Y H:i:s'), 'Usuário:', AuthHelper::user()['nome'] ?? 'Usuário'], $delimiter);
        fputcsv($output, [], $delimiter); // Linha em branco

        // 3. Cabeçalho das Colunas
        $headers = [
            'ID',
            'Data da Compra',
            'Data de Vencimento',
            'Data de Pagamento',
            'Tipo',
            'Descrição',
            'Categoria',
            'Origem / Destino',
            'Meio de Pagamento',
            'Status',
            'Parcela',
            'Valor (R$)',
            'Autor',
            'Observações'
        ];
        fputcsv($output, $headers, $delimiter);

        // 4. Linhas de Lançamentos
        foreach ($dados as $item) {
            $origemDestino = '—';
            if (!empty($item['conta_nome'])) {
                $origemDestino = $item['conta_nome'];
            } elseif (!empty($item['cartao_nome'])) {
                $origemDestino = 'Cartão: ' . $item['cartao_nome'];
            }

            $parcelaInfo = '—';
            if (!empty($item['total_parcelas']) && (int)$item['total_parcelas'] > 1) {
                $parcelaInfo = "{$item['numero_parcela']}/{$item['total_parcelas']}";
            }

            // Valor formatado em formato numérico brasileiro (ex: 1250,50 ou com sinal)
            $valorFormatado = number_format((float)$item['valor'], 2, ',', '.');
            if ($item['tipo'] === 'despesa') {
                $valorFormatado = '-' . $valorFormatado;
            } else {
                $valorFormatado = '+' . $valorFormatado;
            }

            $sanitizeCsvCell = static function (mixed $value): string {
                $text = (string) ($value ?? '');
                if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                    return "'" . $text;
                }
                return $text;
            };

            $linha = [
                $item['id'],
                FormatHelper::date($item['data_transacao']),
                FormatHelper::date($item['data_vencimento']),
                !empty($item['data_pagamento']) ? FormatHelper::date($item['data_pagamento']) : '—',
                ucfirst((string)$item['tipo']),
                $sanitizeCsvCell($item['descricao']),
                $sanitizeCsvCell($item['categoria_nome'] ?? 'Sem Categoria'),
                $sanitizeCsvCell($origemDestino),
                $sanitizeCsvCell($item['meio_pagamento_nome'] ?? '—'),
                ucfirst((string)$item['status']),
                $parcelaInfo,
                $valorFormatado,
                $sanitizeCsvCell($item['usuario_nome'] ?? 'Sistema'),
                $sanitizeCsvCell($item['observacoes'] ?? '')
            ];

            fputcsv($output, $linha, $delimiter);
        }

        // 5. Linhas de Totais e Resumo no Final
        fputcsv($output, [], $delimiter);
        fputcsv($output, ['RESUMO DO PERÍODO'], $delimiter);
        fputcsv($output, ['Total de Registros:', $resumo['total_registros']], $delimiter);
        fputcsv($output, ['Total de Receitas:', number_format($resumo['total_receitas'], 2, ',', '.')], $delimiter);
        fputcsv($output, ['Receitas Recebidas:', number_format($resumo['receitas_recebidas'], 2, ',', '.')], $delimiter);
        fputcsv($output, ['Receitas Pendentes:', number_format($resumo['receitas_pendentes'], 2, ',', '.')], $delimiter);
        fputcsv($output, ['Total de Despesas:', number_format($resumo['total_despesas'], 2, ',', '.')], $delimiter);
        fputcsv($output, ['Despesas Pagas:', number_format($resumo['despesas_pagas'], 2, ',', '.')], $delimiter);
        fputcsv($output, ['Despesas Pendentes:', number_format($resumo['despesas_pendentes'], 2, ',', '.')], $delimiter);
        fputcsv($output, ['Balanço Líquido:', number_format($resumo['saldo_periodo'], 2, ',', '.')], $delimiter);
        fputcsv($output, ['Ticket Médio:', number_format($resumo['ticket_medio'], 2, ',', '.')], $delimiter);

        fclose($output);
        if ($exit) {
            exit;
        }
    }

    /**
     * Extrai e sanitiza os parâmetros de filtro da requisição GET.
     *
     * @return array<string, mixed>
     */
    private function extractFilters(): array
    {
        $mesAtual = (int) date('n');
        $anoAtual = (int) date('Y');

        $tipoPeriodo = !empty($_GET['tipo_periodo']) ? (string) $_GET['tipo_periodo'] : 'mes';
        $mes = !empty($_GET['mes']) ? max(1, min(12, (int) $_GET['mes'])) : $mesAtual;
        $ano = !empty($_GET['ano']) ? (int) $_GET['ano'] : $anoAtual;

        $primeiroDiaMes = sprintf('%04d-%02d-01', $ano, $mes);
        $ultimoDiaMes = (new DateTimeImmutable($primeiroDiaMes))->format('Y-m-t');

        $dataInicio = !empty($_GET['data_inicio']) ? (string) $_GET['data_inicio'] : $primeiroDiaMes;
        $dataFim = !empty($_GET['data_fim']) ? (string) $_GET['data_fim'] : $ultimoDiaMes;

        return [
            'tipo_periodo'      => $tipoPeriodo,
            'mes'               => $mes,
            'ano'               => $ano,
            'data_inicio'       => $dataInicio,
            'data_fim'          => $dataFim,
            'tipo'              => !empty($_GET['tipo']) ? (string) $_GET['tipo'] : '',
            'status'            => !empty($_GET['status']) ? (string) $_GET['status'] : '',
            'categoria_id'      => !empty($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : null,
            'conta_id'          => !empty($_GET['conta_id']) ? (int) $_GET['conta_id'] : null,
            'cartao_credito_id' => !empty($_GET['cartao_credito_id']) ? (int) $_GET['cartao_credito_id'] : null,
            'meio_pagamento_id' => !empty($_GET['meio_pagamento_id']) ? (int) $_GET['meio_pagamento_id'] : null,
            'busca'             => !empty($_GET['busca']) ? trim((string) $_GET['busca']) : null,
            'agrupamento'       => !empty($_GET['agrupamento']) ? (string) $_GET['agrupamento'] : 'analitico',
        ];
    }
}
