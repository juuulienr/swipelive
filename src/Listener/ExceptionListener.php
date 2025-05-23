<?php

declare(strict_types=1);

namespace App\Listener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
  public function onKernelException(ExceptionEvent $event): void
  {
    $request   = $event->getRequest();
    $exception = $event->getThrowable();

    if ($this->isApiRequest($request)) {
      $response = new JsonResponse([
        'error'   => true,
        'message' => $exception->getMessage(),
      ]);

      if ($exception instanceof HttpExceptionInterface) {
        $response->setStatusCode($exception->getStatusCode());
      } else {
        $response->setStatusCode(500);
      }

      $event->setResponse($response);
    }
  }

  private function isApiRequest(Request $request): bool
  {
    return \str_starts_with($request->getPathInfo(), '/api') || \str_starts_with($request->getPathInfo(), '/user/api');
  }
}
