<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2019
 */

namespace TeqFw\Lib\Db\Repo3\Api;

/**
 * Place this module's objects into container.
 */
class ContainerBuilder
{
    public static function populate(\TeqFw\Lib\Di\Api\Container $container)
    {
        $container->populate(
            \TeqFw\Lib\Db\Api\Dao\Generic::class,
            \TeqFw\Lib\Db\Repo3\Dao\Generic::class
        );
        $container->populate(
            \TeqFw\Lib\Dem\Api\Helper\Ddl\Entity::class,
            \TeqFw\Lib\Db\Repo3\Helper\Ddl\Entity::class
        );
    }

}