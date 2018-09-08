<?php

namespace MyNS \  WhiteSpace\OK\ NotOK;

use OtherVendor  \OtherPackage\   BazClass;
use My\Full\Classname as Another, My\Full\NSname;

use function My\Full\functionName as func;

use const My\Full\CONSTANT;

use some\namespace\{ClassA, ClassB, ClassC as C};
use function some\namespace\{fn_a, fn_b, fn_c};
use const some\namespace\{ConstA, ConstB, ConstC};


class ABC extends \ MyNamespace \ /* comment */ Something \    Other \
    Deeper \ ClassName
{
    public function myFunction(\ MyNS  \Sub\  Deeper \ ClassName $param) : \ MyNS  \Sub\  Deeper \ ClassName
    {
        $a = new \SomeNS \Sub\  ClassName;
        $b = \SomeNS \Sub\  ClassName :: $staticVar;
        $c = \SomeNS \Sub\  ClassName :: staticMethod();
        $d = \SomeNS \Sub\  ClassName :: CLASS_CONSTANT;
        $e = SubNS \ DeeperSub \ NAMESPACED_CONSTANT;
        $f = SubNS \ DeeperSub \ namespaced_function();
    }
}
