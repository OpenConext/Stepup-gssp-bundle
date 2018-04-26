Feature: When an user needs to enroll for a new token
  To enroll an user for a new token
  As a service provider
  I need to send an AuthnRequest to the identity provider

  Scenario: When an user needs to enroll for a new token
    Given a normal SAML 2.0 AuthnRequest
    And AuthnRequest is signed with sha256

    When the service provider sends the AuthnRequest with HTTP-Redirect binding

    Then the identity provider register the user with an unique identifier token

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
      | notice | Redirect user to the application registration route https://identity_provider/registration                                    | present |

      | notice | Application sets the subject nameID to unique-identifier-token                                                                | present |
      | notice | Created redirect response for sso return endpoint "https://identity_provider/saml/sso_return"                                 | present |

      | notice | Received sso return request                                                                                                   | present |
      | info   | Create sso response                                                                                                           | present |
      | notice | /Saml response created with id ".+", request ID: ".+"/                                                                        | present |
      | notice | Invalidate current state and redirect user to service provider assertion consumer url "https://service_provider/saml/acu"     | present |

  Scenario: When an user request the sso return endpoint without being registered the user should be redirected to the application registration endpoint
    Given a normal SAML 2.0 AuthnRequest
    And AuthnRequest is signed with sha256
    And the service provider sends the AuthnRequest with HTTP-Redirect binding
    And I clear the logs

    When the user is redirected to the identity provider sso return endpoint without registration

    Then the response should be an redirect the application registration endpoint

    And the logs are:
      | level   | message                                                                                                                        | sari    |
      | notice  | Received sso return request                                                                                                    | present |
      | warning | User was not registered by the application, redirect user back the registration route "https://identity_provider/registration" | present |

