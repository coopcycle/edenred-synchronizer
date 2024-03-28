<?php

namespace App\Security;

use App\Security\Authentication\Token\ApiKeyToken;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ApiKeyManager
{
    /**
     * @var TokenExtractorInterface
     */
    private $tokenExtractor;
    private $logger;

    public function __construct(TokenExtractorInterface $tokenExtractor, LoggerInterface $logger)
    {
        $this->tokenExtractor = $tokenExtractor;
        $this->logger = $logger;
    }

    public function supports(Request $request)
    {
        $token = $this->tokenExtractor->extract($request);

        return false !== $token && 0 === strpos($token, 'ak_');
    }

    /**
     * Returns a decoded JWT token extracted from a request.
     *
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        if (false === ($rawToken = $this->tokenExtractor->extract($request))) {
            return;
        }

        return new ApiKeyToken(['ROLE_API_KEY'], $rawToken);
    }
}
