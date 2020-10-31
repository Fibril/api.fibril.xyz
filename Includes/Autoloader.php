<?php
spl_autoload_register('Autoloader::autoload');
class Autoloader
{
    public static function autoload($className) 
    {
        // $parts = explode('\\', $className);
        // include_once end($parts) . '.php';
        
        $fullPath = 'Application/' . str_replace('\\', "/", $className) . '.php';
        //$fullPath = 'classes/' . $className . '.php';
        //$fullPath = $className . '.php';

        // echo "<b>Full path: " . $fullPath . "</b><br><br><br>";

        // if (!file_exists($fullPath)) 
        // {
        //     return false;
        // }

        include_once $fullPath;
    }
}
