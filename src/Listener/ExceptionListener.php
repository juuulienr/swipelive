<?php

namespace App\Listener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
  public function onKernelException(ExceptionEvent $event)
  {
    $request = $event->getRequest();
    $exception = $event->getThrowable();

    if ($this->isApiRequest($request)) {
      $response = new JsonResponse([
        'error' => true,
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

  private function isApiRequest($request): bool
  {
    return strpos($request->getPathInfo(), '/api') === 0 || strpos($request->getPathInfo(), '/user/api') === 0;
  }
}
