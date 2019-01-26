<?php
if (!empty($_GET['action'])) {
    chdir('../../../');
    require_once 'include/utils/utils.php';
    require 'include/events/include.inc';
    if(empty($current_user)) {
        $current_user = Users::getActiveAdminUser();
    }
    if ($current_user->is_admin === 'on') {
        $em = new VTEventsManager($adb);
        $em->listHandlersForModule('Leads');
        if ($_GET['action'] === 'add') {
            // add fields
            // UTM
            $module = Vtiger_Module::getInstance('Leads');
            $block = Vtiger_Block::getInstance('LBL_LEAD_INFORMATION', $module);
            if ($block) {
                $field = new Vtiger_Field();
                $field->name = 'UTM';
                $field->label= 'UTM';
                $field->uitype= 1;
                $field->column = $field->name;
                $field->columntype = 'VARCHAR(255)';
                $field->typeofdata = 'V~O~LE~255';
                $block->addField($field);
            }

            // utmID
            $module = Vtiger_Module::getInstance('Campaigns');
            $block = Vtiger_Block::getInstance('LBL_CAMPAIGN_INFORMATION', $module);
            if ($block) {
                $field = new Vtiger_Field();
                $field->name = 'utmID';
                $field->label= 'utmID';
                $field->uitype= 1;
                $field->column = $field->name;
                $field->columntype = 'VARCHAR(255)';
                $field->typeofdata = 'V~O~LE~255';
                $block->addField($field);

            }

            $block = Vtiger_Block::getInstance('LBL_EXPECTATIONS_AND_ACTUALS', $module);

            // Количество Обращений и Конверсия в заказы
            if ($block) {

                $field = new Vtiger_Field();
                $field->name = 'count_leads';
                $field->label= 'Количество Обращений';
                $field->uitype= 7;
                $field->column = $field->name;
                $field->columntype = 'int';
                $field->typeofdata = 'I~O';
                $block->addField($field);

                $field = new Vtiger_Field();
                $field->name = 'conversion_leads';
                $field->label= 'Конверсия в заказы';
                $field->uitype= 1;
                $field->column = $field->name;
                $field->columntype = 'VARCHAR(255)';
                $field->typeofdata = 'V~O~LE~255';
                $block->addField($field);
            }

            $em->registerHandler("vtiger.entity.aftersave", 'modules/Leads/handlers/LeadsUtmEventHandler.php', 'LeadsUtmEventHandler');
            $em->setModuleForHandler('Leads', 'LeadsUtmEventHandler');
            echo 'Add event succesfully';
        } elseif ($_GET['action'] === 'del') {
            $em->unregisterHandler('LeadsUtmEventHandler');
            echo 'Delete event succesfully';
        } else {
            echo 'No action';
        }
    } else {
        echo 'Access deny';
    }
} else {
    echo '';
}
