<?php

namespace Nimbly\Shuttle;

use Psr\Http\Message\ResponseInterface;

trait RawResponseTrait
{
	/**
	 * Process and parse a raw string HTTP response.
	 *
	 * @param string $raw_response
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	protected function parseRawResponse(string $raw_response, ResponseInterface $response): ResponseInterface
	{
		$lines = \explode("\n", $raw_response);

		$part = "headers";

		foreach( $lines as $line ){
			if( \trim($line) === "" ){
				$part = "body";
				continue;
			}

			if( $part === "headers" ){
				if( \preg_match("/^HTTP\/([\d\.]+) ([\d]{3})(?: ([\w\h]+))?\R?+$/i", \trim($line), $httpResponse) ){
					$response = $response->withStatus((int) $httpResponse[2], $httpResponse[3] ?? "");
					$response = $response->withProtocolVersion($httpResponse[1]);
				}

				elseif( \preg_match("/^([\w\-]+)\: (\N+)\R?+$/", \trim($line), $httpHeader) ){
					$response = $response->withAddedHeader($httpHeader[1], $httpHeader[2]);
				}
			}
			else {
				$response->getBody()->write($line);
			}
		}

		if( $response->getBody()->isSeekable() ){
			$response->getBody()->rewind();
		}

		return $response;
	}
}