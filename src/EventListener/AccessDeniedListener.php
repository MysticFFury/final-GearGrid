<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccessDeniedListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Check if it's an AccessDeniedException
        if ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
            // Add flash message if session is available
            $request = $this->requestStack->getCurrentRequest();
            if ($request && $request->hasSession()) {
                $request->getSession()->getFlashBag()->add('error', 'Access denied. Please log in to continue.');
            }
            
            // Redirect to login page
            $response = new RedirectResponse(
                $this->urlGenerator->generate('app_login')
            );
            
            $event->setResponse($response);
        }
    }
}
