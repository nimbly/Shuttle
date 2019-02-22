<?php declare(strict_types=1);

namespace Shuttle\Body;

interface BodyInterface
{
    /**
     * Get the body's Content-Type header value.
     *
     * @return string
     */
    public function getContentType(): string;
}