<?php


namespace App\Controller;


use App\Security\Key\Key;
use App\Utils\TaskManager;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class CronController extends AbstractController
{
    private string $content = "";


    public function __construct(
        private KernelInterface $kernel,
        private TaskManager $taskManager
    )
    {
    }

    #[Route('/cron/run-post-deploy')]
    #[IsGranted(Key::TYPE_CRON)]
    public function runPostDeploy(): Response
    {
        $commands = [
            ['doctrine:migrations:migrate', '--no-interaction']
        ];

        foreach ($commands as $args) {
            $this->runCommand($args);
        }

        return new Response($this->content);
    }


    private function runCommand(array $args): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput($args);

        $output = new BufferedOutput();
        $application->run($input, $output);

        $this->content .= $output->fetch();
    }

    #[Route('/cron/run-tasks')]
    #[IsGranted(Key::TYPE_CRON)]
    public function runAllTasks(): Response
    {
        foreach ($this->taskManager->getTaskNames() as $taskName) {
            if ($this->taskManager->longEnoughSinceLastExecution($taskName)) {
                try {
                    $this->taskManager->execute($taskName);
                } catch (Exception $e){
                    $this->content .= $e->getMessage();
                }
            }
        }

        return new Response($this->content);
    }

}