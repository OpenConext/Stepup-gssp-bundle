Stepup-gssp-bundle
===================

<a href="#">
    <img src="https://travis-ci.org/OpenConext/Stepup-gssp-bundle.svg?branch=master" alt="build:">
</a></br>

Generic SAML Stepup Provider bundle.

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
