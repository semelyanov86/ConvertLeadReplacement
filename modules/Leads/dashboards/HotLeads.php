<?php

class Leads_HotLeads_Dashboard extends Vtiger_IndexAjax_View {

    function getSearchParams($value,$assignedto,$dates) {
        $listSearchParams = array();
        $conditions = array(array('industry','e',$value));
        if($assignedto != '') array_push($conditions,array('assigned_user_id','e',getUserFullName($assignedto)));
        if(!empty($dates)){
            array_push($conditions,array('createdtime','bw',$dates['start'].' 00:00:00,'.$dates['end'].' 23:59:59'));
        }
        $listSearchParams[] = $conditions;
        return '&search_params='. json_encode($listSearchParams);
    }

    public function process(Vtiger_Request $request) {

        global $currentModule;
        $pageNumber = 1;
        $limit = 25;
        $createdtime = $request->get('createdtime');
        if (!$createdtime || $createdtime < 0) {
            $createdtime = 10;
        }

        if ($createdtime > 10080) {
            $createdtime = 10080;
        }
        $nowdate = date('Y-m-d H:i:s', strtotime("- 7 days"));
        $userdate = date('Y-m-d H:i:s', strtotime("- $createdtime minutes"));
        $finalDate = "$nowdate, $userdate";
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $qualifiedModuleName = $request->get("module");
        $qualifiedModuleModel = Vtiger_Module_Model::getInstance($qualifiedModuleName);
        $currentModule = 'Leads';
        $linkId = $request->get('linkid');

        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set("page", $pageNumber);
        $pagingModel->set("limit", $limit);

        $listView = Vtiger_ListView_Model::getInstance($qualifiedModuleName);
        $listView->set('search_params', array(
           '0' => array(
               'columns' => array(
                   '0' => array(
                       'columnname' => 'vtiger_crmentity:createdtime:createdtime:Leads_Created_Time:DT',
                       'comparator' => 'bw',
                       'value' => $finalDate,
                       'column_condition' => 'and'
                   ),
                   '1' => array(
                       'columnname' => 'vtiger_leaddetails:leadstatus:leadstatus:Leads_Lead_Status:V',
                       'comparator' => 'e',
                       'value' => 'Новая заявка!',
                       'column_condition' => ''
                   )
               )
           )
        ));
        $listView->set('orderby', 'createdtime');
        $listView->set('sortorder', 'ASC');
        $fieldList = array('createdtime', 'firstname', 'cf_838', 'cf_1051');
        $listView->extendPopupFields($fieldList);
        $models = $listView->getListViewEntries($pagingModel);

        $nodata = false;

        if (count($models) <= 0) {
            $nodata = true;
//            echo vtranslate('LBL_NO_RECORDS', $qualifiedModuleName);
        }

            $header = array();
            if ($fieldList) {
                foreach ($fieldList as $fieldname) {
                        $fieldModel = Vtiger_Field_Model::getInstance($fieldname, $qualifiedModuleModel);
                        if ($fieldModel->isViewable()) {
                            $header[$fieldname] = $fieldModel;
                        }
                }
            } else {
                $header = $listView->getListViewHeaders();
            }
            $widget = Vtiger_Widget_Model::getInstance($linkId, $currentUser->getId());
            $viewer = $this->getViewer($request);
            $accessibleUsers = $currentUser->getAccessibleUsersForModule($qualifiedModuleName);
            $viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
            $viewer->assign('WIDGET', $widget);
            $viewer->assign("NODATA", $nodata);
            $viewer->assign("MODULE", $qualifiedModuleName);
            $viewer->assign("CREATEDTIME", $createdtime);
            $viewer->assign("QUALIFIED_MODEL", $qualifiedModuleModel);
            $viewer->assign("MODULE_NAME", $qualifiedModuleName);
            $viewer->assign("RELATED_RECORDS", $models);
            $viewer->assign("RELATED_HEADERS", $header);
            $viewer->assign("RELATED_MODULE_MODEL", $qualifiedModuleModel);
            $viewer->assign("RELATED_MODULE_NAME", $qualifiedModuleName);
            $content = $request->get('content');
            if(!empty($content)) {
                $content = false;
            } else {
                $content = true;
            }
            $viewer->assign("CONTENT", $content);

            echo $viewer->view("dashboards/HotLeadsContent.tpl", $qualifiedModuleName, true);

        /*$content = $request->get('content');
        if(!empty($content)) {
            $viewer->view('dashboards/DashBoardWidgetContents.tpl', $qualifiedModuleName);
        } else {
            $viewer->view("dashboards/HotLeadsContent.tpl", $qualifiedModuleName, true);
        }*/
    }

    public function getEntries($relationListView, $pagingModel)
    {
        $db = PearDatabase::getInstance();
        $parentModule = $relationListView->getParentRecordModel()->getModule();
        $relationModule = $relationListView->getRelationModel()->getRelationModuleModel();
        $relationModuleName = $relationModule->get("name");
        $relatedColumnFields = array();
        $fieldModelList = $relationModule->getFields();
        foreach ($fieldModelList as $fieldName => $fieldModel) {
            $relatedColumnFields[$fieldModel->get("column")] = $fieldModel->get("name");
        }
        $instance = CRMEntity::getInstance($relationModuleName);
        $fieldSelect = "";
        $excepttableUserField = "vtiger_" . strtolower($relationModule->get("name")) . "_user_field";
        $excepttable = array("vtiger_inventoryproductrel", "vtiger_crmentityrel", "vtiger_activityproductrel", "vtiger_cntactivityrel", "vtiger_contpotentialrel", "vtiger_inventoryshippingrel", "vtiger_inventorysubproductrel", "vtiger_pricebookproductrel", "vtiger_crmentity_user_field", $excepttableUserField);
        foreach ($instance->tab_name as $table_name) {
            if (!in_array($table_name, $excepttable)) {
                $fieldSelect .= $table_name . ".*, ";
            }
        }
        $fieldSelect = trim($fieldSelect);
        $fieldSelect = substr($fieldSelect, 0, -1);
        if ($relationModuleName == "Calendar") {
            $relatedColumnFields["visibility"] = "visibility";
        }
        if ($relationModuleName == "PriceBooks") {
            $relatedColumnFields["unit_price"] = "unit_price";
            $relatedColumnFields["listprice"] = "listprice";
            $relatedColumnFields["currency_id"] = "currency_id";
        }
        if ($relationModuleName == "Documents") {
            $relatedColumnFields["filelocationtype"] = "filelocationtype";
            $relatedColumnFields["filestatus"] = "filestatus";
        }
        $query = $relationListView->getRelationQuery();
        $queries = explode(" DISTINCT ", $query);
        if (1 < count($queries)) {
            $queries2 = explode(" FROM ", $queries[1]);
            $queries[1] = $fieldSelect . " FROM " . $queries2[1];
            $query = implode(" DISTINCT ", $queries);
        } else {
            $queries2 = explode(" FROM ", $query);
            if (1 < count($queries2)) {
                $queries2[0] .= ", " . $fieldSelect;
                $query = implode(" FROM ", $queries2);
            }
        }
        if ($relationModuleName == "PBXManager" && strpos($query, "vtiger_pbxmanagercf.pbxmanagerid") == false) {
            $query = str_replace("FROM vtiger_pbxmanager", "FROM vtiger_pbxmanager LEFT JOIN vtiger_pbxmanagercf on vtiger_pbxmanagercf.pbxmanagerid = vtiger_pbxmanager.pbxmanagerid", $query);
        }
        $rs = $db->pquery($query, array());
        if ($db->num_rows($rs) <= 0) {
            $query = $relationListView->getRelationQuery();
            $queries = explode(" DISTINCT ", $query);
            $main_table_name = $instance->table_name;
            if (1 < count($queries)) {
                $queries2 = explode(" FROM ", $queries[1]);
                $queries[1] = $main_table_name . ".* FROM " . $queries2[1];
                $query = implode(" DISTINCT ", $queries);
            } else {
                $queries2 = explode(" FROM ", $query);
                if (1 < count($queries2)) {
                    $queries2[0] .= ", " . $main_table_name . ".*";
                    $query = implode(" FROM ", $queries2);
                }
            }
        }
        $queries3 = explode(" FROM ", $query);
        if (1 < count($queries3)) {
            $queries3[0] .= ", vtiger_crmentity.crmid";
            $query = implode(" FROM ", $queries3);
        }
        if ($relationListView->get("whereCondition")) {
            $query = $relationListView->updateQueryWithWhereCondition($query);
        }
        $startIndex = $pagingModel->getStartIndex();
        $pageLimit = $pagingModel->getPageLimit();
        $orderBy = $relationListView->getForSql("orderby");
        $sortOrder = $relationListView->getForSql("sortorder");
        if ($orderBy) {
            if ($orderBy == "fullname" && ($relationModuleName == "Contacts" || $relationModuleName == "Leads")) {
                $orderBy = "firstname";
            }
            $orderByFieldModuleModel = $relationModule->getFieldByColumn($orderBy);
            if ($orderByFieldModuleModel && $orderByFieldModuleModel->isReferenceField()) {
                $queryComponents = $split = spliti(" where ", $query);
                list($selectAndFromClause, $whereCondition) = $queryComponents;
                $qualifiedOrderBy = "vtiger_crmentity" . $orderByFieldModuleModel->get("column");
                $selectAndFromClause .= " LEFT JOIN vtiger_crmentity AS " . $qualifiedOrderBy . " ON " . $orderByFieldModuleModel->get("table") . "." . $orderByFieldModuleModel->get("column") . " = " . $qualifiedOrderBy . ".crmid ";
                $query = $selectAndFromClause . " WHERE " . $whereCondition;
                $query .= " ORDER BY " . $qualifiedOrderBy . ".label " . $sortOrder;
            } else {
                if ($orderByFieldModuleModel && $orderByFieldModuleModel->isOwnerField()) {
                    $query .= " ORDER BY COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname) " . $sortOrder;
                } else {
                    if ($relationModuleName == "Emails") {
                        $queryComponents = $split = spliti(" where ", $query);
                        list($selectAndFromClause, $whereCondition) = $queryComponents;
                        $selectAndFromClause .= " INNER JOIN vtiger_emaildetails ON vtiger_emaildetails.emailid=vtiger_crmentity.crmid ";
                        $query = $selectAndFromClause . " WHERE " . $whereCondition;
                    }
                    $orderByFieldModel = $relationModule->getField($orderBy);
                    $orderByField = $orderByFieldModel->get("column");
                    $query = (string) $query . " ORDER BY " . $orderByField . " " . $sortOrder;
                }
            }
        }
        $limitQuery = $query . " LIMIT " . $startIndex . "," . $pageLimit;
        $result = $db->pquery($limitQuery, array());
        $relatedRecordList = array();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $groupsIds = $this->getGroupsIdsForUsers($currentUser->getId());
        for ($i = 0; $i < $db->num_rows($result); $i++) {
            $row = $db->fetch_row($result, $i);
            $recordId = $db->query_result($result, $i, "crmid");
            $newRow = array();
            foreach ($row as $col => $val) {
                if (array_key_exists($col, $relatedColumnFields)) {
                    if ($relationModuleName == "Documents" && $col == "filename") {
                        $fileName = $db->query_result($result, $i, "filename");
                        $downloadType = $db->query_result($result, $i, "filelocationtype");
                        $status = $db->query_result($result, $i, "filestatus");
                        $fileIdQuery = "select attachmentsid from vtiger_seattachmentsrel where crmid=?";
                        $fileIdRes = $db->pquery($fileIdQuery, array($recordId));
                        $fileId = $db->query_result($fileIdRes, 0, "attachmentsid");
                        if ($fileName != "" && $status == 1) {
                            if ($downloadType == "I") {
                                $val = "<a onclick=\"Javascript:Documents_Index_Js.updateDownloadCount('index.php?module=Documents&action=UpdateDownloadCount&record=" . $recordId . "');\"" . " href=\"index.php?module=Documents&action=DownloadFile&record=" . $recordId . "&fileid=" . $fileId . "\"" . " title=\"" . getTranslatedString("LBL_DOWNLOAD_FILE", $relationModuleName) . "\" >" . textlength_check($val) . "</a>";
                            } else {
                                if ($downloadType == "E") {
                                    $val = "<a onclick=\"Javascript:Documents_Index_Js.updateDownloadCount('index.php?module=Documents&action=UpdateDownloadCount&record=" . $recordId . "');\"" . " href=\"" . $fileName . "\" target=\"_blank\"" . " title=\"" . getTranslatedString("LBL_DOWNLOAD_FILE", $relationModuleName) . "\" >" . textlength_check($val) . "</a>";
                                } else {
                                    $val = " --";
                                }
                            }
                        }
                    }
                    $newRow[$relatedColumnFields[$col]] = $val;
                }
            }
            $ownerId = $row["smownerid"];
            $newRow["assigned_user_id"] = $row["smownerid"];
            if ($relationModuleName == "Calendar") {
                $visibleFields = array("activitytype", "date_start", "time_start", "due_date", "time_end", "assigned_user_id", "visibility", "smownerid", "parent_id");
                $visibility = true;
                if (in_array($ownerId, $groupsIds)) {
                    $visibility = false;
                } else {
                    if ($ownerId == $currentUser->getId()) {
                        $visibility = false;
                    }
                }
                if (!$currentUser->isAdminUser() && $newRow["activitytype"] != "Task" && $newRow["visibility"] == "Private" && $ownerId && $visibility) {
                    foreach ($newRow as $data => $value) {
                        if (in_array($data, $visibleFields) != -1) {
                            unset($newRow[$data]);
                        }
                    }
                    $newRow["subject"] = vtranslate("Busy", "Events") . "*";
                }
                if ($newRow["activitytype"] == "Task") {
                    unset($newRow["visibility"]);
                }
            }
            $record = Vtiger_Record_Model::getCleanInstance($relationModule->get("name"));
            $record->setData($newRow)->setModuleFromInstance($relationModule);
            $record->setId($row["crmid"]);
            $relatedRecordList[$row["crmid"]] = $record;
        }
        $pagingModel->calculatePageRange($relatedRecordList);
        $nextLimitQuery = $query . " LIMIT " . ($startIndex + $pageLimit) . " , 1";
        $nextPageLimitResult = $db->pquery($nextLimitQuery, array());
        if (0 < $db->num_rows($nextPageLimitResult)) {
            $pagingModel->set("nextPageExists", true);
        } else {
            $pagingModel->set("nextPageExists", false);
        }
        return $relatedRecordList;
    }
    protected function getGroupsIdsForUsers($userId)
    {
        vimport("~~/include/utils/GetUserGroups.php");
        $userGroupInstance = new GetUserGroups();
        $userGroupInstance->getAllUserGroups($userId);
        return $userGroupInstance->user_groups;
    }
}