<?php
namespace MyNS \  WhiteSpace\OK\ NotOK;

use OtherVendor  \OtherPackage\   BazClass;

class ABC extends \ MyNamespace \ /* comment */ Something \    Other \
    Deeper \ ClassName implements \ MyNamespace \ /* comment */ Something \    Other \
    Deeper \ InterfaceName
{
    public function myFunction(\ MyNS  \Sub\  Deeper \ ClassName $param) : \ MyNS  \Sub\  Deeper \ ClassName
    {
        $a = new \SomeNS \Sub\  ClassName;
        $b = \SomeNS \Sub\  ClassName::$staticVar;
        $c = \SomeNS \Sub\  ClassName::staticMethod();
        $d = \SomeNS \Sub\  ClassName::CLASS_CONSTANT;
        $e = SubNS \ DeeperSub \ NAMESPACED_CONSTANT;
        $f = SubNS \ DeeperSub \ namespaced_function();
    }
}
