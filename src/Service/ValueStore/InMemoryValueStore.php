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

namespace Surfnet\GsspBundle\Service\ValueStore;

use Surfnet\GsspBundle\Exception\NotFound;
use Surfnet\GsspBundle\Service\ValueStore;

final class InMemoryValueStore implements ValueStore
{
    private $values = [];

    public function set($key, $value)
    {
        $this->values[$key] = $value;
        return $this;
    }

    public function get($key)
    {
        if (!isset($this->values[$key])) {
            throw NotFound::stateProperty($key);
        }
        return $this->values[$key];
    }

    public function is($key, $value)
    {
        if (!$this->has($key)) {
            return false;
        }
        return $this->values[$key] === $value;
    }

    public function has($key)
    {
        return isset($this->values[$key]);
    }

    public function clear()
    {
        $this->values = [];
        return $this;
    }
}
