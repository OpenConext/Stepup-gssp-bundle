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

      | notice | Application sets the subject nameID to unique-identifier-token                                                               | present |
      | notice | Created redirect response for sso return endpoint "https://identity_provider/saml/sso_return"                                 | present |

      | notice | Received sso return request                                                                                                   | present |
      | info   | Create sso response                                                                                                           | present |
      | notice | /Saml response created with id ".+", request ID: ".+"/                                                                        | present |
      | notice | Invalidate current state and redirect user to service provider assertion consumer url "https://service_provider/saml/acu"      | present |

  Scenario: When a service provider is unknown the AuthnRequest should be denied
    Given a normal SAML 2.0 AuthnRequest form a unknown service provider entityId 'https://service_provider_unkown/saml/metadata' acu 'https://service_provider_unkown/saml/acu'
    And AuthnRequest is signed with sha256
    When the service provider send the AuthnRequest with HTTP-Redirect binding
    Then the identity provider response should be an unrecoverable error 'AuthnRequest received from ServiceProvider with an unknown EntityId: "https://service_provider_unkown/saml/metadata"'
    And the logs are:
      | level    | message                                                                                                                                                  | sari |
      | notice   | Received sso request                                                                                                                                     |      |
      | info     | Processing AuthnRequest                                                                                                                                  |      |
      | critical | Could not process Request, error: "AuthnRequest received from ServiceProvider with an unknown EntityId: "https://service_provider_unkown/saml/metadata"" |      |


  Scenario: When a service provider sends an AuthnRequest without signature the request should be denied
    Given a normal SAML 2.0 AuthnRequest
    When the service provider send an unsigned AuthnRequest with HTTP-Redirect binding
    Then the identity provider response should be an unrecoverable error 'The SAMLRequest is expected to be signed but it was not'
    And the logs are:
      | level    | message                                                                                     | sari |
      | notice   | Received sso request                                                                        |      |
      | info     | Processing AuthnRequest                                                                     |      |
      | critical | Could not process Request, error: "The SAMLRequest is expected to be signed but it was not" |      |

  Scenario: When a service provider sends an AuthnRequest with incorrect signature the request should be denied
    Given a normal SAML 2.0 AuthnRequest
    When the service provider send an invalided signed AuthnRequest with HTTP-Redirect binding
    Then the identity provider response should be an unrecoverable error 'The SAMLRequest has been signed, but the signature format is not supported'
    And the logs are:
      | level    | message                                                                                                        | sari |
      | notice   | Received sso request                                                                                           |      |
      | info     | Processing AuthnRequest                                                                                        |      |
      | debug    | Extracting public keys for ServiceProvider "https://service_provider/saml/metadata"                            |      |
      | debug    | Found "1" keys, filtering the keys to get X509 keys                                                            |      |
      | debug    | Found "1" X509 keys, attempting to use each for signature verification                                         |      |
      | debug    | /Attempting to verify signature with certificate.*/                                                            |      |
      | debug    | Signature NOT VERIFIED                                                                                         |      |
      | debug    | Signature could not be verified with any of the found X509 keys.                                               |      |
      | critical | Could not process Request, error: "The SAMLRequest has been signed, but the signature format is not supported" |      |

  Scenario: When an user request the sso endpoint without AuthnRequest the request should be denied
    When an user request identity provider sso endpoint
    Then the identity provider response should be an unrecoverable error 'Could not receive AuthnRequest from HTTP Request: expected query parameters, none found'
    And the logs are:
      | level    | message                                                                                                                     | sari |
      | notice   | Received sso request                                                                                                        |      |
      | info     | Processing AuthnRequest                                                                                                     |      |
      | critical | Could not process Request, error: "Could not receive AuthnRequest from HTTP Request: expected query parameters, none found" |      |

  Scenario: When an user request the sso return endpoint without being registered the user should be redirected to the application registration endpoint
    Given a normal SAML 2.0 AuthnRequest
    And AuthnRequest is signed with sha256
    And the service provider send the AuthnRequest with HTTP-Redirect binding
    And I clear the logs

    When the user is redirected to the identity provider sso return endpoint without registration

    Then the response should be an redirect the application registration endpoint

    And the logs are:
      | level   | message                                                                                                                        | sari    |
      | notice  | Received sso return request                                                                                                    | present |
      | warning | User was not registered by the application, redirect user back the registration route "https://identity_provider/registration" | present |

  Scenario: When an AuthnRequest is requested twice the previous state should be invalidated
    Given a normal SAML 2.0 AuthnRequest
    And AuthnRequest is signed with sha256
    And the service provider send the AuthnRequest with HTTP-Redirect binding
    And the identity provider register the user with an unique identifier token

    When the service provider send the AuthnRequest with HTTP-Redirect binding
    Then there should not be an unique identifier token assigned

    And the logs are:
      | level   | message                                                                                                                       | sari    |

      | notice  | Received sso request                                                                                                          |         |
      | info    | Processing AuthnRequest                                                                                                       |         |
      | debug   | Extracting public keys for ServiceProvider "https://service_provider/saml/metadata"                                           |         |
      | debug   | Found "1" keys, filtering the keys to get X509 keys                                                                           |         |
      | debug   | Found "1" X509 keys, attempting to use each for signature verification                                                        |         |
      | debug   | /Attempting to verify signature with certificate.*/                                                                           |         |
      | debug   | Signature VERIFIED                                                                                                            |         |
      | notice  | /AuthnRequest processing complete, received AuthnRequest from "https:\/\/service_provider\/saml\/metadata", request ID: ".+"/ |         |
      | info    | AuthnRequest stored in state                                                                                                  | present |
      | notice  | Redirect user to the application registration route https://identity_provider/registration                                    | present |

      | notice  | Application sets the subject nameID to unique-identifier-token                                                               | present |
      | notice  | Created redirect response for sso return endpoint "https://identity_provider/saml/sso_return"                                 | present |

      | notice  | Received sso request                                                                                                          | present |
      | warning | There is already state present, clear previous state                                                                          | present |
      | info    | Processing AuthnRequest                                                                                                       |         |
      | debug   | Extracting public keys for ServiceProvider "https://service_provider/saml/metadata"                                           |         |
      | debug   | Found "1" keys, filtering the keys to get X509 keys                                                                           |         |
      | debug   | Found "1" X509 keys, attempting to use each for signature verification                                                        |         |
      | debug   | /Attempting to verify signature with certificate.*/                                                                           |         |
      | debug   | Signature VERIFIED                                                                                                            |         |
      | notice  | /AuthnRequest processing complete, received AuthnRequest from "https:\/\/service_provider\/saml\/metadata", request ID: ".+"/ |         |
      | info    | AuthnRequest stored in state                                                                                                  | present |
      | notice  | Redirect user to the application registration route https://identity_provider/registration                                    | present |
