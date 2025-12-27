<?php

use PHPUnit\Framework\TestCase;
use PaigeJulianne\PicoMVC\Router;
use PaigeJulianne\PicoMVC\Request;
use PaigeJulianne\PicoMVC\Response;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        Router::clear();
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
    }

    public function testGetRouteMatches(): void
    {
        Router::get('/test', function () {
            return 'Hello';
        });

        $_SERVER['REQUEST_URI'] = '/test';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('Hello', $response->getContent());
    }

    public function testPostRouteMatches(): void
    {
        Router::post('/submit', function () {
            return 'Submitted';
        });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('Submitted', $response->getContent());
    }

    public function testPutRouteMatches(): void
    {
        Router::put('/update', function () {
            return 'Updated';
        });

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/update';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('Updated', $response->getContent());
    }

    public function testDeleteRouteMatches(): void
    {
        Router::delete('/delete', function () {
            return 'Deleted';
        });

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/delete';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('Deleted', $response->getContent());
    }

    public function testPatchRouteMatches(): void
    {
        Router::patch('/patch', function () {
            return 'Patched';
        });

        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $_SERVER['REQUEST_URI'] = '/patch';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('Patched', $response->getContent());
    }

    public function testAnyRouteMatchesAllMethods(): void
    {
        Router::any('/any', function () {
            return 'Any method';
        });

        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REQUEST_URI'] = '/any';
            $request = new Request();
            $response = Router::dispatch($request);
            $this->assertEquals('Any method', $response->getContent());
        }
    }

    public function testMatchRouteMatchesSpecifiedMethods(): void
    {
        Router::match(['GET', 'POST'], '/match', function () {
            return 'Matched';
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/match';
        $request = new Request();
        $response = Router::dispatch($request);
        $this->assertEquals('Matched', $response->getContent());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $response = Router::dispatch($request);
        $this->assertEquals('Matched', $response->getContent());
    }

    public function testRouteWithParameter(): void
    {
        Router::get('/users/{id}', function (Request $request) {
            return 'User: ' . $request->param('id');
        });

        $_SERVER['REQUEST_URI'] = '/users/123';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('User: 123', $response->getContent());
    }

    public function testRouteWithMultipleParameters(): void
    {
        Router::get('/posts/{year}/{month}/{slug}', function (Request $request) {
            return $request->param('year') . '/' . $request->param('month') . '/' . $request->param('slug');
        });

        $_SERVER['REQUEST_URI'] = '/posts/2024/12/hello-world';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('2024/12/hello-world', $response->getContent());
    }

    public function testRouteGroup(): void
    {
        Router::group(['prefix' => 'api'], function () {
            Router::get('/users', function () {
                return 'API Users';
            });
            Router::get('/posts', function () {
                return 'API Posts';
            });
        });

        $_SERVER['REQUEST_URI'] = '/api/users';
        $request = new Request();
        $response = Router::dispatch($request);
        $this->assertEquals('API Users', $response->getContent());

        $_SERVER['REQUEST_URI'] = '/api/posts';
        $request = new Request();
        $response = Router::dispatch($request);
        $this->assertEquals('API Posts', $response->getContent());
    }

    public function testNestedRouteGroups(): void
    {
        Router::group(['prefix' => 'api'], function () {
            Router::group(['prefix' => 'v1'], function () {
                Router::get('/users', function () {
                    return 'API v1 Users';
                });
            });
        });

        $_SERVER['REQUEST_URI'] = '/api/v1/users';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('API v1 Users', $response->getContent());
    }

    public function testNotFoundReturns404(): void
    {
        Router::get('/exists', function () {
            return 'Exists';
        });

        $_SERVER['REQUEST_URI'] = '/does-not-exist';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testCustomNotFoundHandler(): void
    {
        Router::setNotFoundHandler(function () {
            return Response::html('Custom 404', 404);
        });

        $_SERVER['REQUEST_URI'] = '/not-found';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Custom 404', $response->getContent());
    }

    public function testRouteReturnsResponseObject(): void
    {
        Router::get('/json', function () {
            return Response::json(['status' => 'ok']);
        });

        $_SERVER['REQUEST_URI'] = '/json';
        $request = new Request();
        $response = Router::dispatch($request);

        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals('ok', $decoded['status']);
    }

    public function testRouteReturnsArray(): void
    {
        Router::get('/array', function () {
            return ['key' => 'value'];
        });

        $_SERVER['REQUEST_URI'] = '/array';
        $request = new Request();
        $response = Router::dispatch($request);

        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals('value', $decoded['key']);
    }

    public function testRootRoute(): void
    {
        Router::get('/', function () {
            return 'Home';
        });

        $_SERVER['REQUEST_URI'] = '/';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('Home', $response->getContent());
    }

    public function testTrailingSlashNormalization(): void
    {
        Router::get('/test', function () {
            return 'Test';
        });

        $_SERVER['REQUEST_URI'] = '/test/';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertEquals('Test', $response->getContent());
    }

    public function testGetRoutes(): void
    {
        Router::get('/a', function () {});
        Router::post('/b', function () {});

        $routes = Router::getRoutes();

        $this->assertArrayHasKey('GET', $routes);
        $this->assertArrayHasKey('POST', $routes);
        $this->assertCount(1, $routes['GET']);
        $this->assertCount(1, $routes['POST']);
    }

    public function testClearRemovesAllRoutes(): void
    {
        Router::get('/test', function () {});
        Router::clear();

        $routes = Router::getRoutes();
        $this->assertEmpty($routes);
    }

    public function testMiddlewareCanHaltRequest(): void
    {
        $middlewareRan = false;

        Router::get('/protected', function () {
            return 'Protected content';
        }, [function (Request $request) use (&$middlewareRan) {
            $middlewareRan = true;
            return Response::html('Unauthorized', 401);
        }]);

        $_SERVER['REQUEST_URI'] = '/protected';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertTrue($middlewareRan);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getContent());
    }

    public function testMiddlewareAllowsPassthrough(): void
    {
        $middlewareRan = false;

        Router::get('/allowed', function () {
            return 'Allowed content';
        }, [function (Request $request) use (&$middlewareRan) {
            $middlewareRan = true;
            return null; // Allow request to continue
        }]);

        $_SERVER['REQUEST_URI'] = '/allowed';
        $request = new Request();
        $response = Router::dispatch($request);

        $this->assertTrue($middlewareRan);
        $this->assertEquals('Allowed content', $response->getContent());
    }
}
