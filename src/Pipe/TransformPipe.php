<?php

namespace Lneicelis\Transformer\Pipe;

use Lneicelis\Transformer\Contract\CanPipe;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use Lneicelis\Transformer\TransformerRepository;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Path;

class TransformPipe implements CanPipe
{
    /** @var TransformerRepository */
    private $transformerRepository;

    /**
     * @param TransformerRepository $transformerRepository
     */
    public function __construct(TransformerRepository $transformerRepository)
    {
        $this->transformerRepository = $transformerRepository;
    }

    /**
     * @param object $source
     * @param Context $context
     * @param $data
     * @param Path $path
     * @return mixed
     * @throws TransformerNotFoundException
     */
    public function pipe($source, Context $context, Path $path, $data)
    {
        $transformer = $this->transformerRepository->getTransformer($source);

        return $transformer->transform($source);
    }
}
