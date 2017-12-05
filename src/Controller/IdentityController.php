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

namespace Surfnet\GsspBundle\Controller;

use Psr\Log\LoggerInterface;
use RuntimeException;
use SAML2_Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Surfnet\GsspBundle\Saml\ResponseContextInterface;
use Surfnet\GsspBundle\Saml\StateHandler;
use Surfnet\GsspBundle\Service\AuthenticationRegistrationService;
use Surfnet\GsspBundle\Service\ConfigurationContainer;
use Surfnet\GsspBundle\Service\ResponseServiceInterface;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(service="surfnet_gssp.saml.identity_controller")
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class IdentityController extends Controller
{
    private $httpBinding;
    private $registrationRoute;
    private $stateHandler;
    private $responseService;
    private $responseContext;
    private $logger;

    public function __construct(
        RedirectBinding $httpBinding,
        ConfigurationContainer $configuration,
        StateHandler $stateHandler,
        ResponseServiceInterface $responseService,
        ResponseContextInterface $responseContext,
        LoggerInterface $logger
    ) {
        $this->registrationRoute = $configuration;
        $this->stateHandler = $stateHandler;
        $this->responseService = $responseService;
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
            $this->logger->critical(sprintf('Could not process Request, error: "%s"', $e->getMessage()));

            return $this->render('@SurfnetGssp/StepupGssp/unrecoverableError.html.twig', [
                'message' => $e->getMessage(),
            ], new Response(null, Response::HTTP_NOT_ACCEPTABLE));
        }

        // Store AuthnRequest state.
        $this->stateHandler
            ->setRequestId($originalRequest->getRequestId())
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRelayState($request->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
            ->setRequestTypeRegistration();
        $this->logger->info(sprintf(
            'AuthnRequest stored in state'
        ));

        // Redirect user to the application registration route.
        $route = $this->generateRegistrationRoute();
        $this->logger->notice(sprintf(
            'Redirect user to the application registration route %s',
            $route
        ));

        return new RedirectResponse($route);
    }

    /**
     * When the application is done with registration process, the user will be redirected to this route.
     *
     * This will redirect the user back the service provider with a saml response.
     *
     * @see AuthenticationRegistrationService
     *
     * @Route("/saml/sso_return", name="gssp_saml_sso_return", methods={"POST", "GET"})
     * @throws \Exception
     */
    public function ssoReturnAction()
    {
        $this->logger->notice('Received sso return request');

        // Show error if we don't have an active AuthnRequest.
        if (!$this->responseContext->hasRequest()) {
            $this->logger->critical('There is no request state present');

            return $this->render('@SurfnetGssp/StepupGssp/unrecoverableError.html.twig', [
                'message' => 'There is no active AuthnRequest to process the return',
            ], new Response(null, Response::HTTP_NOT_ACCEPTABLE));
        }

        // This should not happen, we are not yet registered, redirect back to the application.
        if (!$this->responseContext->inErrorState() && !$this->responseContext->isRegistered()) {
            $url = $this->generateRegistrationRoute();
            $this->logger->warning(sprintf(
                'User was not registered by the application, redirect user back the registration route "%s"',
                $url
            ));

            return new RedirectResponse($url);
        }

        // Create saml response.
        try {
            $this->logger->info('Create sso response');
            $response = $this->responseService->createResponse();
            $this->logger->notice(sprintf(
                'Saml response created with id "%s", request ID: "%s"',
                $response->getId(),
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
            'response' => $this->getResponseAsXML($response),
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
     * @param SAML2_Response $response
     * @return string
     */
    private function getResponseAsXML(SAML2_Response $response)
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }

    /**
     * @return string
     */
    private function generateRegistrationRoute()
    {
        return $this->generateUrl($this->registrationRoute->getRegistrationRoute());
    }
}
