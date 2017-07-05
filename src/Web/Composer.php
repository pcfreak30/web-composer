<?php

namespace pcfreak30\Web;

use Composer\Console;
use Unirest\Exception;
use Unirest\Request;

/**
 * Class Composer
 *
 * @author Derrick Hammer
 * @license GPL3
 * @SuppressWarnings(PHPMD.StaticAccess)
 *
 */
class Composer
{
    /**
     * @var string
     */
    protected $download_target;

    /**
     * @var string
     */
    protected $install_target;

    /**
     * @return string
     */
    public function getDownloadTarget()
    {
        return $this->download_target;
    }

    /**
     * @param string $download_target
     */
    public function setDownloadTarget($download_target)
    {
        $this->download_target = $download_target;
    }

    /**
     * @return string
     */
    public function getInstallTarget()
    {
        return $this->install_target;
    }

    /**
     * @param string $install_target
     */
    public function setInstallTarget($install_target)
    {
        $this->install_target = $install_target;
    }

    /**
     * @return bool
     */
    public function run()
    {
        if (!$this->download()) {
            return false;
        }
        if (!$this->install()) {
            return $this->cleanup();
        }
        return $this->cleanup();
    }

    /**
     * @return bool
     */
    public function download()
    {
        if ($this->pharExists()) {
            return true;
        }
        try {
            $response = Request::get('https://getcomposer.org/composer.phar');
        } catch (Exception $e) {
            return false;
        }
        $result = file_put_contents($this->download_target, $response->body);
        if (false !== $result) {
            $result = true;
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function pharExists()
    {
        if (file_exists($this->download_target)) {
            try {
                $response = Request::get('https://getcomposer.org/composer.phar.sig');
            } catch (Exception $e) {
                return false;
            }
            if (!$this->validatePhar($response->body)) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @param $signatures
     * @return bool
     */
    protected function validatePhar($signatures)
    {
        foreach ($signatures as $hash_algo => $signature) {
            if ($signature != hash_file(strtoupper($hash_algo), $this->download_target)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool|int
     */
    public function install()
    {
        $_SERVER['argv'] = array('composer', '-q', '--working-dir=' . $this->install_target, '--no-dev', 'install');
        $_SERVER['argc'] = count($_SERVER['argv']);

        if (!file_exists($this->install_target . '/composer.json')) {
            return false;
        }
        if (!class_exists('\Composer\Console\Application')) {
            try {
                \Phar::loadPhar($this->download_target);
                require_once 'phar://composer.phar/src/bootstrap.php';
            } catch (\Exception $e) {
                return false;
            }
        }
        $orig_memory_limit = trim(ini_get('memory_limit'));
        $this->increaseMemory();
        putenv('COMPOSER_NO_INTERACTION=1');
        $app = new Console\Application();
        $app->setAutoExit(false);
        $result = $app->run();
        @ini_set('memory_limit', $orig_memory_limit);
        $result = 0 == $result;
        return $result;
    }

    /**
     *
     */
    protected function increaseMemory()
    {
        $memory_limit = trim(ini_get('memory_limit'));
        // Increase memory_limit if it is lower than 1.5GB
        if ($memory_limit != -1 && $this->memoryInBytes($memory_limit) < 1024 * 1024 * 1536) {
            @ini_set('memory_limit', '1536M');
        }
    }

    /**
     * @param $value
     * @return int
     */
    protected function memoryInBytes($value)
    {
        $unit = strtolower(substr($value, -1, 1));
        $value = (int)$value;
        switch ($unit) {
            case 'g':
                $value *= 1024;
            // no break (cumulative multiplier) @noinspection PhpMissingBreakStatementInspection
            case 'm':
                $value *= 1024;
            // no break (cumulative multiplier) @noinspection PhpMissingBreakStatementInspection
            case 'k':
                $value *= 1024;
            // no break (cumulative multiplier) @noinspection PhpMissingBreakStatementInspection
        }
        return $value;
    }

    /**
     * @return bool
     */
    public function cleanup()
    {
        if (file_exists($this->download_target)) {
            return unlink($this->download_target);
        }
        return true;
    }
}
