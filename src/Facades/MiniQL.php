<?php

namespace MiniQL\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array execute(array $payload)
 * @method static \MiniQL\Query\FluentQuery query(string $model)
 *
 * @see \MiniQL\MiniQL
 */
class MiniQL extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'miniql';
    }
}
