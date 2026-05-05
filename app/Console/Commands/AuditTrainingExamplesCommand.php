<?php

namespace App\Console\Commands;

use App\Models\AITrainingExample;
use App\Models\ReportType;
use App\Services\ExampleAuditorService;
use Illuminate\Console\Command;

class AuditTrainingExamplesCommand extends Command
{
    protected $signature = 'examples:audit
                            {--report-type= : ID del tipo de reporte a auditar (omitir = todos)}
                            {--mark : Persistir el resultado en la DB (audit_status, excluido_few_shot)}
                            {--exclude-contaminated : Marcar contaminados como excluido_few_shot=true}';

    protected $description = 'Audita ejemplos de entrenamiento contra el prompt del cliente y detecta contaminación.';

    public function handle(ExampleAuditorService $auditor): int
    {
        $query = ReportType::query()->with('training.examples');
        if ($id = $this->option('report-type')) {
            $query->where('id', $id);
        }

        $reportTypes = $query->get();
        if ($reportTypes->isEmpty()) {
            $this->warn('No se encontraron tipos de reporte.');
            return self::SUCCESS;
        }

        $globalSummary = ['total' => 0, 'ok' => 0, 'warning' => 0, 'contaminated' => 0];

        foreach ($reportTypes as $reportType) {
            $this->newLine();
            $this->line("<fg=cyan>━━━ {$reportType->nombre} (ID {$reportType->id}) ━━━</>");

            $report = $auditor->auditReportType($reportType);

            if ($report['status'] === 'no_training') {
                $this->warn('  Sin entrenamiento — saltado.');
                continue;
            }

            $forbidden = $report['forbidden_terms_detected'];
            $this->line('  Palabras prohibidas detectadas en prompt: '
                . (empty($forbidden) ? '<fg=yellow>ninguna</>' : '<fg=magenta>' . implode(', ', $forbidden) . '</>'));

            $limits = $report['word_limits'];
            if ($limits['min'] || $limits['max']) {
                $this->line("  Límites de palabras: min={$limits['min']} max={$limits['max']}");
            }

            $this->table(
                ['ID', 'Capítulo', 'Status', 'Hits', 'Palabras', 'Violación'],
                array_map(fn($e) => [
                    $e['example_id'],
                    mb_strimwidth($e['capitulo'] ?? '-', 0, 30, '…'),
                    $this->colorStatus($e['status']),
                    count($e['forbidden_hits']) . (empty($e['forbidden_hits']) ? '' : ': ' . mb_strimwidth(implode(', ', $e['forbidden_hits']), 0, 35, '…')),
                    $e['word_count'],
                    $e['word_violation'] ?? '-',
                ], $report['examples'])
            );

            $s = $report['summary'];
            $this->line("  Resumen: {$s['total']} ejemplos | <fg=green>ok={$s['ok']}</> | <fg=yellow>warning={$s['warning']}</> | <fg=red>contaminados={$s['contaminated']}</>");

            foreach (['total', 'ok', 'warning', 'contaminated'] as $k) {
                $globalSummary[$k] += $s[$k];
            }

            if ($this->option('mark') || $this->option('exclude-contaminated')) {
                $this->persistResults($report);
            }
        }

        $this->newLine();
        $this->line("<fg=cyan>━━━ TOTAL GLOBAL ━━━</>");
        $this->line("  {$globalSummary['total']} ejemplos | <fg=green>ok={$globalSummary['ok']}</> | <fg=yellow>warning={$globalSummary['warning']}</> | <fg=red>contaminados={$globalSummary['contaminated']}</>");

        if (!$this->option('mark') && !$this->option('exclude-contaminated')) {
            $this->newLine();
            $this->comment('Tip: usá --mark para persistir el audit_status, o --exclude-contaminated para sacar los contaminados del few-shot.');
        }

        return self::SUCCESS;
    }

    private function persistResults(array $report): void
    {
        $excludeContaminated = (bool) $this->option('exclude-contaminated');

        foreach ($report['examples'] as $e) {
            $update = [
                'audit_status' => $e['status'],
                'audit_findings' => [
                    'forbidden_hits' => $e['forbidden_hits'],
                    'word_count' => $e['word_count'],
                    'word_violation' => $e['word_violation'],
                ],
                'audited_at' => now(),
            ];

            if ($excludeContaminated && $e['status'] === 'contaminated') {
                $update['excluido_few_shot'] = true;
            }

            AITrainingExample::where('id', $e['example_id'])->update($update);
        }

        $this->info('  → Resultados persistidos.');
    }

    private function colorStatus(string $status): string
    {
        return match ($status) {
            'ok' => '<fg=green>ok</>',
            'warning' => '<fg=yellow>warning</>',
            'contaminated' => '<fg=red>CONTAMINATED</>',
            default => $status,
        };
    }
}
