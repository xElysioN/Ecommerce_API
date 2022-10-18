<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationDecorator implements OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['Credentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'example' => 'admin@admin.fr',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'admin',
                ],
            ],
        ]);

        $pathItem = new PathItem(
            ref: 'Authentication',
            summary: 'Login endpoint.',
            description: 'Login endpoint',
            post: new Operation(
                operationId: 'postCredentialsItem',
                tags: ['Authentication'],
                responses: [
                    Response::HTTP_OK => [
                    ],
                    Response::HTTP_BAD_REQUEST => [
                        'description' => 'Invalid input',
                    ],
                    Response::HTTP_UNAUTHORIZED => [
                        'description' => 'Email or password incorrect',
                    ],
                ],
                summary: 'Login endpoint.',
                description: 'Login endpoint.',
                requestBody: new RequestBody(
                    description: 'Credentials',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Credentials',
                            ],
                        ],
                    ]),
                    required: true
                ),
            ),
        );

        $openApi->getPaths()->addPath('/login', $pathItem);

        return $openApi;
    }
}
