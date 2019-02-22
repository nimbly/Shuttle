<?php declare(strict_types=1);

namespace Shuttle\Body;

/**
 * @package Shuttle\Body
 * 
 * Format a key => value pair array into a Form Urlencoded string.
 * 
 * Sets Content-Type as "application/x-www-form-urlencoded".
 * 
 */
class FormBody extends BufferBody implements BodyInterface
{
    /**
     * @inheritDoc
     */
    protected $contentType = "application/x-www-form-urlencoded";

    /**
     * FormBody constructor.
     *
     * @param array $data
     * @param string|null $contentType
     */
    public function __construct(array $data, string $contentType = null)
    {
        $this->buffer = http_build_query($data, "n", "&", PHP_QUERY_RFC1738);

        if( $contentType ){
            $this->contentType = $contentType;
        }
    }
}