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

use Surfnet\GsspBundle\Service\StateHandlerInterface;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Entity\ServiceProviderRepository;

final readonly class ResponseContext implements ResponseContextInterface
{
    public function __construct(
        private IdentityProvider $hostedIdentityProvider,
        private ServiceProviderRepository $serviceProviderRepository,
        private StateHandlerInterface $stateHandler
    ) {
    }

    public function getAssertionConsumerUrl(): string
    {
        return $this->getServiceProvider()->getAssertionConsumerUrl();
    }

    public function getIssuer(): ?string
    {
        return $this->hostedIdentityProvider->getEntityId();
    }

    public function getInResponseTo(): string
    {
        return $this->stateHandler->getRequestId();
    }

    public function getIdentityProvider(): IdentityProvider
    {
        return $this->hostedIdentityProvider;
    }

    public function getServiceProvider(): ServiceProvider
    {
        $serviceProviderId = $this->stateHandler->getRequestServiceProvider();
        return $this->serviceProviderRepository->getServiceProvider($serviceProviderId);
    }

    public function getRelayState(): ?string
    {
        return $this->stateHandler->getRelayState();
    }

    public function getSubjectNameId(): string
    {
        return $this->stateHandler->getSubjectNameId();
    }

    public function inErrorState(): bool
    {
        return $this->stateHandler->hasErrorStatus();
    }

    public function getErrorStatus(): array
    {
        return $this->stateHandler->getErrorStatus();
    }

    public function getRequestId(): string
    {
        return $this->stateHandler->getRequestId();
    }

    public function hasRequest(): bool
    {
        return $this->stateHandler->hasRequestId();
    }

    public function isRegistered(): bool
    {
        return $this->stateHandler->isRequestTypeRegistration() && $this->stateHandler->hasSubjectNameId();
    }
}
