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

namespace Surfnet\GsspBundle\Service\ValueStore;

use Surfnet\GsspBundle\Exception\NotFound;
use Surfnet\GsspBundle\Service\ValueStore;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionValueStore implements ValueStore
{
    public const SESSION_PATH = 'surfnet/gssp/request/';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function set(string $key, mixed $value): self
    {
        $this->session->set(self::SESSION_PATH.$key, $value);

        return $this;
    }

    public function get(string $key): mixed
    {
        if (!$this->has($key)) {
            throw NotFound::stateProperty($key);
        }

        return $this->session->get(self::SESSION_PATH.$key);
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function is(string $key, mixed $value): bool
    {
        return $this->has($key) && $this->get($key) === $value;
    }

    public function has(string $key): bool
    {
        return $this->session->has(self::SESSION_PATH.$key);
    }

    public function clear(): self
    {
        $this->session->invalidate();
        return $this;
    }
}
