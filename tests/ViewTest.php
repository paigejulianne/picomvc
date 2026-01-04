<?php

use PHPUnit\Framework\TestCase;
use PaigeJulianne\NanoMVC\View;
use PaigeJulianne\NanoMVC\Response;
use PaigeJulianne\NanoMVC\PhpAdapter;
use PaigeJulianne\NanoMVC\TwigAdapter;

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
        View::configure($this->testViewsPath, sys_get_temp_dir() . '/nanomvc_test_cache', 'php');
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
        $result = View::render('pages.home', ['title' => 'NanoMVC']);
        $this->assertEquals('Welcome to NanoMVC', $result);
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

    public function testEngineAvailableForTwig(): void
    {
        // Twig availability depends on whether the package is installed
        $expected = class_exists('Twig\Environment');
        $this->assertEquals($expected, View::engineAvailable('twig'));
    }

    public function testTwigAdapterIsAvailable(): void
    {
        // TwigAdapter::isAvailable() depends on whether twig/twig is installed
        $expected = class_exists('Twig\Environment');
        $this->assertEquals($expected, TwigAdapter::isAvailable());
    }

    public function testTwigAdapterRender(): void
    {
        if (!class_exists('Twig\Environment')) {
            $this->markTestSkipped('Twig is not installed');
        }

        // Create Twig test view
        file_put_contents(
            $this->testViewsPath . '/twig_test.twig',
            'Hello {{ name }}'
        );

        $cachePath = sys_get_temp_dir() . '/nanomvc_twig_test_cache';
        $adapter = new TwigAdapter($this->testViewsPath, $cachePath);
        $result = $adapter->render('twig_test', ['name' => 'World']);

        $this->assertEquals('Hello World', $result);
    }

    public function testTwigAdapterWithInheritance(): void
    {
        if (!class_exists('Twig\Environment')) {
            $this->markTestSkipped('Twig is not installed');
        }

        // Create base layout
        file_put_contents(
            $this->testViewsPath . '/base.twig',
            '<html>{% block content %}{% endblock %}</html>'
        );

        // Create child template
        file_put_contents(
            $this->testViewsPath . '/child.twig',
            '{% extends "base.twig" %}{% block content %}Hello {{ name }}{% endblock %}'
        );

        $cachePath = sys_get_temp_dir() . '/nanomvc_twig_test_cache';
        $adapter = new TwigAdapter($this->testViewsPath, $cachePath);
        $result = $adapter->render('child', ['name' => 'Twig']);

        $this->assertEquals('<html>Hello Twig</html>', $result);
    }

    public function testTwigAdapterAutoEscapes(): void
    {
        if (!class_exists('Twig\Environment')) {
            $this->markTestSkipped('Twig is not installed');
        }

        file_put_contents(
            $this->testViewsPath . '/escape_test.twig',
            '{{ content }}'
        );

        $cachePath = sys_get_temp_dir() . '/nanomvc_twig_test_cache';
        $adapter = new TwigAdapter($this->testViewsPath, $cachePath);
        $result = $adapter->render('escape_test', ['content' => '<script>alert("xss")</script>']);

        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result);
    }

    public function testTwigAdapterGetTwig(): void
    {
        if (!class_exists('Twig\Environment')) {
            $this->markTestSkipped('Twig is not installed');
        }

        $cachePath = sys_get_temp_dir() . '/nanomvc_twig_test_cache';
        $adapter = new TwigAdapter($this->testViewsPath, $cachePath);

        $this->assertInstanceOf(\Twig\Environment::class, $adapter->getTwig());
    }
}
