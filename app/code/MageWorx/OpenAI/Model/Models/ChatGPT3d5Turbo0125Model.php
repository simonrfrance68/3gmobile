<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types = 1);

namespace MageWorx\OpenAI\Model\Models;

class ChatGPT3d5Turbo0125Model extends AbstractChatGPTModel
{
    /** @var string Model type */
    protected string $type = 'gpt-3.5-turbo-0125';
    protected string $path = 'v1/chat/completions';
    protected int    $maxContextLength = 16384;
}
