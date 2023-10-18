Stepup-gssp-bundle
===================

[![Run tests (static analysis, lint and unit tests)](https://github.com/OpenConext/Stepup-gssp-example/actions/workflows/test-integration.yml/badge.svg)](https://github.com/OpenConext/Stepup-gssp-example/actions/workflows/test-integration.yml)
[![Run acceptance tests (Behat)](https://github.com/OpenConext/Stepup-gssp-example/actions/workflows/test-acceptance.yml/badge.svg)](https://github.com/OpenConext/Stepup-gssp-example/actions/workflows/test-acceptance.yml)

Generic SAML Stepup Provider bundle.

## Installation

* Add the package to your Composer file
```sh
composer require surfnet/stepup-gssp-bundle
```

* Add the bundle to your kernel in `app/AppKernel.php`
```php
public function registerBundles()
{
  // ...
  $bundles[] = new Surfnet\SamlBundle\SurfnetSamlBundle();
  $bundles[] = new Surfnet\GsspBundle\GsspBundle();
}
```

## Configuration

**config.yml**

```yaml
surfnet_saml:
    hosted:
        identity_provider:
            enabled: true
            service_provider_repository: surfnet_gssp.saml.service_provider_repository
            sso_route: sso
            public_key: "%saml_idp_publickey%"
            private_key: "%saml_idp_privatekey%"
        metadata:
            entity_id_route: gssp_saml_metadata
            public_key: "%saml_metadata_publickey%"
            private_key: "%saml_metadata_privatekey%"
    remote:
        identity_provider:
            enabled: true
            entity_id: "%saml_remote_idp_entity_id%"
            sso_url: "%saml_remote_idp_sso_url%"
            certificate_file: "%saml_remote_idp_certificate%"
            
            
surfnet_gssp:
    registration_route: '<your-registration-route>'
    authentication_route: '<your-authentication-route>'             
```

See [Saml bundle documentation](https://github.com/OpenConext/Stepup-saml-bundle) for more information about the 'surfnet_saml' configuration above.

See the controller for working examples of the 'surfnet_gssp' routes in [Example controller](https://github.com/OpenConext/Stepup-gssp-example/blob/master/src/AppBundle/Controller/DefaultController.php)

The AuthenticationService and the RegistrationService is the API for the application to interact with this bundle. 
Both services document an example how to use them in a controller action.

**routing.yml**

```yaml
gssp_saml:
    resource: '@SurfnetGsspBundle/Resources/config/routing.yml'
```

**parameters.yml**

```yaml
parameters:
    saml_idp_publickey: '%kernel.root_dir%/../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_publickey.cer'
    saml_idp_privatekey: '%kernel.root_dir%/../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_privatekey.pem'
    saml_metadata_publickey: '%kernel.root_dir%/../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_publickey.cer'
    saml_metadata_privatekey: '%kernel.root_dir%/../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_privatekey.pem'
    saml_remote_idp_entity_id: 'https://pieter.aai.surfnet.nl/simplesamlphp/saml2/idp/metadata.php'
    saml_remote_idp_sso_url: 'https://pieter.aai.surfnet.nl/simplesamlphp/saml2/idp/SSOService.php'
    saml_remote_idp_certificate: '%kernel.root_dir%/../vendor/surfnet/stepup-gssp-bundle/src/Resources/keys/pieter.aai.surfnet.nl.pem'
```

This is example idp configuration that works with [pieter.aai.surfnet.nl](https://pieter.aai.surfnet.nl/) idp.

Development environment
======================

The purpose of the development environment is only for running the different test and metric tools.

To get started, first setup the development environment. The development environment is a docker container. That is
controlled via the [OpenConext-devconf](https://github.com/OpenConext/OpenConext-devconf/) project. 

Every task described below should be run from that container.  

Requirements
-------------------
- Docker
- OpenConext-devconf

Debugging
-------------------
Xdebug is configured when provisioning your development Vagrant box. 
It's configured with auto connect IDE_KEY=phpstorm and ```xon``` on cli env. 

Tests en metrics
======================

To run all required test you can run the following command from the dev env:

```composer test```

Every part can be run separately. Check "scripts" section of the composer.json file for the different options.

Release instructions
=====================

Please read: https://github.com/OpenConext/Stepup-Deploy/wiki/Release-Management for more information on the release strategy used in Stepup projects.

Other resources
======================

 - [Developer documentation](docs/index.md)
 - [Issue tracker](https://www.pivotaltracker.com/n/projects/1163646)
 - [License](LICENSE)
 - [GSSP documentation](https://github.com/OpenConext/Stepup-Gateway/blob/develop/docs/GSSP.md)
