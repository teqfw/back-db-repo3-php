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
    /** @var \TeqFw\Lib\Db\Api\Connection\Query */
    private $conn;

    public function __construct(
        \TeqFw\Lib\Db\Api\Connection\Query $conn
    ) {
        $this->conn = $conn;
    }

    public function create($entityName, $data)
    {
        assert($data instanceof \TeqFw\Lib\Data);
        $fields = (array)$data;
        $this->conn->insert($entityName, $fields);
        $result = $this->conn->lastInsertId();
        return $result;
    }

}