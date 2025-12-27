<?php

use PHPUnit\Framework\TestCase;
use PaigeJulianne\PicoMVC\Controller;
use PaigeJulianne\PicoMVC\Request;
use PaigeJulianne\PicoMVC\Response;
use PaigeJulianne\PicoMVC\View;
use PaigeJulianne\PicoMVC\ValidationException;

/**
 * Test controller implementation
 */
class TestController extends Controller
{
    public function index(): Response
    {
        return $this->html('<h1>Index</h1>');
    }

    public function show(Request $request): Response
    {
        $id = $request->param('id');
        return $this->json(['id' => $id]);
    }

    public function redirectAction(): Response
    {
        return $this->redirect('/dashboard');
    }

    public function textAction(): Response
    {
        return $this->text('Plain text response');
    }

    public function validateAction(): array
    {
        return $this->validate([
            'email' => 'required|email',
            'name' => 'required|min:3|max:50',
        ]);
    }

    public function getRequest(): ?Request
    {
        return $this->request();
    }
}

class ControllerTest extends TestCase
{
    private string $testViewsPath;

    protected function setUp(): void
    {
        $this->testViewsPath = __DIR__ . '/fixtures/views';

        if (!is_dir($this->testViewsPath)) {
            mkdir($this->testViewsPath, 0755, true);
        }

        file_put_contents(
            $this->testViewsPath . '/hello.php',
            '<?php echo "Hello " . $name; ?>'
        );

        View::configure($this->testViewsPath, sys_get_temp_dir() . '/picomvc_test_cache', 'php');

        $_GET = [];
        $_POST = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testViewsPath)) {
            $this->removeDirectory($this->testViewsPath);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testSetAndGetRequest(): void
    {
        $controller = new TestController();
        $request = new Request();
        $controller->setRequest($request);

        $this->assertSame($request, $controller->getRequest());
    }

    public function testHtmlResponse(): void
    {
        $controller = new TestController();
        $response = $controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('<h1>Index</h1>', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testJsonResponse(): void
    {
        $_SERVER['REQUEST_URI'] = '/users/42';
        $request = new Request();
        $request->setRouteParams(['id' => '42']);

        $controller = new TestController();
        $controller->setRequest($request);
        $response = $controller->show($request);

        $this->assertInstanceOf(Response::class, $response);
        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals('42', $decoded['id']);
    }

    public function testRedirectResponse(): void
    {
        $controller = new TestController();
        $response = $controller->redirectAction();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testTextResponse(): void
    {
        $controller = new TestController();
        $response = $controller->textAction();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Plain text response', $response->getContent());
    }

    public function testValidationPasses(): void
    {
        $_POST = [
            'email' => 'test@example.com',
            'name' => 'Alice',
        ];

        $request = new Request();
        $controller = new TestController();
        $controller->setRequest($request);

        $validated = $controller->validateAction();

        $this->assertEquals('test@example.com', $validated['email']);
        $this->assertEquals('Alice', $validated['name']);
    }

    public function testValidationFailsForRequiredField(): void
    {
        $_POST = [
            'email' => '',
            'name' => 'Alice',
        ];

        $request = new Request();
        $controller = new TestController();
        $controller->setRequest($request);

        $this->expectException(ValidationException::class);
        $controller->validateAction();
    }

    public function testValidationFailsForInvalidEmail(): void
    {
        $_POST = [
            'email' => 'not-an-email',
            'name' => 'Alice',
        ];

        $request = new Request();
        $controller = new TestController();
        $controller->setRequest($request);

        $this->expectException(ValidationException::class);
        $controller->validateAction();
    }

    public function testValidationFailsForMinLength(): void
    {
        $_POST = [
            'email' => 'test@example.com',
            'name' => 'AB', // Too short
        ];

        $request = new Request();
        $controller = new TestController();
        $controller->setRequest($request);

        $this->expectException(ValidationException::class);
        $controller->validateAction();
    }

    public function testValidationExceptionContainsErrors(): void
    {
        $_POST = [
            'email' => 'invalid',
            'name' => '',
        ];

        $request = new Request();
        $controller = new TestController();
        $controller->setRequest($request);

        try {
            $controller->validateAction();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('name', $errors);
        }
    }

    public function testValidationExceptionToResponse(): void
    {
        $errors = ['email' => ['Invalid email']];
        $exception = new ValidationException($errors);
        $response = $exception->toResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $decoded = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $decoded);
    }
}
