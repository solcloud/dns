<?php

declare(strict_types=1);

interface IResolve
{
    /**
     * @throws ResolverNotAvailable
     */
    public function resolve(string $domain, int $type): ?string;
}
