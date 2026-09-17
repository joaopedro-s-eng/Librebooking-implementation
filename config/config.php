<?php

return [
    'settings' => [
        'authentication' => [
            'keycloak.login.enabled' => true,
            'keycloak.url' => 'http://localhost:8082',
            'keycloak.realm' => 'Teste_Librebooking',
            'keycloak.client.id' => 'librebooking',
            'keycloak.client.secret' => 'z8s0KmrUXToV4kI5WSrqxnKeXA75I0kHog7UUPljbH3ymLtgUSFpz461GWCkht6qfu3TeF0IG609SHTH9wJ4Js',
            'keycloak.client.uri' => '/Web/keycloak-auth.php',
        ],
    ],
];


// Ativar o plugin de autenticação OpenID / SAML / Externa
$conf['settings']['plugins']['Authentication'] = 'OIDC'; // Ou 'OAuth2' dependendo da versão do LibreBooking

// Configurações do Provedor OIDC (Keycloak)
$conf['settings']['oidc']['provider_url'] = 'http://localhost:8082/realms/librebooking';
$conf['settings']['oidc']['client_id'] = 'librebooking';
$conf['settings']['oidc']['client_secret'] = 'SEU_CLIENT_SECRET_COPIADO_DO_KEYCLOAK';
$conf['settings']['oidc']['redirect_uri'] = 'http://localhost:8081/index.php';

// Mapeamento de atributos do usuário
$conf['settings']['oidc']['user_identifier'] = 'preferred_username';
$conf['settings']['oidc']['email_attribute'] = 'email';
$conf['settings']['oidc']['firstname_attribute'] = 'given_name';
$conf['settings']['oidc']['lastname_attribute'] = 'family_name';

// Se estiver usando o config.php:
$conf['settings']['authentication']['oauth2.url.authorize'] = 'http://localhost:8082/realms/Teste_Librebooking/protocol/openid-connect/auth';
$conf['settings']['authentication']['oauth2.url.token'] = 'http://host.docker.internal:8082/realms/Teste_Librebooking/protocol/openid-connect/token';
$conf['settings']['authentication']['oauth2.url.userinfo'] = 'http://host.docker.internal:8082/realms/Teste_Librebooking/protocol/openid-connect/userinfo';

// Opcional: permitir criação automática do usuário no LibreBooking no primeiro login
$conf['settings']['oidc']['auto_register'] = true;
