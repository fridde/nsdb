<?php

namespace App\Security;

use League\OAuth2\Client\Provider\Google;
use App\Entity\User;
use App\Utils\RepoContainer;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends AbstractAuthenticator
{

    private array $userData;

    private array $googleKeyTranslator = [
        'Mail' => 'email',
        'FirstName' => 'given_name',
        'LastName' => 'family_name'
    ];


    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RepoContainer          $rc,
        private readonly AuthenticationUtils    $auth,
        private readonly ClientRegistry         $clientRegistry,
        private readonly UrlGeneratorInterface  $router,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        /** @var Google $provider */
        $provider = $client->getOAuth2Provider();

        try {
            $token = $client->getAccessToken();
            $provider->getResourceOwner($token);
            $googleUser= $provider->getResourceOwner($token);
            /** @var GoogleUser $googleUser */
            $this->setRelevantUserData($googleUser);
        } catch (IdentityProviderException $e) {
            throw new AuthenticationException($e->getMessage());
        }

        $badge = new UserBadge($this->userData['Mail'], fn ($m) => $this->findSingleUser($m));
        try {
            $user = $badge->getUser();
        } catch (AuthenticationServiceException $e) {
            throw new UserNotFoundException($e->getMessage());
        }
        /** @var User $user  */
        if ($user->isPending()) {
            throw new DisabledException();
        }

        return new SelfValidatingPassport($badge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if (!($user instanceof User)) {
            throw new AuthenticationException('The token could not be converted to a user. This is pretty unexpected!');
        }
        $this->addUserDataIfAvailable($user);

        $cookie = $this->auth->createCookieWithAuthKey($user);

        $response = new RedirectResponse($request->getSession()->get('request_url'));
        $response->headers->setCookie($cookie);

        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($exception instanceof UserNotFoundException) {
            $request->getSession()->set('user_data', json_encode($this->userData));
            $url = $this->router->generate('register');

            return new RedirectResponse($url, Response::HTTP_TEMPORARY_REDIRECT);
        }
        if ($exception instanceof DisabledException) {
            $url = $this->router->generate('register_pending');
            return new RedirectResponse($url, Response::HTTP_TEMPORARY_REDIRECT);
        }

        // This is some weird Authentication issue
        // TODO: throw a better response
        return new Response($exception->getMessage());
    }

    private function addUserDataIfAvailable(User $user): void
    {
        foreach ($this->userData as $propertyName => $propertyValue) {
            $missing = match ($propertyName) {
                'Mobil' => !$user->hasMobil(),
                'FirstName' => empty($user->getFirstName()),
                'LastName' => empty($user->getLastName()),
                default => false
            };
            if ($missing && $propertyValue !== null) {
                $user->{'set' . $propertyName}($propertyValue);
            }
        }

        $this->em->persist($user);
        $this->em->flush();
    }

    private function setRelevantUserData(GoogleUser $googleUser): void
    {
        $userValues = $googleUser->toArray();

        foreach ($this->googleKeyTranslator as $userProperty => $googleKey) {
            $this->userData[$userProperty] = $userValues[$googleKey] ?? null;
        }
    }

    private function findSingleUser(string $mail): ?User
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->rc->getUserRepo()->findOneBy(['Mail' => $mail]);
    }
}
