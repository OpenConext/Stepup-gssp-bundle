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

namespace Surfnet\GsspBundle\Saml\StateHandler;

use Surfnet\GsspBundle\Exception\NotFound;

final class MemoryStateHandler extends AbstractStateHandler
{
    private $state = [];

    protected function set($key, $value)
    {
        $this->state[$key] = $value;
        return $this;
    }

    protected function get($key)
    {
        if (!isset($this->state[$key])) {
            throw NotFound::stateProperty($key);
        }
        return $this->state[$key];
    }

    public function invalidate()
    {
        $this->state = [];
    }
}
