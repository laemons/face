<?php

namespace Face\Sql\Query\Clause;


use Face\Sql\Query\Clause\Where\AbstractWhereClause;
use Face\Sql\Query\QueryInterface;

class Where implements SqlClauseInterface {

    /**
     * @var AbstractWhereClause
     */
    protected $where;

    function __construct(AbstractWhereClause $where)
    {
        $this->where = $where;
    }

    /**
     * @inheritdoc
     */
    public function getSqlString(QueryInterface $q)
    {

        $w = $this->where->getSqlString($q);

        if (empty($w)) {
            return "";
        }

        return "WHERE " . $w;

    }

}
