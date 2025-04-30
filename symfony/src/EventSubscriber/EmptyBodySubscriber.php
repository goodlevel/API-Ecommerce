<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class EmptyBodySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        
        if ($request->getPathInfo() === '/api/token' && $request->isMethod('POST')) {
            $data = json_decode($request->getContent());

            if (empty($request->getContent())) {
                $event->setResponse(new JsonResponse([
                    'error' => 'Empty request body',
                    'message' => 'Request body should be a JSON object'
                ], Response::HTTP_BAD_REQUEST));
            }
            
        }


    }
}