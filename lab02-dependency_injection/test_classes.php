<?php

class ComponentBase
{
    private static $instances = [];

    public function __construct()
    {
        $className = get_class($this);
        // echo "!!!$className.__construct() called\n";
        if (array_key_exists($className, self::$instances)) {
            echo "\n________________________\n";
            echo "trying to create object $className\n";
            echo "instances contains: \n";
            print_r(self::$instances);
            throw new Exception("Component $className is created multiple times.");
        }
        self::$instances[$className] = $this;
    }
}

interface IA
{
    public function foo();
}

class ComponentA extends ComponentBase implements IA
{
    private $b;
    private $c;

    /**
     * @inject ComponentD
     */
    public $d;

    public function __construct(ComponentB $b, ComponentC $c)
    {
        parent::__construct();
        $this->b = $b;
        $this->c = $c;
    }

    public function foo()
    {
        echo "foo";
    }
}

class ComponentB extends ComponentBase
{
    private $c;

    public function __construct(ComponentC $c) //, ComponentA $a)
    {
        parent::__construct();
        $this->c = $c;
    }
}

class ComponentC extends ComponentBase
{
}

class ComponentD extends ComponentBase
{
    /**
     * @inject ComponentX
     * this should be ignored since $x is static
     */
    public static $x = null;

    /**
     * @inject ComponentE
     */
    public $e;
}

class ComponentE extends ComponentBase
{
    /**
     * @inject ComponentC
     * this injection should be ignored, since $c is private
     */
    private $c;

    /**
     * @inject ComponentA
     */
    public $a;

    public function __construct(ComponentC $c)
    {
        parent::__construct();
        $this->c = $c;
    }
}


class ComponentZ extends ComponentBase
{
    /**
     * @inject ComponentX
     */
    public $x;
    private $y;

    public function __construct(ComponentY $y) //, ComponentA $a)
    {
        parent::__construct();
        $this->y = $y;
    }
}


class ComponentY extends ComponentBase
{
    /**
     * @inject ComponentX
     */
    public $x;

    /**
     * @inject ComponentZ
     */
    public $z;
}

class ComponentX extends ComponentBase
{
    /**
     * @inject ComponentY
     */
    public $y;

    /**
     * @inject ComponentZ
     */
    public $z;
}


interface tIFCE {
    public function foo();
}

interface nIFCE {
    
}

class ComponentT1 extends ComponentBase implements tIFCE {
    /**
     * @inject ComponentU
     */
    public $u;

    /**
     * @inject ComponentW
     */
    public $w;

    /**
     * @inject ComponentS
     */
    public $s;

    /**
     * @inject nIFCE
     */
    public $ne;

    public function foo() {
        echo "foo";
    }
}

// class ComponentT2 extends ComponentBase implements tIFCE {
//     public function foo() {
//         echo "foo";
//     }
// }

class ComponentU extends ComponentBase
{
    private $t;

    /**
     * @inject ComponentW
     */
    public $w;

    public function __construct(tIFCE $t) //, ComponentA $a)
    {
        parent::__construct();
        $this->t = $t;
    }
}

class ComponentV extends ComponentBase
{
    private $t;
    private $u;

    /**
     * @inject ComponentW
     */
    public $w;

    public function __construct(tIFCE $t, ComponentU $u) //, ComponentA $a)
    {
        parent::__construct();
        $this->t = $t;
        $this->u = $u;
    }
}

class ComponentW extends ComponentBase
{
    private $ti;
    private $u;
    private $v;

    public function __construct(tIFCE $t, ComponentU $u, ComponentV $v)
    {
        parent::__construct();
        $this->ti = $t;
        $this->u = $u;
        $this->v = $v;
    }
}

class ComponentS extends ComponentBase
{
    private $v;
    private $u;

    /**
     * @inject ComponentR
     */
    public $r;

    public function __construct(ComponentV $v, ComponentU $u) //, ComponentA $a)
    {
        parent::__construct();
        $this->v = $v;
        $this->u = $u;
    }
}

class ComponentR extends ComponentBase
{
}