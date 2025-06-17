<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types = 1);

namespace MageWorx\GoogleAI\Model\Models\Gemini;

use MageWorx\GoogleAI\Model\MessengerGemini;
use MageWorx\OpenAI\Model\Models\AbstractModel;
use MageWorx\OpenAI\Api\QueueManagementInterface as QueueManagement;

class GenericGeminiModel extends AbstractGeminiModel
{
    protected string $type;
    protected int    $maxContextLength;
    protected string $path;

    public function __construct(
        MessengerGemini $messenger,
        QueueManagement $queueManagement,
        string          $type,
        int             $maxContextLength,
        string          $path
    ) {
        parent::__construct($messenger, $queueManagement);
        $this->type             = $type;
        $this->maxContextLength = $maxContextLength;
        $this->path             = $path;
    }
}
