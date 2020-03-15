<?php

namespace Shuttle\Body;

use Capsule\Stream\BufferStream;


class MultipartFormBody extends BufferStream implements BodyInterface
{
    /**
     * Multi-part content type, without boundary.
     *
     * @var string
     */
    protected $contentType = "multipart/form-data";

    /**
     * Boundary string.
     *
     * @var string
     */
    protected $boundary;

    /**
     * MultipartFormBody constructor.
     *
     * @param array<string, PartInterface> $parts
     */
    public function __construct(array $parts)
    {
        // Create a random boundary name for each multipart request.
        $this->boundary = \uniqid("Capsule") . "Z";

        /**
         * @var string $name
         * @var PartInterface $part
         */
        foreach( $parts as $name => $part ){

			/** @psalm-suppress DocblockTypeContradiction */
            if( !\is_string($name) ){
                throw new \Exception('Please provide a name for each part of a Multipart request.');
            }

            $this->write(
                $part->getMultiPart($this->boundary, $name)
            );
        }

        $this->write("\r\n--{$this->boundary}--\r\n");
    }

    /**
     * Get the boundary string.
     *
     * @return string
     */
    public function getBoundary(): string
    {
        return $this->boundary;
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): string
    {
        return "{$this->contentType};boundary={$this->boundary}";
    }
}