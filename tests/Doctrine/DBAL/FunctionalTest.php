<?php
declare(strict_types=1);

namespace ZoiloMora\Tests\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Throwable;
use ZoiloMora\Doctrine\DBAL\Driver\MsAccess\Driver;

class FunctionalTest extends TestCase
{
    /**
     * @test
     * @dataProvider connections
     */
    public function given_an_connection_when_get_database_name_then_returns_an_empty_text(Connection $connection)
    {
        $this->assertSame(
            '',
            $connection->getDatabase()
        );
    }

    /**
     * @test
     * @dataProvider connections
     */
    public function given_an_connection_when_try_create_table_then_does_not_return_error(Connection $connection)
    {
        $stmt = $connection->query('SELECT * FROM Table1');

        $this->assertNotEmpty(
            $stmt->fetchAll()
        );
    }

    /**
     * @test
     * @dataProvider connections
     */
    public function given_an_connection_when_try_get_rows_count_then_does_not_return_error(Connection $connection)
    {
        $stmt = $connection->query('SELECT * FROM Table1');

        $this->assertIsInt(
            $stmt->rowCount()
        );
    }

    /**
     * @test
     * @dataProvider connections
     */
    public function given_an_connection_when_try_get_last_insert_id_then_does_not_return_error(Connection $connection)
    {
        $this->assertSame(
            '0',
            $connection->lastInsertId()
        );
    }

    /**
     * @test
     * @dataProvider connections
     */
    public function given_an_connection_when_try_insert_row_then_does_not_return_error (Connection $connection)
    {
        $affectedRows = $connection->insert(
            'Table1',
            [
                'first_name' => 'Ray',
                'last_name' => 'Sanders',
                'birthday' => '05/02/1983',
                'points' => 4,
            ]
        );

        $this->assertIsInt($affectedRows);
        $this->assertSame(1, $affectedRows);
    }

    /**
     * @test
     * @dataProvider connections
     */
    public function given_an_connection_when_try_list_tables_then_returns_array_tables(Connection $connection)
    {
        $expected = [
            'Table1',
        ];

        $tables = $connection->getSchemaManager()->listTableNames();

        $this->assertIsArray($tables);
        $this->assertSame($expected, $tables);
    }

    /**
     * @test
     * @dataProvider connections
     */
    public function given_an_connection_when_try_list_columns_then_returns_array_tables(Connection $connection)
    {
        $columns = $connection->getSchemaManager()->listTableColumns('Table1');

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    public function connections(): array
    {
        if (false === $this->isMicrosoftWindows()) {
            return [];
        }

        $drivers = $this->getDriverInstalled();
        if (0 === count($drivers)) {
            throw new \Exception('No drivers for the tests were detected');
        }

        $connections = [];
        foreach ($drivers as $driver) {
            $filename = $this->createDatabase();
            $params = [
                'driverClass' => Driver::class,
                'odbc_driver' => '{' . $driver . '}',
                'filename' => $filename,
            ];

            $connections[] = [
                DriverManager::getConnection($params),
            ];
        }

        return $connections;
    }

    private function createDatabase(): string
    {
        $ds = DIRECTORY_SEPARATOR;
        $root = __DIR__ . $ds . '..' . $ds . '..' . $ds . '..' . $ds;

        $source = $root . 'tests' . $ds . 'database.mdb';
        $dest = $root . 'var' . $ds . rand(0, 99999) . '.mdb';

        copy($source, $dest);

        return $dest;
    }

    private function isMicrosoftWindows(): bool
    {
        if (false !== strpos(php_uname(), 'Windows NT')) {
            return true;
        }

        return false;
    }

    private function getDriverInstalled(): array
    {
        $this->assertSame(
            true,
            class_exists(\COM::class),
            'To determine which drivers are installed in Microsoft Windows you need the DOTNET extension'
        );

        $wsh = new \COM("WScript.Shell");

        $path = 'HKEY_LOCAL_MACHINE\SOFTWARE\ODBC\ODBCINST.INI\ODBC Drivers\\';
        $keys = [
            'Microsoft Access Driver (*.mdb)',
            'Microsoft Access Driver (*.mdb, *.accdb)',
        ];

        $drivers = [];
        foreach ($keys as $key) {
            try {
                $wsh->RegRead($path . $key);
                $drivers[] = $key;
            } catch (Throwable $ex) {
                if (-2147352567 !== $ex->getCode()) {
                    throw $ex;
                }
            }
        }

        return $drivers;
    }
}
