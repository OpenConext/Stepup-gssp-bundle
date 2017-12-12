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

use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;

/**
 * Knows and preserves the integrity of the GSSP application state.
 */
interface StateHandlerInterface
{
    /**
     * Stores the state of a registration request.
     *
     * @param ReceivedAuthnRequest $authnRequest
     * @param string $relayState
     * @param string $stepupRequestId
     */
    public function saveRegistrationRequest(ReceivedAuthnRequest $authnRequest, $relayState, $stepupRequestId);

    /**
     * Stores the state of a authentication request.
     *
     * @param ReceivedAuthnRequest $authnRequest
     * @param string $relayState
     * @param string $stepupRequestId
     */
    public function saveAuthenticationRequest(ReceivedAuthnRequest $authnRequest, $relayState, $stepupRequestId);

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
     * @param $nameId
     * @return $this
     */
    public function saveSubjectNameId($nameId);

    /**
     * @return self
     */
    public function authenticate();

    /**
     * @return string
     */
    public function getRequestId();

    /**
     * @return string
     */
    public function getRequestServiceProvider();

    /**
     * @return string
     */
    public function getRelayState();

    /**
     * @return string
     */
    public function getSubjectNameId();

    /**
     * Is the current request type registration flow?
     *
     * @return bool
     */
    public function isRequestTypeRegistration();

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
     * @return bool
     */
    public function isAuthenticated();
}
