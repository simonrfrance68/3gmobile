<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\GoogleAI\Plugin;

use MageWorx\OpenAI\Model\Source\OpenAIModels;

/**
 * Add Gemini models to the source list (option array)
 */
class OpenAIModelsSourcePlugin
{
    /**
     * List of supported Google Gemini models for selection
     */
    private const GEMINI_MODELS = [
        // Gemini 1.5
        'gemini-1.5-pro'             => 'Gemini 1.5 Pro',
        'gemini-1.5-pro-002'         => 'Gemini 1.5 Pro 002',
        'gemini-1.5-flash'           => 'Gemini 1.5 Flash',
        'gemini-1.5-flash-001'       => 'Gemini 1.5 Flash 001',

        // Gemini 2.0
        'gemini-2.0-flash'           => 'Gemini 2.0 Flash',
        'gemini-2.0-flash-001'       => 'Gemini 2.0 Flash 001',
        'gemini-2.0-flash-lite-001'  => 'Gemini 2.0 Flash Lite 001',

        // Gemini 2.5
        'gemini-2.5-flash-preview-04-17' => 'Gemini 2.5 Flash Preview 04-17',
        'gemini-2.5-pro-exp-03-25'       => 'Gemini 2.5 Pro Experimental 03-25',
        'gemini-2.5-pro-preview-03-25'   => 'Gemini 2.5 Pro Preview 03-25',
    ];

    /**
     * Add Gemini models to the model selector
     *
     * @param OpenAIModels $subject
     * @param array $result
     * @return array
     */
    public function afterToOptionArray(OpenAIModels $subject, array $result): array
    {
        foreach (self::GEMINI_MODELS as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => __($label),
            ];
        }

        return $result;
    }
}
