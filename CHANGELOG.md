# 4.0.2

**Deprecation warning updates**
 - Use the AbstractController base controller

# 4.0.0
* Dropped support for PHP 5.6
* Dropped support for Symfony 4.3 and older
* Add support for SAML extensions

# 3.0.6
For this release the StateHandlerInterface and the AuthenticationService interface have been updated. If you implement 
these interfaces yourself, please implement these methods in your concrete implementations.
Use the bundles implementations as inspiration or, if you do not use them, leave them unimplemented logic wise.

- Expose the Scoping -> RequesterIds on StateBasedAuthenticationService

# 3.0.5
- Expose Issuer on StateBasedAuthenticationService

# 3.0.4
- Upgrade Stepup-saml-bundle to version 4.1.8 #31
- Version pin Symfony 3.4 dependencies #31

# 3.0.1 -- 3.0.3
- No change notes provided

# 3.0.0 
Support Symfony 4.3 #30

# Prior to version 3
RMT was used to write the change logs.

## VERSION 2  Don't do error handling
Applications implementing this bundle should handle uncaught exceptions,
Include logging and showing an error page to the user.

### Version 2.0 - Remove error handling
- 26/04/2018 10:00  2.0.0  initial release
- 19/06/2018 10:00  2.1.0  initial release

## VERSION 1  Initial release
- 25/04/2018 10:00  1.1.0  initial release
- 18/04/2018 11:50  1.1.0  initial release
- 15/02/2018 15:25  1.0.2  initial release
