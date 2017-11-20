Stepup-gssp-bundle
===================

<a href="#">
    <img src="https://travis-ci.org/OpenConext/Stepup-gssp-bundle.svg?branch=master" alt="build:">
</a></br>

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
```

See [Saml bundle documentation](https://github.com/OpenConext/Stepup-saml-bundle) for more information about this configuration

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

To get started, first setup the development environment. The dev. env. is a virtual machine. Every task described is run
from that machine.  

Requirements
-------------------
- ansible 2.x
- vagrant 1.9.x
- Virtualbox

Install
-------------------

``` vagrant up ```

If everything goes as planned you can develop inside the virtual machine

``` vagrant ssh ```

Debugging
-------------------
Xdebug is configured when provisioning your development Vagrant box. 
It's configured with auto connect IDE_KEY=phpstorm.

Tests en metrics
======================

To run all required test you can run the following command from the dev env:

```composer test```

Every part can be run separately. Check "scripts" section of the composer.json file for the different options.

Other resources
======================

 - [Developer documentation](docs/index.md)
 - [Issue tracker](https://www.pivotaltracker.com/n/projects/1163646)
 - [License](LICENSE)
 - [GSSP documentation](https://github.com/OpenConext/Stepup-Gateway/blob/develop/docs/GSSP.md)
