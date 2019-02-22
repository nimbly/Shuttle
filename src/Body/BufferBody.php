<?php declare(strict_types=1);

namespace Shuttle\Body;

use Shuttle\Stream\BufferStream;

/**
 * @package Shuttle\Body
 * 
 * A generic string buffer body with no formatting/serialization or
 * default Content-Type.
 * 
 */
class BufferBody extends BufferStream implements BodyInterface
{
    /**
     * Content-Type header data.
     * 
     * E.g. application/json
     *
     * @var string
     */
    protected $contentType;

    /**
     * BufferBody constructor.
     *
     * @param string $data
     * @param string|null $contentType
     */
    public function __construct(string $data, string $contentType = null)
    {
        $this->buffer = $data;

        if( $contentType ){
            $this->contentType = $contentType;
        }
    }

    /**
     * Get the Content-Type header data for this body.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }
}