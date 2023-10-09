<?php

declare(strict_types = 1);

/**
 * Copyright 2017 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\GsspBundle\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;
use Surfnet\GsspBundle\Service\StateHandlerInterface;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;

/**
 * It depends what the current application state is will it use the sari logger.
 */
final class StepupRequestIdSariLogger extends AbstractLogger
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SamlAuthenticationLogger $sariLogger,
        private readonly StateHandlerInterface $stateHandler
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        if ($this->isRequiredToLogWithSari()) {
            $this->createSariLogger()->log($level, $message, $context);
            return;
        }
        $this->logger->log($level, $message, $context);
    }

    private function isRequiredToLogWithSari(): bool
    {
        return $this->stateHandler->hasRequestId();
    }

    private function createSariLogger(): SamlAuthenticationLogger
    {
        return $this->sariLogger->forAuthentication(
            $this->stateHandler->getRequestId()
        );
    }
}
