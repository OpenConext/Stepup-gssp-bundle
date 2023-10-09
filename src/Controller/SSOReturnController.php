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
use SAML2\Response as SAMLResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Saml\ResponseContextInterface;
use Surfnet\GsspBundle\Service\StateHandlerInterface;
use Surfnet\GsspBundle\Service\ConfigurationContainer;
use Surfnet\GsspBundle\Service\ResponseServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends back the SAML return response to the service provider.
 *
 * @Route(service="surfnet_gssp.saml.sso_return_controller")
 */
final class SSOReturnController extends AbstractController
{
    private $registrationRoute;
    private $stateHandler;
    private $responseService;
    private $responseContext;
    private $logger;

    public function __construct(
        ConfigurationContainer $configuration,
        StateHandlerInterface $stateHandler,
        ResponseServiceInterface $responseService,
        ResponseContextInterface $responseContext,
        LoggerInterface $logger
    ) {
        $this->registrationRoute = $configuration;
        $this->stateHandler = $stateHandler;
        $this->responseService = $responseService;
        $this->responseContext = $responseContext;
        $this->logger = $logger;
    }

    /**
     * When the application is done with registration or authentication process, the user will be redirected to this route.
     *
     * This will redirect the user back the service provider with a saml response. The replyToServiceProvider from
     * the RegistrationService and the AuthenticationService will redirect to this.
     *
     * @Route("/saml/sso_return", name="gssp_saml_sso_return", methods={"POST", "GET"})
     * @throws \Exception
     */
    public function ssoReturnAction()
    {
        $this->logger->notice('Received sso return request');

        // Show error if we don't have an active AuthnRequest.
        if (!$this->responseContext->hasRequest()) {
            throw new UnrecoverableErrorException('There is no request state present');
        }

        if ($this->responseContext->inErrorState()) {
            return $this->createSamlResponse();
        }

        if ($this->stateHandler->isRequestTypeRegistration()) {
            return $this->ssoRegistrationReturnAction();
        }

        if ($this->stateHandler->isRequestTypeAuthentication()) {
            return $this->ssoAuthenticationReturnAction();
        }

        throw new UnrecoverableErrorException('Application state invalid');
    }

    private function ssoRegistrationReturnAction()
    {
        // This should not happen, the user is not yet registered, redirect back to the application.
        if (!$this->responseContext->isRegistered()) {
            $url = $this->generateUrl($this->registrationRoute->getRegistrationRoute());
            $this->logger->warning(sprintf(
                'User was not registered by the application, redirect user back the registration route "%s"',
                $url
            ));

            return new RedirectResponse($url);
        }
        return $this->createSamlResponse();
    }

    private function ssoAuthenticationReturnAction()
    {
        // This should not happen, if the user is not authenticated, redirect back to the application.
        if (!$this->stateHandler->isAuthenticated()) {
            $url = $this->generateUrl($this->registrationRoute->getAuthenticationRoute());
            $this->logger->warning(sprintf(
                'User was not authenticated by the application, redirect user back the authentication route "%s"',
                $url
            ));
            return new RedirectResponse($url);
        }
        return $this->createSamlResponse();
    }

    /**
     * @return Response
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    private function createSamlResponse()
    {
        try {
            $this->logger->info('Create sso response');
            $samlResponse = $this->responseService->createResponse();
            $this->logger->notice(sprintf(
                'Saml response created with id "%s", request ID: "%s"',
                $samlResponse->getId(),
                $this->responseContext->getRequestId()
            ));
        } catch (RuntimeException $e) {
            return $this->render('@SurfnetGssp/StepupGssp/unrecoverableError.html.twig', [
                'message' => $e->getMessage(),
            ], new Response('', Response::HTTP_NOT_ACCEPTABLE));
        }

        $acu = $this->responseContext->getAssertionConsumerUrl();
        $response = $this->render('@SurfnetGssp/StepupGssp/ssoReturn.html.twig', [
            'acu' => $acu,
            'response' => $this->getResponseAsXML($samlResponse),
            'relayState' => $this->responseContext->getRelayState(),
        ]);

        // We clear the state, because we don't need it anymore.
        $this->logger->notice(sprintf(
            'Invalidate current state and redirect user to service provider assertion consumer url "%s"',
            $acu
        ));
        $this->stateHandler->invalidate();

        return $response;
    }

    /**
     * @param SAMLResponse $response
     * @return string
     */
    private function getResponseAsXML(SAMLResponse $response)
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }
}
