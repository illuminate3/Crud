<?php

namespace Illuminate3\Crud;

use Zend\Code\Reflection\FileReflection;
use File;

class Scanner
{
    /**
     * 
     * @param array $directories
     * @return array
     */
    public function scanForControllers(Array $directories)
    {
        $subclass = 'Illuminate3\Crud\CrudController';
        $controllers = array();
        $files = $this->globFolders('*Controller.php', $directories);
        
        foreach($files as $filename) {
            
            require_once $filename;
            $file = new FileReflection($filename);
            $class = $file->getClass();
            
            if($class->isSubclassOf($subclass)) {
                $key = str_replace('\\', '/', $class->getName());
                $controllers[$key] = $class;
            }
        }
        
        return $controllers;
    }
    
    /**
     * 
     * @param type $pattern
     * @param array $folders
     * @return array
     */
    protected function globFolders($pattern, Array $folders)
    {
        $files = array();
        foreach($folders as $folder) {
            $files = array_merge($this->glob($pattern, $folder), $files);
        }
        return $files;
    }

    /**
     * 
     * @param type $pattern
     * @param type $folder
     * @return array
     */
    protected function glob($pattern, $folder)
    {        
        $files = File::glob($folder . '/' . $pattern, GLOB_BRACE);
                
        foreach(File::directories($folder) as $sub) {
            $files = array_merge($this->glob($pattern, $sub), $files);
        }
        
        return $files;
    }

}