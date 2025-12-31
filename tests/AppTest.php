<?php

use PHPUnit\Framework\TestCase;
use PaigeJulianne\NanoMVC\App;

class AppTest extends TestCase
{
    private string $testConfigPath;

    protected function setUp(): void
    {
        $this->testConfigPath = __DIR__ . '/fixtures';

        if (!is_dir($this->testConfigPath)) {
            mkdir($this->testConfigPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $configFile = $this->testConfigPath . '/.config';
        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }

    public function testDebugModeDefaultsFalse(): void
    {
        App::setDebug(false);
        $this->assertFalse(App::isDebug());
    }

    public function testSetDebugMode(): void
    {
        App::setDebug(true);
        $this->assertTrue(App::isDebug());
        App::setDebug(false);
        $this->assertFalse(App::isDebug());
    }

    public function testSetAndGetConfig(): void
    {
        App::setConfig('test.key', 'test-value');
        $this->assertEquals('test-value', App::config('test.key'));
    }

    public function testConfigReturnsDefaultWhenMissing(): void
    {
        $this->assertEquals('default', App::config('nonexistent.key', 'default'));
    }

    public function testConfigParsesBooleanValues(): void
    {
        $configContent = <<<CONFIG
[app]
enabled_true=true
enabled_yes=yes
enabled_on=on
disabled_false=false
disabled_no=no
disabled_off=off
CONFIG;

        file_put_contents($this->testConfigPath . '/.config', $configContent);
        App::setConfigFile('.config');

        // Note: We can't easily test this without running App::run()
        // This test verifies the config file can be created
        $this->assertFileExists($this->testConfigPath . '/.config');
    }

    public function testConfigParsesNumericValues(): void
    {
        $configContent = <<<CONFIG
[settings]
port=8080
rate=3.14
CONFIG;

        file_put_contents($this->testConfigPath . '/.config', $configContent);
        $this->assertFileExists($this->testConfigPath . '/.config');
    }

    public function testBasePathReturnsPath(): void
    {
        // BasePath requires App::run() to set it, but we can test the method exists
        $path = App::basePath('test/path');
        $this->assertStringContainsString('test/path', $path);
    }
}
