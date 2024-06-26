<?php

declare(strict_types = 1);

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\GsspBundle\Monolog\Processor;

use Monolog\LogRecord;

class RequestIdProcessor
{
    private string $requestId;

    public function __construct()
    {
        $this->requestId = md5(openssl_random_pseudo_bytes(50));
    }

    /**
     * Adds the random request ID onto the records extra data.
     *
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record['extra']['request_id'] = $this->requestId;

        return $record;
    }
}
