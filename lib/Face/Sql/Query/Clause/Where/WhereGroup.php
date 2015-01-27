<?php

namespace Face\Sql\Query\Clause\Where;

use Face\Sql\Query\Clause\Where;
use Face\Sql\Query\FQuery;

class WhereGroup extends Where {

    /**
     * @var Where[]
     */
    protected $whereList = [];

    public function getSqlString(FQuery $q){

        if(count($this->whereList) == 0 ){
            return "";
        }

        $str = "(";
        foreach ($this->whereList as $k=>$w) {
            $where = $w[0];
            $logic = $w[1] === "OR" ? "OR" : "AND";

            if($k !== 0){
                $str .= " " . $logic . " ";
            }

            $str .= $where->getSqlString($q);
        }


        return $str . ")";
    }

    public function addWhere(Where $where,$logic=null){
        $this->whereList[] = [$where,$logic];
    }
}