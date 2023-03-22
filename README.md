# Giltza Provider for OAuth 2.0 Client

This package provides Giltza OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require ikerib/giltza-oauth2
```

KnpUOAuth2ClientBundle-rekin batera erabiltzeko.

```
https://github.com/knpuniversity/oauth2-client-bundle
```

Aurrena IZFE-ri zure aplikazioak garatu ahal izateko eskaera egin behar zaio erabiltzaile eta pasahitza lor ditzazun.

Behin edukita, KnpUOAuth2ClientBundle instalatu eta liburutegi hau.

Symfony-ren CustomAuthenticator bat sortu eta bertan authenticate funtzioa:

```php
public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('giltza');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                $user = $client->fetchUserFromToken($accessToken);
                // 1) have they logged in with Facebook before? Easy!
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['NA' => $user->getId()]);

                if ($user) {                    
                    return $user;
                }

                throw new UserNotFoundException();
            })
        );
    }
```

KnpUOAuth2ClientBundle-ren konfigurazio fitxategia horrela izan behar du:
```yml
knpu_oauth2_client:
    clients:
        # configure your clients as described here: https://github.com/knpuniversity/oauth2-client-bundle#configuration
        giltza:
            type: generic
            provider_class: Giltza\OAuth2\Client\Provider\OauthGiltzaProvider
            client_id: "%env(CLIENT_ID)%"
            client_secret: "%env(CLIENT_SECRET)%"
            redirect_route: oauth_check
            redirect_params: { }
            use_state: true
```

Azkenik controller-ean deia egin:

```php
    #[Route(path: '/login/giltza/connect', name: 'oauth_connect')]
    public function connect(ClientRegistry $clientRegistry): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return $clientRegistry->getClient('giltza')->redirect(['urn:izenpe:identity:global urn:izenpe:fea:properties urn:safelayer:eidas:authn_details']);
    }

    #[Route(path: '/login/giltza/connect/check', name: 'oauth_check')]
    public function check(Request $request, ClientRegistry $clientRegistry): void
    {
    }

```

Security.yml horrela dago:

```yaml
    ...
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            pattern: ^/
            lazy: true
            provider: app_user_provider
            custom_authenticator:
                - App\Security\OauthAuthenticator
            entry_point: App\Security\OauthAuthenticator
            logout:
                path: app_logout
                target: /
                invalidate_session: true

    # Easy way to control access for large sections of your site
    # Note: Only the *first* access control that matches will be used
    access_control:
        - { path: ^/admin/, roles: ROLE_ADMIN }
```