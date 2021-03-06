<?php

namespace Face\Sql\Query;

use Face\Config;
use Face\ContextAwareInterface;
use Face\Core\EntityFace;
use Face\Exception\BadParameterException;
use Face\Exception\QueryFailedException;
use Face\Sql\Query\Clause\Select\Column;
use Face\Sql\Query\SelectBuilder\QueryFace;
use Face\Util\StringUtils;

abstract class FQuery implements QueryInterface
{


    // when we generate sql queries
    // we have to replace the face navigation token "." by an other
    // Because "." is not compatible with alias in sql and we want to avoid conflicts with user table / column names
    // then ___ is safe enough
    public static $DOT_TOKEN = ".";

    // alias that can be replaced from an instance
    protected $dotToken;


    /**
     * @var QueryFace
     */
    protected $fromQueryFace;


    /**
     * list of binds to pass to pdo object
     * @var array
     */
    protected $valueBinds;

    function __construct(EntityFace $baseFace)
    {
        $this->fromQueryFace = new QueryFace("this", $baseFace, $this);
        $this->valueBinds=[];
    }


    /**
     * replaces the waved string by the sql-valid column name
     * @param $string
     */
    public function parseColumnNames($string, ContextAwareInterface $context = null)
    {
        $matchArray = [];
        preg_match_all("#~([a-zA-Z0-9_]\\.{0,1})+#", $string, $matchArray);
        $matchArray = array_unique($matchArray[0]);

        foreach ($matchArray as $match) {
            if($context){
                $nsMatch = $context->getNameInContext($match);
            }else{
                $nsMatch = $match;
            }

            $path=ltrim($nsMatch, "~");

            $tablePath = rtrim(substr($nsMatch, 1, strrpos($nsMatch, ".")), ".");

            $replace= $this->_doFQLTableName($tablePath, null, true)
                . "."
                . $this->getBaseFace()
                    ->getElement($path)
                    ->getSqlColumnName(true);

            $string = str_replace($match, $replace, $string);
        }

        return $string;
    }


    /**
     * @inheritdoc
     */
    public function getBaseQueryFace(){
        return $this->fromQueryFace;
    }


    /**
     * Executes the query from the given pdo object
     * @param \PDO|Config|null $pdo
     * @return \PDOStatement the pdo statement ready to fetch
     */
    public function execute($config = null)
    {

        if(null == $config){
            $pdo = Config::getDefault()->getPdo();
        }else if($config instanceof \PDO){
            $pdo = $config;
        }else if($config instanceof Config){
            $pdo = $config->getPdo();
        }else{
            throw new BadParameterException('First parameter of FQuery::execute is not correct');
        }

        $stmt = $this->getPdoStatement($pdo);

        if ($stmt->execute()) {
            return $stmt;

        } else {
            // TODO : handle errors ".__FILE__.":".__LINE__;
            throw new QueryFailedException($stmt);
            //return false;
        }

    }

    /**
     * get a statement for the given pdo object, values are already bound
     * @param \PDO $pdo
     */
    public function getPdoStatement(\PDO $pdo)
    {
        $stmt = $pdo->prepare($this->getSqlString());

        $bound = $this->getBoundValues();

        foreach ($bound as $name => $bind) {
            $stmt->bindValue($name, $bind[0], $bind[1]);
        }

        return $stmt;
    }

    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {
        $this->valueBinds[$parameter] = [$value,$data_type];

        return $this;
    }


    /**
     * @param $name
     * @return array
     */
    public function getBoundValue($name)
    {
        return $this->getBoundValues()[$name];
    }

    /**
     * @param $name
     * @return array
     */
    public function getBoundValues()
    {
        return $this->valueBinds;
    }

    /**
     * gives all the entities which are part of the FQuery
     * @return QueryFace[] list of the face
     */
    public function getAvailableQueryFaces()
    {
        return ["this" => $this->fromQueryFace];
    }

    /**
     * convention for having the same table alias every where. E.G  "a.b" will become "this__dot_a__dot__b"
     * @param string $path
     * @param string $token the token to use for separate elements of the path. Default  $this->getDotToken() will be used
     * @return string
     */
    public function _doFQLTableName($path, $token = null, $escape = false)
    {

        if (null === $token) {
            $token = $this->dotToken;
        }

        return self::__doFQLTableNameStatic($path, $token, $escape);
    }

    /**
     * reserved for internal usage
     * @param $path
     * @param null $token
     * @return mixed|string
     */
    public static function __doFQLTableNameStatic($path, $token = null, $escape = false)
    {
        if (null===$token) {
            $token=self::$DOT_TOKEN;
        }

        if ("this"===$path || empty($path)) {
            if($escape){
                return "`this`";
            }else{
                return "this";
            }
        }

        if ( ! StringUtils::beginsWith("this.", $path)) {
            // if doesn't begin with "this." then we prepend "this."
            $path = "this.".$path;
        }

        $name = str_replace(".", $token, $path);

        if($escape){
            return "`$name`";
        }else{
            return $name;
        }
    }


    /**
     *
     * @return EntityFace
     */
    public function getBaseFace()
    {
        return $this->fromQueryFace->getFace();
    }
}
