<?php

namespace Lneicelis\Transformer\Pipe;

use Lneicelis\Transformer\Contract\CanPipe;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use Lneicelis\Transformer\TransformerRegistry;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Path;

class TransformPipe implements CanPipe
{
    /** @var TransformerRegistry */
    private $transformerRepository;

    /**
     * @param TransformerRegistry $transformerRepository
     */
    public function __construct(TransformerRegistry $transformerRepository)
    {
        $this->transformerRepository = $transformerRepository;
    }

    /**
     * @param object $resource
     * @param Context $context
     * @param $data
     * @param Path $path
     * @return mixed
     * @throws TransformerNotFoundException
     */
    public function pipe($resource, Context $context, Path $path, $data)
    {
        $transformer = $this->transformerRepository->getTransformer($resource);

        return $transformer->transform($resource);
    }
}
