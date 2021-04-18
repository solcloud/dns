<?php

declare(strict_types=1);

class FallbackResolver implements IResolve
{
    /**
     * @var IResolve[]
     */
    private array $resolvers;

    /**
     * @param IResolve[] $resolvers
     */
    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    public function addResolver(string $name, IResolve $resolver): void
    {
        $this->resolvers[$name] = $resolver;
    }

    public function resolve(string $domain, int $type): ?string
    {
        if ([] === $this->resolvers) {
            throw new Exception("No resolvers available");
        }

        foreach ($this->resolvers as $name => $resolver) {
            try {
                return $resolver->resolve($domain, $type);
            } catch (ResolverNotAvailable $ex) {
                $this->log("Resolver '{$name}' failed, trying next...");
            }
        }

        if (isset($ex)) {
            $this->log("Every resolver failed, sleeping");
            sleep(4);
        }
        return null;
    }

    protected function log(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}
