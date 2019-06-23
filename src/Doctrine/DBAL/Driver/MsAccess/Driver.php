<?php
declare(strict_types=1);

namespace ZoiloMora\Doctrine\DBAL\Driver\MsAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOException;
use ZoiloMora\Doctrine\DBAL\Platforms\MsAccessPlatform;
use ZoiloMora\Doctrine\DBAL\Schema\MsAccessSchemaManager;

class Driver implements \Doctrine\DBAL\Driver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        try {
            $conn = new MsAccessConnection(
                $this->constructPdoDsn($params),
                $username,
                $password,
                $driverOptions
            );
        } catch (PDOException $e) {
            throw DBALException::driverException($this, $e);
        }

        return $conn;
    }

    protected function constructPdoDsn(array $params)
    {
        $dsn = 'odbc:';

        if (isset($params['dsn']) && $params['dsn'] !== '') {
            return $dsn . $params['dsn'];
        }

        if (isset($params['odbc_driver']) && $params['odbc_driver'] !== '') {
            $dsn .= 'DRIVER=' . $params['odbc_driver'] . ';';
        }

        if (isset($params['filename']) && $params['filename'] !== '') {
            $dsn .= 'DBQ=' . $params['filename'] . ';';
        }

        return $dsn;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return new MsAccessPlatform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn)
    {
        return new MsAccessSchemaManager($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_msaccess';
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(Connection $conn)
    {
        return '';
    }
}
