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

use Assert\Assertion;
use SAML2\Constants;
use Surfnet\GsspBundle\Exception\RuntimeException;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;

/**
 * Knows and preserves the integrity of the GSSP application state.
 */
final class StateHandler implements StateHandlerInterface
{
    const REQUEST_ID = 'request_id';
    const REQUEST_SERVICE_PROVIDER = 'service_provider';
    const REQUEST_RELAY_STATE = 'relay_state';

    const REQUEST_TYPE = 'request_type';
    const REQUEST_TYPE_AUTHENTICATION = 'request_type_authentication';
    const REQUEST_TYPE_REGISTRATION = 'request_type_registration';

    /**
     * This can be the request subject name id or the response registered named id.
     */
    const NAME_ID = 'name_id';

    const RESPONSE_TYPE = 'response_type';
    const RESPONSE_TYPE_USER_REGISTERED = 'response_type_user_registered';
    const RESPONSE_TYPE_USER_AUTHENTICATED = 'response_type_user_authenticated';
    const RESPONSE_TYPE_ERROR = 'response_type_error';

    const RESPONSE_ERROR_SUB_CODE = 'error_response_sub_code';
    const RESPONSE_ERROR_MESSAGE = 'error_response_message';

    const SCOPING_REQUESTER_IDS = 'scoping_requester_ids';

    private $store;

    public function __construct(ValueStore $store)
    {
        $this->store = $store;
    }

    public function saveRegistrationRequest(ReceivedAuthnRequest $authnRequest, $relayState)
    {
        $this->assertRequestTypeNotSet();
        Assertion::string($relayState);

        $this->setRequestId($authnRequest->getRequestId())
            ->setRequestServiceProvider($authnRequest->getServiceProvider())
            ->setRelayState($relayState)
            ->set(self::REQUEST_TYPE, self::REQUEST_TYPE_REGISTRATION)
        ;
    }

    public function saveAuthenticationRequest(ReceivedAuthnRequest $authnRequest, $relayState)
    {
        $this->assertRequestTypeNotSet();
        Assertion::string($relayState);

        $this->setRequestId($authnRequest->getRequestId())
            ->setRequestServiceProvider($authnRequest->getServiceProvider())
            ->setRelayState($relayState)
            ->set(self::NAME_ID, $authnRequest->getNameId())
            ->set(self::SCOPING_REQUESTER_IDS, $authnRequest->getScopingRequesterIds())
            ->set(self::REQUEST_TYPE, self::REQUEST_TYPE_AUTHENTICATION)
        ;
    }

    public function saveSubjectNameId($nameId)
    {
        if (!$this->store->is(self::REQUEST_TYPE, self::REQUEST_TYPE_REGISTRATION)) {
            throw new RuntimeException('Can only set subject name id when a registration is required');
        }
        return $this->set(self::NAME_ID, $nameId);
    }

    public function setErrorStatus($message, $subCode)
    {
        if (!$this->hasRequestType()) {
            throw new RuntimeException('Can only return a error response to the identity provider when we have a pending request');
        }
        $this->set(self::RESPONSE_ERROR_MESSAGE, $message);
        return $this->set(self::RESPONSE_ERROR_SUB_CODE, $subCode);
    }

    public function authenticate()
    {
        if (!$this->store->is(self::REQUEST_TYPE, self::REQUEST_TYPE_AUTHENTICATION)) {
            throw new RuntimeException('Can only authenticate the user when an authentication is required');
        }
        return $this->set(self::RESPONSE_TYPE_USER_AUTHENTICATED, true);
    }

    public function getRequestId()
    {
        return $this->store->get(self::REQUEST_ID);
    }

    public function hasRequestId()
    {
        return $this->store->has(self::REQUEST_ID);
    }

    public function getRequestServiceProvider()
    {
        return $this->store->get(self::REQUEST_SERVICE_PROVIDER);
    }

    public function getRelayState()
    {
        return $this->store->get(self::REQUEST_RELAY_STATE);
    }

    public function getSubjectNameId()
    {
        return $this->store->get(self::NAME_ID);
    }

    public function hasSubjectNameId()
    {
        return $this->store->has(self::NAME_ID);
    }

    public function isRequestTypeRegistration()
    {
        return $this->store->is(self::REQUEST_TYPE, self::REQUEST_TYPE_REGISTRATION);
    }

    public function isRequestTypeAuthentication()
    {
        return $this->store->is(self::REQUEST_TYPE, self::REQUEST_TYPE_AUTHENTICATION);
    }

    public function hasRequestType()
    {
        return $this->store->has(self::REQUEST_TYPE);
    }

    public function hasErrorStatus()
    {
        return $this->store->has(self::RESPONSE_ERROR_SUB_CODE) || $this->store->has(self::RESPONSE_ERROR_MESSAGE);
    }

    public function getErrorStatus()
    {
        return [
            'Code' => Constants::STATUS_RESPONDER,
            'Message' => $this->store->get(self::RESPONSE_ERROR_MESSAGE),
            'SubCode' => $this->store->get(self::RESPONSE_ERROR_SUB_CODE),
        ];
    }

    public function isAuthenticated()
    {
        return $this->store->is(self::RESPONSE_TYPE_USER_AUTHENTICATED, true);
    }

    public function invalidate()
    {
        $this->store->clear();
    }

    public function getScopingRequesterIds()
    {
        return $this->store->get(self::SCOPING_REQUESTER_IDS);
    }

    public function hasScopingRequesterIds()
    {
        return $this->store->has(self::SCOPING_REQUESTER_IDS);
    }

    private function setRelayState($relayState)
    {
        return $this->set(self::REQUEST_RELAY_STATE, $relayState);
    }

    private function setRequestServiceProvider($serviceProvider)
    {
        return $this->set(self::REQUEST_SERVICE_PROVIDER, $serviceProvider);
    }

    private function setRequestId($originalRequestId)
    {
        return $this->set(self::REQUEST_ID, $originalRequestId);
    }

    /**
     * Convenience function that directly returns this reference.
     *
     * @param string $key
     * @param mixed $value
     *    Any scalar
     *
     * @return $this
     */
    private function set($key, $value)
    {
        $this->store->set($key, $value);
        return $this;
    }

    private function assertRequestTypeNotSet()
    {
        if ($this->store->has(self::REQUEST_TYPE)) {
            throw RuntimeException::requestTypeAlreadyKnown($this->store->get(self::REQUEST_TYPE));
        }
    }
}
