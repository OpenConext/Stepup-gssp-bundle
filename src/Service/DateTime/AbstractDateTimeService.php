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

namespace Surfnet\GsspBundle\Service\DateTime;

use DateInterval;
use DateTimeImmutable;
use Exception;
use Surfnet\GsspBundle\Service\DateTimeService;

abstract class AbstractDateTimeService implements DateTimeService
{

    /**
     * @param string $interval
     *      a \DateInterval compatible interval to skew the time with
     * @return DateTimeImmutable
     *
     * @throws Exception
     */
    public function interval($interval)
    {
        $time = $this->getCurrent();
        return $time->add(new DateInterval($interval));
    }
}
