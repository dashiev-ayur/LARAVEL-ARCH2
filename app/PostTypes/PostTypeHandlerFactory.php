<?php

namespace App\PostTypes;

use App\Enums\PostType;
use App\PostTypes\Contracts\PostTypeHandlerInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Подставляет handler по `PostType` (карта в `config/post_types.php`).
 */
final class PostTypeHandlerFactory
{
    /**
     * @param  array<string, class-string<PostTypeHandlerInterface>>  $handlers
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $handlers,
    ) {
        foreach (PostType::cases() as $case) {
            $class = $this->handlers[$case->value] ?? null;
            if (! is_string($class) || ! is_subclass_of($class, PostTypeHandlerInterface::class)) {
                throw new InvalidArgumentException(
                    "config post_types.handlers[{$case->value}] должен ссылаться на PostTypeHandlerInterface.",
                );
            }
        }
    }

    public function make(PostType $type): PostTypeHandlerInterface
    {
        $class = $this->handlers[$type->value] ?? null;

        if (! is_string($class) || ! is_subclass_of($class, PostTypeHandlerInterface::class)) {
            throw new InvalidArgumentException("Нет валидного handler для PostType: {$type->value}.");
        }

        return $this->container->make($class);
    }
}
