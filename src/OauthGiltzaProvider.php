<?php

namespace Giltza\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class OauthGiltzaProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;
    /**
     * Name of the resource owner identifier field that is
     * present in the access token response (if applicable)
     */
    public const ACCESS_TOKEN_RESOURCE_OWNER_ID = null;

    public string $auth_authServer = 'https://eidas.izenpe.com/trustedx-authserver/oauth/izenpe';
    public string $auth_tokenServer = 'https://eidas.izenpe.com/trustedx-authserver/oauth/izenpe/token';
    public string $auth_apiUri = 'https://eidas.izenpe.com/trustedx-resources/openid/v1/users/me';

    protected function getAuthorizationParameters(array $options): array
    {
        if (empty($options['state'])) {
            $options['state'] = $this->getRandomState();
        }

        if (empty($options['scope'])) {
            $options['scope'] = $this->getDefaultScopes();
        }

        $options += [
            'response_type'   => 'code',
            'approval_prompt' => 'auto'
        ];

        if (is_array($options['scope'])) {
            $separator = $this->getScopeSeparator();
            $options['scope'] = implode($separator, $options['scope']);
        }

        // Store the state as it may need to be accessed later on.
        $this->state = $options['state'];

        // Business code layer might set a different redirect_uri parameter
        // depending on the context, leave it as-is
        if (!isset($options['redirect_uri'])) {
            $options['redirect_uri'] = $this->redirectUri;
        }

        $options['client_id'] = $this->clientId;

        // Begiratu hemen zein autentikazio sistema nahi diren ezarri
        // https://www.izenpe.eus/contenidos/recurso_tecnico/descargas_giltza_doc/es_def/adjuntos/Giltza_Manual_de_Integracion.pdf
        $options['acr_values'] = "urn:safelayer:tws:policies:authentication:flow:izmobileid:citizen_prof|urn:safelayer:tws:policies:authentication:flow:bakq|urn:safelayer:tws:policies:authentication:flow:cert";

        return $options;
    }

    public function getBaseAuthorizationUrl(): string
    {
        return $this->auth_authServer;
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->auth_tokenServer;
    }

    public function getHeaders($token = null): array
    {
        // We have to use HTTP Basic Auth when requesting an access token
        $headers = [];
        if ( ! $token) {
            $auth = base64_encode(sprintf('%s:%s',$this->clientId, $this->clientSecret));
            $headers["Authorization"] = "Basic $auth";
        }

        return array_merge(parent::getHeaders($token), $headers);
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->auth_apiUri;
    }

    protected function getDefaultScopes(): string
    {
        return 'urn:izenpe:identity:global urn:izenpe:fea:properties urn:safelayer:eidas:authn_details';
    }

    protected function checkResponse(ResponseInterface $response, $data):void
    {
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('Providerrean arazoa');
        }

        if (isset($data['error'])) {
            throw new \RuntimeException('Providerrean arazoa');
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token): GiltzaUser
    {
        return new GiltzaUser($response);
    }
}
