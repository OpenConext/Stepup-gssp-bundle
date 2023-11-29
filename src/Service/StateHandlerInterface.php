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

use Surfnet\SamlBundle\SAML2\Extensions\GsspUserAttributesChunk;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;

/**
 * Knows and preserves the integrity of the GSSP application state.
 */
interface StateHandlerInterface
{
    /*
     * Stores the state of a registration request.
     */
    public function saveRegistrationRequest(ReceivedAuthnRequest $authnRequest, string $relayState): void;

    /*
     * Stores the state of a authentication request.
     */
    public function saveAuthenticationRequest(ReceivedAuthnRequest $authnRequest, string $relayState): void;

    public function setErrorStatus(string $message, string $subCode): self;

    public function saveSubjectNameId(string $nameId): self;

    public function authenticate(): self;

    public function getRequestId(): string;

    public function getRequestServiceProvider(): string;

    public function getRelayState(): ?string;

    public function getSubjectNameId(): string;

    public function getScopingRequesterIds(): array;

    public function getGsspUserAttributes(): ?GsspUserAttributesChunk;

    /*
     * Is the current request type registration flow?
     */
    public function isRequestTypeRegistration(): bool;

    /*
     * Is the current request type authentication flow?
     */
    public function isRequestTypeAuthentication(): bool;

    public function hasRequestType(): bool;

    public function hasSubjectNameId(): bool;

    public function hasRequestId(): bool;

    public function hasScopingRequesterIds(): bool;

    /**
     * Invalidate and delete the full state with all attributes.
     */
    public function invalidate(): void;

    public function hasErrorStatus(): bool;

    public function getErrorStatus(): array;

    public function isAuthenticated(): bool;
}
