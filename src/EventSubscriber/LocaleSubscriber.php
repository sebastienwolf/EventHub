<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Laravel middleware equivalent, acting before and after the controller:
 *  - kernel.request: picks the locale from the Accept-Language header (fr or en);
 *  - kernel.response: tells the client which language was used (Content-Language).
 *
 * Note: FrameworkBundle can do the same natively with the
 * "set_locale_from_accept_language" and "set_content_language_from_locale" options;
 * this subscriber shows how such a cross-cutting concern is written by hand.
 */
final class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire('%kernel.enabled_locales%')]
        private readonly array $enabledLocales,
        #[Autowire('%kernel.default_locale%')]
        private readonly string $defaultLocale,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Must run before Symfony's LocaleListener (16), which syncs the translator locale
            KernelEvents::REQUEST => ['onKernelRequest', 20],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // An explicit "_locale" route parameter or query string wins over the header
        $locale = $request->query->getString('_locale') ?: $request->attributes->getString('_locale');
        if (!\in_array($locale, $this->enabledLocales, true)) {
            $locale = $request->getPreferredLanguage($this->enabledLocales) ?? $this->defaultLocale;
        }

        $request->setLocale($locale);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('Content-Language', $event->getRequest()->getLocale());
    }
}
