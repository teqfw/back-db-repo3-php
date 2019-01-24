<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2019
 */

namespace TeqFw\Lib\Db\Repo3\Api\Dao;
/**
 * Default implementation for entity repository to do universal operations with specific entity data (CRUD).
 *
 */
class Entity
    implements \TeqFw\Lib\Db\Api\Dao\Entity
{

    /**
     * Descendants should define these constants:
     *
     *     const ENTITY_CLASS = Entity::class;
     *     const ENTITY_PK = [Entity::ID];
     *     const ENTITY_NAME = 'entity_table_name';
     */

    /** @var \TeqFw\Lib\Db\Api\Connection\Query */
    private $connQuery;
    /** @var \TeqFw\Lib\Db\Api\Connection\Schema */
    private $connSchema;

    public function __construct(
        \TeqFw\Lib\Db\Api\Connection\Query $connQuery,
        \TeqFw\Lib\Db\Api\Connection\Schema $connSchema
    ) {
        $this->connQuery = $connQuery;
        $this->connSchema = $connSchema;
    }

    /**
     * @param array|null $data
     * @return \TeqFw\Lib\Data
     */
    private function composeEntity($data = null)
    {
        $class = static::ENTITY_CLASS;
        /** @var \TeqFw\Lib\Data $result */
        $result = new $class();
        if ($data) {
            $result->setData($data);
        }
        return $result;
    }

    public function create($data)
    {
        $result = null;
        $conn = $this->connSchema;
        $tbl = $this->getTableName();
        if ($data instanceof \TeqFw\Lib\Data) {
            $bind = (array)$data->getData();
        } elseif (is_array($data)) {
            $bind = $data;
        } else {
            $bind = $data;
        }
        $rowsAdded = $conn->insert($tbl, $bind);
        if ($rowsAdded) {
            /* TODO: validate case for composite PK */
            $result = $conn->lastInsertId($tbl);
        }
        return $result;

    }

    public function deleteOne($pk)
    {
        $conn = $this->connQuery->getConnection();
        /* prepare primary key */
        if (
            is_array($pk) ||
            ($pk instanceof \TeqFw\Lib\Data)
        ) {
            /* extract PK only if whole entity was given */
            $pk = $this->getPkFromData($pk);
        } else {
            /* probably PK contains one attribute only */
            $attrName = $this->getPkFirstAttr();
            $pk = [$attrName => $pk];
        }
        $tbl = $this->getTableName();

        /* prepare where conditions */
        $where = [];
        foreach ($pk as $field => $value) {
            /* this is primary key, compose 'where' filter */
            $qField = $conn->quoteIdentifier($field);
            $qValue = $conn->quote($value);
            $where[] = "$qField=$qValue";
        }

        $result = $conn->delete($tbl, $where);
        return $result;
    }

    public function deleteSet($where)
    {
        $result = null;
        /* TODO: implement entity deletion by clause */
        return $result;
    }

    public function getOne($key)
    {
        $result = null;
        if (is_array($key)) {
            /* probably this is complex PK */
            $key = $key;
        } else {
            $idFieldName = $this->getPkFirstAttr();
            $key = [$idFieldName => $key];
        }
        $tbl = $this->getTableName();
        /* selection query */
        $conn = $this->connQuery->getConnection();
        $query = $conn->select();
        $query->from($tbl);
        foreach (array_keys($key) as $field) {
            $query->where("`$field`=:$field");
        }
        $found = $conn->fetchRow($query, $key);
        if ($found) {
            $result = $this->composeEntity($found);
        }
        return $result;
    }

    private function getPkFirstAttr()
    {
        $ids = static::ENTITY_PK;
        $result = reset($ids);
        return $result;
    }

    /**
     * Extract primary key from given data according to ENTITY structure.
     *
     * @param \TeqFw\Lib\Data|array $data
     * @return array
     * @throws \Exception
     */
    private function getPkFromData($data)
    {
        $result = [];
        $ids = static::ENTITY_PK;
        /* transform entity data to and array */
        if ($data instanceof \TeqFw\Lib\Data) {
            $given = (array)$data->getData();
        } else {
            $given = $data;
        }
        foreach ($ids as $key) {
            if (isset($given[$key])) {
                $result[$key] = $given[$key];
            } else {
                throw new \Exception("Cannot find value for primary key part '$key' in given data.");
            }
        }
        return $result;
    }

    public function getPrimaryKey(): array
    {
        // TODO: Implement getPrimaryKey() method.
    }

    public function getSet(
        $where = null,
        $bind = null,
        $order = null,
        $limit = null,
        $offset = null
    ) {
        $result = [];

        $tbl = $this->getTableName();
        $conn = $this->connQuery->getConnection();
        $query = $conn->select();
        $query->from($tbl, '*');
        if ($where) $query->where($where);
        if ($order) $query->order($order);
        if ($limit) $query->limit($limit, $offset);

        $rs = $conn->fetchAll($query, $bind);
        foreach ($rs as $one) {
            $entity = $this->composeEntity($one);
            $result[] = $entity;
        }
        return $result;
    }

    private function getTableName()
    {
        $result = $this->connQuery->getTableName(static::ENTITY_NAME);
        return $result;
    }

    public function updateByPk($data, $id = null)
    {
        $conn = $this->connQuery->getConnection();
        /* prepare primary key */
        if (is_null($id)) {
            $pk = $this->getPkFromData($data);
        } elseif (is_array($id)) {
            /* probably this is complex PK */
            $pk = $id;
        } else {
            $idFieldName = $this->getPkFirstAttr();
            $pk = [$idFieldName => $id];
        }
        $tbl = $this->getTableName();
        /* prepare data to update & where conditions */
        $bind = [];
        $where = [];
        $keys = array_keys($pk);
        if ($data instanceof \TeqFw\Lib\Data) {
            $given = (array)$data->getData();
        } else {
            $given = $data;
        }
        foreach ($given as $field => $value) {
            if (in_array($field, $keys)) {
                /* this is primary key, compose 'where' filter */
                $qField = $conn->quoteIdentifier($field);
                $qValue = $conn->quote($value);
                $where[] = "$qField=$qValue";
            } else {
                /* compose array of the values to be changed */
                $bind[$field] = $value;
            }
        }

        $result = $conn->update($tbl, $bind, $where);
        return $result;
    }

    public function updateOne($data)
    {
        $result = $this->updateByPk($data);
        return $result;
    }

    public function updateSet($data, $where)
    {
        $result = null;
        /* TODO: implement entity update by clause */
        return $result;
    }
}