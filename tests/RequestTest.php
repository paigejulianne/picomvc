<?php

use PHPUnit\Framework\TestCase;
use PaigeJulianne\NanoMVC\Request;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset superglobals
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
    }

    public function testMethodReturnsGetByDefault(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();
        $this->assertEquals('GET', $request->method());
    }

    public function testMethodReturnsPostWhenSet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $this->assertEquals('POST', $request->method());
    }

    public function testMethodOverrideViaPostField(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_method'] = 'PUT';
        $request = new Request();
        $this->assertEquals('PUT', $request->method());
    }

    public function testMethodOverrideViaHeader(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';
        $request = new Request();
        $this->assertEquals('DELETE', $request->method());
    }

    public function testPathReturnsRoot(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $request = new Request();
        $this->assertEquals('/', $request->path());
    }

    public function testPathReturnsPathWithoutQueryString(): void
    {
        $_SERVER['REQUEST_URI'] = '/users/123?foo=bar';
        $request = new Request();
        $this->assertEquals('/users/123', $request->path());
    }

    public function testQueryReturnsValue(): void
    {
        $_GET['name'] = 'Alice';
        $request = new Request();
        $this->assertEquals('Alice', $request->query('name'));
    }

    public function testQueryReturnsDefaultWhenMissing(): void
    {
        $request = new Request();
        $this->assertEquals('default', $request->query('missing', 'default'));
    }

    public function testInputReturnsPostValue(): void
    {
        $_POST['email'] = 'test@example.com';
        $request = new Request();
        $this->assertEquals('test@example.com', $request->input('email'));
    }

    public function testInputReturnsGetValueWhenPostMissing(): void
    {
        $_GET['email'] = 'query@example.com';
        $request = new Request();
        $this->assertEquals('query@example.com', $request->input('email'));
    }

    public function testInputPostTakesPrecedenceOverGet(): void
    {
        $_GET['email'] = 'query@example.com';
        $_POST['email'] = 'post@example.com';
        $request = new Request();
        $this->assertEquals('post@example.com', $request->input('email'));
    }

    public function testAllReturnsMergedGetAndPost(): void
    {
        $_GET['a'] = '1';
        $_POST['b'] = '2';
        $request = new Request();
        $all = $request->all();
        $this->assertEquals('1', $all['a']);
        $this->assertEquals('2', $all['b']);
    }

    public function testOnlyReturnsSpecifiedKeys(): void
    {
        $_POST = ['a' => '1', 'b' => '2', 'c' => '3'];
        $request = new Request();
        $result = $request->only(['a', 'c']);
        $this->assertEquals(['a' => '1', 'c' => '3'], $result);
    }

    public function testExceptExcludesSpecifiedKeys(): void
    {
        $_POST = ['a' => '1', 'b' => '2', 'c' => '3'];
        $request = new Request();
        $result = $request->except(['b']);
        $this->assertEquals(['a' => '1', 'c' => '3'], $result);
    }

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        $_POST['email'] = 'test@example.com';
        $request = new Request();
        $this->assertTrue($request->has('email'));
    }

    public function testHasReturnsFalseWhenKeyMissing(): void
    {
        $request = new Request();
        $this->assertFalse($request->has('email'));
    }

    public function testHeaderReturnsValue(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $request = new Request();
        $this->assertEquals('application/json', $request->header('Accept'));
    }

    public function testCookieReturnsValue(): void
    {
        $_COOKIE['session'] = 'abc123';
        $request = new Request();
        $this->assertEquals('abc123', $request->cookie('session'));
    }

    public function testIsAjaxReturnsTrueWhenXhrHeader(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $request = new Request();
        $this->assertTrue($request->isAjax());
    }

    public function testIsAjaxReturnsFalseWhenNoHeader(): void
    {
        $request = new Request();
        $this->assertFalse($request->isAjax());
    }

    public function testExpectsJsonReturnsTrueWhenAcceptJson(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $request = new Request();
        $this->assertTrue($request->expectsJson());
    }

    public function testRouteParamsCanBeSetAndRetrieved(): void
    {
        $request = new Request();
        $request->setRouteParams(['id' => '123', 'slug' => 'test-post']);
        $this->assertEquals('123', $request->param('id'));
        $this->assertEquals('test-post', $request->param('slug'));
        $this->assertEquals(['id' => '123', 'slug' => 'test-post'], $request->params());
    }

    public function testParamReturnsDefaultWhenMissing(): void
    {
        $request = new Request();
        $this->assertEquals('default', $request->param('missing', 'default'));
    }
}
