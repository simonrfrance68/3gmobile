<?php

namespace MageWorx\OpenAI\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OpenAIModels implements OptionSourceInterface
{
    // Add OpenAI models here
    private const MODELS = [
        'gpt-4',
        'gpt-4-1106-preview',
        'gpt-4-turbo',
        'gpt-4o',
        'gpt-4.1',
        'gpt-4.1-nano',
        'gpt-4.1-mini',
        'gpt-3.5-turbo',
        'gpt-3.5-turbo-16k',
        'gpt-3.5-turbo-0125',
        'gpt-3.5-turbo-1106',
        'gpt-3.5-turbo-instruct',
        'text-davinci-003',
        'text-davinci-002',
        'text-davinci-001',
        'text-ada-001',
        'text-embedding-3-small',
        'text-embedding-3-large',
        'text-embedding-ada-002',
        'text-curie-001',
        'text-babbage-001',
        'curie',
        'babbage',
        'ada',
        'davinci',
        'curie-similarity',
        'babbage-similarity',
        'ada-similarity',
        'davinci-similarity',
        'text-similarity-curie-001',
        'text-similarity-babbage-001',
        'text-similarity-ada-001',
        'text-similarity-davinci-001',
        'code-davinci-edit-001',
        'text-davinci-edit-001',
        'code-search-babbage-code-001',
        'code-search-babbage-text-001',
        'code-search-ada-code-001',
        'code-search-ada-text-001',
        'text-search-ada-query-001',
        'text-search-ada-doc-001',
        'text-search-babbage-query-001',
        'text-search-babbage-doc-001',
        'text-search-curie-query-001',
        'text-search-curie-doc-001',
        'text-search-davinci-query-001',
        'text-search-davinci-doc-001',
        'ada-search-query',
        'ada-search-document',
        'babbage-search-query',
        'babbage-search-document',
        'curie-search-query',
        'curie-search-document',
        'davinci-search-query',
        'davinci-search-document',
        'curie-instruct-beta',
        'davinci-instruct-beta',
        'ada-code-search-code',
        'ada-code-search-text',
        'whisper-1'
    ];

    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::MODELS as $value) {
            $options[] = ['value' => $value, 'label' => $this->getLabel($value)];
        }
        return $options;
    }

    private function getLabel(string $model): string
    {
        return ucwords(str_replace(['-', '_'], [' ', ' '], $model));
    }
}
