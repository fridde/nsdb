<?php


namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class GoogleAuthController extends AbstractController
{


    #[Route("/connect/google", name:"connect_via_google")]
    public function connectToAzure(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect([], []);
    }

    #[Route("/connect/google/check", name:"connect_google_check")]
    #[Template('base.html.twig')]
    public function checkAzureLogin(): void
    {
    }

}