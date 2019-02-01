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
    /**
     * Alias for main table.
     *
     * @var string
     */
    public const AS = 'entity';
    private const SELECT_ALL = '*';

    /** @var \TeqFw\Lib\Db\Api\Connection\Main */
    private $conn;
    /** @var \TeqFw\Lib\Dem\Api\Helper\Util\Path */
    private $hlpPath;

    public function __construct(
        \TeqFw\Lib\Db\Api\Connection\Main $conn,
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

    public function getOne(
        \TeqFw\Lib\Db\Api\Dao\Entity $dao,
        $key)
    {
        $result = null;
        /* compose filter parameters */
        $bind = [];
        if (is_array($key)) {
            $bind = $key;
        } else {
            /* get the first attribute from the key (probably, the one) */
            $pkey = $dao->getPrimaryKey();
            $first = reset($pkey);
            $bind[$first] = $key;
        }
        /* get entity config */
        $entityPath = $dao->getEntityPath();
        $table = $this->hlpPath->toName($entityPath);
        /* compose query */
        $qb = $this->conn->createQueryBuilder();
        $qb->select(self::SELECT_ALL);
        $qb->from($table, self::AS);
        foreach ($bind as $attr => $value) {
            $qb->andWhere("$attr=:$attr");
        }
        $qb->setParameters($bind);
        $sql = $qb->getSQL();
        /* execute query */
        $stmt = $qb->execute();
        $all = $stmt->fetchAll(
            \Doctrine\DBAL\FetchMode::CUSTOM_OBJECT,
            $dao->getEntityClass()
        );
        if (count($all) == 1) {
            $result = reset($all);
        }
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

        $qb = $this->conn->createQueryBuilder();
        $qb->select(self::SELECT_ALL);
        $qb->from($table, self::AS);
        if ($where)
            $qb->where($where);
        if ($bind)
            $qb->setParameters($bind);
        $sql = $qb->getSQL();
        /* execute query */
        $stmt = $qb->execute();
        $result = $stmt->fetchAll(
            \Doctrine\DBAL\FetchMode::CUSTOM_OBJECT,
            $dao->getEntityClass()
        );
        return $result;
    }
}