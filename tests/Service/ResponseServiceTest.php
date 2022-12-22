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

namespace Tests\Surfnet\GsspBundle\Service;

use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SAML2\Compat\ContainerSingleton;
use SAML2\Constants;
use Surfnet\GsspBundle\Saml\AssertionSigningServiceInterface;
use Surfnet\GsspBundle\Saml\ResponseContextInterface;
use Surfnet\GsspBundle\Service\DateTime\StaticDateTimeService;
use Surfnet\GsspBundle\Service\ResponseService;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\SAML2\BridgeContainer;

class ResponseServiceTest extends TestCase
{
    /**
     * @var ResponseContextInterface|MockInterface
     */
    protected $responseContext;
    /**
     * @var IdentityProvider|MockInterface
     */
    protected $identityProvider;
    /**
     * @var ServiceProvider|MockInterface
     */
    protected $serviceProvider;
    /**
     * @var AssertionSigningServiceInterface|MockInterface
     */
    protected $assertionSigningService;

    protected function setUp(): void
    {
        $logger = new NullLogger();
        $container = new BridgeContainer($logger);
        ContainerSingleton::setContainer($container);
        $this->responseContext = \Mockery::mock(ResponseContextInterface::class);
        $this->identityProvider = \Mockery::mock(IdentityProvider::class);
        $this->serviceProvider = \Mockery::mock(ServiceProvider::class);
        $this->assertionSigningService = \Mockery::spy(AssertionSigningServiceInterface::class);
    }

    /**
     * @test
     */
    public function canCreateResponse()
    {
        $datetimeService = new StaticDateTimeService(
            \DateTimeImmutable::createFromFormat(
                \DateTime::ATOM,
                '2016-12-08T10:42:59+0100'
            )
        );

        $this->identityProvider->shouldReceive([
            'getEntityId' => 'https://identity_provider/saml/metadata'
        ]);

        $this->serviceProvider->shouldReceive([
            'getEntityId' => 'https://service_provider/saml/metadata',
            'getAssertionConsumerUrl' => 'https://service_provider/saml/acu'
        ]);
        $this->responseContext
            ->shouldReceive([
                'getServiceProvider' => $this->serviceProvider,
                'getSubjectNameId' => 'subject_name_id',
                'getRequestId' => 'sp_request_id',
                'inErrorState' => false
            ]);
        $service = new ResponseService(
            $this->identityProvider,
            $this->responseContext,
            $this->assertionSigningService,
            $datetimeService
        );
        $response = $service->createResponse();
        $xml = $response->toUnsignedXML()->ownerDocument->saveXML();
        $xml = preg_replace('/ID=".*?"/', 'ID="1234"', $xml);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/fixture/saml_response.xml',
            $xml
        );

        $this->assertionSigningService->shouldHaveReceived('signAssertion');
    }

    /**
     * @test
     */
    public function canCreateErrorResponse()
    {
        $datetimeService = new StaticDateTimeService(
            \DateTimeImmutable::createFromFormat(
                \DateTime::ATOM,
                '2016-12-08T10:42:59+0100'
            )
        );

        $this->identityProvider->shouldReceive([
            'getEntityId' => 'https://identity_provider/saml/metadata'
        ]);

        $this->serviceProvider->shouldReceive([
            'getEntityId' => 'https://service_provider/saml/metadata',
            'getAssertionConsumerUrl' => 'https://service_provider/saml/acu'
        ]);
        $this->responseContext
            ->shouldReceive([
                'getServiceProvider' => $this->serviceProvider,
                'getRequestId' => 'sp_request_id',
                'inErrorState' => true,
                'getErrorStatus' => [
                    'Code' => Constants::STATUS_RESPONDER,
                    'Message' => 'message',
                    'SubCode' => Constants::STATUS_AUTHN_FAILED
                ]
            ]);
        $service = new ResponseService(
            $this->identityProvider,
            $this->responseContext,
            $this->assertionSigningService,
            $datetimeService
        );
        $response = $service->createResponse();
        $xml = $response->toUnsignedXML()->ownerDocument->saveXML();
        $xml = preg_replace('/ID=".*?"/', 'ID="1234"', $xml);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/fixture/saml_error_response.xml',
            $xml
        );
    }

    /**
     * Limit SubjectConfirmationData validity to Assertion validity.
     *
     * See https://www.pivotaltracker.com/story/show/158356792
     * See https://www.pivotaltracker.com/story/show/157880479
     */
    public function testSubjectConfirmationNotOnOrAfterEqualsAssertionNotOnOrAfter()
    {
        $datetimeService = new StaticDateTimeService(
            \DateTimeImmutable::createFromFormat(
                \DateTime::ATOM,
                '2016-12-08T10:42:59+0100'
            )
        );

        $this->identityProvider->shouldReceive(['getEntityId' => 'https://identity_provider/saml/metadata']);

        $this->serviceProvider->shouldReceive(
            [
                'getEntityId' => 'https://service_provider/saml/metadata',
                'getAssertionConsumerUrl' => 'https://service_provider/saml/acu'
            ]
        );

        $this->responseContext->shouldReceive(
            [
                'getServiceProvider' => $this->serviceProvider,
                'getSubjectNameId' => 'subject_name_id',
                'getRequestId' => 'sp_request_id',
                'inErrorState' => false
            ]
        );

        $service = new ResponseService(
            $this->identityProvider,
            $this->responseContext,
            $this->assertionSigningService,
            $datetimeService
        );

        $response = $service->createResponse();

        $assertions = $response->getAssertions();

        /** @var \SAML2\Assertion $assertion */
        $assertion = reset($assertions);

        $subjects = $assertion->getSubjectConfirmation();

        /** @var \SAML2\XML\saml\SubjectConfirmation $subjectConfirmation */
        $subjectConfirmation = reset($subjects);

        $this->assertEquals(
            $assertion->getNotOnOrAfter(),
            $subjectConfirmation->getSubjectConfirmationData()->getNotOnOrAfter()
        );
    }
}
