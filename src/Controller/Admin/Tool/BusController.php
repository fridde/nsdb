<?php

namespace App\Controller\Admin\Tool;

use App\Entity\Location;
use App\Entity\School;
use App\Utils\ExtendedCollection;
use App\Utils\RepoContainer;
use Carbon\Carbon;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BusController extends AbstractController
{

    private ?Request $request;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RepoContainer       $repo,
        private readonly KernelInterface     $kernel,
        private readonly RequestStack        $requestStack
    )
    {
        $this->request = $this->requestStack->getCurrentRequest();
    }

    #[Route('/admin/confirm-bus-orders', name: 'tools_confirm_bus_orders')]
    #[Template('admin/tools/confirm_bus_orders.html.twig')]
    public function confirmBusOrders(): array
    {
        if ((int)$this->request->get('update') === 1){
            $this->updateBookedTripsFromUrls();
        }

        $settings = $this->getSettings();
        $data = ['locations_and_schools' => $this->getAllLocationsAndSchools()];
        $data['booked_trips'] = $settings['trips'];

        $data['unknowns'] = array_filter(array_unique(array_map(fn(array $trip) => $trip['order']['unknown'] ?? null, $data['booked_trips'])));

        return $data;
    }

    private function updateBookedTripsFromUrls(): void
    {
        $settings = $this->getSettings();
        $trips = [];


        foreach ($settings['locations'] as $date => $urls) {
            if (Carbon::createFromDate($date)->lt(Carbon::now()->subYears(2))) {
                continue;
            }
            foreach ($urls as $url) {

                $response = $this->httpClient->request('GET', $url);
                $content = $response->getContent();

                $crawler = new Crawler($content);
                $crawler->filter('#orders p')
                    ->reduce(function (Crawler $node, $i) {
                    })
                    ->each(function (Crawler $node, $i) use (&$data) {
                        if (str_contains($node->attr('style') ?? '', 'line-through')) {
                            return; // we ignore these
                        }
                        if ($node->matches('.orderrow')) {
                            $trips[$i]['order'] = $this->parseOrderRow($node->html());
                        } else if ($node->matches('.buses')) {
                            $trips[$i - 1]['bus'] = $this->parseBusesRow($node->html());
                        }
                    });
            }
        }

        $settings['trips'] = $trips;
        $this->writeToSettings($settings);
    }

    private function parseOrderRow(string $rowHtml): array
    {
        $parts = explode('<br>', $rowHtml);
        $parts = array_map(fn(string $p) => strip_tags(trim($p)), $parts);

        $return = [];

        foreach ($parts as $part) {
            $return += $this->parseOrderPart($part);
        }

        return $return;
    }

    private function parseOrderPart(string $part): ?array
    {
        if (str_contains($part, 'passenger')) {
            return ['pax' => filter_var($part, FILTER_SANITIZE_NUMBER_INT)];
        }
        if (str_starts_with($part, 'Service starts:')) {
            return ['date' => Carbon::create(trim(str_replace('Service starts:', '', $part)))];
        }
        if (str_starts_with($part, 'From:')) {
            $from = trim(explode(',', str_replace('From:', '', $part))[0]);
            return $this->matchLocation($from);
        }
        if (str_starts_with($part, 'To:')) {
            $to = trim(explode(',', str_replace('To:', '', $part))[0]);
            return $this->matchLocation($to);
        }

        return [];
    }

    private function parseBusesRow(string $busHtml): array
    {
        $parts = explode(',', strip_tags($busHtml));
        $parts = array_map(fn(string $p) => trim($p), $parts);

        $return = [];

        foreach ($parts as $part) {
            $return += $this->parseBusPart($part);
        }

        return $return;
    }

    private function parseBusPart(string $part): ?array
    {
        if (str_starts_with($part, 'Bus')) {
            return ['bus_nr' => filter_var($part, FILTER_SANITIZE_NUMBER_INT)];
        }
        if (str_starts_with($part, 'Driver')) {
            return ['driver' => trim(str_replace('Driver', '', $part))];
        }
        if (str_starts_with($part, 'Telephone')) {
            return ['driver_phone' => filter_var($part, FILTER_SANITIZE_NUMBER_INT)];
        }

        return [];
    }


    private function matchLocation(string $locationString): ?array
    {
        // TODO: use file instead of hard coded array
        $locationArray = [
            'Galaxskolan' => 'school:gala',
            'FLOTTVIK 141' => 'location:2'
        ];

        $found = $locationArray[$locationString] ?? null;
        if ($found === null) {
            return ['unknown' => $locationString];
        }

        [$key, $value] = explode(':', $found);

        return [$key => $value];
    }

    private function getAllLocationsAndSchools(): ExtendedCollection
    {
        $locations = $this->repo->getLocationRepo()->getActiveLocations();
        $schools = $this->repo->getSchoolRepo()->getActiveSchools();

        return $locations->map(fn(Location $l) => ['location:' . $l->getId() => $l->getName()])->attach(
            $schools->map(fn(School $s) => ['school:' . $s->getId() => $s->getName()])
        )->collapse();
    }

    public function writeToSettings(array $data): void
    {
        file_put_contents($this->getSettingsPath(), json_encode($data));
    }

    public function getSettings(): array
    {
        return json_decode(file_get_contents($this->getSettingsPath()), true);
    }

    public function cleanUrl(string $url): string
    {
        $part = parse_url($url)['path'];

        return str_replace('/offer/show/', '', $part);
    }

    public function buildUrl(string $part): string
    {
        return 'https://kund.travellerbuss.se/offer/show/' . $part . '?locale=en_GB';
    }

    private function getSettingsPath(): string
    {
        return $this->kernel->getProjectDir() . '/data/bus_routes.json';
    }

}