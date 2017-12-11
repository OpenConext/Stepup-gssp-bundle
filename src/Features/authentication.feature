Feature: When an user needs to be authenticated
  As a service provider
  I need to send an AuthnRequest with a nameID to the identity provider

  Scenario: When an user needs to be authenticated
    Given a normal SAML 2.0 AuthnRequest
    And AuthnRequest is signed with sha256
    And set the subject nameId to 'unique-identifier-token'

    When the service provider send the AuthnRequest with HTTP-Redirect binding

    Then the identity provider authenticates the user

    When the user is redirected to the identity provider sso return endpoint
    Then Identity provider sso return endpoint should redirect client-side a saml response to the service provider
    And the saml response assertion should be signed
    And the saml response status code should be "urn:oasis:names:tc:SAML:2.0:status:Success"
    And the saml response should have an authenticating authority of the IdP EntityId with class ref 'urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorUnregistered'
    And the saml response should have the token identifier in the Subject NameID of the Assertion section

    And the logs are:
      | level  | message                                                                                                                       | sari    |
      | notice | Received sso request                                                                                                          |         |
      | info   | Processing AuthnRequest                                                                                                       |         |
      | debug  | Extracting public keys for ServiceProvider "https://service_provider/saml/metadata"                                           |         |
      | debug  | Found "1" keys, filtering the keys to get X509 keys                                                                           |         |
      | debug  | Found "1" X509 keys, attempting to use each for signature verification                                                        |         |
      | debug  | /Attempting to verify signature with certificate.*/                                                                           |         |
      | debug  | Signature VERIFIED                                                                                                            |         |
      | notice | /AuthnRequest processing complete, received AuthnRequest from "https:\/\/service_provider\/saml\/metadata", request ID: ".+"/ |         |
      | info   | AuthnRequest stored in state                                                                                                  | present |
      | notice | Redirect user to the application authentication route https://identity_provider/authentication                                | present |

      | notice | Application authenticates the user                                                                                            | present |
      | notice | Received sso return request                                                                                                   | present |

      | info   | Create sso response                                                                                                           | present |
      | notice | /Saml response created with id ".+", request ID: ".+"/                                                                        | present |
      | notice | Invalidate current state and redirect user to service provider assertion consumer url "https://service_provider/saml/acu"     | present |

  Scenario: When an user request the sso return endpoint without being authenticated the user should be redirected to the application authentication endpoint
    Given a normal SAML 2.0 AuthnRequest
    And AuthnRequest is signed with sha256
    And set the subject nameId to 'unique-identifier-token'
    And the service provider send the AuthnRequest with HTTP-Redirect binding
    And I clear the logs

    When the user is redirected to the identity provider sso return endpoint without authentication

    Then the response should be an redirect the application authentication endpoint

    And the logs are:
      | level   | message                                                                                                                               | sari    |
      | notice  | Received sso return request                                                                                                           | present |
      | warning | User was not authenticated by the application, redirect user back the authentication route "https://identity_provider/authentication" | present |
