<?php
declare(strict_types=1);

namespace ZoiloMora\Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Platforms\SQLServerPlatform;

class MsAccessPlatform extends SQLServerPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getListTablesSQL()
    {
        return 'SELECT MSysObjects.Name, MSysObjects.Type, MSysObjects.Flags FROM MSysObjects ORDER BY MSysObjects.Name';
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        // TODO Build sql to get list of columns.
        return [];
    }
}
