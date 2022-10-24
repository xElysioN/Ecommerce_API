<?php

namespace App\Tests\Functional;

use App\DataFixtures\Processor\UserProcessor;
use App\Entity\User;
use App\Tests\ApiTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group user
 * @group debug
 */
class UserTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private const VALID_PASSWORD = 'AC0rr3ctP4ssw0rd';

    /**
     * As an anonymous or user, I shouldn't see the collection of users.
     */
    public function testGetCollectionAsAnonymous(): void
    {
        static::createClient()->request('GET', '/users');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        static::createAuthenticatedClient(UserProcessor::USER_TOKEN)->request('GET', '/users');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * As an admin, I should see a collection of users.
     */
    public function testGetCollectionAsAdmin(): void
    {
        static::createAuthenticatedClient(UserProcessor::ADMIN_TOKEN)->request('GET', '/users');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(User::class);
    }

    /**
     * As an anonymous, I shouldn't see user data.
     */
    public function testGetItemAsAnonymous(): void
    {
        static::createClient()->request('GET', '/users/1');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * As a user, I should see my personal information but not other people's information.
     */
    public function testGetItemAsUser(): void
    {
        $client = static::createAuthenticatedClient(UserProcessor::USER_TOKEN);
        $client->request('GET', '/users/1');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/users/2');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(User::class);
    }

    /**
     * As an admin, I should see personal information for everyone.
     */
    public function testGetItemAsAdmin(): void
    {
        $client = static::createAuthenticatedClient(UserProcessor::ADMIN_TOKEN);
        $client->request('GET', '/users/1');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(User::class);

        $client->request('GET', '/users/2');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(User::class);
    }

    /**
     * As a user, I shouldn't be able to register a new account.
     */
    public function testPostCollectionAsAUser(): void
    {
        $client = static::createAuthenticatedClient(UserProcessor::USER_TOKEN);
        $client->request('POST', '/users', [
            'json' => [
                'email' => 'new@user.fr',
                'password' => self::VALID_PASSWORD,
                'firstname' => 'Firstname',
                'lastname' => 'Lastname'
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * As an anonymous, I should be able to register myself with correct credentials.
     */
    public function testPostCollectionAsAnAnonymous(): void
    {
        $client = static::createClient();
        $client->request('POST', '/users', [
            'json' => [
                'email' => 'new@user.fr',
                'password' => self::VALID_PASSWORD,
                'firstname' => 'Firstname',
                'lastname' => 'Lastname'
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(User::class);
    }
}
