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

namespace Surfnet\GsspBundle\Controller;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Saml\ResponseContextInterface;
use Surfnet\GsspBundle\Service\StateHandlerInterface;
use Surfnet\GsspBundle\Service\ConfigurationContainer;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the SAML AuthnRequest from the service provider.
 *
 * @Route(service="surfnet_gssp.saml.sso_controller")
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class SSOController extends AbstractController
{
    private $httpBinding;
    private $registrationRoute;
    private $stateHandler;
    private $responseContext;
    private $logger;

    public function __construct(
        RedirectBinding $httpBinding,
        ConfigurationContainer $configuration,
        StateHandlerInterface $stateHandler,
        ResponseContextInterface $responseContext,
        LoggerInterface $logger
    ) {
        $this->registrationRoute = $configuration;
        $this->stateHandler = $stateHandler;
        $this->responseContext = $responseContext;
        $this->httpBinding = $httpBinding;
        $this->logger = $logger;
    }

    /**
     * The route that receives the AuthnRequest from the service provider.
     *
     * If the request is valid the user will be redirected to the application registration route.
     *
     * @Route("/saml/sso", name="gssp_saml_sso", methods={"GET"})
     * @throws \InvalidArgumentException
     */
    public function ssoAction(Request $request)
    {
        $this->logger->notice('Received sso request');

        // If we already have a request, we clear the current state.
        if ($this->responseContext->hasRequest()) {
            $this->logger->warning('There is already state present, clear previous state');
            $this->stateHandler->invalidate();
        }

        try {
            $this->logger->info('Processing AuthnRequest');
            $originalRequest = $this->httpBinding->receiveSignedAuthnRequestFrom($request);

            $this->logger->notice(sprintf(
                'AuthnRequest processing complete, received AuthnRequest from "%s", request ID: "%s"',
                $originalRequest->getServiceProvider(),
                $originalRequest->getRequestId()
            ));
        } catch (RuntimeException $e) {
            throw new UnrecoverableErrorException(
                sprintf(
                    'Error processing the SAML authentication request: %s',
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        // Determine the AuthnRequest type. If there is a nameId present it's an authentication request
        $nameId = $originalRequest->getNameId();
        if ($nameId) {
            return $this->authenticationAction($request, $originalRequest);
        }

        return $this->registrationAction($request, $originalRequest);
    }

    private function authenticationAction(Request $request, ReceivedAuthnRequest $originalRequest)
    {
        $this->stateHandler->saveAuthenticationRequest(
            $originalRequest,
            $this->getRelayStateFromRequest($request)
        );

        $this->logger->info(sprintf(
            'AuthnRequest stored in state'
        ));

        $route = $this->generateUrl($this->registrationRoute->getAuthenticationRoute());

        $this->logger->notice(sprintf(
            'Redirect user to the application authentication route %s',
            $route
        ));

        return new RedirectResponse($route);
    }

    private function registrationAction(Request $request, ReceivedAuthnRequest $originalRequest)
    {
        $this->stateHandler->saveRegistrationRequest(
            $originalRequest,
            $this->getRelayStateFromRequest($request)
        );

        $this->logger->info(sprintf(
            'AuthnRequest stored in state'
        ));

        $route = $this->generateUrl($this->registrationRoute->getRegistrationRoute());

        $this->logger->notice(sprintf(
            'Redirect user to the application registration route %s',
            $route
        ));

        return new RedirectResponse($route);
    }

    /**
     * Directly fetches the relay-state from the AuthnRequest query parameter.
     *
     * The idea is that the SP verifies the integrity of the relay-state like sending an opaque identifier as RelayState to the IDP,
     * and not the direct Target Resource URL. This protects the integrity of the RelayState and also prevents third-party tampering
     * and verify the RelayState with the RelayState value in the SAML Response sent by the IDP.
     *
     * The conclusion is that IDP does not need to and cannot verify integrity of the relay-state.
     */
    private function getRelayStateFromRequest(Request $request)
    {
        return $request->get(AuthnRequest::PARAMETER_RELAY_STATE, '');
    }
}
