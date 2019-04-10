<?php

namespace Shuttle\Body;

interface PartInterface
{
    /**
     * Get the content disposition for multi part.
     *
     * @param string $boundary
     * @param string $name
     * @return string
     */
    public function getMultiPart(string $boundary, string $name): string;
}