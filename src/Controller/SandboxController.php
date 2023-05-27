<?php


namespace App\Controller;


use App\Entity\User;
use App\Message\MessageBuilder;
use App\Message\MessageRecorder;
use App\Security\Role;
use App\Utils\Calendar;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SandboxController extends AbstractController
{

    public function __construct(
        LoggerInterface         $logger,
        private Filesystem      $fs,
        private KernelInterface $kernel
    )
    {
        $GLOBALS['logger'] = $logger;
    }


    #[Route(
        'sandbox/calendar'
    )]
    public function testCalendar(Calendar $calendar): Response
    {
        $calendar->listAllEvents();
        return $this->render('base.html.twig');
    }

    #[Route(
        'sandbox/user'
    )]
    public function testUser(EntityManagerInterface $em): Response
    {
        $user = $em->find(User::class, 1);
        $role_status = $user?->hasAtLeastRole(Role::ACTIVE_USER);

        return $this->render('base.html.twig');
    }

    #[Route('sandbox/mail')]
    public function saveSentMail(MessageRecorder $mr): Response
    {
        $path = $this->kernel->getProjectDir() . '/tests/sent_mails.json';
        $text = ($this->fs->exists($path) ? file_get_contents($path) : '{}');
        $mails = json_decode($text, true);
        $validMails = $mails['valid'];

        array_walk($validMails, fn(array &$m) => $m['date'] = '2019-09-02');
        $mails['valid'] = $validMails;

        $mr->saveSentMailRecords($mails['valid']);


        return new Response('');

    }

    #[Route('sandbox/bus')]
    public function parseBusConfirmationPage(HttpClientInterface $httpClient): Response
    {
    }
}