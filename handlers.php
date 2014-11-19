<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

class Adron {
    static $handler;
    static function registerHandler($name, $class) {
        static::$handler[get_called_class()][$name] = $class;
    }
    static function getHandler($name) {
        $class = get_called_class();
        if (isset(static::$handler[$class][$name])) return static::$handler[$class][$name];
        $parent = get_parent_class($class);
        return $parent ? $parent::getHandler($name) : false;
    }
    static function listHandlers($recursive = false) {
        $class = get_called_class();
        $result = isset(static::$handler[$class]) ? static::$handler[$class] : array();
        if ($recursive) {
            $parent = get_parent_class($class);
            if ($parent) $result = array_merge($parent::listHandlers(true), $result) ;
        }
        return $result;
    }
    /*static function init() {
        //static::$handler = array();
        $tmp = 'x';
        static::$handler =& $tmp; // break reference
        // and now this works as expected: (changes only ClassChild1::$var1)
        static::$handler = array();
    }*/
}
//Adron::init();

class Hudron extends Adron {
    
}
Adron::registerHandler('siska', 'Hudron');

class Boson extends Hudron {
    
}
Hudron::registerHandler('sossage', 'Boson');

class Lepton extends Hudron {
    
}
Hudron::registerHandler('cat', 'Lepton');

class Kripton extends Lepton {
    
}
Lepton::registerHandler('siska', 'Kripton');

var_dump(Hudron::getHandler('shit'));
?>

<pre>
Adron:: <?print_r(Adron::listHandlers());?>
Hudron:: <?print_r(Hudron::listHandlers());?>
Boson:: <?print_r(Boson::listHandlers());?>
Lepton:: <?print_r(Lepton::listHandlers(true));?>
</pre>