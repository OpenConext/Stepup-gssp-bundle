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

namespace Surfnet\GsspBundle\Saml;

interface StateHandler
{
    /**
     * @param string $originalRequestId
     * @return $this
     */
    public function setRequestId($originalRequestId);

    /**
     * @return string
     */
    public function getRequestId();

    /**
     * @param string $serviceProvider
     * @return $this
     */
    public function setRequestServiceProvider($serviceProvider);

    /**
     * @return string
     */
    public function getRequestServiceProvider();

    /**
     * @param string $relayState
     * @return $this
     */
    public function setRelayState($relayState);

    /**
     * @return string
     */
    public function getRelayState();

    /**
     * @param $nameId
     * @return $this
     */
    public function saveIdentityNameId($nameId);

    /**
     * @return string
     */
    public function getIdentityNameId();

    /**
     * @param string $locale
     * @return $this
     */
    public function setPreferredLocale($locale);

    /**
     * @return string
     */
    public function getPreferredLocale();

    /**
     * Set the current request type as registration flow.
     *
     * @return $this
     */
    public function setRequestTypeRegistration();

    /**
     * Is the current request type registration flow?
     *
     * @return bool
     */
    public function isRequestTypeRegistration();

    /**
     * @param string $nameId
     *
     * @return self
     */
    public function setRequestTypeAuthentication($nameId);

    /**
     * Is the current request type authentication flow?
     *
     * @return bool
     */
    public function isRequestTypeAuthentication();

    /**
     * @return bool
     */
    public function hasRequestType();

    /**
     * @param string $nameId
     * @return $this
     */
    public function setSubjectNameId($nameId);

    /**
     * @return string
     */
    public function getSubjectNameId();

    /**
     * @return bool
     */
    public function hasSubjectNameId();

    /**
     * @return bool
     */
    public function hasRequestId();

    /**
     * Invalidate and delete the full state with all attributes.
     */
    public function invalidate();

    /**
     * @return bool
     */
    public function hasErrorStatus();

    /**
     * @param string $message
     *   The error message.
     * @param string $subCode
     *   Saml response sub code.
     *
     * @return $this
     */
    public function setErrorStatus($message, $subCode);

    /**
     * @return array
     */
    public function getErrorStatus();

    /**
     * @return string
     */
    public function getStepupRequestId();

    /**
     * @return bool
     */
    public function hasStepupRequestId();

    /**
     * @param string $requestId
     *
     * @return self
     */
    public function setStepupRequestId($requestId);

    /**
     * @return self
     */
    public function authenticate();

    /**
     * @return bool
     */
    public function isAuthenticated();
}
