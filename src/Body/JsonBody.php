<?php declare(strict_types=1);

namespace Shuttle\Body;

/**
 * @package Shuttle\Body
 *
 * Formats an associative array as a JSON encoded string.
 *
 * Sets the Content-Type as "application/json" by default.
 */
class JsonBody extends BufferBody
{
    /**
     * @inheritDoc
     */
    protected $contentType = "application/json";

    /**
     * JsonBody constructor.
     *
     * @param array $data
     * @param string|null $contentType
     */
    public function __construct(array $data, string $contentType = null)
    {
        $this->buffer = (string) \json_encode($data);

        if( $contentType ){
            $this->contentType = $contentType;
        }
    }
}