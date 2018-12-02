<?php

namespace Lneicelis\Transformer\Pipe;

use Lneicelis\Transformer\Contract\CanGuard;
use Lneicelis\Transformer\Contract\CanPipe;
use Lneicelis\Transformer\Contract\HasAccessConfig;
use Lneicelis\Transformer\Exception\AccessDeniedException;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use Lneicelis\Transformer\TransformerRegistry;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Path;

class AccessControlPipe implements CanPipe
{
    /** @var TransformerRegistry */
    private $transformerRepository;

    /** @var CanGuard[] */
    private $guardByName = [];

    /**
     * @param TransformerRegistry $transformerRepository
     * @param array $guards
     */
    public function __construct(TransformerRegistry $transformerRepository, array $guards = [])
    {
        $this->transformerRepository = $transformerRepository;

        foreach ($guards as $guard) {
            $this->addGuard($guard);
        }
    }

    public function addGuard(CanGuard $guard): void
    {
        $this->guardByName[$guard->getName()] = $guard;
    }

    /**
     * @param object $resource
     * @param Context $context
     * @param $data
     * @param Path $path
     * @return mixed
     * @throws TransformerNotFoundException
     * @throws AccessDeniedException
     */
    public function pipe($resource, Context $context, Path $path, $data)
    {
        $this->assertCanAccess($resource, $context, $path);

        return $data;
    }

    /**
     * @param $resource
     * @param Context $context
     * @param Path $path
     * @throws TransformerNotFoundException
     * @throws AccessDeniedException
     */
    private function assertCanAccess($resource, Context $context, Path $path): void {
        $transformer = $this->transformerRepository->getTransformer($resource);

        if (! $transformer instanceof HasAccessConfig) {
            return;
        }

        $optionalProps = $this->getCurrentPathSchemaProperties($path, $context->getSchema());
        $accessConfig = $transformer->getAccessConfig();
        $defaultAccessGroups = $accessConfig->getDefaultGroups();
        $accessGroupsByProperty = $accessConfig->getGroupsByProperty();

        $optionalPropsAcl = $this->pick($accessGroupsByProperty, $optionalProps);
        $allAcl = array_unique(array_merge($defaultAccessGroups, ...array_values($optionalPropsAcl)));

        foreach ($allAcl as $acl) {
            $guard = $this->guardByName[$acl];

            if (! $guard->canAccess($resource, $context)) {
                throw new AccessDeniedException('test');
            }
        }
    }

    private function pick(array $array, array $keys)
    {
        return array_filter($array, function (string $key) use ($keys): bool {
            return in_array($key, $keys, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    private function getCurrentPathSchemaProperties(Path $path, array $schema)
    {
        $schema = $this->getPathSchema($path, $schema);

        return $this->getProperties($schema);
    }

    private function getProperties(array $schema): array
    {
        return array_map(function ($value, $key) {
            return is_string($value) ? $value : $key;
        }, $schema, array_keys($schema));
    }

    private function getPathSchema(Path $path, array $schema): array
    {
        $segments = array_filter($path->getSegments(), 'is_string');

        return $this->arrayGet($schema, $segments, []);
    }

    private function arrayGet(array $array, array $path, $default): array
    {
        foreach ($path as $key) {
            if (! array_key_exists($key, $array)) {
                return $default;
            }

            $array = $array[$key];
        }

        return $array;
    }
}
