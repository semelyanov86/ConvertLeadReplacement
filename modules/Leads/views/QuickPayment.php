<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Leads_QuickPayment_View extends Vtiger_IndexAjax_View {
    
    public function process(Vtiger_Request $request) {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();

        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('PAYTYPE', $this->getPickList());
        echo $viewer->view('QuickPaymentForm.tpl', $moduleName, true);
    }

    private function getPickList($id = '648')
    {
        $moduleName = 'SalesOrder';
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        
        $fieldModel = Vtiger_Field_Model::getInstance('cf_'.$id, $moduleModel);

        return $fieldModel->getPicklistValues();
    }

}
