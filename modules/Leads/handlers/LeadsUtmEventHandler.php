<?php
/**
 * 
 * @author php-technolog
 * 
 */
class LeadsUtmEventHandler extends VTEventHandler
{
    
    const UTM_LABEL = 'UTM метка'; // название поля, в котором храним UTM метки
    const UTM_CAMPAIGN = 'utm_campaign'; // назнание UTM поля, в котором хранится идентификатор кампании
    const UTM_CAMPAIGN_LABEL = 'utmID'; // название поля, в котором храниться идентификатор рекламной кампании
    const SOURCE_MODEL = 'Leads';
    const RELATED_MODEL = 'Campaigns';
    
    /**
     * @param VTEntityData $entityData
     */
    public function handleEvent($handlerType, $entityData){
        $this->db = PearDatabase::getInstance();
        $moduleName = $entityData->getModuleName();
        if ($moduleName != 'Leads') return;
        if ($handlerType != 'vtiger.entity.aftersave') return;

        $utm = $this->getUtm($entityData);
        if (empty($utm)) {
            return false; // нет данных для продолжения
        }
        $utm_campaign = $this->getUtmCampaign($utm);
        if (empty($utm_campaign)) {
            return false; // нет метки 
        }
        $fieldname = $this->getFieldByLabel(
            self::RELATED_MODEL,
            self::UTM_CAMPAIGN_LABEL
        );
        if (empty($fieldname)) {
            return false; // нет поля 
        }
        $listViewModel = Vtiger_ListView_Model::getInstance(self::RELATED_MODEL);
        $listViewModel->set('search_params', [
            ['columns' => [
                [
                    'columnname' => 'vtiger_campaignscf:'.$fieldname.':'.$fieldname.':Campaigns_'.self::UTM_CAMPAIGN_LABEL.':V',
                    'comparator' => 'c',
                    'value' => $utm_campaign,
                    'column_condition' => 'and',
                ],
                [
                    'columnname' => 'vtiger_campaign:campaignstatus:campaignstatus:Campaigns_Campaign_Status:V',
                    'comparator' => 'e',
                    'value' => 'Active',
                    'column_condition' => '',
                ],
            ]]
        ]);
        $pagingModel = new Vtiger_Paging_Model();
        $list_campaigns = $listViewModel->getListViewEntries($pagingModel);
        $campaign = array_pop($list_campaigns);
        if ($campaign) {
            $campaign_id = $campaign->getId();
        } else {
            $campaign_id = $this->_createCampaign($utm_campaign);
        }

        $sourceModuleModel = Vtiger_Module_Model::getInstance(self::RELATED_MODEL);
        $relatedModuleModel = Vtiger_Module_Model::getInstance(self::SOURCE_MODEL);
        $relationModel = Vtiger_Relation_Model::getInstance($sourceModuleModel, $relatedModuleModel);
        $relationModel->addRelation($campaign_id, $entityData->getId());
    }

    /**
     * Получение UTM
     */
    private function getUtm($entityData) {
        $fieldname = $this->getFieldByLabel(self::SOURCE_MODEL, self::UTM_LABEL);
        return !empty($fieldname) ? $entityData->get($fieldname) : null;
    }
    
    /**
     * Получаем ИД рекламной кампании
     * @param unknown $utm
     * @return multitype:|NULL
     */
    private function getUtmCampaign($utm) {
        $correct_utm = preg_replace('#utm_[^=]*?=#', '&$0', $utm);
        $arr = explode('&utm_', $correct_utm);
        foreach ($arr as $str) {
            $str = 'utm_' . $str;
            list($key, $val) = explode('=', $str);
            if ($key == self::UTM_CAMPAIGN) {
                return str_replace([' ', '&', "\n"], '', $val);
            }
        }
        return null;
    }
    
    /**
     * Определяем имя поля по метке
     *
     * @param unknown $moduleName
     * @param unknown $fieldlabel
     *
     * @return Ambigous <NULL, string>
     */
    private function getFieldByLabel($moduleName, $fieldlabel) {
        $module = Vtiger_Module_Model::getInstance($moduleName);
        $fieldname = null;
        if ($module) {
            $fields = $module->getFields();
            foreach ($fields as $key => $val) {
                if ($val->label == $fieldlabel) {
                    $fieldname = $key;
                    break;
                }
            }
        }
        return $fieldname;
    }

    /**
     * Create campaign with closing date +1 month
     */
    private function _createCampaign($utm)
    {
        global $current_user;
        $newCampaign = Vtiger_Record_Model::getCleanInstance('Campaigns');
        $newCampaign->set('mode', '');
        $campData = [
            'campaignname' => 'Кампания '. date('Ymd'),
            'closingdate'  => date('Y-m-d', strtotime("+1 month", time())),
            'utmID'        => $utm,
            'campaignstatus' => 'Active',
            'createdtime'  => date('Y-m-d H:i:s'),
            'assigned_user_id' => $current_user->id
        ];

        $newCampaign->setData($campData);
        $newCampaign->save();

        return $newCampaign->getId();
    }
}
