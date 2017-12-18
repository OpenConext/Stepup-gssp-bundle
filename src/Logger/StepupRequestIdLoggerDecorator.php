<?php
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
use Surfnet\GsspBundle\Service\StateHandlerInterface;

/**
 * Add's stepup request id to the logs.
 */
final class StepupRequestIdLoggerDecorator extends AbstractLogger
{
    private $logger;
    private $stateHandler;

    public function __construct(
        LoggerInterface $logger,
        StateHandlerInterface $stateHandler
    ) {
        $this->logger = $logger;
        $this->stateHandler = $stateHandler;
    }

    public function log($level, $message, array $context = array())
    {
        if ($this->stateHandler->hasStepupRequestId()) {
            $context['_request_id'] = $this->stateHandler->getStepupRequestId();
        }
        $this->logger->log($level, $message, $context);
    }
}
