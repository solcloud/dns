<?php

declare(strict_types=1);

interface IResolve
{
    public function resolve(string $domain, int $type): ?string;
}
