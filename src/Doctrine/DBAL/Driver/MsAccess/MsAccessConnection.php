<?php
declare(strict_types=1);

namespace ZoiloMora\Doctrine\DBAL\Driver\MsAccess;

use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use function strpos;
use function substr;

class MsAccessConnection extends PDOConnection
{
    protected $_pdoTransactionsSupport = null;
    protected $_pdoLastInsertIdSupport = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($dsn, $user = null, $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $user, $password, $options);

        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [MsAccessStatement::class, []]);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type = ParameterType::STRING)
    {
        $val = parent::quote($input, $type);

        // Fix for a driver version terminating all values with null byte
        if (false !== strpos($val, "\0")) {
            $val = substr($val, 0, -1);
        }

        return $val;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        if ($this->_pdoLastInsertId()) {
            $id = parent::lastInsertId($name);
        } else {
            $stmt = $this->query('SELECT @@Identity');
            $id = $stmt->fetchColumn();
            $stmt->closeCursor();
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        if ($this->_pdoTransactionsSupported()) {
            parent::beginTransaction();
        } else {
            $this->exec('BEGIN TRANSACTION');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        if ($this->_pdoTransactionsSupported()) {
            parent::commit();
        } else {
            $this->exec('COMMIT TRANSACTION');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        if ($this->_pdoTransactionsSupported()) {
            parent::rollback();
        } else {
            $this->exec('ROLLBACK TRANSACTION');
        }
    }

    /**
     * @return bool
     */
    private function _pdoLastInsertId() {
        if (null !== $this->_pdoLastInsertIdSupport) {
            return $this->_pdoLastInsertIdSupport;
        }

        try {
            parent::lastInsertId();
            $this->_pdoLastInsertIdSupport = true;
        } catch (PDOException $e) {
            $this->_pdoLastInsertIdSupport = false;
        }

        return $this->_pdoLastInsertIdSupport;
    }

    /**
     * @return bool
     */
    private function _pdoTransactionsSupported()
    {
        if (null !== $this->_pdoTransactionsSupport) {
            return $this->_pdoTransactionsSupport;
        }

        try {
            parent::beginTransaction();
            parent::commit();
            $this->_pdoTransactionsSupport = true;
        } catch (PDOException $e) {
            $this->_pdoTransactionsSupport = false;
        }

        return $this->_pdoTransactionsSupport;
    }
}
