<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Deja pasar solo a usuarios con sesión iniciada; al resto los manda a /login.
 */
final class RequireAuth implements MiddlewareInterface
{
  public function __construct(
    private readonly ResponseFactoryInterface $responseFactory,
  ) {}

  public function process(Request $request, RequestHandler $handler): Response
  {
    if (empty($_SESSION["user_id"])) {
      return redirect($this->responseFactory->createResponse(), "/login");
    }

    return $handler->handle($request);
  }
}