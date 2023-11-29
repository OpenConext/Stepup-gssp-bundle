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
use Surfnet\SamlBundle\SAML2\Extensions\GsspUserAttributesChunk;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the interface for the application to do the authentication with.
 *
 * @example Controller action example.
 *      <code>
 *      public function authenticationAction() {
 *
 *           if (!$this->authenticationService->authenticationRequired()) {
 *               return new Response('No authentication required', Response::HTTP_BAD_REQUEST);
 *           }
 *
 *           // Implement logic to verify the user name id.
 *           if ($this->authenticationService->getNameId() === '<user-name-id>') {
 *              $this->authenticationService->authenticate();
 *           } else {
 *              $this->authenticationService->reject('Authentication failed message');
 *           }
 *
 *          return $this->authenticationService->replyToServiceProvider();
 *      }
 *     </code>
 */
interface AuthenticationService
{
    /**
     * If there is need for user authentication.
     */
    public function authenticationRequired(): bool;

    /**
     * Authenticates the user.
     */
    public function authenticate(): void;

    /**
     * If the user is authenticated
     */
    public function isAuthenticated(): bool;

    public function reject(string $message, string $subCode = Constants::STATUS_AUTHN_FAILED): void;

    /*
     * Creates the response that handles the redirect back to the service provider.
     */
    public function replyToServiceProvider(): Response;

    /*
     * Returns the current name id.
     */
    public function getNameId(): string;

    /*
     * Get the entity id of the SP that is issuing the AuthNRequest
     */
    public function getIssuer(): string;

    /**
     * Returns the chain of requester ids originally saved from the
     * actual authnrequest that instantiated the authentication.
     *
     * @return string[]
     */
    public function getScopingRequesterIds(): array;

    public function getGsspUserAttributes(): ?GsspUserAttributesChunk;
}
