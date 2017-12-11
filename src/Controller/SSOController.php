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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Surfnet\GsspBundle\Saml\ResponseContextInterface;
use Surfnet\GsspBundle\Saml\StateHandler;
use Surfnet\GsspBundle\Service\ConfigurationContainer;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the SAML AuthnRequest from the service provider.
 *
 * @Route(service="surfnet_gssp.saml.sso_controller")
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class SSOController extends Controller
{
    private $httpBinding;
    private $registrationRoute;
    private $stateHandler;
    private $responseContext;
    private $logger;

    public function __construct(
        RedirectBinding $httpBinding,
        ConfigurationContainer $configuration,
        StateHandler $stateHandler,
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
            $this->logger->critical(sprintf('Could not process Request, error: "%s"', $e->getMessage()));

            return $this->render('@SurfnetGssp/StepupGssp/unrecoverableError.html.twig', [
                'message' => $e->getMessage(),
            ], new Response(null, Response::HTTP_NOT_ACCEPTABLE));
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
        $this->stateHandler
            ->setRequestId($originalRequest->getRequestId())
            ->setStepupRequestId($this->getOrGenerateStepupRequestId($request))
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRelayState($request->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
            ->setRequestTypeAuthentication($originalRequest->getNameId())
        ;

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
        $this->stateHandler
            ->setRequestId($originalRequest->getRequestId())
            ->setStepupRequestId($this->getOrGenerateStepupRequestId($request))
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRelayState($request->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
            ->setRequestTypeRegistration()
        ;

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
     * Generates a stepup requets id.
     *
     * The request ID should be generated by
     * hashing the application and machine name and timestamp to a hexadecimal string.
     *
     * @param Request $request
     *
     * @return array|string
     */
    private function getOrGenerateStepupRequestId(Request $request)
    {
        if ($request->headers->has('X-Stepup-Request-Id')) {
            return $request->headers->get('X-Stepup-Request-Id');
        }

        return bin2hex(implode([$request->getHost(), gethostname(), time()]));
    }
}
