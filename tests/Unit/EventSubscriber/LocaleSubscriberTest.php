<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LocaleSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class LocaleSubscriberTest extends TestCase
{
    #[DataProvider('acceptLanguageProvider')]
    public function testLocaleIsResolvedFromAcceptLanguage(?string $header, string $expected): void
    {
        $request = Request::create('/api/events');
        if (null !== $header) {
            $request->headers->set('Accept-Language', $header);
        }

        $this->subscriber()->onKernelRequest($this->requestEvent($request));

        self::assertSame($expected, $request->getLocale());
    }

    /** @return iterable<string, array{?string, string}> */
    public static function acceptLanguageProvider(): iterable
    {
        yield 'french' => ['fr', 'fr'];
        yield 'regional variant' => ['fr-BE,fr;q=0.9,en;q=0.8', 'fr'];
        yield 'english preferred' => ['en-US,fr;q=0.5', 'en'];
        yield 'unsupported language falls back' => ['de-DE', 'en'];
        yield 'no header' => [null, 'en'];
    }

    public function testQueryParameterWinsOverHeader(): void
    {
        $request = Request::create('/api/events?_locale=fr');
        $request->headers->set('Accept-Language', 'en');

        $this->subscriber()->onKernelRequest($this->requestEvent($request));

        self::assertSame('fr', $request->getLocale());
    }

    public function testResponseAdvertisesContentLanguage(): void
    {
        $request = Request::create('/api/events');
        $request->setLocale('fr');
        $response = new Response();

        $this->subscriber()->onKernelResponse(new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        ));

        self::assertSame('fr', $response->headers->get('Content-Language'));
    }

    private function subscriber(): LocaleSubscriber
    {
        return new LocaleSubscriber(['en', 'fr'], 'en');
    }

    private function requestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
