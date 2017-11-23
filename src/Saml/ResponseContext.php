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

use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Entity\ServiceProviderRepository;

final class ResponseContext implements ResponseContextInterface
{
    private $hostedIdentityProvider;
    private $stateHandler;
    private $serviceProviderRepository;

    public function __construct(
        IdentityProvider $identityProvider,
        ServiceProviderRepository $serviceProviderRepository,
        StateHandler $stateHandler
    ) {
        $this->hostedIdentityProvider = $identityProvider;
        $this->stateHandler           = $stateHandler;
        $this->serviceProviderRepository = $serviceProviderRepository;
    }

    /**
     * @return string
     */
    public function getAssertionConsumerUrl()
    {
        return $this->getServiceProvider()->getAssertionConsumerUrl();
    }

    /**
     * @return null|string
     */
    public function getIssuer()
    {
        return $this->hostedIdentityProvider->getEntityId();
    }

    /**
     * @return null|string
     */
    public function getInResponseTo()
    {
        return $this->stateHandler->getRequestId();
    }

    /**
     * @return IdentityProvider
     */
    public function getIdentityProvider()
    {
        return $this->hostedIdentityProvider;
    }

    /**
     * @return ServiceProvider
     */
    public function getServiceProvider()
    {
        $serviceProviderId = $this->stateHandler->getRequestServiceProvider();
        return $this->serviceProviderRepository->getServiceProvider($serviceProviderId);
    }

    /**
     * @return null|string
     */
    public function getRelayState()
    {
        return $this->stateHandler->getRelayState();
    }

    /**
     * @return string
     */
    public function getIdentityNameId()
    {
        return $this->stateHandler->getIdentityNameId();
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->stateHandler->getRequestId();
    }

    /**
     * @return string
     */
    public function getSubjectNameId()
    {
        return $this->stateHandler->getSubjectNameId();
    }

    /**
     * @return bool
     */
    public function hasRequest()
    {
        return $this->stateHandler->hasRequestId();
    }

    /**
     * @return bool
     */
    public function isRegistered()
    {
        return $this->stateHandler->hasSubjectNameId();
    }
}
