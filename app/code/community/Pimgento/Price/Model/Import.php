<?php
/**
 * @author    Agence Dn'D <magento@dnd.fr>
 * @copyright Copyright (c) 2015 Agence Dn'D (http://www.dnd.fr)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Pimgento_Price_Model_Import extends Pimgento_Core_Model_Import_Abstract
{

    /**
     * @var string
     */
    protected $_code = 'product';

    /**
     * Create table (Step 1)
     *
     * @param Pimgento_Core_Model_Task $task
     *
     * @return bool
     */
    public function createTable($task)
    {
        $file = $task->getFile();

        $this->getRequest()->createTableFromFile($this->getCode(), $file, 2);

        return true;
    }

    /**
     * Insert data (Step 2)
     *
     * @param Pimgento_Core_Model_Task $task
     *
     * @return bool
     * @throws Exception
     */
    public function insertData($task)
    {
        $file = $task->getFile();

        $lines = $this->getRequest()->insertDataFromFile($this->getCode(), $file);

        if (!$lines) {
            $task->error(
                Mage::helper('pimgento_price')->__(
                    'No data to insert, verify the file is not empty or CSV configuration is correct'
                )
            );
        }

        $task->setMessage(
            Mage::helper('pimgento_price')->__('%s lines found', $lines)
        );

        return true;
    }

    /**
     * Insert data (Step 3)
     *
     * @param Pimgento_Core_Model_Task $task
     *
     * @return bool
     */
    public function updateColumn($task)
    {
        $adapter = $this->getAdapter();

        if (!$this->columnsRequired(array('sku'), $task)) {
            $task->error(
                Mage::helper('pimgento_price')->__('Column %s not found', 'sku')
            );
        }

        $adapter->changeColumn($this->getTable(), 'sku', 'code', 'VARCHAR(255)');

        return true;
    }

    /**
     * Match Entity with Code (Step 4)
     *
     * @param Pimgento_Core_Model_Task $task
     *
     * @return bool
     */
    public function matchEntity($task)
    {
        $this->getRequest()->matchEntity($this->getCode(), 'catalog/product', 'entity_id', null, false);

        return true;
    }

    /**
     * Update price data (Step 5)
     *
     * @param Pimgento_Core_Model_Task $task
     *
     * @return bool
     */
    public function updatePrice($task)
    {
        $adapter  = $this->getAdapter();

        if (!$this->columnsRequired(array('price'), $task)) {
            return false;
        }

        $price = 'price';

        $values = array();

        if ($adapter->tableColumnExists($this->getTable(), $price)) {
            $values[$price] = new Zend_Db_Expr(
                'IF(`' . $price . '` <> "",`' . $price . '`,NULL)'
            );
        }

        $storeId = 0;

        if (count($values)) {
            $this->getRequest()->setValues(
                $this->getCode(), 'catalog/product', $values, 4, $storeId
            );
        }

        return true;
    }

    /**
     * Drop table (Step 6)
     *
     * @param Pimgento_Core_Model_Task $task
     *
     * @return bool
     */
    public function dropTable($task)
    {
        $this->getRequest()->dropTable($this->getCode());

        Mage::dispatchEvent('task_executor_drop_table_after', array('task' => $task));

        return true;
    }

    /**
     * Reindex (Step 7)
     *
     * @param Pimgento_Core_Model_Task $task
     *
     * @return bool
     */
    public function reindex($task)
    {
        if ($task->getNoReindex()) {
            return false;
        }

        if (!$this->getConfig('reindex')) {
            $task->setMessage(
                Mage::helper('pimgento_price')->__('Reindex is disabled')
            );
            return false;
        }

        /* @var $indexer Mage_Index_Model_Indexer */
        $indexer = Mage::getSingleton('index/indexer');

        Mage::dispatchEvent('shell_reindex_init_process');

        $processes = array(
            'catalog_product_price',
        );

        foreach ($processes as $code) {
            $process = $indexer->getProcessByCode($code);
            if ($process) {
                $process->reindexEverything();
                Mage::dispatchEvent($code . '_shell_reindex_after');
            }
        }

        Mage::dispatchEvent('shell_reindex_finalize_process');

        return true;
    }

}