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

use SAML2\Constants;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the interface for the application to do the registration with.
 *
 * @example Controller action example.
 *      <code>
 *      public function registrationAction() {
 *
 *           if (!$this->registrationService->registrationRequired()) {
 *               return new Response('No registration required', Response::HTTP_BAD_REQUEST);
 *           }
 *
 *           // Implement logic to create your own SAML Subject NameID.
 *           if (TRUE) {
 *              $this->registrationService->register('<user-name-id>');
 *           } else {
 *              $this->registrationService->reject('Registration failed message');
 *           }
 *
 *          return $this->registrationService->replyToServiceProvider();
 *      }
 *     </code>
 */
interface RegistrationService
{
    /**
     * If there is need for a registration.
     *
     * @return bool
     */
    public function registrationRequired();

    /**
     * Register the user, this will set the saml subject nameId.
     *
     * @param string $subjectNameId
     */
    public function register($subjectNameId);

    /**
     * If the user is already registered.
     *
     * @return boolean
     */
    public function isRegistered();

    /**
     * @param string $message
     *   The error message.
     * @param string $subCode
     *   Saml response sub code.
     */
    public function reject($message, $subCode = Constants::STATUS_AUTHN_FAILED);

    /**
     * Creates the response that handles the redirect back to the service provider.
     *
     * @return Response
     */
    public function replyToServiceProvider();
}
