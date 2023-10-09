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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\SamlBundle\Metadata\MetadataFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route(service="surfnet_gssp.saml.metadata_controller")
 */
final class MetadataController extends AbstractController
{
    public function __construct(private readonly MetadataFactory $metadataFactory)
    {
    }

    /**
     * @Method("GET")
     * @Route("/saml/metadata", name="gssp_saml_metadata")
     */
    public function metadata(): XMLResponse
    {
        return new XMLResponse((string) $this->metadataFactory->generate());
    }
}
