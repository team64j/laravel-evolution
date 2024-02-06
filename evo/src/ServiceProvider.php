<?php

namespace EvolutionCMS;

use Exception;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * @property $app
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Массовая регистрация виртуальных сниппетов с использованием неймспейса
     *
     * @param $path
     * @param string $namespace
     *
     * @throws Exception
     */
    protected function loadSnippetsFrom($path, string $namespace = ''): void
    {
        $found = app('evo')->findElements('snippet', $path, array('php'));
        foreach ($found as $name => $code) {
            $this->addSnippet($name, $code, $namespace);
        }
    }

    /**
     * Массовая регистрация виртуальных чанков с использованием неймспейса
     *
     * @param $path
     * @param string $namespace
     *
     * @throws Exception
     */
    protected function loadChunksFrom($path, string $namespace = ''): void
    {
        $found = app('evo')->findElements('chunk', $path, array('tpl', 'html'));
        foreach ($found as $name => $code) {
            $this->addChunk($name, $code, $namespace);
        }
    }

    /**
     * Массовая регистрация виртуальных плагинов
     *
     * @param $path
     * @throws Exception
     */
    protected function loadPluginsFrom($path): void
    {
        foreach (glob($path . '*.php') as $file) {
            include $file;
        }
    }


    /**
     * Регистрация виртуального сниппета с использованием неймспейса
     *
     * @param $name
     * @param $code
     * @param string $namespace
     */
    protected function addSnippet($name, $code, string $namespace = ''): void
    {
        app('evo')->addSnippet($name, $code, !empty($namespace) ? $namespace . '#' : '');
    }

    /**
     * Регистрация виртуального чанка с использованием неймспейса
     *
     * @param $name
     * @param $code
     * @param string $namespace
     */
    protected function addChunk($name, $code, string $namespace = ''): void
    {
        app('evo')->addChunk($name, $code, !empty($namespace) ? $namespace . '#' : '');
    }
}
