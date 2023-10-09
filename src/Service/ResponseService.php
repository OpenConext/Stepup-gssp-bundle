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

use SAML2\Assertion;
use SAML2\Constants;
use SAML2\Response;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use Surfnet\GsspBundle\Saml\AssertionSigningServiceInterface;
use Surfnet\GsspBundle\Saml\ResponseContextInterface;
use Surfnet\SamlBundle\Entity\IdentityProvider;

final class ResponseService implements ResponseServiceInterface
{

    private $hostedIdentityProvider;
    private $responseContext;
    private $assertionSigningService;
    private $dateTimeService;

    public function __construct(
        IdentityProvider $hostedIdentityProvider,
        ResponseContextInterface $responseContext,
        AssertionSigningServiceInterface $assertionSigningService,
        DateTimeService $dateTimeService
    ) {
        $this->hostedIdentityProvider = $hostedIdentityProvider;
        $this->responseContext = $responseContext;
        $this->assertionSigningService = $assertionSigningService;
        $this->dateTimeService = $dateTimeService;
    }

    public function createResponse(): Response
    {
        $response = $this->createNewAuthnResponse();

        if ($this->responseContext->inErrorState()) {
            $response->setStatus($this->responseContext->getErrorStatus());
            return $response;
        }

        $assertion = $this->createAssertion();
        $response->setAssertions([$assertion]);

        return $response;
    }

    private function addSubjectConfirmationFor(Assertion $assertion): void
    {
        $confirmation = new SubjectConfirmation();
        $confirmation->setMethod(Constants::CM_BEARER);
        $nameId = new NameID();
        $nameId->setValue($this->responseContext->getSubjectNameId());
        $nameId->setFormat(Constants::NAMEID_PERSISTENT);
        $assertion->setNameId($nameId);

        $confirmationData = new SubjectConfirmationData();
        $confirmationData->setInResponseTo($this->responseContext->getRequestId());
        $confirmationData->setRecipient($this->responseContext->getServiceProvider()->getAssertionConsumerUrl());
        $confirmationData->setNotOnOrAfter($assertion->getNotOnOrAfter());

        $confirmation->setSubjectConfirmationData($confirmationData);

        $assertion->setSubjectConfirmation([$confirmation]);
    }

    private function addAuthenticationStatementTo(Assertion $assertion): void
    {
        $assertion->setAuthnInstant($this->dateTimeService->getCurrent()->getTimestamp());
        $assertion->setAuthnContextClassRef('urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorUnregistered');

        $authority = $assertion->getAuthenticatingAuthority();
        $assertion->setAuthenticatingAuthority(
            array_merge(
                (empty($authority) ? [] : $authority),
                [$assertion->getIssuer()->getValue()]
            )
        );
    }

    private function createNewAuthnResponse(): Response
    {
        $response = new Response();
        $issuer = new Issuer();
        $issuer->setValue($this->hostedIdentityProvider->getEntityId());
        $response->setIssuer($issuer);
        $response->setIssueInstant($this->dateTimeService->getCurrent()->getTimestamp());
        $response->setDestination($this->responseContext->getServiceProvider()->getAssertionConsumerUrl());
        $response->setInResponseTo($this->responseContext->getRequestId());

        return $response;
    }

    private function createAssertion(): Assertion
    {
        $assertion = new Assertion();
        $assertion->setNotBefore($this->dateTimeService->getCurrent()->getTimestamp());
        $assertion->setNotOnOrAfter($this->dateTimeService->interval('PT5M')->getTimestamp());
        $issuer = new Issuer();
        $issuer->setValue($this->hostedIdentityProvider->getEntityId());
        $assertion->setIssuer($issuer);
        $assertion->setIssueInstant($this->dateTimeService->getCurrent()->getTimestamp());

        $this->assertionSigningService->signAssertion($assertion);
        $targetServiceProvider = $this->responseContext->getServiceProvider();
        $this->addSubjectConfirmationFor($assertion);

        $assertion->setValidAudiences([$targetServiceProvider->getEntityId()]);

        $this->addAuthenticationStatementTo($assertion);

        return $assertion;
    }
}
