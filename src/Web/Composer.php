<?php

namespace pcfreak30\Web;

use Composer\Console;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
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
     * @var string
     */
    protected $source_target;

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

        $this->preCleanup();

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
        if (!file_exists($this->install_target . '/composer.json')) {
            return false;
        }
        $result = false;
        if (!class_exists('\Composer\Console\Application')) {
            if (extension_loaded('suhosin') && false === strpos(ini_get('suhosin.executor.include.whitelist'),
                    'phar')) {
                $result = $this->loadSource();
            } else {
                $result = $this->loadPhar();
            }
            if (!$result) {
                return $result;
            }
        }
        $orig_memory_limit = trim(ini_get('memory_limit'));
        $this->increaseMemory();
        putenv('COMPOSER_NO_INTERACTION=1');
        putenv('COMPOSER_HOME=' . dirname($this->download_target) . '/.composer');
        $output = new BufferedOutput();
        $input = new ArrayInput(array(
            'install',
            '--prefer-dist' => true,
            '--no-dev' => true,
            '--working-dir' => $this->install_target,
        ));
        $app = new Console\Application();
        $app->setAutoExit(false);
        $result = $app->run($input, $output);
        @ini_set('memory_limit', $orig_memory_limit);
        $output_message = $output->fetch();
        if (!empty($result)) {
            echo $output_message;
            $result = 1;
        }
        $result = 0 == $result;
        return $result;
    }

    protected function loadPhar()
    {
        try {
            \Phar::loadPhar($this->download_target);
            require_once 'phar://composer.phar/src/bootstrap.php';
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    protected function loadSource()
    {
        $this->source_target = dirname($this->download_target) . '/composer';
        @mkdir($this->source_target);
        try {
            $phar = new \Phar($this->download_target);
            $phar->extractTo($this->source_target, null, true);
            require_once $this->source_target . '/src/bootstrap.php';
        } catch (\Exception $e) {
            return false;
        }
        return true;
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
            unlink($this->download_target);
        }
        $home = getenv('COMPOSER_HOME');
        if (!empty($home)) {
            $this->rmdir($home);
        }
        if (!empty($this->source_target) && is_dir($this->source_target)) {
            $this->rmdir($this->source_target);
        }
        return true;
    }

    private function rmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->rmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    private function preCleanup()
    {
        $this->rmdir($this->install_target . '/vendor');
    }
}