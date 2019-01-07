<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2019
 */

namespace TeqFw\Lib\Db\Repo3\Dao;

use TeqFw\Lib\Db\Api\Dao\DataEntity;

/**
 * NF3 compatible implementation of Generic DAO.
 */
class Generic
    implements \TeqFw\Lib\Db\Api\Dao\Generic
{
    /** @var \TeqFw\Lib\Db\Api\Connection\Schema */
    private $conn;
    /** @var \TeqFw\Lib\Dem\Api\Helper\Util\Path */
    private $hlpPath;

    public function __construct(
        \TeqFw\Lib\Db\Api\Connection\Schema $conn,
        \TeqFw\Lib\Dem\Api\Helper\Util\Path $hlpPath
    ) {
        $this->conn = $conn;
        $this->hlpPath = $hlpPath;
    }

    public function create(\TeqFw\Lib\Db\Api\Dao\Entity $dao, $data)
    {
        assert($data instanceof \TeqFw\Lib\Data);
        $fields = (array)$data;
        $entityPath = $dao->getEntityPath();
        $table = $this->hlpPath->toName($entityPath);
        $this->conn->insert($table, $fields);
        $result = $this->conn->lastInsertId();
        return $result;
    }

    public function getSet(
        \TeqFw\Lib\Db\Api\Dao\Entity $dao,
        $where = null,
        $bind = null,
        $order = null,
        $limit = null,
        $offset = null
    ) {
        $entityPath = $dao->getEntityPath();
        $table = $this->hlpPath->toName($entityPath);

        $query = "SELECT * FROM $table WHERE 1";
        /* execute query */
        /** @var \Doctrine\DBAL\Driver\PDOStatement $statement */
        $statement = $this->conn->executeQuery($query);
        $class = $dao->getEntityClass();
        $result = $statement->fetchAll(\Doctrine\DBAL\FetchMode::CUSTOM_OBJECT, $class);
        return $result;
    }


}