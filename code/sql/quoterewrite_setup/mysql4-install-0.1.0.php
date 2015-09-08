<?php
//$installer = $this;
//$installer->startSetup();
//$sql=<<<SQLTEXT
//create table totm_recurrent_promo(tablename_id int not null auto_increment, name varchar(100), primary key(tablename_id));
//    insert into tablename values(1,'tablename1');
//    insert into tablename values(2,'tablename2');
//
//SQLTEXT;
//
//$installer->run($sql);
////demo
////Mage::getModel('core/url_rewrite')->setId(null);
////demo
//$installer->endSetup();

$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()
	->newTable($installer->getTable('quoterewrite/recurrentpromo'))
	->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'identity'  => true,
		'nullable'  => false,
		'primary'   => true,
	), 'Entity Id')
	->addColumn('coupon_code', Varien_Db_Ddl_Table::TYPE_TEXT, 15, array(
		'nullable'  => false,
		'default'   => '',
		'comment'   => 'coupon_code'
	), 'coupon_code')

	->addColumn('period', Varien_Db_Ddl_Table::TYPE_INTEGER, 5, array(
		'unsigned'  => true,
		'nullable'  => false,
		'default'   => '0',
		'comment'   => 'period'
	), 'Country Id')
	->addColumn('type', Varien_Db_Ddl_Table::TYPE_TEXT, 15, array(
		'nullable'  => false,
		'default'   => '',
		'comment'   => 'type: months/cycles'
	), 'type: months/cycles')
;
$installer->getConnection()->createTable($table);

$installer->endSetup();