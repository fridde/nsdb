<?php

namespace App\Security;

use App\Entity\User;
use App\Security\AuthenticationUtils as AuthUtil;
use App\Security\Key\ApiKeyManager;
use App\Security\Key\Key;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
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
        private readonly UrlGeneratorInterface $router,
    )
    {
        $this->key = $this->akm->createKeyFromGivenString($this->akm->getKeyCodeFromRequest());
    }

    public function supports(Request $request): ?bool
    {
        return $this->key->isValidType();
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
        /** @var User $user */
        $user = $token->getUser();
        if($this->key->isType(Key::TYPE_LOGIN)){
            $params = $request->query->all();
            unset($params['key']);

            $newUrl = $request->getPathInfo();
            if (!empty($params)) {
                $newUrl .= '?' . http_build_query($params);
            }

            $response = new RedirectResponse($newUrl);
            $cookie = $this->AuthUtil->createCookieWithAuthKey($user);
            $response->headers->setCookie($cookie);
            return $response;
        }
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {       
        if($this->key->isType(Key::TYPE_COOKIE)){
            $redirectResponse = new RedirectResponse($request->getRequestUri());
            $redirectResponse->headers->clearCookie('key');
            
            return $redirectResponse;
        }
        if($this->key->isType(Key::TYPE_LOGIN)){
            return new RedirectResponse($this->router->generate('outdated login link')); // TODO: create this page
        }

        return new Response('The key provided was not valid');
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $params = array_merge($request->query->all(), ['redirect' => $request->getUri()]) ;

        $url = $this->router->generate('enter_mail', $params);

        return new RedirectResponse($url, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
