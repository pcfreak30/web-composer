<?php

namespace pcfreak30;


use pcfreak30\Web\Composer;
use Unirest\Request;

class ComposerTest extends \PHPUnit_Framework_TestCase
{
    public function testDownloadFileExists()
    {
        $response = Request::get('https://getcomposer.org/composer.phar');
        file_put_contents(__DIR__ . '/composer.phar', $response->body);
        $composer = new Composer();
        $composer->setDownloadTarget(__DIR__ . '/composer.phar');
        $this->assertTrue($composer->download());
    }

    public function testDownloadFileNotExists()
    {
        $composer = new Composer();
        $composer->setDownloadTarget(__DIR__ . '/composer.phar');
        $this->assertTrue($composer->download());
    }

    public function testDownloadFileExistsInvalidChecksum()
    {
        file_put_contents(__DIR__ . '/composer.phar', 'test');
        $composer = new Composer();
        $composer->setDownloadTarget(__DIR__ . '/composer.phar');
        $this->assertTrue($composer->download());
    }

    public function testCleanup()
    {
        $composer = new Composer();
        $composer->setDownloadTarget(__DIR__ . '/composer.phar');
        $composer->download();
        $this->assertTrue($composer->cleanup());
    }

    public function testInstall()
    {
        $composer = new Composer();
        $composer->setDownloadTarget(__DIR__ . '/composer.phar');
        $composer->setInstallTarget(__DIR__);
        $composer->download();
        $this->assertTrue($composer->install());
    }

    public function testInstallTwice()
    {
        $composer = new Composer();
        $composer->setDownloadTarget(__DIR__ . '/composer.phar');
        $composer->setInstallTarget(__DIR__);
        $composer->download();
        $this->assertTrue($composer->install());
        $this->assertTrue($composer->install());
    }

    protected function setUp()
    {
        parent::setUp();
        self::cleanUp();
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        self::cleanUp();
    }

    protected static function cleanUp()
    {
        if (file_exists(__DIR__ . '/composer.phar')) {
            @unlink(__DIR__ . '/composer.phar');
        }
        if (file_exists(__DIR__ . '/composer.phar.sig')) {
            @unlink(__DIR__ . '/composer.phar.sig');
        }
        if (is_dir(__DIR__ . '/vendor')) {
            self::deleteDir(__DIR__ . '/vendor');
        }
    }

    protected static function deleteDir($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::deleteDir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
