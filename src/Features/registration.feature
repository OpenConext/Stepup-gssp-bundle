Feature: When an user needs to enroll for a new token
  To enroll an user for a new token
  As a service provider
  I need to send an AuthnRequest to the identity provider

  Scenario: When an user needs to enroll for a new token
    Given a normal SAML 2.0 AuthnRequest
    And AuthnRequest is signed with sha256

    When the service provider send the AuthnRequest with HTTP-Redirect binding

    Then the identity provider register the user with an unique identifier token

    When the user is redirected to the identity provider sso return endpoint
    Then Identity provider sso return endpoint should redirect client-side a saml response to the service provider
    And the saml response assertion should be signed
    And the saml response status code should be "urn:oasis:names:tc:SAML:2.0:status:Success"
    And the saml response should have an authenticating authority of the IdP EntityId with class ref 'urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorUnregistered'
    And the saml response should have the token identifier in the Subject NameID of the Assertion section

  Scenario: When a service provider is unknown the AuthnRequest should be denied
    Given a normal SAML 2.0 AuthnRequest form a unknown service provider entityId 'https://service_provider_unkown/saml/metadata' acu 'https://service_provider_unkown/saml/acu'
    And AuthnRequest is signed with sha256
    When the service provider send the AuthnRequest with HTTP-Redirect binding
    Then the identity provider response should be an unrecoverable error 'AuthnRequest received from ServiceProvider with an unknown EntityId: "https://service_provider_unkown/saml/metadata"'

  Scenario: When a service provider sends an AuthnRequest without signature the request should be denied
    Given a normal SAML 2.0 AuthnRequest
    When the service provider send an unsigned AuthnRequest with HTTP-Redirect binding
    Then the identity provider response should be an unrecoverable error 'The SAMLRequest is expected to be signed but it was not'

  Scenario: When a service provider sends an AuthnRequest with incorrect signature the request should be denied
    Given a normal SAML 2.0 AuthnRequest
    When the service provider send an invalided signed AuthnRequest with HTTP-Redirect binding
    Then the identity provider response should be an unrecoverable error 'The SAMLRequest has been signed, but the signature format is not supported'

  Scenario: When an user request the sso endpoint without AuthnRequest the request should be denied
    When an user request identity provider sso endpoint
    Then the identity provider response should be an unrecoverable error 'Could not receive AuthnRequest from HTTP Request: expected query parameters, none found'

  Scenario: When an user request the sso return endpoint without being registered the user should be redirected to the application registration endpoint
    Given a normal SAML 2.0 AuthnRequest
    And AuthnRequest is signed with sha256
    And the service provider send the AuthnRequest with HTTP-Redirect binding

    When the user is redirected to the identity provider sso return endpoint without registration

    Then the response should be an redirect the application registration endpoint

  Scenario: When an AuthnRequest is requested twice the previous state should be invalidated
    Given a normal SAML 2.0 AuthnRequest
    And AuthnRequest is signed with sha256
    And the service provider send the AuthnRequest with HTTP-Redirect binding
    And the identity provider register the user with an unique identifier token

    When the service provider send the AuthnRequest with HTTP-Redirect binding
    Then there should not be an unique identifier token assigned
