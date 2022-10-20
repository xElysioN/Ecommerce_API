<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group login
 */
class LoginTest extends ApiTestCase
{
    /**
     * As an anonymous, I should be able to authenticate myself.
     */
    public function testSuccessFullLogin(): void
    {
        $response = static::createClient()->request(
            'POST',
            '/login',
            [
                'json' => [
                    'email' => 'admin@admin.fr',
                    'password' => 'admin',
                ],
            ]
        );

        $data = $response->toArray();

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesRegularExpression('/\w{'.User::TOKEN_SIZE_HEX.'}/', $data['access_token']);
        self::assertEquals(User::TOKEN_HEADER_NAME, $data['token_type']);
        self::assertEquals(User::TOKEN_DURATION, $data['expires_in']);
    }

    /**
     * As an anonymous, I shouldn't be able to authenticate if I enter invalid credentials.
     *
     * @dataProvider loginProvider
     */
    public function testInvalidLogin(string $email, string $password): void
    {
        static::createClient()->request(
            'POST',
            '/login',
            [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertJsonContains([
            'type' => 'https://datatracker.ietf.org/doc/html/rfc2616#section-10.4.2',
            'title' => 'An error occurred',
            'detail' => 'Bad credentials.',
            'status' => Response::HTTP_UNAUTHORIZED,
        ]);
    }

    /**
     * As an anonymous, I should see a bad request if I enter incorrect parameters.
     */
    public function testBadRequestLogin(): void
    {
        static::createClient()->request(
            'POST',
            '/login',
            [
                'json' => [
                    'wrong_field' => 'admin@admin.fr',
                    'password' => 'password',
                ],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * As an anonymous, I should see a bad request if I enter an invalid email syntax.
     */
    public function testInvalidEmailSyntaxLogin(): void
    {
        static::createClient()->request(
            'POST',
            '/login',
            [
                'json' => [
                    'email' => 'NOTANEMAIL',
                    'password' => 'password',
                ],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function loginProvider(): \Generator
    {
        yield ['email' => 'admin@admin.fr', 'password' => 'INVALID PASSWORD'];
        yield ['email' => 'unknown@email.fr', 'password' => 'admin'];
    }
}
