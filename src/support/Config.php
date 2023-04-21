<?php
namespace royfee\tracking\support;

class Config implements \ArrayAccess{
    /**
     * @var array
     */
    protected $config;

    /**
     * Config constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * get a config.
     *
     * @author JasonYan <me@yansongda.cn>
     *
     * @param string $key
     * @param string $default
     *
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        $config = $this->config;

        if (is_null($key)) {
            return $config;
        }

        if (isset($config[$key])) {
            return $config[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    /**
     * set a config.
     *
     * @author JasonYan <me@yansongda.cn>
     *
     * @param string $key
     * @param array  $value
     */
    public function set(string $key, $value)
    {
        if ($key == '') {
            throw new InvalidArgumentException('Invalid config key.');
        }
        $this->config[$key] = $value;
        return $this->config;
    }
}