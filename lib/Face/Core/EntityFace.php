<?php

namespace Face\Core;

use Face\Exception\BadParameterException;
use Face\Exception\FaceElementDoesntExistsException;
use Face\Util\ArrayUtils;
use Face\Util\StringUtils;

class EntityFace implements \IteratorAggregate
{
    private $elements;
    private $identifiers;
    

    private $name;
    private $class;
    
    // SQL
    protected $sqlTable;
    private $primaries;

    protected $faceLoader;

    /**
     *
     * @param array $params array to construct the face is described here :  TODO array description
     */
    function __construct($params = array(), FaceLoader $faceLoader = null)
    {
        $this->faceLoader = $faceLoader;

        $this->elements = array();
        $this->primaries = array();
        $this->relatedTable = array();
        
        $this->class = ArrayUtils::getIfArrayKey($params, "class");
        $this->name = ArrayUtils::getIfArrayKey($params, "name");;

        if (isset($params['elements'])) {
            foreach ($params['elements'] as $k => $elmParams) {
                if (is_numeric($k)) {
                    $element = new EntityFaceElement($elmParams, []);
                } else {
                    $element = new EntityFaceElement($k, $elmParams);
                }
                $this->addElement($element);
            }
        }
        if (isset($params["sqlTable"])) {
            $this->sqlTable = $params["sqlTable"];
        } else {
            $this->sqlTable = strtolower($this->getClass());
        }
        
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return FaceLoader
     */
    public function getFaceLoader()
    {
        return $this->faceLoader;
    }


    public function getClass()
    {
        return $this->class;
    }

    public function setClass($class)
    {
        $this->class = $class;
    }

    public function addElement(EntityFaceElement $element)
    {
        $this->elements[$element->getName()]=$element;
        
        if ($element->isPrimary()) {
            $this->primaries[]=$element;
        }
        
        if ($element->isIdentifier()) {
            $this->identifiers[]=$element;
        }
        
      
        
        $element->setParentFace($this);
        
    }
    

    /**
     *  get the element in this element with the given name
     * @param string $name name of the element to get
     * @param int $offset allows to jumps some elements
     * @param array $pieceOfPath if given will be filled with in [0] the base path and in [1] the last element
     * @return EntityFaceElement the EntityFaceElement with the given name
     * @throws \Exception
     * @throws \Face\Exception\RootFaceReachedException
     */
    public function getElement($name, $offset = null, &$pieceOfPath = null)
    {
        
        if (StringUtils::beginsWith("this.", $name)) {
            $name = substr($name, 5);
        }
        
        if (null!==$offset) {
            if ($offset<0) {
                throw new BadParameterException("\$offset can't be negative. ".$offset." given");
            }

            $lastPath="";
            while ($offset>0) {
                $lastDot= strrpos($name, ".");
                $lastPath=substr($name, $lastDot+1).".".$lastPath;
                $name=substr($name, 0, $lastDot);
                $offset--;
            }

            if (""===$name) {
                throw new \Face\Exception\RootFaceReachedException("Offset was too deep and reached root face");
            }


            $lastPath=rtrim($lastPath, ".");
            $pieceOfPath[0]=$name;
            $pieceOfPath[1]=$lastPath;


        }
        
        
        if (false!==strpos($name, ".")) {
            $firstChildFace = $this->getElement(strstr($name, ".", true))->getFace();
            return $firstChildFace->getElement(trim(strstr($name, "."), "."));
        }
        
        if (!isset($this->elements[$name])) {

            throw new FaceElementDoesntExistsException($name, $this);
        }


        return $this->elements[$name];
    }

    /**
     * @return EntityFaceElement[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    public function setElements($elements)
    {
        $this->elements = $elements;
    }

    /**
     * @return EntityFaceElement[]
     */
    public function getPrimaries()
    {
        return $this->primaries;
    }

    public function setPrimaries($primaries)
    {
        $this->primaries = $primaries;
    }

    
    public function getIdentifiers()
    {
        return $this->identifiers;
    }
    

    public function getIterator()
    {
        return new \ArrayIterator($this->elements);
    }

    public function getSqlTable($escape = false)
    {
        if($escape) {
            return '`' . $this->sqlTable . '`';
        } else {
            return $this->sqlTable;
        }
    }

    public function setSqlTable($sqltable)
    {
        $this->sqlTable = $sqltable;
    }

    /**
     * @param string $node the elemtn we want to get
     * @return EntityFaceElement
     */
    public function getDirectElement($node)
    {
        
        if (isset($this->elements[$node])) {
            return $this->elements[$node];
        }
        
        throw new \Exception("Element '$node' doesnt exist in face '".$this->getName() ."'");
    }
}
