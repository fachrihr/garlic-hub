<?php
/*
 garlic-hub: Digital Signage Management Platform

 Copyright (C) 2025 Nikolaos Sagiadinos <garlic@saghiadinos.de>
 This file is part of the garlic-hub source code

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License, version 3,
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
declare(strict_types=1);

namespace App\Framework\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * CorsMiddleware adds CORS headers to responses for allowed origins.
 *
 * Pass an array of allowed origin strings to the constructor.
 * Use '*' to allow all origins.
 *
 * Example usage in route.php:
 *   $app->get('/smil-index', ...)
 *       ->add(new CorsMiddleware(['https://example.com', 'https://other.com']));
 */
class CorsMiddleware implements MiddlewareInterface
{
	/** @var string[] */
	private array $allowedOrigins;

	/**
	 * @param string[] $allowedOrigins  List of allowed origins, e.g. ['https://example.com'].
	 *                                  Pass ['*'] to allow every origin.
	 */
	public function __construct(array $allowedOrigins = [])
	{
		$this->allowedOrigins = $allowedOrigins;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$origin = $request->getHeaderLine('Origin');

		// Handle preflight OPTIONS request
		if ($request->getMethod() === 'OPTIONS')
		{
			$response = new Response();
			return $this->addCorsHeaders($response, $origin);
		}

		$response = $handler->handle($request);
		return $this->addCorsHeaders($response, $origin);
	}

	private function addCorsHeaders(ResponseInterface $response, string $origin): ResponseInterface
	{
		if (empty($origin))
			return $response;

		$allowedOrigin = $this->resolveAllowedOrigin($origin);
		if ($allowedOrigin === null)
			return $response;

		return $response
			->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
			->withHeader('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS')
			->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Signage-Agent')
			->withHeader('Access-Control-Max-Age', '86400');
	}

	private function resolveAllowedOrigin(string $origin): ?string
	{
		if (in_array('*', $this->allowedOrigins, true))
			return '*';

		if (in_array($origin, $this->allowedOrigins, true))
			return $origin;

		return null;
	}
}
