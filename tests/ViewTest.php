<?php

use PHPUnit\Framework\TestCase;
use PaigeJulianne\PicoMVC\View;
use PaigeJulianne\PicoMVC\Response;
use PaigeJulianne\PicoMVC\PhpAdapter;

class ViewTest extends TestCase
{
    private string $testViewsPath;

    protected function setUp(): void
    {
        $this->testViewsPath = __DIR__ . '/fixtures/views';

        // Create test views directory and files
        if (!is_dir($this->testViewsPath)) {
            mkdir($this->testViewsPath, 0755, true);
        }

        // Create a simple test view
        file_put_contents(
            $this->testViewsPath . '/test.php',
            '<?php echo "Hello " . $name; ?>'
        );

        // Create a view in a subdirectory
        if (!is_dir($this->testViewsPath . '/pages')) {
            mkdir($this->testViewsPath . '/pages', 0755, true);
        }
        file_put_contents(
            $this->testViewsPath . '/pages/home.php',
            '<?php echo "Welcome to " . $title; ?>'
        );

        // Create a view that uses shared data
        file_put_contents(
            $this->testViewsPath . '/shared.php',
            '<?php echo "App: " . $appName . ", User: " . $userName; ?>'
        );

        // Configure the view system
        View::configure($this->testViewsPath, sys_get_temp_dir() . '/picomvc_test_cache', 'php');
    }

    protected function tearDown(): void
    {
        // Clean up test files
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

    public function testRenderSimpleView(): void
    {
        $result = View::render('test', ['name' => 'World']);
        $this->assertEquals('Hello World', $result);
    }

    public function testRenderNestedView(): void
    {
        $result = View::render('pages.home', ['title' => 'PicoMVC']);
        $this->assertEquals('Welcome to PicoMVC', $result);
    }

    public function testShareDataWithAllViews(): void
    {
        View::share('appName', 'MyApp');
        $result = View::render('shared', ['userName' => 'Alice']);
        $this->assertEquals('App: MyApp, User: Alice', $result);
    }

    public function testShareMultipleValues(): void
    {
        View::share([
            'appName' => 'TestApp',
            'userName' => 'Bob'
        ]);
        $result = View::render('shared', []);
        $this->assertEquals('App: TestApp, User: Bob', $result);
    }

    public function testMakeReturnsResponse(): void
    {
        $response = View::make('test', ['name' => 'Test']);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Hello Test', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMakeWithCustomStatus(): void
    {
        $response = View::make('test', ['name' => 'Error'], 500);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testRenderThrowsExceptionForMissingView(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('View not found');
        View::render('nonexistent', []);
    }

    public function testEngineAvailableForPhp(): void
    {
        $this->assertTrue(View::engineAvailable('php'));
    }

    public function testPhpAdapterIsAvailable(): void
    {
        $this->assertTrue(PhpAdapter::isAvailable());
    }
}
