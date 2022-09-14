<?php

namespace app\services;

class ConfigService
{
    /** @var array */
    private $config;

    public function __construct()
    {
        $this->config = array_replace(
            require __DIR__ . '/../../app/configs/services.php',
            require __DIR__ . '/../../app/configs/others.php'
        );
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function get(string $path)
    {
        $pathParts = explode('.', $path);
        $config = $this->config;
        foreach ($pathParts as $part) {
            $config = $config[$part] ?? null;
            if(!$config) {
                return false;
            }
        }

        return $config;
    }
}