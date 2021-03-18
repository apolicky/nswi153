<?php

require_once __DIR__ . '/ComponentManager.php';
require_once __DIR__ . '/test_classes.php';

define('CACHE_FILE', __DIR__ . '/.reflection.cache');

function main()
{
    echo "Initializing component manager...\n";
    
    
    $manager = new ComponentManager();

    // if (!file_exists(CACHE_FILE)) {
        // this may be expensive -> that is why we save the annotations in cache file
        // $manager->loadComponentsAnnotations([ 'ComponentA', 'ComponentB', 'ComponentC', 'ComponentD'/*, 'ComponentE' */]);
        $manager->loadComponentsAnnotations([ 'ComponentW', 'ComponentV']);
        // $manager->loadComponentsAnnotations(['ComponentA']);
        $manager->loadComponentsAnnotations(['ComponentM']);
        file_put_contents(CACHE_FILE, $manager->serialize());

    // } else {
    //     $manager->deserialize(file_get_contents(CACHE_FILE));
    // }

    echo "Creating instance of ComponentA...\n";
    // $a = $manager->getInstance('IA');
    // $a = $manager->getInstance('ComponentX');
    $a = $manager->getInstance('ComponentW');
    // $a = $manager->getInstance('tIFCE');
    // $a = $manager->getInstance('ComponentA');
    // $a = $manager->getInstance('ComponentM');

    echo "index.php print_r(\$a);\n";
    print_r($a);

    if (!($a->d instanceof ComponentD)) {
        throw new Exception("ComponentD was not properly injected.");
    }

    if (!($a->d->e instanceof ComponentE)) {
        throw new Exception("ComponentE was not properly injected.");
    }

    echo "Test complete.\n";
}

function test() {
    $holder = [];
    $iiname = "ii";
    $i2name = "i2";
    $iname = "i";

    $ii = new II();

    $holder[$iname] = $ii;
    // $ii->a = 1;
    $holder[$iname]->a = 2;
    
    // print_r($holder[$iname]);
    print_r($ii);
    
}

interface I {

}

class II implements I {
    public $a = 0;
}



main();
// test();
