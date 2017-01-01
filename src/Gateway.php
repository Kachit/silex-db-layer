<?php
/**
 * Created by PhpStorm.
 * User: Kachit
 * Date: 05.02.2016
 * Time: 1:10
 */
namespace Kachit\Database;

use Kachit\Database\Meta\Table;
use Kachit\Database\Query\Builder;
use Kachit\Database\Query\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

abstract class Gateway implements GatewayInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Table
     */
    private $metaTable;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * Gateway constructor
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @param Filter|null $filter
     * @return array
     */
    public function fetchAll(Filter $filter = null)
    {
        $queryBuilder = $this->createQueryBuilder();
        $this->getBuilder()->build($queryBuilder, $filter);
        return $queryBuilder
            ->execute()
            ->fetchAll()
        ;
    }

    /**
     * @param Filter|null $filter
     * @return array
     */
    public function fetch(Filter $filter = null)
    {
        $queryBuilder = $this->createQueryBuilder();
        $this->getBuilder()->build($queryBuilder, $filter);
        return $queryBuilder
            ->execute()
            ->fetch()
        ;
    }

    /**
     * @param mixed $pk
     * @return array
     */
    public function fetchByPk($pk)
    {
        $filter = $this->buildPrimaryKeyFilter($pk);
        return $this->fetch($filter);
    }

    /**
     * @param string $column
     * @param Filter|null $filter
     * @return string
     */
    public function fetchColumn($column, Filter $filter = null)
    {
        $queryBuilder = $this->createQueryBuilder();
        $this->getBuilder()->build($queryBuilder, $filter, true);
        return $queryBuilder
            ->resetQueryPart('select')
            ->select($column)
            ->execute()
            ->fetchColumn()
        ;
    }

    /**
     * @param Filter|null $filter
     * @return bool|string
     */
    public function count(Filter $filter = null)
    {
        $fieldCount = ($filter->getFieldCount()) ? $filter->getFieldCount() : $this->metaTable->getPrimaryKey();
        $fieldCount = $this->getTableAlias() . '.' . $fieldCount;
        $count = 'COUNT(' . $fieldCount . ')';
        return $this->fetchColumn($count, $filter);
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->getConnection()
            ->createQueryBuilder()
            ->from($this->getTableName(), $this->getTableAlias())
            ->select($this->getTableAlias() . '.*')
        ;
    }

    /**
     * @param array $data
     * @return int
     */
    public function insert(array $data)
    {
        $row = array_merge($this->getMetaTable()->getDefaultRow(), $data);
        if (isset($row[$this->getMetaTable()->getPrimaryKey()])) {
            unset($row[$this->getMetaTable()->getPrimaryKey()]);
        }
        $result = $this->getConnection()->insert($this->getTableName(), $row);
        return ($result) ? $this->getConnection()->lastInsertId() : $result;
    }

    /**
     * @param array $data
     * @param Filter $filter
     * @return int
     */
    public function update(array $data, Filter $filter = null)
    {
        $queryBuilder = $this->createQueryBuilder();
        $this->builder->build($queryBuilder, $filter);
        $queryBuilder
            ->resetQueryPart('select')
            ->resetQueryPart('orderBy')
            ->update($this->getTableName(), $this->getTableAlias())
        ;
        foreach ($data as $column => $value)
        {
            $queryBuilder->set($column, $value);
        }
        return $queryBuilder->execute();
    }

    /**
     * @param array $data
     * @param mixed $pk
     * @return int
     */
    public function updateByPk(array $data, $pk)
    {
        $filter = $this->buildPrimaryKeyFilter($pk);
        return $this->update($data, $filter);
    }

    /**
     * @param Filter $filter
     * @return int
     */
    public function delete(Filter $filter = null)
    {
        $queryBuilder = $this->createQueryBuilder();
        $this->getBuilder()->build($queryBuilder, $filter);
        $queryBuilder
            ->resetQueryPart('select')
            ->resetQueryPart('orderBy')
            ->delete($this->getTableName(), $this->getTableAlias())
        ;
        return $queryBuilder->execute();
    }

    /**
     * @param mixed $pk
     * @return int
     */
    public function deleteByPk($pk)
    {
        $filter = $this->buildPrimaryKeyFilter($pk);
        return $this->delete($filter);
    }

    /**
     * @return string
     */
    abstract protected function getTableName();

    /**
     * @return string
     */
    protected function getTableAlias()
    {
        return 't';
    }

    /**
     * @param mixed $pk
     * @return Filter
     */
    protected function buildPrimaryKeyFilter($pk)
    {
        $filter = (new Filter())->createCondition($this->getMetaTable()->getPrimaryKey(), $pk);
        return $filter;
    }

    /**
     * @return Table
     */
    protected function getMetaTable()
    {
        if (empty($this->metaTable)) {
            $this->metaTable = new Table($this->getConnection(), $this->getTableName());
            $this->metaTable->initialize();
        }
        return $this->metaTable;
    }

    /**
     * @return Builder
     */
    protected function getBuilder()
    {
        if (empty($this->builder)) {
            $this->builder = new Builder($this->getMetaTable()->getColumns(), $this->getTableAlias());
        }
        return $this->builder;
    }
}