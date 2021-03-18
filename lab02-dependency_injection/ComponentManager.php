<?php

/**
 * TODO
 * pole kde jsou ulozence instance dostupnych trid
 * rozdelit getInstance do vice casti
 *      class/ifce exists
 *      instance exists
 *          return it
 *      instance must be created
 *          check cycles
 *              throw
 *          create everthing needed
 *          save it
 *          return it
 * 
 * rozdel load instance?
 * 
 * 
 * 
 */




/**
 * Component that manages all other components.
 * Takes care of component instantiation and resolves dependency injection.
 */
class ComponentManager
{

    private $component_holder = [];

    // $implements_interface[interfaceName] = componentName[];
    private $implements_interface = []; 
    private $known_components = [];
    private $constructor_dependencies = [];
    private $property_dependencies = [];
    private $can_be_created = [];

    private $property_dependencies_tbd = [];
    private $newly_constructed = [];


    // ------------------------------ METADATA ----------------------------------------------------

    /**
     * Load metadata (annotations) of all known components. Given class names act like initial seeds,
     * the algorithm should incorporate all reachable components names as known names.
     * It should also keep list of known interfaces and which components implement them.
     * Loaded metadata should be stored in member variables only.
     * @param string[] $classNames List of names of classes of known components.
     */
    public function loadComponentsAnnotations(array $classNames): void
    {
        $scanned = $classNames;
        $this->scan_classes($scanned);

        // at least one not known component found -> scan it.
        while ($this->known_components != $scanned) {
            $to_scan = array_diff($this->known_components, $scanned);
            $scanned = array_unique(array_merge($scanned, $to_scan));

            // just to be sure
            if(count($to_scan) === 0) {
                break;
            }

            $this->scan_classes($to_scan);
        }

        $this->check_dependency_cycles();

        if (0) {
            echo "interfaces implemented by";
            print_r($this->implements_interface);
            
            echo "known components";
            print_r($this->known_components);

            echo "constructor dependencies";
            print_r($this->constructor_dependencies);

            echo "property dependencies";
            print_r($this->property_dependencies);

            echo "can be created";
            print_r($this->can_be_created);    
        }
        
    }

    private function scan_classes(array $classNames) : void {
        $decl_classes = get_declared_classes();
        
        foreach($classNames as $i) {            
            
            if (interface_exists($i)) {
                // filter classes implementing interface $i
                foreach($decl_classes as $c) {
                    if (in_array($i, class_implements($c))) {
                        // these classes will be scanned in the next iteration of scan_classes(...)
                        $this->known_components[] = $c;
                    }
                }
            }
            else if(class_exists($i)) {
                $this->known_components[] = $i;
            
                $impl_ifaces = class_implements($i);
                
                if (count($impl_ifaces)) {
                    foreach($impl_ifaces as $j) {
                        $this->implements_interface[$j][] = $i;
                    }
                }

                $reflection_class = new ReflectionClass($i);

                $this->check_constructor_dependencies($i, $reflection_class);
                $this->check_property_dependencies($i, $reflection_class);
            }
            else {
                throw new \Exception("Class $i does not exist");
            }
        }

        $this->known_components = array_unique($this->known_components);
    }

    // --------------------- DEPENDENCY CHECKING --------------------------------------------------

    private function check_constructor_dependencies($cls_name, $refl_class) {
        // constructor dependencies
        $constructor = $refl_class->getConstructor();            
        if ($constructor) {
            foreach(($constructor->getParameters()) as $j) {
                $jn = $j->getClass()->name;
                $this->constructor_dependencies[$cls_name][] = $jn;
                $this->known_components[] = $jn;
            }
        }
    }

    private function check_property_dependencies($cls_name, $refl_class) {
        // property dependencies
        foreach($refl_class->getProperties() as $j) {
            if (!$j->isStatic() && $j->isPublic()) {
                $doc = $j->getDocComment();

                if($doc) {
                    /*borrowed from https://www.php.net/manual/en/reflectionclass.getdoccomment.php */
                    //define the regular expression pattern to use for string matching
                    $pattern = "#(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#";
                    $pattern2 = "#(@inject\s+[a-zA-Z0-9,()_]+)#";

                    //perform the regular expression on the string provided
                    preg_match_all($pattern2, $doc, $matches, PREG_PATTERN_ORDER);

                    // echo "matches of regex:\n";
                    // print_r($matches);

                    if (count($matches[0]) === 1) {
                        $annot = explode(" ",$matches[0][0]);
                        if(count($annot) === 2) {
                            $annot_comp = end($annot);
                            $this->property_dependencies[$cls_name][$j->getName()] = $annot_comp;
                            $this->known_components[] = $annot_comp;
                        }                            
                    }
                    else {
                        throw new \Exception("more @inject annotations than 1");
                    }
                }                    
            }
        }
    }

    // ---------------------------- CYCLE DETECTION FUNCTIONS -------------------------------------

    /**
     * Checks whether there are any dependency cycles
     */
    private function check_dependency_cycles(): void {
        foreach($this->known_components as $i) {
            $this->can_be_created[$i] = ($this->in_cycle($i))? 0 : 1;
        }
    }

    private function in_cycle(string $comp_name) : bool {
        $vis = [];
        $rec_stack = [];
        return $this->dfs_cycle($comp_name, $vis, $rec_stack);
    }

    private function dfs_cycle($node, $visited, $stack): bool {
        $visited[] = $node;
        $stack[] = $node;
        
        if(array_key_exists($node, $this->constructor_dependencies)) {
            foreach($this->constructor_dependencies[$node] as $neighbour) {
                if (!in_array($neighbour, $visited)) {
                    if ($this->dfs_cycle($neighbour, $visited, $stack)) {
                        return true;
                    }
                } else if (in_array($neighbour,$stack)) {
                    return true;
                }
            }
        }
        
        array_pop($stack);
        return false;
    }


    // ---------------------------------------- CACHING -------------------------------------------

    /**
     * Create a string that represent complete internal state (all loaded metadata of components).
     * Such string may be persisted (into file or database).
     * @return string serialized manager state
     */
    public function serialize(): string
    {
        $ser = array(
            $this->implements_interface, 
            $this->known_components,
            $this->constructor_dependencies,
            $this->property_dependencies,
            $this->can_be_created );
        return serialize($ser);
    }

    /**
     * Load internal state from serialized string. Deserialization overwrites any previously loaded metadata.
     * @param string $data string which was produced by serialization method
     * @throws Exception if the input string is not valid
     */
    public function deserialize(string $data): void
    {
        $ser = unserialize($data);
        if (count($ser) === 5) {
            $this->implements_interface = $ser[0];
            $this->known_components = $ser[1];
            $this->constructor_dependencies = $ser[2];
            $this->property_dependencies = $ser[3];
            $this->can_be_created = $ser[4];
        }
        else {
            throw new \Exception("input string for deserialization not valid");
        }
    }

    // --------------------------------- INSTANTIATION --------------------------------------------

    /**
     * Creates and returns instance of given component. Each component is a singleton, internal
     * cache must be implemented to avoid creating the same component multiple times.
     * If interface is given and multiple classes implement the interface, exception is thrown (ambiguous situation).
     * @param stirng $classOrInterface Name of the class or interface of the desired component.
     * @return mixed instance of a component object
     * @throws Exception if the component does not exist or cannot be created
     *                   (unavoidable cyclic dependency, ambiguous interface, ...)
     */
    public function getInstance(string $classOrInterface)
    {
        return $this->getInstance2($classOrInterface);
        
        if (0) {
            // $classOrInterface is an interface
            if (interface_exists($classOrInterface)) {
                
                // $classOrInterface is an interface that we know
                if (count($this->implements_interface) &&
                    array_key_exists($classOrInterface, $this->implements_interface)) {

                    $instances = $this->implements_interface[$classOrInterface];
                    if(count($instances) === 1) {
                        $classOrInterface = $instances[0];
                    }
                    else {
                        throw new \Exception("Multiple classes implement interface $classOrInterface.");
                    }
                }
                // interface not known to us
                else {
                    print_r($this->implements_interface);
                    print_r($this->known_components);
                    throw new \Exception("Unknown interface $classOrInterface. No existing implementation.");
                }
            }
            // else it should be a class
            
            if(array_key_exists($classOrInterface, $this->can_be_created)) {
                if ($this->can_be_created[$classOrInterface] !== 1) {
                    throw new \Exception("Class $classOrInterface in cyclic dependency.");
                }
            }
            else {
                throw new \Exception("Unknown class $classOrInterface.");
            }
            


            // $classOrInterface already created -> return it
            if (array_key_exists($classOrInterface, $this->component_holder)) {
                return $this->component_holder[$classOrInterface];
            } 
            // $classOrInterface must be created. (It should be possible)
            else {

                $cls_instance = $this->create_component($classOrInterface);
                $this->inject_prop_dependencies($classOrInterface, $cls_instance);

                return $cls_instance;
            }
        }
    }

    /**
     * Creates and saves component $new_class into $this->component_holedr
     */
    private function create_component($new_class) {
        $params = [];
        if(array_key_exists($new_class, $this->constructor_dependencies)) {
            foreach($this->constructor_dependencies[$new_class] as $k) {
                $params[] = $this->getInstance($k);
            }
        }
        
        $ret = new $new_class(...$params);

        $this->component_holder[$new_class] = $ret;
        
        return $ret;
    }

    private function inject_prop_dependencies(string $nme, $cls) : void {
        // echo "inject props for $nme called\n";
        $props = [];
        if(array_key_exists($nme, $this->property_dependencies)) {
            
            foreach($this->property_dependencies[$nme] as $name => $prop) {
                $props[$name] = $this->getInstance($prop);
                // $cls->$name = $prop;
            }
        }

        foreach($props as $name => $prop) {
            $cls->$name = $prop;
        }
    }

    //---------------------------- INSTANTIATION 2 ------------------------------------------------

    public function getInstance2(string $classOrInterface, bool $inside = false) {
        // $classOrInterface is an interface
        if (interface_exists($classOrInterface)) {
            
            // $classOrInterface is an interface that we know
            if (count($this->implements_interface) &&
                array_key_exists($classOrInterface, $this->implements_interface)) {

                $instances = $this->implements_interface[$classOrInterface];
                if(count($instances) === 1) {
                    $classOrInterface = $instances[0];
                }
                else {
                    // echo "-------------------------\n";
                    // print_r($instances);
                    throw new \Exception("Multiple classes implement interface $classOrInterface.");
                }
            }
            // interface not known to us
            else {
                throw new \Exception("Unknown interface $classOrInterface. No existing implementation.");
            }
        }
        // else it should be a class
        
        if(array_key_exists($classOrInterface, $this->can_be_created)) {
            if ($this->can_be_created[$classOrInterface] !== 1) {
                throw new \Exception("Class $classOrInterface in cyclic dependency.");
            }
        }
        else {
            throw new \Exception("Unknown class $classOrInterface.");
        }
    

        // $classOrInterface already created -> return it
        if (array_key_exists($classOrInterface, $this->component_holder)) {
            return $this->component_holder[$classOrInterface];
        } 
        // $classOrInterface must be created. (It should be possible)
        else {
            // will there be a problem in the next phase?
            if ($this->tbd_injection_problem($classOrInterface)){
                // print_r($this->component_holder);
                throw new \Exception("Byl by problem c11. Wanted instance: $classOrInterface\n");
            }

            $cls_instance = $this->create_component2($classOrInterface);

            if ($inside === false) {
                while(count($this->newly_constructed)) {
                    $i = array_pop($this->newly_constructed);
                    $this->inject_prop_dependencies2($i);
                }
                $this->newly_constructed = [];
            }

            return $cls_instance;
        }
    }


    private function create_component2(string $new_class) {
        $params = [];

        if(array_key_exists($new_class, $this->constructor_dependencies)) {
            foreach($this->constructor_dependencies[$new_class] as $k) {
                $params[] = $this->getInstance2($k, true);
            }
        }
        
        $ret = new $new_class(...$params);

        $this->newly_constructed[] = $new_class;

        $this->component_holder[$new_class] = $ret;
        
        return $ret;
    }

    private function inject_prop_dependencies2(string $class_nme) : void {
        $props = [];

        if(array_key_exists($class_nme, $this->property_dependencies)) {
            foreach($this->property_dependencies[$class_nme] as $prop_name => $prop) {
                $props[$prop_name] = $this->getInstance2($prop, true);
            }
        }

        foreach($props as $prop_name => $prop) {
            $this->component_holder[$class_nme]->$prop_name = $prop;
        }
    }

    private function tbd_injection_problem(string $class_nme) : bool {
        if(array_key_exists($class_nme, $this->property_dependencies)) {
            foreach($this->property_dependencies[$class_nme] as $prop_name => $prop) {
                // $prop is an interface
                if(interface_exists($prop)) {
                    if (count($this->implements_interface) &&
                        array_key_exists($prop, $this->implements_interface)) {

                        $instances = $this->implements_interface[$prop];
                        if(count($instances) !== 1) {
                            // echo "PROBLEM $class_nme: ";
                            // echo "number of instances of interface $prop !== 1\n";
                            return true;
                        }
                    }
                    // interface not known to us
                    else {
                        // echo "PROBLEM $class_nme: ";
                        // echo "existing ifce $prop but not implemented?\n";
                        return true;
                    }
                }
            }
        }
        return false;
    }

}


