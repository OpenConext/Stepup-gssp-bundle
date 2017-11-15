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
use Surfnet\GsspBundle\Saml\StateHandler;

abstract class AbstractStateHandler implements StateHandler
{
    /**
     * @param string $originalRequestId
     * @return $this
     */
    public function setRequestId($originalRequestId)
    {
        $this->set('request_id', $originalRequestId);

        return $this;
    }

    /**
     * @return string|null
     *
     * @throws \Surfnet\GsspBundle\Exception\NotFound
     */
    public function getRequestId()
    {
        return $this->get('request_id');
    }

    /**
     * @return string|null
     *
     * @throws \Surfnet\GsspBundle\Exception\NotFound
     */
    public function hasRequestId()
    {
        return $this->has('request_id');
    }

    /**
     * @param string $serviceProvider
     * @return $this
     */
    public function setRequestServiceProvider($serviceProvider)
    {
        $this->set('service_provider', $serviceProvider);

        return $this;
    }

    /**
     * @return string|null
     *
     * @throws \Surfnet\GsspBundle\Exception\NotFound
     */
    public function getRequestServiceProvider()
    {
        return $this->get('service_provider');
    }

    /**
     * @param string $relayState
     * @return $this
     */
    public function setRelayState($relayState)
    {
        $this->set('relay_state', $relayState);

        return $this;
    }

    /**
     * @return string|null
     *
     * @throws \Surfnet\GsspBundle\Exception\NotFound
     */
    public function getRelayState()
    {
        return $this->get('relay_state');
    }

    /**
     * @param $nameId
     * @return $this
     */
    public function saveIdentityNameId($nameId)
    {
        $this->set('name_id', $nameId);

        return $this;
    }

    /**
     * @return null|string
     *
     * @throws \Surfnet\GsspBundle\Exception\NotFound
     */
    public function getIdentityNameId()
    {
        return $this->get('name_id');
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setPreferredLocale($locale)
    {
        $this->set('locale', $locale);

        return $this;
    }

    /**
     * @return string|null
     *
     * @throws \Surfnet\GsspBundle\Exception\NotFound
     */
    public function getPreferredLocale()
    {
        return $this->get('locale');
    }

    /**
     * @param string $nameId
     *
     * @return $this
     */
    public function setSubjectNameId($nameId)
    {
        $this->set('subject_name_id', $nameId);

        return $this;
    }

    /**
     * @return string
     *
     * @throws \Surfnet\GsspBundle\Exception\NotFound
     */
    public function getSubjectNameId()
    {
        return $this->get('subject_name_id');
    }

    /**
     * @return bool
     */
    public function hasSubjectNameId()
    {
        return $this->has('subject_name_id');
    }

    protected function has($key)
    {
        try {
            return !empty($this->get($key));
        } catch (NotFound $exception) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param mixed $value Any scalar
     */
    abstract protected function set($key, $value);

    /**
     * @param string $key
     * @return mixed|null Any scalar
     *
     * @throws \Surfnet\GsspBundle\Exception\NotFound
     */
    abstract protected function get($key);
}
