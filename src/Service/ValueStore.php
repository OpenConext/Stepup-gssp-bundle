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

namespace Surfnet\GsspBundle\Service;

interface ValueStore
{
    /**
     * Is the property the given value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function is($key, $value);

    /**
     * Is there property with the given key defined.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * @param string $key
     * @param mixed $value
     *    Any scalar
     *
     * @return $this
     */
    public function set($key, $value);

    /**
     * @param string $key
     * @return mixed
     *    Any scalar
     *
     * @throws \Surfnet\GsspBundle\Exception\NotFound
     */
    public function get($key);

    /**
     * Clear all properties inside the store.
     *
     * @return $this
     */
    public function clear();
}
