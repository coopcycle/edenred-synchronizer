<?php

namespace App\Security;

use App\Entity\ApiClient;
use App\Entity\User;
use App\Security\Authentication\Token\ApiKeyToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PreAuthenticatedUserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    private $logger;
    private $entityManager;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    public function supports(Request $request): ?bool
    {
        return $request->query->has('api_key');
    }

    public function authenticate(Request $request): Passport
    {
        try {
            return $this->authenticateWithApiKey($request);
        } catch (AuthenticationException $e) {
            $this->logAuthenticationException($request, 'ApiKey', $e);
            throw $e;
        }
    }

    private function authenticateWithApiKey(Request $request): Passport
    {
        $token = new ApiKeyToken(['ROLE_API_KEY'], $request->query->get('api_key'));

        $apiClient = $this->entityManager
            ->getRepository(ApiClient::class)
            ->findOneBy([
                'apiKey' => $token->getCredentials(),
            ]);

        if (null === $apiClient) {
            throw new AuthenticationException(sprintf('API Key "%s" does not exist', $token->getCredentials()));
        }

        $user = new User();
        $user->setUsername($token->getCredentials());

        $token->setUser($user);

        $passport = new SelfValidatingPassport(new UserBadge($token->getCredentials()), [
            new PreAuthenticatedUserBadge()
        ]);
        $passport->setAttribute('apikey_token', $token);

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $apiKeyToken = $passport->getAttribute('apikey_token');

        if ($apiKeyToken) {

            return $apiKeyToken;
        }

        $jwtToken = $passport->getAttribute('jwt_token');

        if ($jwtToken) {

            return $jwtToken;
        }

        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }

    private function logAuthenticationException(Request $request, string $authenticator, AuthenticationException $e)
    {
        $this->logger->info('BearerTokenAuthenticator; '.$request->getRequestUri().' failed to authenticate with '.$authenticator.': '.$e->getMessage(). ' '.$e->getMessageKey());
    }
}
