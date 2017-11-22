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

namespace Surfnet\GsspBundle\Features\Context;

use Assert\Assertion;
use Assert\AssertionFailedException;
use Behat\Behat\Context\Context;
use Psr\Log\NullLogger;
use SAML2_AuthnRequest;
use SAML2_Certificate_KeyLoader;
use SAML2_Certificate_PrivateKeyLoader;
use SAML2_Compat_ContainerSingleton;
use SAML2_Configuration_PrivateKey;
use SAML2_Const;
use SAML2_DOMDocumentFactory;
use SAML2_Message;
use SAML2_Response;
use Surfnet\GsspBundle\Controller\IdentityController;
use Surfnet\GsspBundle\Saml\AssertionSigningService;
use Surfnet\GsspBundle\Saml\ResponseContext;
use Surfnet\GsspBundle\Saml\StateHandler\MemoryStateHandler;
use Surfnet\GsspBundle\Service\StateBasedAuthenticationRegistrationService;
use Surfnet\GsspBundle\Service\AuthenticationRegistrationService;
use Surfnet\GsspBundle\Service\ConfigurationContainer;
use Surfnet\GsspBundle\Service\DateTime\SystemDateTimeService;
use Surfnet\GsspBundle\Service\ResponseService;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Entity\StaticServiceProviderRepository;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\BridgeContainer;
use Surfnet\SamlBundle\Signing\SignatureVerifier;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig_Environment;
use XMLSecurityKey;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class GsspContext implements Context
{
    /**
     * @var ResponseContext
     */
    private $responseContext;
    /**
     * @var AuthenticationRegistrationService
     */
    private $authenticationRegistrationService;
    /**
     * @var \Mockery\MockInterface|Twig_Environment
     */
    private $twigEnvironment;
    /**
     * Last controller response.
     *
     * @var Response
     */
    private $response;
    /**
     * Last controller twig render template
     */
    private $twigTemplate;
    /**
     * Last controller twig render parameters
     */
    private $twigParameters = [];
    /**
     * @var IdentityController
     */
    private $controller;
    /**
     * @var IdentityProvider
     */
    private $identityProvider;

    /**
     * @var SAML2_AuthnRequest
     */
    private $authnRequest;

    /**
     * @var BridgeContainer
     */
    private $container;
    /**
     * @var ServiceProvider
     */
    private $serviceProvider;

    /**
     * @var SAML2_Response
     */
    private $ssoReturnResponse;

    public function __construct()
    {
        $logger = new NullLogger();
        $this->container = new BridgeContainer($logger);
        SAML2_Compat_ContainerSingleton::setContainer($this->container);

        $samlBundle = __DIR__.'/../../../vendor/surfnet/stepup-saml-bundle';

        $this->serviceProvider = new ServiceProvider([
            'entityId' => 'https://service_provider/saml/metadata',
            'assertionConsumerUrl' => 'https://service_provider/saml/acu',
            'certificateFile' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
            'privateKeys' => [
                new SAML2_Configuration_PrivateKey(
                    sprintf('%s/src/Resources/keys/development_privatekey.pem', $samlBundle),
                    'default'
                ),
            ],
            'sharedKey' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
        ]);

        $this->identityProvider = new IdentityProvider([
            'entityId' => 'https://identity_provider/saml/metadata',
            'certificateFile' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
            'privateKeys' => [
                new SAML2_Configuration_PrivateKey(
                    sprintf('%s/src/Resources/keys/development_privatekey.pem', $samlBundle),
                    'default'
                ),
            ],
            'sharedKey' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
        ]);

        $serviceProviders = new StaticServiceProviderRepository([$this->serviceProvider]);
        $keyLoader = new SAML2_Certificate_KeyLoader();
        $signatureVerifier = new SignatureVerifier($keyLoader, $logger);
        $redirectBinding = new RedirectBinding($logger, $signatureVerifier, $serviceProviders);
        $configuration = new ConfigurationContainer([
            'registration_route' => 'registration_route_action',
        ]);
        $stateHandler = new MemoryStateHandler();
        $router = \Mockery::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->with('registration_route_action', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('https://identity_provider/registration');

        $router->shouldReceive('generate')
            ->with('gssp_saml_sso_return')
            ->andReturn('https://identity_provider/saml/sso_return');

        $this->authenticationRegistrationService = new StateBasedAuthenticationRegistrationService(
            $stateHandler,
            $router
        );
        $signingService = new AssertionSigningService($this->identityProvider);
        $this->responseContext = new ResponseContext(
            $this->identityProvider,
            $serviceProviders,
            $stateHandler
        );
        $responseService = new ResponseService(
            $this->identityProvider,
            $this->responseContext,
            $signingService,
            new SystemDateTimeService()
        );
        $this->controller = new IdentityController(
            $redirectBinding,
            $configuration,
            $stateHandler,
            $responseService,
            $this->responseContext
        );
        $container = \Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')->with('templating')->andReturn(false);
        $container->shouldReceive('has')->with('twig')->andReturn(true);
        $this->twigEnvironment = \Mockery::mock(Twig_Environment::class);
        $this->twigEnvironment->shouldReceive('render')->andReturnUsing(function (
            $template,
            $parameters = []
        ) {
            $this->twigParameters = $parameters;
            $this->twigTemplate = $template;
            return $template.'-response';
        });
        $container->shouldReceive('get')->with('twig')->andReturn($this->twigEnvironment);


        $container->shouldReceive('get')->with('router')->andReturn($router);
        $this->controller->setContainer($container);
    }

    /**
     * Creates a normal AuthnRequest and set it to this context.
     *
     * @Given a normal SAML 2.0 AuthnRequest
     *
     * @throws \Exception
     */
    public function createANormalAuthnRequest()
    {
        $request = new SAML2_AuthnRequest();
        $request->setAssertionConsumerServiceURL($this->serviceProvider->getAssertionConsumerUrl());
        $request->setDestination($this->identityProvider->getSsoUrl());
        $request->setIssuer($this->serviceProvider->getEntityId());
        $request->setProtocolBinding(SAML2_Const::BINDING_HTTP_REDIRECT);
        $this->authnRequest = $request;
    }

    /**
     * Creates a normal AuthnRequest from an random SP.
     *
     * @Given a normal SAML 2.0 AuthnRequest form a unknown service provider entityId :entityId acu :acu
     *
     * @param string $entityId
     *   The service provider entity id
     * @param string $acu
     *   The assertion consumer service url
     *
     * @throws \Exception
     */
    public function createANormalAuthnRequestFromServiceProvider($entityId, $acu)
    {
        $request = new SAML2_AuthnRequest();
        $request->setAssertionConsumerServiceURL($acu);
        $request->setDestination($this->identityProvider->getSsoUrl());
        $request->setIssuer($entityId);
        $request->setProtocolBinding(SAML2_Const::BINDING_HTTP_REDIRECT);
        $this->authnRequest = $request;
    }

    /**
     * Sign the AuthnRequest on this context from the SP.
     *
     * @Given AuthnRequest is signed with sha256
     *
     * @throws \Exception
     */
    public function signAuthnRequest()
    {
        $this->authnRequest->setSignatureKey(self::loadPrivateKey(
            $this->serviceProvider->getPrivateKey(SAML2_Configuration_PrivateKey::NAME_DEFAULT)
        ));
    }

    /**
     * Call the SSO IdP endpoint action with HTTP AuthnRequest request, set the response on this context.
     *
     * @When the service provider send the AuthnRequest with HTTP-Redirect binding
     */
    public function callIdentityProviderSSOActionWithAuthnRequest()
    {
        $request = AuthnRequest::createNew($this->authnRequest);
        $query = $request->buildRequestQuery();
        parse_str($query, $parameters);
        $this->callIdentityProviderSSOAction($parameters);
    }

    /**
     * Call the SSO IdP endpoint action with HTTP AuthnRequest request.
     *
     *  Removes the signature from the request.
     *
     * @When the service provider send an unsigned AuthnRequest with HTTP-Redirect binding
     *
     * @throws \Exception
     */
    public function callIdentityProviderSSOActionWithAuthnRequestWithoutSignature()
    {
        $request = AuthnRequest::createNew($this->authnRequest);

        // Do the signing, but we will remove it, otherwise buildRequestQuery will fail.
        $this->signAuthnRequest();
        $query = $request->buildRequestQuery();
        parse_str($query, $parameters);
        unset($parameters['SigAlg'], $parameters['Signature']);
        $this->callIdentityProviderSSOAction($parameters);
    }

    /**
     * Call the SSO IdP endpoint action with HTTP AuthnRequest request.
     *
     *  Set an invalid signature
     *
     * @When the service provider send an invalided signed AuthnRequest with HTTP-Redirect binding
     *
     * @throws \Exception
     */
    public function callIdentityProviderSSOActionWithAuthnRequestWithIncorrectSignature()
    {
        $request = AuthnRequest::createNew($this->authnRequest);

        // Do the signing, but we will change it to create a mismatch.
        $this->signAuthnRequest();
        $query = $request->buildRequestQuery();
        parse_str($query, $parameters);
        $parameters['Signature'] = '32o8462398469dihdsajhdjashdohasd';
        $this->callIdentityProviderSSOAction($parameters);
    }

    /**
     * @Then the identity provider response should be an unrecoverable error :message
     *
     * @throws AssertionFailedException
     */
    public function responseShouldBeAnUnrecoverableError($message)
    {
        Assertion::eq('@SurfnetGssp/StepupGssp/unrecoverableError.html.twig', $this->twigTemplate);
        Assertion::eq($message, $this->twigParameters['message']);
        Assertion::eq(Response::HTTP_NOT_ACCEPTABLE, $this->response->getStatusCode());
    }

    /**
     * @Then the identity provider register the user with an unique identifier token
     */
    public function assignAUniqueIdentifierToTheToken()
    {
        $this->authenticationRegistrationService->register('unique-identifier-token');
    }

    /**
     * @Then the user is redirected to the identity provider sso return endpoint
     * @Then the user is redirected to the identity provider sso return endpoint without registration
     *
     * @throws AssertionFailedException
     * @throws \Exception
     */
    public function requestSSOreturnEndpoint()
    {
        $request = Request::create('http://identity_provider/saml/sso_return');
        $this->response = $this->controller->ssoReturnAction($request);
    }

    /**
     * @Then Identity provider sso return endpoint should redirect client-side a saml response to the service provider
     *
     * @throws AssertionFailedException
     * @throws \Exception
     */
    public function shouldReturnSamlResponse()
    {
        $parameters = $this->twigParameters;
        Assertion::eq('@SurfnetGssp/StepupGssp/consumeAssertion.html.twig', $this->twigTemplate);
        Assertion::eq('', $parameters['relayState']);
        Assertion::eq('https://service_provider/saml/acu', $parameters['acu']);
        $decodedSamlRequest = base64_decode($parameters['response']);
        $document = SAML2_DOMDocumentFactory::fromString($decodedSamlRequest);
        $this->ssoReturnResponse = SAML2_Message::fromXML($document->firstChild);
        Assertion::eq('@SurfnetGssp/StepupGssp/consumeAssertion.html.twig-response', $this->response->getContent());
    }

    /**
     * @Then the saml response status code should be :code
     */
    public function theResponseStatusCodeShouldBe($code)
    {
        $status = $this->ssoReturnResponse->getStatus();
        Assertion::eq($code, $status['Code']);
    }

    /**
     * @Then the saml response assertion should be signed
     *
     * @throws AssertionFailedException
     */
    public function theResponseAssertionShouldBeSigned()
    {
        $assertion = $this->getSsoAssertionResponse();
        Assertion::eq('http://www.w3.org/2001/04/xmldsig-more#rsa-sha256', $assertion->getSignatureMethod());
        Assertion::contains(
            str_replace("\r\n", '', $this->loadIdpPublicCertificate()),
            $assertion->getCertificates()[0]
        );
        $key = self::loadPublicKey($this->identityProvider->getSharedKey());
        $assertion->validate($key);
    }

    /**
     * @Then the saml response should have an authenticating authority of the IdP EntityId with class ref :classRef
     */
    public function theResponseShouldHaveAnAuthenticatingAuthorityOfTheIdpWithClassRef($classRef)
    {
        $assertion = $this->getSsoAssertionResponse();
        Assertion::eq($classRef, $assertion->getAuthnContextClassRef());
        Assertion::eq([$this->identityProvider->getEntityId()], $assertion->getAuthenticatingAuthority());
    }

    /**
     * @Then the saml response should have the token identifier in the Subject NameID of the Assertion section
     */
    public function theResponseShouldHaveTheTokenIdentifierInTheSubjectNameIdOfTheAssertionSection()
    {
        $assertion = $this->getSsoAssertionResponse();
        $nameId = $assertion->getNameId();
        Assertion::eq('unique-identifier-token', $nameId['Value']);
        Assertion::eq('urn:oasis:names:tc:SAML:2.0:nameid-format:persistent', $nameId['Format']);
    }

    /**
     * @param SAML2_Configuration_PrivateKey $key
     * @return SAML2_Configuration_PrivateKey|XMLSecurityKey
     * @throws \Exception
     */
    private static function loadPrivateKey(SAML2_Configuration_PrivateKey $key)
    {
        $keyLoader = new SAML2_Certificate_PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($key);

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }

    /**
     * @param SAML2_Configuration_PrivateKey $publicKey
     * @return SAML2_Configuration_PrivateKey|XMLSecurityKey
     *
     * @throws \Exception
     */
    private static function loadPublicKey($publicKey)
    {
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'public']);
        $key->loadKey($publicKey, true);

        return $key;
    }


    /**
     * @return string
     */
    private function loadIdpPublicCertificate()
    {
        $keyLoader = new SAML2_Certificate_KeyLoader();
        $keyLoader->loadCertificateFile($this->identityProvider->getCertificateFile());
        /** @var \SAML2_Certificate_X509 $publicKey */
        $publicKey = $keyLoader->getKeys()->getOnlyElement();

        return $publicKey->getCertificate();
    }

    /**
     * @return mixed|\SAML2_Assertion|\SAML2_EncryptedAssertion
     */
    private function getSsoAssertionResponse()
    {
        $assertions = $this->ssoReturnResponse->getAssertions();

        return reset($assertions);
    }

    /**
     * @param array $parameters
     *
     * @When /^an user request identity provider sso endpoint$/
     * @When /^the user request identity provider sso endpoint$/
     */
    public function callIdentityProviderSSOAction(array $parameters = [])
    {
        $request = Request::create('http://identity_provider/saml/sso', 'GET', $parameters);
        $this->response = $this->controller->ssoAction($request);
    }

    /**
     * @Then /^the response should be an redirect the application registration endpoint$/
     *
     * @throws \Assert\AssertionFailedException
     */
    public function theResponseShouldBeAnRedirectTheApplicationRegistrationEndpoint()
    {
        Assertion::isInstanceOf($this->response, RedirectResponse::class);
        /** @var RedirectResponse $response */
        $response = $this->response;
        Assertion::eq($response->getTargetUrl(), 'https://identity_provider/registration');
    }

    /**
     * @Then /^there should not be an unique identifier token assigned$/
     *
     * @throws \Assert\AssertionFailedException
     */
    public function thereShouldNotBeAnUniqueIdentifierTokenAssigned()
    {
        Assertion::false($this->responseContext->isRegistered());
    }
}
