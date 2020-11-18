<?php

namespace Shuttle\Handler;

use Capsule\Response;
use Capsule\ResponseStatus;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class CurlMultiHandler extends CurlHandler
{
	/**
	 * cURL multi handler instance.
	 *
	 * @var resource
	 */
	protected $multiHandler;

	public function __construct(array $options = [])
	{
		$this->multiHandler = \curl_multi_init();
		$this->options += $options;
	}

	/**
	 * Process multiple requests at once.
	 *
	 * @param array<RequestInterface> $requests
	 * @return array<ResponseInterface>
	 */
	public function batch(array $requests): array
	{
		/**
		 * @var array<resource>
		 */
		$curl_handlers = [];

		/**
		 * @var array<ResponseInterface>
		 */
		$responses = [];

		foreach( $requests as $i => $request ){
			if( $request instanceof RequestInterface === false ){
				throw new RuntimeException("Request must be an instance of RequestInterface");
			}

			$curl_handlers[$i] = \curl_init();
			$responses[$i] = new Response(ResponseStatus::OK, $this->makeResponseBodyStream());

			\curl_setopt_array(
				$curl_handlers[$i],
				$this->buildCurlRequestOptions($request, $responses[$i])
			);

			\curl_multi_add_handle(
				$this->multiHandler,
				$curl_handlers[$i]
			);
		}

		if( $curl_handlers ){
			do {

				\curl_multi_exec($this->multiHandler, $running);

			} while( $running );

			foreach( $curl_handlers as $curl_handler ){
				\curl_multi_remove_handle($this->multiHandler, $curl_handler);
				\curl_close($curl_handler);
			}

			foreach( $responses as $response ){
				if( $response->getBody()->isSeekable() ){
					$response->getBody()->rewind();
				}
			}
		}

		return $responses;
	}
}