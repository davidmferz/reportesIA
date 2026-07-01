<?php

namespace Tests\Unit;

use App\Services\AITrainingService;
use App\Services\DocumentExtractorService;
use App\Services\OutputSimilarityJudgeService;
use App\Services\OutputValidatorService;
use App\Services\PromptParserService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AITrainingValidationMergeTest extends TestCase
{
    public function test_la_validacion_final_falla_si_el_juez_de_similitud_falla(): void
    {
        $service = new AITrainingService(
            new DocumentExtractorService(),
            new OutputValidatorService(new PromptParserService()),
            null,
            new OutputSimilarityJudgeService(),
        );

        $promptValidation = [
            'valid' => true,
            'violations' => [],
            'metrics' => ['word_count' => 120],
            'feedback_for_ai' => null,
        ];

        $similarityValidation = [
            'valid' => false,
            'violations' => [[
                'type' => 'training_output_similarity_below_threshold',
                'severity' => 'critical',
                'detail' => 'Similitud insuficiente',
            ]],
            'best_score' => 0.18,
            'threshold' => 0.42,
            'best_reference' => ['id' => 9, 'grupo_id' => 1, 'capitulo' => 'Objetivo'],
            'comparisons' => [],
            'feedback_for_ai' => 'Acercá el texto a las salidas de entrenamiento.',
        ];

        $method = new ReflectionMethod($service, 'mergeValidationResults');
        $result = $method->invoke($service, $promptValidation, $similarityValidation);

        $this->assertFalse($result['valid']);
        $this->assertCount(1, $result['violations']);
        $this->assertSame('training_output_similarity_below_threshold', $result['violations'][0]['type']);
        $this->assertArrayHasKey('training_output_similarity', $result['metrics']);
        $this->assertStringContainsString('Acercá el texto', $result['feedback_for_ai']);
    }
}
