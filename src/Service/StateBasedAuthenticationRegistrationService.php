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

use Surfnet\GsspBundle\Exception\RuntimeException;
use Surfnet\GsspBundle\Saml\StateHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

final class StateBasedAuthenticationRegistrationService implements AuthenticationRegistrationService
{
    /**
     * @var StateHandler
     */
    private $stateHandler;
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        StateHandler $stateHandler,
        RouterInterface $router
    ) {
        $this->stateHandler = $stateHandler;
        $this->router = $router;
    }

    public function register($subjectNameId)
    {
        if (!$this->stateHandler->isRequestTypeRegistration()) {
            throw RuntimeException::shouldNotRegister();
        }
        $this->stateHandler->setSubjectNameId($subjectNameId);
        return new RedirectResponse($this->router->generate('gssp_saml_sso_return'));
    }
}
