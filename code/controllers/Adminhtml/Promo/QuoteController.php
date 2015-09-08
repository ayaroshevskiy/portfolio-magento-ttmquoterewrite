<?php
require_once "Mage/Adminhtml/controllers/Promo/QuoteController.php";  
class Totm_QuoteRewrite_Adminhtml_Promo_QuoteController extends Mage_Adminhtml_Promo_QuoteController{
	/**
	 * Promo quote save action
	 *
	 */
	public function saveAction()
	{
		if ($this->getRequest()->getPost()) {
			try {
				/** @var $model Mage_SalesRule_Model_Rule */
				$model = Mage::getModel('salesrule/rule');
				Mage::dispatchEvent(
					'adminhtml_controller_salesrule_prepare_save',
					array('request' => $this->getRequest()));
				$data = $this->getRequest()->getPost();
				$data = $this->_filterDates($data, array('from_date', 'to_date'));
				$id = $this->getRequest()->getParam('rule_id');
				if ($id) {
					$model->load($id);
					if ($id != $model->getId()) {
						Mage::throwException(Mage::helper('salesrule')->__('Wrong rule specified.'));
					}
				}

				$session = Mage::getSingleton('adminhtml/session');

				$validateResult = $model->validateData(new Varien_Object($data));
				if ($validateResult !== true) {
					foreach($validateResult as $errorMessage) {
						$session->addError($errorMessage);
					}
					$session->setPageData($data);
					$this->_redirect('*/*/edit', array('id'=>$model->getId()));
					return;
				}

				if (isset($data['simple_action']) && $data['simple_action'] == 'by_percent'
					&& isset($data['discount_amount'])) {
					$data['discount_amount'] = min(100,$data['discount_amount']);
				}
				if (isset($data['rule']['conditions'])) {
					$data['conditions'] = $data['rule']['conditions'];
				}
				if (isset($data['rule']['actions'])) {
					$data['actions'] = $data['rule']['actions'];
				}
				unset($data['rule']);
				$model->loadPost($data);

				$useAutoGeneration = (int)!empty($data['use_auto_generation']);
				$model->setUseAutoGeneration($useAutoGeneration);

				$session->setPageData($model->getData());

				$model->save();


				// saving additional promo
				$recurrentPromo = Mage::getModel('quoterewrite/recurrentpromo');
				$recurrentPromoCollection = $recurrentPromo->getCollection();
				$couponCode = $data['coupon_code'];
				$recurrentPromoCollection->getSelect()->where("coupon_code='$couponCode'");
				foreach ($recurrentPromoCollection as $item) {
					$item->delete();
				}

				$recurrentPromo->setCouponCode($couponCode);
				$recurrentPromo->setPeriod($data['discount_period']);
				$recurrentPromo->setType($data['discount_type']);
				$recurrentPromo->save();

				$session->addSuccess(Mage::helper('salesrule')->__('The rule has been saved.'));
				$session->setPageData(false);
				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
				$id = (int)$this->getRequest()->getParam('rule_id');
				if (!empty($id)) {
					$this->_redirect('*/*/edit', array('id' => $id));
				} else {
					$this->_redirect('*/*/new');
				}
				return;

			} catch (Exception $e) {
				$this->_getSession()->addError(
					Mage::helper('catalogrule')->__('An error occurred while saving the rule data. Please review the log and try again.'));
				Mage::logException($e);
				Mage::getSingleton('adminhtml/session')->setPageData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('rule_id')));
				return;
			}
		}
		$this->_redirect('*/*/');
	}

	public function editAction()
	{
		$id = $this->getRequest()->getParam('id');
		$model = Mage::getModel('salesrule/rule');

		if ($id) {
			$model->load($id);
			if (! $model->getRuleId()) {
				Mage::getSingleton('adminhtml/session')->addError(
					Mage::helper('salesrule')->__('This rule no longer exists.'));
				$this->_redirect('*/*');
				return;
			}
		}

		$this->_title($model->getRuleId() ? $model->getName() : $this->__('New Rule'));

		// set entered data if was error when we do save
		$data = Mage::getSingleton('adminhtml/session')->getPageData(true);
		if (!empty($data)) {
			$model->addData($data);
		}

		$model->getConditions()->setJsFormObject('rule_conditions_fieldset');
		$model->getActions()->setJsFormObject('rule_actions_fieldset');

		$couponCode = $model->getCouponCode();
		$recurrentPromo = Mage::getModel('quoterewrite/recurrentpromo');
		$recurrentPromoCollection = $recurrentPromo->getCollection();
		$recurrentPromoCollection->getSelect()->where("coupon_code='$couponCode'");
		$rc = $recurrentPromoCollection->getFirstItem();

		$model->setDiscountPeriod($rc->getPeriod());
		$model->setDiscountType($rc->getType());

		Mage::register('current_promo_quote_rule', $model);

		$this->_initAction()->getLayout()->getBlock('promo_quote_edit')
			->setData('action', $this->getUrl('*/*/save'));

		$this
			->_addBreadcrumb(
				$id ? Mage::helper('salesrule')->__('Edit Rule')
					: Mage::helper('salesrule')->__('New Rule'),
				$id ? Mage::helper('salesrule')->__('Edit Rule')
					: Mage::helper('salesrule')->__('New Rule'))
			->renderLayout();

	}
}