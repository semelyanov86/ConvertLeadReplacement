<?php
/*+***********************************************************************************
 * PINstudio
 * Binizik
 *************************************************************************************/
class Leads_ConvertLeadToSalesOrder_View extends Vtiger_QuickCreateAjax_View {

	public function process(Vtiger_Request $request) {
		
		$viewer = $this->getViewer($request);
		$times = '{}';
		$record = $request->get('record');

		$recType = Vtiger_Functions::getCRMRecordType($record);

		$moduleName  = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$shopsModel  = Vtiger_Module_Model::getInstance('Shops');
		$userModel   = Users_Record_Model::getCurrentUserModel();
		$viewer->assign([
			'USERLIST'   => $userModel->getAccessibleUsers(),
			'SRCID'      => $record,
			'shops'      => $this->getPickList(659),
			'brands'     => $this->getPickList(707),
			'shopList'   => $shopsModel->getShopList(),
			'LOOKUP'     => json_encode($shopsModel->getLookupShops()),
			'whereadds'  => $this->getPickList(706),
			'payments'   => $this->getPickList(648),
			'current_user'    => $_SESSION['authenticated_user_id'],
			'default_product' => $this->getDefaultProduct(42)
		]);
		$fieldValues = $this->getFieldValues($record, $recType);
		if ($recType == 'Leads'){
			$viewer->assign('PAID', $this->getLeadBalance($record));
			$times = $this->getTimes($fieldValues);
		}
		$viewer->assign('fieldValues', $fieldValues);
		$viewer->assign('TIMES', $times);

		echo $viewer->view('ConvertLeadToSalesOrder.tpl', $moduleName, true);
	}

	private function getPickList($id)
	{
		$moduleName = 'SalesOrder';
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		
		$fieldModel = Vtiger_Field_Model::getInstance('cf_'.$id, $moduleModel);

		return $fieldModel->getPicklistValues();
	}

    private function getLeadBalance($leadid)
    {
        $db  = PearDatabase::getInstance();
        $sql = 'SELECT COALESCE(sum(amount),0) totals FROM sp_payments
                LEFT JOIN vtiger_crmentity vc on vc.crmid = payid
                WHERE payer = ? AND spstatus = ? AND deleted = 0';
        $result     = $db->pquery($sql, [$leadid, 'Executed']);
        if ($result->NumRows() == 0) return 0;

        $leadTotals = $db->query_result($result, 0, 'totals');

        return (int)$leadTotals;
    }

	private function getFieldValues($crmid, $type)
	{
		$result = [];
		if ($type == 'Leads'){
			$record = Vtiger_Record_Model::getInstanceById($crmid, $type);
			$result = $record->getData();
		}
		if ($type == 'Contacts'){
			$db = PearDatabase::getInstance();
			$sql = "SELECT 
				firstname,
				COALESCE(lastname, '-') lastname,
				mobile,
				COALESCE(shopid,'') shopid,
				COALESCE(shopname,'') cf_847,
				COALESCE(cf_707, 'Fantasy') cf_838,
				COALESCE(cf_706, 'Call-center') cf_845,
				COALESCE(cf_648, 'CloudPayments') cf_839
				FROM vtiger_contactdetails
					LEFT JOIN vtiger_crmentity vcc ON vcc.crmid = contactid
					LEFT JOIN vtiger_salesorder USING (contactid)
					LEFT JOIN vtiger_crmentity vcs ON vcs.crmid = salesorderid
					LEFT JOIN vtiger_salesordercf USING (salesorderid)
					LEFT JOIN vtiger_shops USING (shopid)
				WHERE contactid = ?
					AND vcc.deleted = 0
					AND vcs.deleted = 0
				ORDER BY salesorderid DESC
				LIMIT 1";
			$order = $db->pquery($sql, [$crmid]);
			$nr = $db->num_rows($order);
			if ($nr>0){
				$lastOrder = $db->fetch_array($order);
				$result = $lastOrder;
			}
		}
		$result['crmid'] = $crmid;

		return $result;
	}

	private function getDefaultProduct($productid)
	{
		global $adb;

		try {
			$query = "SELECT * FROM  `vtiger_products`  WHERE  `productid` = ?";
			$result = $adb->pquery($query, array($productid));
			$row = $adb->query_result_rowdata($result, 0);

			$row['unit_price'] = explode('.', $row['unit_price'])[0];
		} catch (Exception $e) {
			echo $e;
			exit();
		}

		return $row;
	}

	// Время доставки, с, до, Дедлайн, Время самовывоза, с, до
	private function getTimes($fieldValues)
	{
		$exp = explode(':', $fieldValues['cf_842']);
		$times['timedostsam'] = $times['timedost'] = $exp[0];
		$times['timedostminutesam'] = $times['timedostminute'] = $exp[1];

		$exp = explode(':', $fieldValues['cf_843']);
		$times['timedostfromsam'] = $times['timededline'] = $times['timedostfrom'] = !empty($exp[0]) ? $exp[0] : $times['timedost'];
		$times['timedostminutefromsam'] = $times['timededlineminute'] = $times['timedostminutefrom'] = !empty($exp[1]) ? $exp[1] : $times['timedostminute'];

		if ($times['timedostsam'] == $times['timedostfromsam']) {
			$times['timedostfromsam'] = '23';
			$times['timedostminutefromsam'] = '00';
		}

		return json_encode($times);
	}
}
