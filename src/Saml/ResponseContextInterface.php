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

namespace Surfnet\GsspBundle\Saml;

use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;

interface ResponseContextInterface
{
    public function getAssertionConsumerUrl(): string;

    public function getIssuer(): ?string;

    public function getInResponseTo(): ?string;

    public function getIdentityProvider(): IdentityProvider;

    public function getServiceProvider(): ServiceProvider;

    public function getRelayState(): ?string;

    public function getSubjectNameId(): string;

    /**
     * Does the current state have an error?
     *
     * When there is an error the SSO return endpoint send an saml error back to the SP.
     */
    public function inErrorState(): bool;

    /**
     * Return saml response status.
     */
    public function getErrorStatus(): array;

    public function getRequestId(): string;

    public function hasRequest(): bool;

    public function isRegistered(): bool;
}
