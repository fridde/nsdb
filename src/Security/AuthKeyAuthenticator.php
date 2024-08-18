<?php

namespace App\Security;

use App\Security\AuthenticationUtils as AuthUtil;
use App\Security\Key\ApiKeyManager;
use App\Security\Key\Key;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthKeyAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private Key $key;

    public function __construct(
        private readonly AuthUtil      $AuthUtil,
        private readonly ApiKeyManager $akm,
        private readonly UrlGeneratorInterface $router
    )
    {
        $this->key = $this->akm->createKeyFromGivenString($this->akm->getKeyCodeFromRequest());
    }

    public function supports(Request $request): ?bool
    {
        if ($request->attributes->get('_route') === 'connect_azure_check') {
            // we don't want to end up in a loop of "bad but existing cookie" ->
            // check microsoft login -> come back and cookie is checked again and rejected
            return false;
        }       

        return in_array($this->key->type, [Key::TYPE_URL, Key::TYPE_COOKIE], true);
    }

    public function authenticate(Request $request): Passport
    {
        if(!$this->akm->isValidKey($this->key)){
            throw new AuthenticationException("The provided authentication key was not valid");
        }

        $user = $this->AuthUtil->getUserFromKey($this->key);
        if($user === null){
            throw new UserNotFoundException("This user does not exist");
        }

        $badge = new UserBadge($user->getUserIdentifier());

        return new SelfValidatingPassport($badge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {       
        if($this->key->isType(Key::TYPE_COOKIE)){
            $redirectResponse = new RedirectResponse($request->getRequestUri());
            $redirectResponse->headers->clearCookie('key');
            
            return $redirectResponse;
        }

        // is url-code
        return new Response('The key provided was not valid');
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $request->getSession()->set('request_url', $request->getUri());
        $url = $this->router->generate('connect_via_azure');

        return new RedirectResponse($url, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
