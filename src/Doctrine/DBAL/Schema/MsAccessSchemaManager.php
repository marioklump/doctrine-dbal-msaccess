<?php
declare(strict_types=1);

namespace ZoiloMora\Doctrine\DBAL\Schema;

use Doctrine\DBAL\Schema\SQLServerSchemaManager;
use function substr;
use function in_array;

class MsAccessSchemaManager extends SQLServerSchemaManager
{
    /**
     * {@inheritdoc}
     */
    protected function _getPortableTablesList($tables)
    {
        $list = [];

        foreach ($tables as $value) {
            if ('~' === substr($value['Name'], 0, 1)) {
                continue;
            }

            if ('MSys' === substr($value['Name'], 0, 4)) {
                continue;
            }

            if (false === in_array($value['Type'], [1, 4, 6])) {
                continue;
            }

            if (0 === $value['Flags']) {
                continue;
            }

            $list[] = $value['Name'];
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function listTableColumns($table, $database = null)
    {
        // TODO Remove method when the SQL statement (getListTableColumnsSQL) is ready.
        return [];
    }
}
