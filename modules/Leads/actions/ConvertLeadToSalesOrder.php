<?php
/*+***********************************************************************************
 * PINstudio
 * Binizik
 *************************************************************************************/

include_once 'include/Webservices/ConvertLead.php';
include_once 'modules/Users/Users.php';

class Leads_ConvertLeadToSalesOrder_Action extends Vtiger_Save_Action {

    private $leadId;       //plain id format
    private $salesOrderId; //plain id format
    private $wsContactId;  //WS format

    //Users::getActiveAdminId();
    const UID = 1; //all operations performed by Admin with uid 1

    // PINstudio @DK #red-86
    function __construct() {
        parent::__construct();
        $this->exposeMethod('getContactByNum');
    }
    // PINstudio end

    public function process(Vtiger_Request $request) {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
            return;
        }
        try {
            //TODO process $_REQUEST before any actions
            $sourceId = $_REQUEST['sourceid'];
            $type = Vtiger_Functions::getCRMRecordType($sourceId);

            switch ($type){
                case 'Contacts':
                    $this->wsContactId = vtws_getWebserviceEntityId('Contacts', $sourceId);
                    $this->salesOrderId = $this->createSalesOrder();
                    //$this->createPayment(); #red-850
                break;
                case 'Leads':
                    $this->leadId = $sourceId;  
                    //PINstudio @DK #red-86
                    $contactAction = $request->get('contactAction');
                    if ($contactAction == 'Create') {
                        $this->wsContactId = $this->createContact();
                    } else {
                        //TODO check $contactAction is a digitonly
                        $this->wsContactId = vtws_getWebserviceEntityId('Contacts', $contactAction);
                        $this->updateExistingContact(); //PINstudio @DK #red-335
                    }
                    if (empty($this->wsContactId)) return false; 

                    $this->salesOrderId = $this->createSalesOrder();
                    $this->setLeadAsConverted();
                    $this->moveRelated();
                    //PINstudio begin @DK #245 #445
                    $this->transferPayments();
                    //$this->createPayment(); #red-850
                    $this->saveLeadContactOrderRelation();
                    //PINstudio end
                break;
            }

            if (!empty($_REQUEST['utmmetka'])){
                $this->bindOrderToCampaign($this->salesOrderId, $_REQUEST['utmmetka']);
            }
            /*
            */
            $this->triggerConverted($this->salesOrderId);

            header('location: index.php?module=SalesOrder&view=Detail&record='.$this->salesOrderId);
            //PINstudio end
        } catch (Exception $e) {
            //TODO reimplement with ajax / trace i.e:
            /*
                1) Contact created <link>
                2) Lead data transfered
                #  Order creation Error
            */
            $viewer = $this->getViewer($request);
            $viewer->assign('DATA', $e->getMessage());
            $viewer->view('Error.tpl', 'Vtiger');
            exit();
        }

    }

    private function createContact()
    {
        $data = $this->getDataForContact();

        $current_user = $this->getOpUser();
        $record = vtws_create('Contacts', $data, $current_user);

        return $record['id']; // Format: XXxXXXX // Module_Webservice_ID x CRM_ID
    }

    //PINstudio begin @DK #red-335
    private function updateExistingContact()
    {
        //PINstudio begin @DK #red-335
        $data = $this->getDataForContact();
        //PINstudio end
        $contactRecord = Vtiger_Record_Model::getInstanceById(
            vtws_getCRMEntityId($this->wsContactId),
            'Contacts'
        );

        if ($_REQUEST['samov'] == 'on') {
            //Самовывоз--------------------
            if (!empty($data['cf_642'])) {
                $contactRecord->set('cf_642', $data['cf_642']);
            }
        } else {
            //Обычная доставка
            if (!empty($data['otherstreet'])) {
                $contactRecord->set('otherstreet', $data['otherstreet']);
            }
            if (!empty($data['othercity'])) {
                $contactRecord->set('othercity', $data['othercity']);
            }
            if (!empty($data['cf_641'])) {
                $contactRecord->set('cf_641', $data['cf_641']);
            }
        }
        //---------------------------------------------------

        if (!empty($data['description'])) {
            $contactRecord->set('description', $data['description']);
        }
        if (!empty($data['otherzip'])) {
            $contactRecord->set('otherzip', $data['otherzip']);
        }
        if (!empty($data['cf_846'])) {
            $contactRecord->set('cf_846', $data['cf_846']);
        }
        //---------------------------------------------------

        if ($_REQUEST['setmefunct'] != 'on') {
            if (!empty($data['email'])) {
                $contactRecord->set('email', $data['email']);
            }

            if (!empty($data['homephone'])) {
                $contactRecord->set('homephone', $data['homephone']);
            }
        }

        $contactRecord->set('mode', 'edit');
        return $contactRecord->save();
    }
    //PINstudio end

    /**
     * Generate required data for Contact
     * globals REQUEST
     *
     * @return array key-value pairs
     */
    private function getDataForContact()
    {
        $data = [];
        $modFields = array_keys(getColumnFields('Contacts'));
        foreach ($modFields as $fieldname) {
            if(empty($_REQUEST[$fieldname])) continue;

            $value = $_REQUEST[$fieldname];
            $data[$fieldname] = $value;
        }

        if ($_REQUEST['samov'] == 'on') {
            $_REQUEST['setmefunct'] = 'on';
        }
        $data['description'] = $_REQUEST['description'];

        if ($_REQUEST['setmefunct'] == 'on') {
            $data['mailingstreet'] = $_REQUEST['lane']; // Адрес
            $data['mailingcity'] = $_REQUEST['city']; // Адрес
            $data['cf_642'] = $_REQUEST['namepolychatel']; // Имя получателя
        } else {
            $data['otherstreet'] = $_REQUEST['lane']; // Адрес
            $data['othercity'] = $_REQUEST['city']; // Адрес
            $data['cf_641'] = $_REQUEST['namepolychatel']; // Имя получателя
        }

        $data['otherzip'] = $_REQUEST['phone']; // Телефон получателя
        $data['cf_846'] = $_REQUEST['brend']; // бренд

        return $data;
    }

    /**
     * Create SalesOrder with WS
     * @return crmid
     */
    private function createSalesOrder()
    {
        $data = $this->getDataForSalesOrder();

        $current_user = Users::getActiveAdminUser();
        $record = vtws_create('SalesOrder', $data, $current_user);
        $crmid = vtws_getCRMEntityId($record['id']);

        return $crmid;
    }

    /**
     * relate order to campaign
     *
     * @param str $orderid crmid of an order
     * @param str $utm raw string
     */
    public function bindOrderToCampaign($orderid, $utmRaw)
    {
        if (empty($orderid) || empty($utmRaw)) return false;

        include_once 'modules/Campaigns/models/Module.php';
        include_once 'modules/Campaigns/Campaigns.php';
        $vtCampaign = Vtiger_Module_Model::getInstance('Campaigns');
        $crmCampaign = CRMEntity::getInstance('Campaigns');
        $utm = $vtCampaign->parseUtm($utmRaw);

        if (empty($utm)) return false;

        $campid = $vtCampaign->getActiveByUTM($utm);
        if (empty($campid)) $campid = $vtCampaign->createByUtm($utm);

        $results = $crmCampaign->save_related_module(
            'Campaigns',
            $campid,
            'SalesOrder',
            $orderid
        );

        if ($results[$orderid] == -1) return $results;

        if (!class_exists('ModTracker')) {
            include_once 'modules/ModTracker/ModTracker.php';
        }

        ModTracker::linkRelation(
            'Campaigns',
            $campid,
            'SalesOrder',
            $orderid
        );

        return $results;
    }

    /**
     * create payment
     * globals REQUEST
     */
    public function createPayment()
    {
        $data = [];
        $data['cf_648'] = $_REQUEST['typepay']; // тип оплаты
        $data['cf_835'] = $_REQUEST['doplata'];
        $data['assigned_user_id'] = '19x'.$_REQUEST['assigned_user_id'];
        //PINstudio begin @DK #445
        if ( $data['cf_835'] > 0) {
            $this->crmCreatePayment($data);
        }
        //PINstudio end
    }

    // Пометить лид, что он конвертированный
    private function setLeadAsConverted()
    {
        $current_user = $this->getOpUser();
        vtws_updateConvertLeadStatus(
            ['Contacts' => $this->wsContactId],
            vtws_getWebserviceEntityId('Leads', $this->leadId),
            $current_user
        );
    }

    // Переместить записи в Контакт
    private function moveRelated()
    {
        vtws_transferLeadRelatedRecords(
            $this->leadId,
            vtws_getCRMEntityId($this->wsContactId),
            'Contacts'
        );

    }

    private function getDataForSalesOrder()
    {
        if (!empty($this->leadId)) $data['fromlead'] = 1;

        $data['subject'] = !empty($_REQUEST['subject']) ? $_REQUEST['subject'] : '-';
        $timedostsam           = $_REQUEST['timedostsam'];
        $timedostminutesam     = $_REQUEST['timedostminutesam'];
        $timedostfromsam       = $_REQUEST['timedostfromsam'];
        $timedostminutefromsam = $_REQUEST['timedostminutefromsam'];

        if ($_REQUEST['samov'] == 'on') {
            $_REQUEST['setmefunct'] = 'on';
            $data['sostatus'] = 'Самовывоз'; // статус
            $intervalDevilery3 = 'Самовывоз '.$timedostsam.':'.$timedostminutesam.'-'.$timedostfromsam.':'.$timedostminutefromsam;
            $data['cf_652']  = $intervalDevilery3; //TODO: Ниже это дважды(!!!) перезаписывается. Почему?
        } else if (($_REQUEST['typepay'] != 'Курьеру') && ($_REQUEST['paystatus'] == '0')) {
            $data['sostatus'] = 'Created';
        } else {
            $data['sostatus'] = 'Approved'; // статус
        }

        $data['cf_648'] = $_REQUEST['typepay']; // тип оплаты
        $data['cf_657'] = $_REQUEST['namepolychatel']; // Получатель
        $data['cf_659'] = $_REQUEST['magazin']; // Магазин доставки

        $shopid = $_REQUEST['shopid'];
        $data['shopid'] = vtws_getWebserviceEntityId('Shops', $shopid);

        $data['cf_707'] = $_REQUEST['brend']; // Бренд
        $data['cf_706'] = $_REQUEST['maptack']; // Где принят заказ
        $data['cf_845'] = $_REQUEST['utmmetka']; // № Заказа
        $data['cf_855'] = $_REQUEST['utmmetka']; // UTM метка //  849
        $data['cf_854'] = $_REQUEST['istok']; // Источник // 848 //TODO: Прокомменитировать почему двум полям присваивается одно и то же значение?
        $data['cf_856'] = $_REQUEST['istok']; // Источник // 848
        $data['cf_851'] = $_REQUEST['nomerzakaz']; // Номер заказа
        $data['cf_649'] = $_REQUEST['proplata']; // Примечание к типу оплаты

        if ($_REQUEST['mobile'] != '') {
            $data['cf_658'] = $_REQUEST['mobile']; // Телефон получателя
        }

        $timedost              = $_REQUEST['timedost'];
        $timedostminute        = $_REQUEST['timedostminute'];
        $timedostfrom          = $_REQUEST['timedostfrom'];
        $timedostminutefrom    = $_REQUEST['timedostminutefrom'];

        $timedostsam           = $_REQUEST['timedostsam'];
        $timedostminutesam     = $_REQUEST['timedostminutesam'];
        $timedostfromsam       = $_REQUEST['timedostfromsam'];
        $timedostminutefromsam = $_REQUEST['timedostminutefromsam'];

        if (($_REQUEST['timeselect'] != 'on')) {
            $intervalDevilery = ''.$timedost.':'.$timedostminute.'-'.$timedostfrom.':'.$timedostminutefrom;
        } else {
            $intervalDevilery = 'Точно к '.$timedost.':'.$timedostminute;
            $data['cf_708'] = $timedost.':'.$timedostminute.':00';
        }

        if ($_REQUEST['samov'] != 'on') {
            $data['cf_652']  = $intervalDevilery; // Интервал доставки
            $data['cf_645'] = 1;
            $data['hdnS_H_Amount'] = (int)$_REQUEST['s_h_amount'];
        }

        $data['ship_street']  = $_REQUEST['lane'];
        $data['ship_city']    = $_REQUEST['city'];
        $data['description']  = $_REQUEST['description'];
        $data['cf_658']       = $_REQUEST['phone'];

        $data['cf_650'] = date('Y-m-d', strtotime( $_REQUEST['cf_650'] )); // $_REQUEST['cf_650'];

        if ($_REQUEST['samov'] != 'on') {
            if ($_REQUEST['timeselect'] != 'on') {
                $data['cf_708'] = $_REQUEST['timededline'].':'.$_REQUEST['timededlineminute'].':00';
            }
        } else {
            $data['cf_652'] = 'Самовывоз '.$timedostsam.':'.$timedostminutesam.'-'.$timedostfromsam.':'.$timedostminutefromsam;
            $data['cf_708'] = $timedostfromsam.':'.$timedostminutefromsam.':00';
        }

        $data['assigned_user_id'] = '19x'.$_REQUEST['assigned_user_id']; //    19 - tabid модуля Users
        $data['cf_834'] = $_REQUEST['paystatus'];
        $data['cf_835'] = $_REQUEST['doplata'];
        $data['cf_675'] = $_REQUEST['aboutme'];  //  $_REQUEST['descriptionpay']    
        $data['contact_id'] = $this->wsContactId;
        $data['LineItems'] = json_decode($_REQUEST['jsonSelectedProducts'], true); // Products
        $data['productid'] = $data['LineItems'][0]['productid'];
        $data['currency_id'] = vtws_getWebserviceEntityId('Currency', 1);
        $data['conversion_rate'] = '1.000';

        return $data;
    }

    //PINstudio begin @DK #445
    /**
     *@param  <String> WS formatted SalesOrder id
     *@param  <Array>  data for a nes SPPayments instance
     *@return <String> WS formatted new payment id
     */
    function crmCreatePayment($data = [])
    {
        $paymentsTable = 'sp_payments';
        $paymentsMod   = 'SPPayments';
        $db = PearDatabase::getInstance();
        $uid = $db->getUniqueID($paymentsTable);
        $contid = vtws_getCRMEntityId($this->wsContactId);
        $soId = $this->salesOrderId;

        $pay = CRMEntity::getInstance($paymentsMod);
        $pay->moduleName = $paymentsMod;
        $pay->column_fields['payer']             = $contid;
        $pay->column_fields['related_to']        = $soId;
        $pay->column_fields['pay_date']          = date('Y-m-d');
        $pay->column_fields['amount']            = $data['cf_835'];
        $pay->column_fields['cf_648']            = $data['cf_648'];
        $pay->column_fields['spstatus']          = 'Scheduled';
        $pay->column_fields['assigned_user_id']  = $_REQUEST['assigned_user_id'];

        $pay->saveentity($paymentsMod);

        return $pay->id;
    }

    public function vtCreatePayment($data = [])
    {
        $paymentsTable = 'sp_payments';
        $paymentsMod   = 'SPPayments';
        $db = PearDatabase::getInstance();
        $uid = $db->getUniqueID($paymentsTable);
        $soContactId = vtws_getCRMEntityId($this->wsContactId);

        $pay = Vtiger_Record_Model::getCleanInstance($paymentsMod);
        $pay->setId($uid);
        $pay->set('mode', '');
        $pay->set('payer',      $soContactId);
        $pay->set('related_to', $this->salesOrderId);
        $pay->set('pay_date',   date('Y-m-d'));
        $pay->set('amount',     $data['cf_835']);
        $pay->set('cf_648',     $data['cf_648']);
        $pay->set('spstatus',   'Scheduled');
        $pay->set('assigned_user_id', $_REQUEST['assigned_user_id']);
        $pay->save();

        return $pay->getId();
    }

    public function wsCreatePayment($data = [])
    {
        $paymentsMod   = 'SPPayments';
        $soId = vtws_getWebserviceEntityId('SalesOrder', $this->salesOrderId);

        $payment['payer']             = $this->wsContactId;
        $payment['related_to']        = $soId;
        $payment['pay_date']          = date('Y-m-d');
        $payment['amount']            = $data['cf_835'];
        $payment['cf_648']            = $data['cf_648'];
        $payment['spstatus']          = 'Scheduled';
        $payment['assigned_user_id']  = $data['assigned_user_id'];

        $current_user = $this->getOpUser();

        return vtws_create($paymentsMod, $payment, $current_user);
    }
    //PINstudio end

    //PINstudio begin @DK #445
    public function saveLeadContactOrderRelation()
    {
        $db = PearDatabase::getInstance();
        $sql = 'INSERT INTO vtiger_leadcontrel VALUES (?, ?, ?)';
        $soContact = vtws_getCRMEntityId($this->wsContactId);
        $result = $db->pquery($sql, [$this->leadId, $soContact, $this->salesOrderId]);

        return $db->getAffectedRowCount($result);
    }
    //PINstudio end

    //PINstudio begin @DK #red-86
    /**
     * Exposed method to check existing contact
     * TODO move to Contact Model
     * @return false if not found and contact info if contact exist
     */
    public function getContactByNum(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $contactData = false;
        $number = substr(preg_replace('/[^\d]/', '', $request->get('number')), -10, 10);

        if ($number && strlen($number) == 10) {

            $sql = "SELECT crmid, concat(firstname,' ',lastname) fio, mobile, phone, fnumber, rnumber, coalesce(rnumber,fnumber)
                FROM vtiger_contactdetails vc
                LEFT JOIN vtiger_pbxmanager_phonelookup vpb ON vpb.crmid = vc.contactid
                LEFT JOIN vtiger_crmentity using (crmid)
                WHERE rnumber LIKE '%{$number}' OR fnumber LIKE '%{$number}' 
                ORDER BY `modifiedtime` DESC LIMIT 1";

            $res = $db->pquery($sql, []);
            $nr  = $db->num_rows($res);

            if ($nr > 0) {
                $sqlResult = $res->FetchRow();
                $contactData = [
                    'crmid' => $sqlResult['crmid'],
                    'fio'   => $sqlResult['fio'],
                    'mob'   => $sqlResult['mobile'],
                    'phone' => $sqlResult['phone']
                ];
            }
        }

        $response = new Vtiger_Response();
        $response->setResult($contactData);
        $response->emit();
    }
    //PINstudio end

    //PINstudio begin @DK #445
    /**
     * Move all Lead related Payments
     * depends on wsContactId, salesOrderId, leadId
     * @return int affected rows
     */
    public function transferPayments()
    {
        $soContact = vtws_getCRMEntityId($this->wsContactId);
        $db = PearDatabase::getInstance();
        $sql = 'UPDATE sp_payments SET payer = ?, related_to = ?
                WHERE payer = ?';
        $result = $db->pquery($sql, [ 
            $soContact,
            $this->salesOrderId,
            $this->leadId
        ]);

        return $db->getAffectedRowCount($result);
    }

    //PINstudio end
    /**
     * Function to retrive Operational User,
     * who is allowed to perform WebServices operations
     * @return <Users> a user object
     */
    private function getOpUser()
    {
        $user = new Users();
        return $user->retrieveCurrentUserInfoFromFile($this::UID);
    }

    function triggerConverted($orderid)
    {
        $db = PearDatabase::getInstance();
        require_once("include/events/include.inc");
        $em = new VTEventsManager($db);
        $em->initTriggerCache();
        $entityData = VTEntityData::fromEntityId($db, $orderid);
        $em->triggerEvent("vtiger.entity.convertlead", $entityData);
    }
}
