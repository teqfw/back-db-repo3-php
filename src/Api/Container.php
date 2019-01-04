<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2019
 */

namespace TeqFw\Lib\Db\Repo3\Api;

/**
 * Place this module's objects into container.
 */
class Container
{
    public static function populate(\TeqFw\Lib\Di\Api\Container $container)
    {
        $ddlEntity = $container->get(\TeqFw\Lib\Db\Repo3\Helper\Ddl\Entity::class);
        $container->add(\TeqFw\Lib\Dem\Api\Helper\Ddl\Entity::class, $ddlEntity, true);
    }
}