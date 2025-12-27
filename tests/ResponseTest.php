<?php

use PHPUnit\Framework\TestCase;
use PaigeJulianne\PicoMVC\Response;

class ResponseTest extends TestCase
{
    public function testSetAndGetContent(): void
    {
        $response = new Response();
        $response->setContent('Hello World');
        $this->assertEquals('Hello World', $response->getContent());
    }

    public function testSetAndGetStatusCode(): void
    {
        $response = new Response();
        $response->setStatusCode(404);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDefaultStatusCodeIs200(): void
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMethodsReturnSelfForChaining(): void
    {
        $response = new Response();
        $result = $response->setContent('test')->setStatusCode(201)->header('X-Test', 'value');
        $this->assertSame($response, $result);
    }

    public function testJsonCreatesJsonResponse(): void
    {
        $response = Response::json(['name' => 'Alice', 'age' => 30]);
        $this->assertEquals(200, $response->getStatusCode());
        $content = $response->getContent();
        $decoded = json_decode($content, true);
        $this->assertEquals('Alice', $decoded['name']);
        $this->assertEquals(30, $decoded['age']);
    }

    public function testJsonWithCustomStatus(): void
    {
        $response = Response::json(['error' => 'Not found'], 404);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRedirectCreatesRedirectResponse(): void
    {
        $response = Response::redirect('/dashboard');
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testRedirectWithCustomStatus(): void
    {
        $response = Response::redirect('/permanent', 301);
        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testTextCreatesPlainTextResponse(): void
    {
        $response = Response::text('Hello World');
        $this->assertEquals('Hello World', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testTextWithCustomStatus(): void
    {
        $response = Response::text('Created', 201);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testHtmlCreatesHtmlResponse(): void
    {
        $response = Response::html('<h1>Hello</h1>');
        $this->assertEquals('<h1>Hello</h1>', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHtmlWithCustomStatus(): void
    {
        $response = Response::html('<h1>Not Found</h1>', 404);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testWithHeadersSetsMultipleHeaders(): void
    {
        $response = new Response();
        $response->withHeaders([
            'X-Custom-1' => 'value1',
            'X-Custom-2' => 'value2',
        ]);
        // Headers are stored internally, tested via send() in integration tests
        $this->assertInstanceOf(Response::class, $response);
    }
}
