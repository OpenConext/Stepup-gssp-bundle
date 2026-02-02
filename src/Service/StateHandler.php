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

use SAML2\Constants;
use Surfnet\GsspBundle\Exception\RuntimeException;
use Surfnet\SamlBundle\SAML2\Extensions\GsspUserAttributesChunk;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use TypeError;

/**
 * Knows and preserves the integrity of the GSSP application state.
 */
final readonly class StateHandler implements StateHandlerInterface
{
    public const REQUEST_ID = 'request_id';
    public const REQUEST_SERVICE_PROVIDER = 'service_provider';
    public const REQUEST_RELAY_STATE = 'relay_state';

    public const REQUEST_TYPE = 'request_type';
    public const REQUEST_TYPE_AUTHENTICATION = 'request_type_authentication';
    public const REQUEST_TYPE_REGISTRATION = 'request_type_registration';

    /**
     * This can be the request subject name id or the response registered named id.
     */
    public const NAME_ID = 'name_id';

    public const RESPONSE_TYPE = 'response_type';
    public const RESPONSE_TYPE_USER_REGISTERED = 'response_type_user_registered';
    public const RESPONSE_TYPE_USER_AUTHENTICATED = 'response_type_user_authenticated';
    public const RESPONSE_TYPE_ERROR = 'response_type_error';

    public const RESPONSE_ERROR_SUB_CODE = 'error_response_sub_code';
    public const RESPONSE_ERROR_MESSAGE = 'error_response_message';

    public const SCOPING_REQUESTER_IDS = 'scoping_requester_ids';

    public const GSSP_USERATTRIBUTES = 'gssp_userattributes';

    public function __construct(private ValueStore $store)
    {
    }

    public function saveRegistrationRequest(ReceivedAuthnRequest $authnRequest, string $relayState): void
    {
        $this->assertRequestTypeNotSet();
        $this->assertHasServiceProvider($authnRequest);

        $this->setRequestId($authnRequest->getRequestId())
            ->setRequestServiceProvider($authnRequest->getServiceProvider())
            ->setRelayState($relayState)
            ->set(self::REQUEST_TYPE, self::REQUEST_TYPE_REGISTRATION)
        ;

        $chunk = $authnRequest->getExtensions()->getGsspUserAttributesChunk();
        if ($chunk instanceof GsspUserAttributesChunk) {
            $this->setGsspUserAttributes($chunk);
        }
    }

    public function saveAuthenticationRequest(ReceivedAuthnRequest $authnRequest, string $relayState): void
    {
        $this->assertRequestTypeNotSet();
        $this->assertHasServiceProvider($authnRequest);

        $this->setRequestId($authnRequest->getRequestId())
            ->setRequestServiceProvider($authnRequest->getServiceProvider())
            ->setRelayState($relayState)
            ->set(self::NAME_ID, $authnRequest->getNameId())
            ->set(self::SCOPING_REQUESTER_IDS, $authnRequest->getScopingRequesterIds())
            ->set(self::REQUEST_TYPE, self::REQUEST_TYPE_AUTHENTICATION)
        ;
    }

    public function saveSubjectNameId(string $nameId): StateHandlerInterface
    {
        if (!$this->store->is(self::REQUEST_TYPE, self::REQUEST_TYPE_REGISTRATION)) {
            throw new RuntimeException('Can only set subject name id when a registration is required');
        }
        return $this->set(self::NAME_ID, $nameId);
    }

    public function setErrorStatus(string $message, string $subCode): StateHandlerInterface
    {
        if (!$this->hasRequestType()) {
            throw new RuntimeException('Can only return a error response to the identity provider when we have a pending request');
        }
        $this->set(self::RESPONSE_ERROR_MESSAGE, $message);
        return $this->set(self::RESPONSE_ERROR_SUB_CODE, $subCode);
    }

    public function authenticate(): StateHandlerInterface
    {
        if (!$this->store->is(self::REQUEST_TYPE, self::REQUEST_TYPE_AUTHENTICATION)) {
            throw new RuntimeException('Can only authenticate the user when an authentication is required');
        }
        return $this->set(self::RESPONSE_TYPE_USER_AUTHENTICATED, true);
    }

    public function getRequestId(): string
    {
        if (!is_string($this->store->get(self::REQUEST_ID))) {
            throw new TypeError(sprintf('The "%s" must be of type string', self::REQUEST_ID));
        }
        return $this->store->get(self::REQUEST_ID);
    }

    public function hasRequestId(): bool
    {
        return $this->store->has(self::REQUEST_ID);
    }

    public function getRequestServiceProvider(): string
    {
        if (!is_string($this->store->get(self::REQUEST_SERVICE_PROVIDER))) {
            throw new TypeError(sprintf('The "%s" must be of type string', self::REQUEST_SERVICE_PROVIDER));
        }
        return $this->store->get(self::REQUEST_SERVICE_PROVIDER);
    }

    public function getRelayState(): ?string
    {
        $relayState = $this->store->get(self::REQUEST_RELAY_STATE);
        if (is_string($relayState) || is_null($relayState)) {
            return $relayState;
        }
        throw new TypeError(sprintf('The "%s" must be of type string|null', self::REQUEST_RELAY_STATE));
    }

    public function getSubjectNameId(): string
    {
        if (!is_string($this->store->get(self::NAME_ID))) {
            throw new TypeError(sprintf('The "%s" must be of type string', self::NAME_ID));
        }
        return $this->store->get(self::NAME_ID);
    }

    public function getGsspUserAttributes(): ?GsspUserAttributesChunk
    {
        if (!is_string($this->store->get(self::GSSP_USERATTRIBUTES))) {
            throw new TypeError(sprintf('The "%s" must be of type string', self::GSSP_USERATTRIBUTES));
        }
        if ($this->store->has(self::GSSP_USERATTRIBUTES)) {
            return GsspUserAttributesChunk::fromXML($this->store->get(self::GSSP_USERATTRIBUTES));
        }
        return null;
    }

    public function hasSubjectNameId(): bool
    {
        return $this->store->has(self::NAME_ID);
    }

    public function isRequestTypeRegistration(): bool
    {
        return $this->store->is(self::REQUEST_TYPE, self::REQUEST_TYPE_REGISTRATION);
    }

    public function isRequestTypeAuthentication(): bool
    {
        return $this->store->is(self::REQUEST_TYPE, self::REQUEST_TYPE_AUTHENTICATION);
    }

    public function hasRequestType(): bool
    {
        return $this->store->has(self::REQUEST_TYPE);
    }

    public function hasErrorStatus(): bool
    {
        return $this->store->has(self::RESPONSE_ERROR_SUB_CODE) || $this->store->has(self::RESPONSE_ERROR_MESSAGE);
    }

    public function getErrorStatus(): array
    {
        return [
            'Code' => Constants::STATUS_RESPONDER,
            'Message' => $this->store->get(self::RESPONSE_ERROR_MESSAGE),
            'SubCode' => $this->store->get(self::RESPONSE_ERROR_SUB_CODE),
        ];
    }

    public function isAuthenticated(): bool
    {
        return $this->store->is(self::RESPONSE_TYPE_USER_AUTHENTICATED, true);
    }

    public function invalidate(): void
    {
        $this->store->clear();
    }

    public function getScopingRequesterIds(): array
    {
        if (!is_array($this->store->get(self::SCOPING_REQUESTER_IDS))) {
            throw new RuntimeException(sprintf('%s must be of type array', self::SCOPING_REQUESTER_IDS));
        }
        return $this->store->get(self::SCOPING_REQUESTER_IDS);
    }

    public function hasScopingRequesterIds(): bool
    {
        return $this->store->has(self::SCOPING_REQUESTER_IDS);
    }

    private function setRelayState(string $relayState): self
    {
        return $this->set(self::REQUEST_RELAY_STATE, $relayState);
    }

    private function setRequestServiceProvider(string $serviceProvider): self
    {
        return $this->set(self::REQUEST_SERVICE_PROVIDER, $serviceProvider);
    }

    private function setRequestId(string $originalRequestId): self
    {
        return $this->set(self::REQUEST_ID, $originalRequestId);
    }

    private function setGsspUserAttributes(GsspUserAttributesChunk $chunk): self
    {
        return $this->set(self::GSSP_USERATTRIBUTES, $chunk->toXML());
    }

    private function set(string $key, mixed $value): self
    {
        $this->store->set($key, $value);
        return $this;
    }

    private function assertRequestTypeNotSet(): void
    {
        // Request_type may not be set
        if ($this->store->has(self::REQUEST_TYPE)) {
            // If it is set, it must be of type string.
            if (!is_string($this->store->get(self::REQUEST_TYPE))) {
                throw new TypeError(sprintf('The "%s" must be of type string', self::REQUEST_TYPE));
            }
            throw RuntimeException::requestTypeAlreadyKnown($this->store->get(self::REQUEST_TYPE));
        }
    }

    private function assertHasServiceProvider(ReceivedAuthnRequest $authnRequest): void
    {
        if (!$authnRequest->getServiceProvider()) {
            throw new RuntimeException('No Service Provider (Issuer) found in the AuthNRequest');
        }
    }
}
