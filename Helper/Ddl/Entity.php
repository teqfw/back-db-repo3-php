<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace TeqFw\Lib\Db\Repo3\Helper\Ddl;

use Doctrine\DBAL\Types\Type as DoctrineType;
use TeqFw\Lib\Dem\Helper\Ddl\Config as DdlCfg;
use TeqFw\Lib\Dem\Helper\Doctrine\Config as DoctrineCfg;
use TeqFw\Lib\Dem\Helper\Parser\Config as ParserCfg;

class Entity
    implements \TeqFw\Lib\Dem\Api\Helper\Ddl\Entity
{
    /** @var \TeqFw\Lib\Dem\Helper\Util\Path */
    private $hlpPath;

    public function __construct(
        \TeqFw\Lib\Dem\Helper\Util\Path $hlpPath
    ) {
        $this->hlpPath = $hlpPath;
    }

    private function addAttribute(
        \Doctrine\DBAL\Schema\Schema $schema,
        \Doctrine\DBAL\Schema\Table $entityTable,
        \TeqFw\Lib\Dem\Api\Data\Entity\Attr $attr
    ) {
        $entityTableName = $entityTable->getName();
        $tableName = $this->getNameForAttrTable($entityTableName, $attr);

        /* create values table for attribute */
        $table = $schema->createTable($tableName);
        if ($attr->desc)
            $table->addOption(DoctrineCfg::TBL_OPT_COMMENT, $attr->desc);
        /* add reference to the entity */
        $this->addColumnAttrRef($table);
        /* add values column */
        $this->addColumnAttrValue($table, $attr);
        /* add foreign key (ref => id)*/
        $table->addForeignKeyConstraint(
            $entityTableName,
            [DdlCfg::ATTR_REF],
            [DdlCfg::ATTR_ID],
            [
                DoctrineCfg::FKEY_ON_DELETE => DoctrineCfg::FKEY_ACT_CASCADE,
                DoctrineCfg::FKEY_ON_UPDATE => DoctrineCfg::FKEY_ACT_CASCADE
            ]
        );
    }

    private function addColumnAttrRef(
        \Doctrine\DBAL\Schema\Table $table
    ) {
        $table->addColumn(
            DdlCfg::ATTR_REF,
            \Doctrine\DBAL\Types\Type::INTEGER,
            [
                DoctrineCfg::COL_OPT_UNSIGNED => true,
                DoctrineCfg::COL_OPT_AUTOINCREMENT => true
            ]);
        $table->setPrimaryKey([DdlCfg::ATTR_REF]);
    }

    private function addColumnAttrValue(
        \Doctrine\DBAL\Schema\Table $table,
        \TeqFw\Lib\Dem\Api\Data\Entity\Attr $attr
    ) {
        $demType = $attr->type;
        $typeName = $this->mapAttrTypeDemToDdl($demType);
        $opts = [];
        if ($attr->precision)
            $opts[DoctrineCfg::COL_OPT_PRECISION] = $attr->precision;
        if ($attr->scale)
            $opts[DoctrineCfg::COL_OPT_SCALE] = $attr->scale;
        $table->addColumn(DdlCfg::ATTR_VALUE, $typeName, $opts);
    }

    private function addColumnEntityId(
        \Doctrine\DBAL\Schema\Table $table
    ) {
        $table->addColumn(
            DdlCfg::ATTR_ID,
            \Doctrine\DBAL\Types\Type::INTEGER,
            [
                DoctrineCfg::COL_OPT_UNSIGNED => true,
                DoctrineCfg::COL_OPT_AUTOINCREMENT => true
            ]);
        $table->setPrimaryKey([DdlCfg::ATTR_ID]);
    }

    /**
     * Create entity table and attributes tables.
     *
     * @param \Doctrine\DBAL\Schema\Schema $schema
     * @param \TeqFw\Lib\Dem\Api\Data\Entity $entity
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function create(
        \Doctrine\DBAL\Schema\Schema $schema,
        \TeqFw\Lib\Dem\Api\Data\Entity $entity
    ) {
        /* create register table for entity */
        $tableName = $this->getNameForEntityTable($entity);
        $table = $schema->createTable($tableName);
        $this->addColumnEntityId($table);
        if ($entity->desc)
            $table->addOption(DoctrineCfg::TBL_OPT_COMMENT, $entity->desc);

        /* create attributes tables */
        $attrs = $entity->attrs;
        foreach ($attrs as $attr) {
            $this->addAttribute($schema, $table, $attr);
        }

        return $schema;
    }

    private function getNameForAttrTable(
        $entityTableName,
        \TeqFw\Lib\Dem\Api\Data\Entity\Attr $attr)
    {
        $fullName = $entityTableName . '/' . DdlCfg::DIV_ATTR . '/' . $attr->name;
        $result = $this->hlpPath->toName($fullName);
        return $result;
    }

    private function getNameForEntityTable(\TeqFw\Lib\Dem\Api\Data\Entity $entity)
    {
        $ns = $entity->namespace ?? '';
        $name = $entity->name;
        $fullName = "$ns/" . DdlCfg::DIV_ENTITY . "/$name";
        $result = $this->hlpPath->toName($fullName);
        return $result;
    }

    private function getNameForEntityView(\TeqFw\Lib\Dem\Api\Data\Entity $entity)
    {
        $ns = $entity->namespace ?? '';
        $name = $entity->name;
        $fullName = "$ns/" . DdlCfg::DIV_VIEW . "/$name";
        $result = $this->hlpPath->toName($fullName);
        return $result;
    }

    /**
     * Convert DEM attribute type to Doctrine column type.
     *
     * @param string $demType
     * @return string
     */
    private function mapAttrTypeDemToDdl($demType)
    {
        $result = null;
        switch ($demType) {
            case ParserCfg::ATTR_TYPE_BINARY:
                $result = DoctrineType::BINARY;
                break;
            case ParserCfg::ATTR_TYPE_BOOLEAN:
                $result = DoctrineType::BOOLEAN;
                break;
            case ParserCfg::ATTR_TYPE_DATETIME:
                $result = DoctrineType::DATETIME_IMMUTABLE;
                break;
            case ParserCfg::ATTR_TYPE_INTEGER:
                $result = DoctrineType::INTEGER;
                break;
            case ParserCfg::ATTR_TYPE_NUMERIC:
                $result = DoctrineType::DECIMAL;
                break;
            case ParserCfg::ATTR_TYPE_TEXT:
                $result = DoctrineType::STRING;
                break;
        }
        return $result;
    }

    /**
     * Create entity with attributes aggregation (view).
     *
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager $man
     * @param \TeqFw\Lib\Dem\Api\Data\Entity $entity
     */
    public function view(
        \Doctrine\DBAL\Schema\AbstractSchemaManager $man,
        \TeqFw\Lib\Dem\Api\Data\Entity $entity
    ) {
        $tblEntityName = $this->getNameForEntityTable($entity);
        $viewName = $this->getNameForEntityView($entity);

        $columns = "`" . DdlCfg::AS_ENTITY . "`.`" . DdlCfg::ATTR_ID . "` as `" . DdlCfg::ATTR_ID . "`,\n";
        $joins = '';
        $attrs = $entity->attrs;
        foreach ($attrs as $attr) {
            $attrName = $attr->name;
            $tblAttrName = $this->getNameForAttrTable($tblEntityName, $attr);
            $columns .= "`$attrName`.`" . DdlCfg::ATTR_VALUE . "` as `$attrName`,\n";
            $joins .= "LEFT JOIN `$tblAttrName` AS `$attrName` ON `$attrName`.`" . DdlCfg::ATTR_REF
                . "`=`" . DdlCfg::AS_ENTITY . "`.`" . DdlCfg::ATTR_ID . "`\n";
        }
        $columns = substr($columns, 0, -2);
        $joins = substr($joins, 0, -1);
        $sql = "SELECT $columns FROM `$tblEntityName` AS `" . DdlCfg::AS_ENTITY . "` $joins";
        /* create view */
        $view = new \Doctrine\DBAL\Schema\View($viewName, $sql);
        $man->dropAndCreateView($view);

    }
}