<?php

class Config
{
    public static function get($section, $key)
    {
        $config = parse_ini_file('../fibril.xyz.ini', TRUE);

        if ($config === false)
            throw new Exception('Failed to read the config file.');

        return $config[$section][$key];
    }
}
