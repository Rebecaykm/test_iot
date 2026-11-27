<?php

namespace Tests\Unit;

use App\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

/** No toca la base de datos. */
class ValidateCsrfTokenTest extends TestCase
{
    private function passes(string $route, array $cookies = [], ?string $origin = 'http://iot.test:9080'): bool
    {
        // Laravel se salta esta revisión en las pruebas; aquí se vuelve a activar
        $middleware = new class($this->app, $this->app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        };

        $request = Request::create('http://iot.test:9080/livewire/update', 'POST', [], $cookies, [], $origin ? ['HTTP_ORIGIN' => $origin] : []);
        $request->setRouteResolver(fn () => (new Route('POST', 'livewire/update', fn () => null))->name($route));
        $session = new Store('test', new ArraySessionHandler(10));
        $session->start();
        $request->setLaravelSession($session);

        try {
            $middleware->handle($request, fn () => new Response);

            return true;
        } catch (TokenMismatchException) {
            return false;
        }
    }

    public function test_livewire_inside_a_preview_of_another_site_works_without_cookies(): void
    {
        $this->assertTrue($this->passes('livewire.update'));
    }

    public function test_every_other_request_without_a_valid_token_is_still_rejected(): void
    {
        $this->assertFalse($this->passes('livewire.update', ['iot_session' => 'x']), 'with cookies the token is required');
        $this->assertFalse($this->passes('livewire.update', [], 'http://otro.test'), 'sent from another site');
        $this->assertFalse($this->passes('livewire.update', [], null), 'without origin');
        $this->assertFalse($this->passes('logout'), 'any other route');
    }
}
