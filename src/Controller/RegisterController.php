<?php


namespace App\Controller;

use App\Entity\School;
use App\Message\MessageRecorder;
use App\Repository\SchoolRepository;
use App\Security\AuthenticationUtils;
use App\Security\AuthKeyAuthenticator;
use App\Security\Key\Key;
use App\Utils\RepoContainer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\MakerBundle\Security\Model\Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class RegisterController extends AbstractController
{

    /**
     * 'Mail' => 'mail',
     * 'Mobil' => 'mobile',
     * 'FirstName' => 'givenName',
     * 'LastName' => 'surname'
     * *
     */

    public function __construct(
        private readonly RequestStack  $requestStack,
        private readonly RepoContainer $rc,
        private readonly RouterInterface $router,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly UrlMatcherInterface $matcher,
    )
    {
    }


    #[Route('/register', name: 'register')]
    #[Template('register.html.twig')]
    public function registerForm(): array
    {
        $data['user'] = json_decode($this->requestStack->getSession()->get('user_data'), true);
        $data['schools'] = $this->rc->getSchoolRepo()->getActiveSchools();

        return $data;
    }

    #[Route('/confirm-registration', name: 'confirm_registration')]
    #[Template('confirm-registration.html.twig')]
    public function confirmRegistration(): array
    {
        return [];
    }



    #[Route('/register/pending', name: 'register_pending')]
    #[Template('register_pending.html.twig')]
    public function registerPendingInfo(): array
    {
        $data['user'] = json_decode($this->requestStack->getSession()->get('user_data'), true);
        $data['schools'] = $this->rc->getSchoolRepo()->getActiveSchools();

        return $data;
    }

    #[Route('/enter-mail', name: 'enter_mail')]
    #[Template('enter_your_mail.html.twig')]
    public function enterMail(Request $request): array
    {
        return [
            'login_endpoint' => $this->router->generate('login_link_sent'),
            'redirect' => $request->query->get('redirect'),
        ];
    }

    #[Route('/login-link-sent', name: 'login_link_sent')]
    #[Template('login_link_sent.html.twig')]
    public function sendLoginLink(Request $request): array|RedirectResponse
    {
        $mailAdress = $request->query->get('user');

        $user = $this->rc->getUserRepo()->findOneByMail($mailAdress);
        if($user === null){
            $this->requestStack->getSession()->set('user_data', json_encode(['Mail' => $mailAdress]));
            return $this->redirectToRoute('register');
        }

        $params = [
            'redirect' => $request->query->get('redirect'),
            'key' => $this->authenticationUtils->createKeyStringForUser($user, Key::TYPE_LOGIN)
        ] ;

        $url = $this->router->generate('login', $params, UrlGeneratorInterface::ABSOLUTE_URL);

        $email = new Email()
            ->from('info@sigtunanaturskola.se')
            ->to($user->getMail())
            ->subject('Din inloggning till Naturskolans besöksportal')
            ->html($this->twig->render("mail/login_link.html.twig", ['user' => $user, 'url' => $url]));
        ;


        $this->mailer->send($email);

        return ['user' => $user];

    }



}