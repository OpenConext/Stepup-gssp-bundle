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

use Psr\Log\LoggerInterface;
use SAML2\Constants;
use Surfnet\GsspBundle\Exception\RuntimeException;
use Surfnet\SamlBundle\SAML2\Extensions\GsspUserAttributesChunk;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

/**
 * Contains the application interface for the authentication flow.
 *
 * @see AuthenticationService for an example
 */
final class StateBasedAuthenticationService implements AuthenticationService
{
    private $stateHandler;
    private $router;
    private $logger;

    public function __construct(
        StateHandlerInterface $stateHandler,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->stateHandler = $stateHandler;
        $this->router = $router;
        $this->logger = $logger;
    }

    public function authenticationRequired()
    {
        return $this->stateHandler->isRequestTypeAuthentication();
    }

    public function authenticate()
    {
        if (!$this->stateHandler->isRequestTypeAuthentication()) {
            $this->logger->critical('Current request does not need an authentication');
            throw RuntimeException::shouldNotAuthenticate();
        }
        $this->logger->notice('Application authenticates the user');
        $this->stateHandler->authenticate();
    }

    public function isAuthenticated()
    {
        if (!$this->stateHandler->isRequestTypeAuthentication()) {
            $this->logger->critical('Current request does not need an authentication');
            throw RuntimeException::shouldNotAuthenticate();
        }
        return $this->stateHandler->isAuthenticated();
    }

    public function reject($message, $subCode = Constants::STATUS_AUTHN_FAILED)
    {
        $this->logger->critical($message);
        $this->stateHandler->setErrorStatus($message, $subCode);
    }

    public function replyToServiceProvider()
    {
        $url = $this->generateSSOreturnUrl();
        $this->logger->notice(sprintf('Created redirect response for sso return endpoint "%s"', $url));

        return new RedirectResponse($url);
    }

    public function getNameId(): string
    {
        if (!$this->stateHandler->hasSubjectNameId()) {
            throw new RuntimeException(
                'No SubjectNameId present in state, but it should be in order to handle a GSSP authentication.'
            );
        }
        return $this->stateHandler->getSubjectNameId();
    }

    public function getScopingRequesterIds()
    {
        if (!$this->stateHandler->hasScopingRequesterIds()) {
            return [];
        }
        return $this->stateHandler->getScopingRequesterIds();
    }

    private function generateSSOreturnUrl()
    {
        return $this->router->generate('gssp_saml_sso_return');
    }

    /**
     * @return string
     */
    public function getIssuer()
    {
        return $this->stateHandler->getRequestServiceProvider();
    }

    public function getGsspUserAttributes(): ?GsspUserAttributesChunk
    {
        return $this->stateHandler->getGsspUserAttributes();
    }
}
