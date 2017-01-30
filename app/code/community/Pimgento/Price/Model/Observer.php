<?php
/**
 * @author    Agence Dn'D <magento@dnd.fr>
 * @copyright Copyright (c) 2015 Agence Dn'D (http://www.dnd.fr)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Pimgento_Price_Model_Observer
{

    /**
     * Add Task and steps to executor
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function addTask(Varien_Event_Observer $observer)
    {
        /* @var $task Pimgento_Core_Model_Task */
        $task = $observer->getEvent()->getTask();

        /* @var $helper Pimgento_Price_Helper_Data */
        $helper = Mage::helper('pimgento_price');

        $task->addTask(
            'update_price',
            array(
                'label'   => $helper->__('Price: Update'),
                'type'    => 'file',
                'comment' => $helper->__('Update price. Upload CSV with 2 columns: sku and price'),
                'steps' => array(
                    1 => array(
                        'comment' => $helper->__('Create temporary table'),
                        'method'  => 'pimgento_price/import::createTable'
                    ),
                    2 => array(
                        'comment' => $helper->__('Insert data into temporary table'),
                        'method'  => 'pimgento_price/import::insertData'
                    ),
                    3 => array(
                        'comment' => $helper->__('Update column name'),
                        'method'  => 'pimgento_price/import::updateColumn'
                    ),
                    4 => array(
                        'comment' => $helper->__('Match PIM code with entity'),
                        'method'  => 'pimgento_price/import::matchEntity'
                    ),
                    5 => array(
                        'comment' => $helper->__('Update price data'),
                        'method'  => 'pimgento_price/import::updatePrice'
                    ),
                    6 => array(
                        'comment' => $helper->__('Drop temporary table'),
                        'method'  => 'pimgento_price/import::dropTable'
                    ),
                    7 => array(
                        'comment' => $helper->__('Reindex Data'),
                        'method'  => 'pimgento_price/import::reindex'
                    ),
                    8 => array(
                        'comment' => $helper->__('Clean cache'),
                        'method'  => 'pimgento_price/import::cleanCache'
                    ),
                )
            )
        );

        return $this;
    }

}