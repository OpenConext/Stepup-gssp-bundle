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

namespace Surfnet\GsspBundle\Features\Context;

use Assert\Assertion;
use Assert\AssertionFailedException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Exception;
use JakubOnderka\PhpParallelLint\RunTimeException;
use Mockery;
use Mockery\MockInterface;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\AuthnRequest as SAMLAuthnRequest;
use SAML2\Certificate\KeyLoader;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Certificate\X509;
use SAML2\Compat\ContainerSingleton;
use SAML2\Configuration\PrivateKey;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\EncryptedAssertion;
use SAML2\Message;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use Surfnet\GsspBundle\Controller\SSOController;
use Surfnet\GsspBundle\Controller\SSOReturnController;
use Surfnet\GsspBundle\Logger\StepupRequestIdSariLogger;
use Surfnet\GsspBundle\Saml\AssertionSigningService;
use Surfnet\GsspBundle\Saml\ResponseContext;
use Surfnet\GsspBundle\Service\ConfigurationContainer;
use Surfnet\GsspBundle\Service\DateTime\SystemDateTimeService;
use Surfnet\GsspBundle\Service\ResponseService;
use Surfnet\GsspBundle\Service\StateBasedAuthenticationService;
use Surfnet\GsspBundle\Service\StateBasedRegistrationService;
use Surfnet\GsspBundle\Service\StateHandler;
use Surfnet\GsspBundle\Service\ValueStore\InMemoryValueStore;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Entity\StaticServiceProviderRepository;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\BridgeContainer;
use Surfnet\SamlBundle\Signing\SignatureVerifier;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class GsspContext implements Context
{
    private ?BufferingLogger $logger = null;
    private ?ResponseContext $responseContext = null;
    private ?StateBasedRegistrationService $registrationService = null;
    private ?StateBasedAuthenticationService $authenticationService = null;
    /**
     * @var MockInterface|Environment
     */
    private $twigEnvironment;
    /**
     * Last controller response.
     */
    private null|\Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse $response = null;
    /**
     * Last controller twig render template
     */
    private ?string $twigTemplate = null;
    /**
     * Last controller twig render parameters
     */
    private array $twigParameters = [];
    private ?SSOController $ssoController = null;
    private ?SSOReturnController $ssoReturnController = null;
    private ?IdentityProvider $identityProvider = null;
    private ?SAMLAuthnRequest $authnRequest = null;
    private ?BridgeContainer $container = null;
    private ?ServiceProvider $serviceProvider = null;

    private ?Message $ssoReturnResponse = null;

    private ?Exception $lastException = null;

    /**
     * Every scenario we start with a clean slate.
     *
     * @BeforeScenario
     * @throws AssertionFailedException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function bootstrapDependencies(): void
    {
        // Empty state variables.
        $this->ssoReturnResponse = null;
        $this->response = null;
        $this->twigParameters = [];
        $this->twigTemplate = null;

        // Create required dependencies.
        $stateHandler = new StateHandler(new InMemoryValueStore());
        $this->logger = new BufferingLogger();
        $logger = new StepupRequestIdSariLogger(
            $this->logger,
            new SamlAuthenticationLogger($this->logger),
            $stateHandler
        );
        $this->container = new BridgeContainer($logger);
        ContainerSingleton::setContainer($this->container);

        $samlBundle = __DIR__.'/../../../vendor/surfnet/stepup-saml-bundle';

        $this->serviceProvider = new ServiceProvider(
            [
                'entityId' => 'https://service_provider/saml/metadata',
                'assertionConsumerUrl' => 'https://service_provider/saml/acu',
                'certificateFile' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
                'privateKeys' => [
                    new PrivateKey(
                        sprintf('%s/src/Resources/keys/development_privatekey.pem', $samlBundle),
                        'default'
                    ),
                ],
                'sharedKey' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
            ]
        );

        $this->identityProvider = new IdentityProvider(
            [
                'entityId' => 'https://identity_provider/saml/metadata',
                'ssoUrl' => 'https://identity_provider/saml/sso',
                'certificateFile' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
                'privateKeys' => [
                    new PrivateKey(
                        sprintf('%s/src/Resources/keys/development_privatekey.pem', $samlBundle),
                        'default'
                    ),
                ],
                'sharedKey' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
            ]
        );

        $serviceProviders = new StaticServiceProviderRepository([$this->serviceProvider]);
        $keyLoader = new KeyLoader();
        $signatureVerifier = new SignatureVerifier($keyLoader, $logger);
        $redirectBinding = new RedirectBinding($signatureVerifier, $serviceProviders);
        $configuration = new ConfigurationContainer(
            [
                'registration_route' => 'registration_route_action',
                'authentication_route' => 'authentication_route_action',
            ]
        );

        $router = Mockery::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->with('registration_route_action', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('https://identity_provider/registration')
        ;

        $router->shouldReceive('generate')
            ->with('authentication_route_action', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('https://identity_provider/authentication')
        ;

        $router->shouldReceive('generate')
            ->with('gssp_saml_sso_return')
            ->andReturn('https://identity_provider/saml/sso_return')
        ;

        $this->registrationService = new StateBasedRegistrationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->authenticationService = new StateBasedAuthenticationService(
            $stateHandler,
            $router,
            $logger
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
        $this->ssoController = new SSOController(
            $redirectBinding,
            $configuration,
            $stateHandler,
            $this->responseContext,
            $logger
        );
        $this->ssoReturnController = new SSOReturnController(
            $configuration,
            $stateHandler,
            $responseService,
            $this->responseContext,
            $logger
        );
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')->with('templating')->andReturn(false);
        $container->shouldReceive('has')->with('twig')->andReturn(true);
        $this->twigEnvironment = Mockery::mock(Environment::class);
        $this->twigEnvironment->shouldReceive('render')->andReturnUsing(
            function (
                string $template,
                $parameters = []
            ): string {
                $this->twigParameters = $parameters;
                $this->twigTemplate = $template;

                return $template.'-response';
            }
        )
        ;
        $container->shouldReceive('get')->with('twig')->andReturn($this->twigEnvironment);


        $container->shouldReceive('get')->with('router')->andReturn($router);
        $this->ssoController->setContainer($container);
        $this->ssoReturnController->setContainer($container);
    }

    /**
     * Creates a normal AuthnRequest and set it to this context.
     *
     * @Given a normal SAML 2.0 AuthnRequest
     *
     * @throws Exception
     */
    public function createANormalAuthnRequest(): void
    {
        $request = new SAMLAuthnRequest();
        $request->setAssertionConsumerServiceURL($this->serviceProvider->getAssertionConsumerUrl());
        $request->setDestination($this->identityProvider->getSsoUrl());
        $issuer = new Issuer();
        $issuer->setValue($this->serviceProvider->getEntityId());
        $request->setIssuer($issuer);
        $request->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
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
     * @throws Exception
     */
    public function createANormalAuthnRequestFromServiceProvider(string $entityId, string $acu): void
    {
        $request = new SAMLAuthnRequest();
        $request->setAssertionConsumerServiceURL($acu);
        $request->setDestination($this->identityProvider->getSsoUrl());
        $issuer = new Issuer();
        $issuer->setValue($entityId);
        $request->setIssuer($issuer);
        $request->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $this->authnRequest = $request;
    }

    /**
     * Sign the AuthnRequest on this context from the SP.
     *
     * @Given AuthnRequest is signed with sha256
     *
     * @throws Exception
     */
    public function signAuthnRequest(): void
    {
        $this->authnRequest->setSignatureKey(
            self::loadPrivateKey($this->serviceProvider->getPrivateKey(PrivateKey::NAME_DEFAULT))
        );
    }

    /**
     * Call the SSO IdP endpoint action with HTTP AuthnRequest request, set the response on this context.
     *
     * @When the service provider sends the AuthnRequest with HTTP-Redirect binding
     */
    public function callIdentityProviderSSOActionWithAuthnRequest(): void
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
     * @When the service provider sends an unsigned AuthnRequest with HTTP-Redirect binding
     *
     * @throws Exception
     */
    public function callIdentityProviderSSOActionWithAuthnRequestWithoutSignature(): void
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
     * @When the service provider sends an invalided signed AuthnRequest with HTTP-Redirect binding
     *
     * @throws Exception
     */
    public function callIdentityProviderSSOActionWithAuthnRequestWithIncorrectSignature(): void
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
     * @Then the identity provider response should be an unrecoverable error :expected
     *
     * This error should be handled by the application implementing this bundle.
     *
     * @throws AssertionFailedException
     */
    public function responseShouldBeAnUnrecoverableError($expected): void
    {
        $actual = '';

        if ($this->lastException instanceof \Exception) {
            $actual = $this->lastException->getMessage();
        }

        Assertion::eq(
            $actual,
            sprintf('Error processing the SAML authentication request: %s', $expected)
        );
    }

    /**
     * @Then the identity provider register the user with an unique identifier token
     */
    public function assignAUniqueIdentifierToTheToken(): void
    {
        $this->registrationService->register('unique-identifier-token');
        $this->registrationService->replyToServiceProvider();
    }

    /**
     * @Then the user is redirected to the identity provider sso return endpoint
     * @Then the user is redirected to the identity provider sso return endpoint without registration
     * @Then the user is redirected to the identity provider sso return endpoint without authentication
     *
     * @throws AssertionFailedException
     * @throws Exception
     */
    public function requestSSOreturnEndpoint(): void
    {
        try {
            $this->response = $this->ssoReturnController->ssoReturn();
        } catch (Exception $e) {
            $this->lastException = $e;
        }
    }

    /**
     * @Then Identity provider sso return endpoint should redirect client-side a saml response to the service provider
     *
     * @throws AssertionFailedException
     * @throws Exception
     */
    public function shouldReturnSamlResponse(): void
    {
        $parameters = $this->twigParameters;
        Assertion::eq('@SurfnetGssp/StepupGssp/ssoReturn.html.twig', $this->twigTemplate);
        Assertion::eq('', $parameters['relayState']);
        Assertion::eq('https://service_provider/saml/acu', $parameters['acu']);
        $decodedSamlRequest = base64_decode((string) $parameters['response']);
        $document = DOMDocumentFactory::fromString($decodedSamlRequest);
        $this->ssoReturnResponse = Message::fromXML($document->firstChild);
        Assertion::eq('@SurfnetGssp/StepupGssp/ssoReturn.html.twig-response', $this->response->getContent());
    }

    /**
     * @Then the saml response status code should be :code
     */
    public function theResponseStatusCodeShouldBe($code): void
    {
        $status = $this->ssoReturnResponse->getStatus();
        Assertion::eq($code, $status['Code']);
    }

    /**
     * @Then the saml response assertion should be signed
     *
     * @throws AssertionFailedException
     * @throws RunTimeException
     */
    public function theResponseAssertionShouldBeSigned(): void
    {
        $assertion = $this->getSsoAssertionResponse();
        Assertion::eq('http://www.w3.org/2001/04/xmldsig-more#rsa-sha256', $assertion->getSignatureMethod());
        Assertion::contains(
            str_replace("\r\n", '', $this->loadIdpPublicCertificate()),
            $assertion->getCertificates()[0]
        );
        $key = self::loadPublicKey($this->identityProvider->getSharedKey());
        try {
            $assertion->validate($key);
        } catch (Exception $e) {
            throw new RunTimeException(sprintf('openssl_error_string: "%s" Error: %s', openssl_error_string(), $e->getMessage()));
        }
    }

    /**
     * @Then the saml response should have an authenticating authority of the IdP EntityId with class ref :classRef
     */
    public function theResponseShouldHaveAnAuthenticatingAuthorityOfTheIdpWithClassRef($classRef): void
    {
        $assertion = $this->getSsoAssertionResponse();
        Assertion::eq($classRef, $assertion->getAuthnContextClassRef());
        Assertion::eq([$this->identityProvider->getEntityId()], $assertion->getAuthenticatingAuthority());
    }

    /**
     * @Then the saml response should have the token identifier in the Subject NameID of the Assertion section
     */
    public function theResponseShouldHaveTheTokenIdentifierInTheSubjectNameIdOfTheAssertionSection(): void
    {
        $assertion = $this->getSsoAssertionResponse();
        $nameId = $assertion->getNameId();
        Assertion::eq('unique-identifier-token', $nameId->getValue());
        Assertion::eq(Constants::NAMEID_PERSISTENT, $nameId->getFormat());
    }

    /**
     * @return PrivateKey|XMLSecurityKey
     * @throws Exception
     */
    private function loadPrivateKey(PrivateKey $key): XMLSecurityKey
    {
        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($key);

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }

    /**
     * @param PrivateKey $publicKey
     * @return PrivateKey|XMLSecurityKey
     *
     * @throws Exception
     */
    private function loadPublicKey($publicKey): XMLSecurityKey
    {
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $key->loadKey($publicKey, true);

        return $key;
    }


    /**
     * @return string
     */
    private function loadIdpPublicCertificate(): string
    {
        $keyLoader = new KeyLoader();
        $keyLoader->loadCertificateFile($this->identityProvider->getCertificateFile());
        /** @var X509 $publicKey */
        $publicKey = $keyLoader->getKeys()->getOnlyElement();

        return $publicKey->getCertificate();
    }

    /**
     * @return mixed|\SAML2\Assertion|EncryptedAssertion
     */
    private function getSsoAssertionResponse(): \SAML2\Assertion|EncryptedAssertion|bool
    {
        $assertions = $this->ssoReturnResponse->getAssertions();

        return reset($assertions);
    }

    /**
     *
     * @When /^an user requests the identity provider sso endpoint$/
     * @When /^the user requests the identity provider sso endpoint$/
     */
    public function callIdentityProviderSSOAction(array $parameters = []): void
    {
        $uri = 'https://identity_provider/saml/sso';
        $delimiter = '?';
        foreach ($parameters as $parameterName => $value) {
            $uri .= $delimiter . $parameterName . '=' . urlencode((string) $value);
            $delimiter = '&';
        }
        $request = Request::create($uri);

        unset($this->lastException);

        try {
            $this->response = $this->ssoController->sso($request);
        } catch (Exception $e) {
            $this->lastException = $e;
        }
    }

    /**
     * @Then /^the response should be an redirect the application registration endpoint$/
     *
     * @throws AssertionFailedException
     */
    public function theResponseShouldBeAnRedirectTheApplicationRegistrationEndpoint(): void
    {
        Assertion::isInstanceOf($this->response, RedirectResponse::class);
        /** @var RedirectResponse $response */
        $response = $this->response;
        Assertion::eq($response->getTargetUrl(), 'https://identity_provider/registration');
    }

    /**
     * @Then /^the response should be an redirect the application authentication endpoint$/
     *
     * @throws AssertionFailedException
     */
    public function theResponseShouldBeAnRedirectTheApplicationAuthenticationEndpoint(): void
    {
        Assertion::isInstanceOf($this->response, RedirectResponse::class);
        /** @var RedirectResponse $response */
        $response = $this->response;
        Assertion::eq($response->getTargetUrl(), 'https://identity_provider/authentication');
    }

    /**
     * @Then /^there should not be an unique identifier token assigned$/
     *
     * @throws AssertionFailedException
     */
    public function thereShouldNotBeAnUniqueIdentifierTokenAssigned(): void
    {
        Assertion::false($this->responseContext->isRegistered());
    }

    /**
     * @When /^I clear the logs$/
     */
    public function clearTheLogs(): void
    {
        $this->logger->cleanLogs();
    }

    /**
     * @Given /^the logs are:$/
     *
     * @throws AssertionFailedException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function theLogsAre(TableNode $table): void
    {
        $logs = $this->logger->cleanLogs();
        $rows = array_values($table->getColumnsHash());
        foreach ($rows as $index => $row) {
            Assertion::true(isset($logs[$index]), sprintf('Missing message %s', $row['message']));
            [$level, $message, $context] = $logs[$index];
            if (preg_match('/^\/.*\/$/', (string) $row['message']) === 1) {
                Assertion::regex($message, $row['message']);
            } else {
                Assertion::eq($message, $row['message']);
            }
            Assertion::eq($row['level'], $level, sprintf('Level does not match for %s', $row['message']));
            Assertion::choice($row['sari'], ['', 'present']);
            if ($row['sari'] === 'present') {
                Assertion::keyExists($context, 'sari', sprintf('Missing sari for message %s', $row['message']));
                Assertion::notEmpty($context['sari']);
            } else {
                Assertion::keyNotExists($context, 'sari', sprintf('Having unexpected sari for message %s', $row['message']));
            }
        }
        $logs = array_slice($logs, count($rows));
        Assertion::noContent($logs, var_export($logs, true));
    }

    /**
     * @Then the identity provider rejects the request with the error :message
     */
    public function theIdentityProviderSetsAnError($message): void
    {
        $this->registrationService->reject($message);
        $this->registrationService->replyToServiceProvider();
    }

    /**
     * @Given set the subject nameId to :nameId
     */
    public function setTheSubjectNameIdTo(string $nameId): void
    {
        $nameIdVo = new NameID();
        $nameIdVo->setValue($nameId);
        $nameIdVo->setFormat(Constants::NAMEID_PERSISTENT);
        $this->authnRequest->setNameId($nameIdVo);
    }

    /**
     * @Then /^the identity provider authenticates the user$/
     */
    public function theIdentityProviderAuthenticatesTheUser(): void
    {
        $this->authenticationService->authenticate();
    }

    /**
     * @Then /^the return endpoint should raise an exception with "([^"]*)"$/
     */
    public function theReturnEndpointShouldRaiseAnException($message): void
    {
        Assertion::eq($message, $this->lastException->getMessage());
    }
}
