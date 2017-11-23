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

use Psr\Log\LoggerInterface;
use SAML2_Const;
use Surfnet\GsspBundle\Exception\RuntimeException;
use Surfnet\GsspBundle\Saml\StateHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

final class StateBasedAuthenticationRegistrationService implements AuthenticationRegistrationService
{
    private $stateHandler;
    private $router;
    private $logger;

    public function __construct(
        StateHandler $stateHandler,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->stateHandler = $stateHandler;
        $this->router = $router;
        $this->logger = $logger;
    }

    public function register($subjectNameId)
    {
        if (!$this->stateHandler->isRequestTypeRegistration()) {
            $this->logger->critical('Current request does not need a registration');
            throw RuntimeException::shouldNotRegister();
        }
        $this->logger->notice(sprintf('Application sets the subject nameID to %s', $subjectNameId));
        $this->stateHandler->setSubjectNameId($subjectNameId);
    }

    public function requiresRegistration()
    {
        return $this->stateHandler->isRequestTypeRegistration();
    }

    public function error($message, $subCode = SAML2_Const::STATUS_AUTHN_FAILED)
    {
        $this->logger->critical($message);
        $this->stateHandler->setErrorStatus($message, SAML2_Const::STATUS_AUTHN_FAILED);
    }

    public function createRedirectResponse()
    {
        $url = $this->generateSSOreturnUrl();
        $this->logger->notice(sprintf('Created redirect response for sso return endpoint "%s"', $url));

        return new RedirectResponse($url);
    }

    private function generateSSOreturnUrl()
    {
        return $this->router->generate('gssp_saml_sso_return');
    }
}
