<?php

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
    /**
     * Smoke Test.
     *
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful($url): void
    {
        $client = self::createClient();
        $client->request('GET', $url);

        self::assertNotSame(500, $client->getResponse()->getStatusCode());
    }

    public function urlProvider(): \Generator
    {
        yield ['/'];
    }
}
