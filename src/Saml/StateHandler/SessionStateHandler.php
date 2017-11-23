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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionStateHandler extends AbstractStateHandler
{
    const SESSION_PATH = 'surfnet/gssp/request';

    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    protected function set($key, $value)
    {
        $this->session->set(self::SESSION_PATH.$key, $value);
        return $this;
    }

    protected function get($key)
    {
        $sessionKey = self::SESSION_PATH.$key;
        if (!$this->session->has($sessionKey)) {
            throw NotFound::stateProperty($sessionKey);
        }
        return $this->session->get($sessionKey);
    }

    public function invalidate()
    {
        $this->session->invalidate();
    }
}
