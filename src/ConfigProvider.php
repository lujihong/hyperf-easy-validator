<?php

declare(strict_types=1);

namespace Hyperf\EasyValidator;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                BeforeServerListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for apidog.',
                    'source' => __DIR__ . '/../publish/easy-validator.php',
                    'destination' => BASE_PATH . '/config/autoload/easy-validator.php',
                ],
            ],
        ];
    }
}
