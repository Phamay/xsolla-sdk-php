<?php

namespace Xsolla\SDK\Protocol\Storage\Pdo;

use Xsolla\SDK\Exception\InvoiceNotFoundException;
use Xsolla\SDK\Protocol\Storage\PaymentStorageInterface;

abstract class PaymentStorage implements PaymentStorageInterface
{
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function cancel($invoiceId)
    {
        $table = $this->getTable();
        $update = $this->pdo->prepare("
            UPDATE $table SET
            is_canceled = 1,
            timestamp_canceled = NOW()
            WHERE id_xsolla = :id_xsolla
        ");
        $update->bindValue(':id_xsolla', $invoiceId, \PDO::PARAM_INT);
        $update->execute();
        if ($update->rowCount() == 1) {
            return;
        }
        $select = $this->pdo->prepare("
            SELECT is_canceled, timestamp_canceled
            FROM $table
            WHERE id_xsolla = :id_xsolla
        ");
        $select->bindValue(':id_xsolla', $invoiceId, \PDO::PARAM_INT);
        $select->execute();
        $result = $select->fetch(\PDO::FETCH_ASSOC);
        if ($result === false) {
            throw new InvoiceNotFoundException();
        }
        if ($result['is_canceled'] != 1) {
            throw new \Exception('Temporary error');
        }
    }

    abstract protected function getTable();

} 