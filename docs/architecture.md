# Application architecture

The bundle is configured to be enabled into symphony application. 

## Registration flow

When an user needs to register for a new token
 
1. The service provider sends a normal SAML 2.0 AuthnRequest.
    * HTTP-Redirect binding
    * Signed with http://www.w3.org/2001/04/xmldsig-more#rsa-sha256
2. The GSSP IdP will receive the request on route `/saml/sso`. The `IdentityController::ssoAction` will handle the AuthnRequest
   and redirect the user to the application route where the actual registration takes place.
3. The application controller needs to register the new user by calling the `AuthenticationRegistrationService::register` with Subject NameID. The `register` function 
   will return a response that will redirect the user to the GSSP IdP `/saml/sso_return` route.
4. The `/saml/sso_return` route is handled by `IdentityController::ssoReturnAction` that will assemble the saml response. 
   The saml response will be send client-side to the service provider by a POST call. 
