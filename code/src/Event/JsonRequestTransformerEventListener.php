<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\{
    Request,
    Response,
    JsonResponse
};

class JsonRequestTransformerEventListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isJsonRequest($request)) {
            return;
        }

        $content = $request->getContent();

        if (empty($content)) {
            return;
        }

        if ($this->transformJsonBody($request)) {
            return;
        }

        $message = 'Unable to parse request.';
        $status = Response::HTTP_BAD_REQUEST;

        if ($this->isJsonRequest($request)) {
            $response = new JsonResponse(['exception' => ['message' => $message, 'code' => $status,],], $status);
        } else {
            $response = new Response($message, $status);
        }

        $event->setResponse($response);
    }

    private function isJsonRequest(Request $request): bool
    {
        return 'json' === $request->getContentType();
    }

    private function transformJsonBody(Request $request): bool
    {
        try {
            /** @var array<string, string|mixed> $data */
            $data = json_decode(
                (string)$request->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

        } catch (\Exception $exception) {
            return false;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if($data !== null){
            $request->request->replace($data);
        }

        return true;
    }
}