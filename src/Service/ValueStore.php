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

namespace Surfnet\GsspBundle\Service;

use Surfnet\GsspBundle\Exception\NotFound;

interface ValueStore
{
    /**
     * Is the property the given value.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function is(string $key, mixed $value): bool;

    /**
     * Is there property with the given key defined.
     */
    public function has(string $key): bool;

    public function set(string $key, mixed $value): self;

    /**
     * @throws NotFound
     */
    public function get(string $key): mixed;

    /**
     * Clear all properties inside the store.
     */
    public function clear(): self;
}
