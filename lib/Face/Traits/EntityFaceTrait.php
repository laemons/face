<?php

namespace Face\Traits;

use \Face\Core\EntityFaceElement;
use Face\Core\Navigator;

trait EntityFaceTrait {
    
    /**
     * use the given element and use the right way for getting the value on this instance
     * @param \Face\Core\EntityFaceElement $element the element to get
     * @return mixed
     */
    public function faceGetter($needle){
        
        // look the type of $needle then dispatch
        if( is_string($needle) ){
            /*
             * if it is a string the string can be the name of the element or a chain of elements separated by a dot
             * e.g 
             *  - first form : "elementName"
             *  - secnd form : "elementName1.elementName2.elementName3"
             */
            // TODO catch "this.elementName" case for dont instanciate a Navigator not needed for performances
            if(false!==strpos($needle, "."))
                return (new Navigator($needle))->chainGet($this); // "elementName1.elementName2.elementName3" case
            else
                $element=$this->getEntityFace()->getElement($needle); // "elementName" case
            
        }else if(is_a($needle, "\Face\Core\EntityFaceElement"))
            /*  if is already a face element, dont beed anymore work */
            $element=$needle;
        else
            throw new Exception("Variable of type '".gettype($needle)."' is not a valide type for faceGetter");


        // if has a getter, it can be a custom callable annonymous function, or the name of the the method to call on this object
        if($element->hasGetter()){
            
            $getter = $element->getGetter();
            if(is_string($getter)){ //method of this object
                return $this->$getter();
            }else if(is_callable($getter)){ // custom callable
                return $getter();
            }else{
                throw new Exception('Getter is set but it is not usable : '.var_export($getter,true));
            }
        
        // else we use the property directly
        }else{
            
            $property = $element->getPropertyName();
            return $this->$property;
            
        }
        
        // TODO throw exception on no way to get element
    }
    
    
    
    /**
     * use the given element and use the right way for getting the value on this instance
     * @param \Face\Core\EntityFaceElement $element the element to get
     * @return mixed
     */
    public function faceSetter($path,$value){
        
        // look the type of $needle then dispatch
        if( is_string($path) ){
            /*
             * if it is a string the string can be the name of the element or a chain of elements separated by a dot
             * e.g 
             *  - first form : "elementName"
             *  - secnd form : "elementName1.elementName2.elementName3"
             */
            // TODO catch "this.elementName" case for dont instanciate a Navigator not needed for performances
            
            if(false!==strpos($path, ".")){// "elementName1.elementName2.elementName3" case
                (new Navigator($path))->chainSet($this, $value);
                return $value;
            }else{
                $element=$this->getEntityFace()->getElement($path);
            }
            
        }else if(is_a($path, "\Face\Core\EntityFaceElement")){
            /*  if is already a face element, dont need anymore work */
            $element=$path;
        }else
            throw new Exception("Variable of type '".gettype($path)."' is not a valide type for faceSetter");
        
        /* @var $lastElement \Face\Core\EntityFaceElement */
        
        // if has a getter, it can be a custom callable anonymous function, or the name of the the method to call on this object
        if($element->hasSetter()){
            
            $setter = $element->getSetter();
            if(is_string($setter)){ //method of this object
                return $this->$setter($value);
            }else if(is_callable($setter)){ // custom callable
                return $setter($value);
            }else{
                throw new Exception('Setter is set but it is not usable : '.var_export($setter,true));
            }
        
        // else we use the property directly
        }else{
            
            $property = $element->getPropertyName();
            $this->$property=$value;
            
        }
        // TODO chainSet in Navigator instead than in this trait
        // TODO throw exception on "no way to get element"

    }
    
    
    
    /**
     * 
     * @return \Face\Core\EntityFace
     */
    public static function getEntityFace(){
        return \Face\Core\FacePool::getFace(__CLASS__);
    }
    
    abstract public static function __getEntityFace();
    
}

?>