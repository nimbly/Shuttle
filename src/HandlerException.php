<?php

namespace Nimbly\Shuttle;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

class HandlerException extends Exception implements ClientExceptionInterface
{}