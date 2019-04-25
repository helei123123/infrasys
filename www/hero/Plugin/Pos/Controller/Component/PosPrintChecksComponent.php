<?php
/**
 * Print Check Components Controller Class for POS Module
 *
 * This file define the print check Components Controller Class
 * @author    gigi
 * @copyright Copyright (c) 2013, Infrasys International Ltd.
 */

App::uses('Component', 'Controller');
App::uses('Math', 'Lib' . DS . 'Utility');

/**
 * Print Check components controller class to set report authority checking for the pos module
 */
class PosPrintChecksComponent extends Component
{
    public $controller = null;

    /**
     * startup procedure after initialize
     * @param object $controller the created object
     */
    public function startup(Controller $controller)
    {
        $this->controller = $controller;
        if ($this->params['action'] == 'generateCheckReceiptSlip' || $this->params['action'] == 'generateCheckSlip' || $this->params['action'] == 'generateReceiptSlip' || $this->params['action'] == 'generate_special_slip' || $this->params['action'] == 'generateTestingPrinterSlip' || $this->params['action'] == 'generateMultipleSpecialSlip' || $this->params['action'] == 'generateOctopusSlip' || $this->params['action'] == 'printInterfaceAlertSlip' || $this->params['action'] == 'printLoyaltyTransferCardSlip') {
            $this->isIgnoreRestrict = true;
        }
    }

    /**
     * The generate output of the printing slip
     * @param integer $checkId Check ID
     * @      string  $prtFileNamePrefix    Print file name prefix
     * @      integer $type                    1: guest check, 2:receipt, 3:serving list
     */
    public function generateCheckReceiptSlip($checkId = null, $prtqId = null, $pfmtId = null, $type = 1, $posLangIndex,
                                             $preview = 0, $reprintReceipt = 0, $checkVoided = false, $checkVoidedReasonId = 0, $paymentInterfaceArray = array(),
                                             &$resultFile, $printInfo = array(), $bdayId = "", $renderFormatType = '', $pdfExportPath = '')
    {
        Configure::write('debug', 0);
        $this->controller->layout = 'print_slip';

        //	Check if printing plugin exists
        if (!array_key_exists("printing", $this->controller->plugins))
            return 'load_model_error';
        //	Load required model
        $modelArray = array('Pos.PosStation', 'Pos.PosConfig', 'Pos.PosCheck', 'Pos.PosBusinessDay', 'Pos.PosBusinessPeriod', 'Pos.PosPrintFormat', 'Pos.PosOverrideCondition', 'Pos.PosVoidReason', 'Pos.PosCheckTaxScRef',
            'Pos.PosCheckExtraInfo', 'Pos.PosCheckGratuity', 'Pos.PosTaxScType', 'Pos.PosTaiwanGuiTran', 'Pos.PosDiscountType', 'Pos.PosPaymentMethod', 'Outlet.OutShop', 'Outlet.OutMediaObject',
            'Outlet.OutFloorPlan', 'Outlet.OutFloorPlanTable',
            'Outlet.OutCalendar', 'User.UserUser', 'Menu.MenuItemCategory', 'Menu.MenuItemDept', 'Menu.MenuItemCourse', 'Media.MedMedia', 'Menu.MenuItem');
        if ($this->controller->importModels($modelArray) !== true)
            return 'load_model_error';

        $MemMemberModel = null;
        if ($this->controller->importModel('Member.MemMember'))
            $MemMemberModel = $this->controller->MemMember;

        $ResvResvModel = null;
        if ($this->controller->importModel('Reservation.ResvResv'))
            $ResvResvModel = $this->controller->ResvResv;

        $PosStationModel = $this->controller->PosStation;
        $PosConfigModel = $this->controller->PosConfig;
        $PosCheckModel = $this->controller->PosCheck;
        $PosCheckExtraInfoModel = $this->controller->PosCheckExtraInfo;
        $PosCheckGratuityModel = $this->controller->PosCheckGratuity;
        $PosBusinessDayModel = $this->controller->PosBusinessDay;
        $PosBusinessPeriodModel = $this->controller->PosBusinessPeriod;
        $PosPrintFormatModel = $this->controller->PosPrintFormat;
        $PosOverrideConditionModel = $this->controller->PosOverrideCondition;
        $PosTaxScTypeModel = $this->controller->PosTaxScType;
        $PosDiscountTypeModel = $this->controller->PosDiscountType;
        $PosVoidReasonModel = $this->controller->PosVoidReason;
        $PosPaymentMethodModel = $this->controller->PosPaymentMethod;
        $OutShopModel = $this->controller->OutShop;
        $OutMediaObjectModel = $this->controller->OutMediaObject;
        $OutFloorPlanModel = $this->controller->OutFloorPlan;
        $OutFloorPlanTableModel = $this->controller->OutFloorPlanTable;
        $UserUserModel = $this->controller->UserUser;
        $MenuItemCategoryModel = $this->controller->MenuItemCategory;
        $MenuItemDeptModel = $this->controller->MenuItemDept;
        $MenuItemCourseModel = $this->controller->MenuItemCourse;
        $MenuItemModel = $this->controller->MenuItem;
        $MedMediaModel = $this->controller->MedMedia;

        // Static variable
        $langCount = 5;
        $scCount = 5;
        $taxCount = 25;
        $checkInfoCount = 5;

        //	Add this data path in View folder and set The viewPath be empty (default is the controller name)
        $shareDataPath = $this->controller->Common->getDataPath(array('pos_print_formats'));
        App::build(array('View' => array($shareDataPath)));

        $configPath = $this->controller->Common->getDataPath(array('pos_print_jobs'), true);
        $configUrl = $this->controller->Common->getDataUrl('pos_print_jobs/');
        if (empty($configPath) || empty($configUrl))
            return 'missing_config_path';

        //get print format information
        if ($pfmtId <= 0)
            return 'missing_format';
        $printFormat = $PosPrintFormatModel->findActiveById($pfmtId);
        if (empty($printFormat))
            return 'missing_format';
        $prtFmtDefaultLang = 1;
        if ($printFormat['PosPrintFormat']['pfmt_lang'] != 0)
            $prtFmtDefaultLang = $printFormat['PosPrintFormat']['pfmt_lang'];
        else if ($posLangIndex != 0)
            $prtFmtDefaultLang = $posLangIndex;
        $langIndexArray = array(1 => $prtFmtDefaultLang, 2 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5);

        //get check information
        $check = $PosCheckModel->find('first', array(
                'conditions' => array('chks_id' => $checkId),
                'recursive' => 1
            )
        );
        if (empty($check))
            return 'missing_check (id: ' . $checkId . ')';

        //get check extra infos list(exclude the item related record)
        $checkExtraInfos = $PosCheckExtraInfoModel->findAllByCheckIds($checkId);

        //checking payment existence for printing receipt
        //If payment records not ready, receipt will not be printed.
        if ($type == 2) {
            $paymentRecordReady = false;
            $checkTotal = Math::doRounding((float)$check['PosCheck']['chks_check_total'], '', 4);
            if (isset($check['PosCheckPayment']) && count($check['PosCheckPayment']) > 0) {
                $checkPaymentTotal = 0;
                foreach ($check['PosCheckPayment'] as $checkPayment)
                    $checkPaymentTotal += $checkPayment['cpay_pay_total'];
                $checkPaymentTotal = Math::doRounding($checkPaymentTotal, '', 4);
                if ($checkPaymentTotal == $checkTotal) {
                    $paymentRecordReady = true;
                }
            }

            if (!$paymentRecordReady) {
                $resultFile['error'] = 'check_is_not_ready_for_printing_receipt';
                return 'check_is_not_ready_for_printing_receipt';
            }
        }

        //sorting the items according to setup
        $orderBy1 = $this->__generatingItemSortingInformation(true, $printFormat['PosPrintFormat']['pfmt_sort_item_by1']);
        if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] != '')
            $orderBy2 = $this->__generatingItemSortingInformation(false, $printFormat['PosPrintFormat']['pfmt_sort_item_by2']);
        else
            $orderBy2 = '';
        $conditionArray = array(
            'citm_chks_id' => $checkId,
            'citm_status' => ''
        );
        $bPutItemInMainList = false;
        if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "s" || $printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "u" || $printFormat['PosPrintFormat']['pfmt_sort_item_by2'] == "s" || $printFormat['PosPrintFormat']['pfmt_sort_item_by2'] == "u") {
            $bPutItemInMainList = true;
            $conditionArray['citm_role <>'] = "m";
        } else
            $conditionArray['citm_parent_citm_id'] = array("", 0);
        $checkItems = $PosCheckModel->PosCheckItem->find('all', array(
                'conditions' => $conditionArray,
                'order' => $orderBy1 . $orderBy2,
                'recursive' => -1
            )
        );
        if (empty($checkItems) && $checkVoided == false)
            return '';

        //get check's tax / sc references
        $tempCheckTaxScRefs = $this->controller->PosCheckTaxScRef->findAllByCheckIds($check['PosCheck']['chks_id']);
        $checkTaxScRefs = array();
        foreach ($tempCheckTaxScRefs as $tempCheckTaxScRef)
            $checkTaxScRefs[] = $tempCheckTaxScRef['PosCheckTaxScRef'];

        //get item's discount and extra info
        for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
            if ($checkItems[$itemIndex]['PosCheckItem']['citm_pre_disc'] != 0 || $checkItems[$itemIndex]['PosCheckItem']['citm_mid_disc'] != 0 || $checkItems[$itemIndex]['PosCheckItem']['citm_post_disc'] != 0) {
                $itemDiscs = $PosCheckModel->PosCheckDiscount->findAllByCitmId($checkItems[$itemIndex]['PosCheckItem']['citm_id']);
                if (!empty($itemDiscs)) {
                    $checkItems[$itemIndex]['PosCheckItem']['PosCheckDiscount'] = array();
                    //foreach($itemDiscs as $itemDisc) {
                    for ($itemDiscIndex = 0; $itemDiscIndex < count($itemDiscs); $itemDiscIndex++)
                        $checkItems[$itemIndex]['PosCheckItem']['PosCheckDiscount'][] = $itemDiscs[$itemDiscIndex]['PosCheckDiscount'];
                }
            }
        }

        //get user info for open check, print and close check user
        $checkOpenUser = $UserUserModel->findActiveById($check['PosCheck']['chks_open_user_id']);
        $printUser = $UserUserModel->findActiveById($check['PosCheck']['chks_print_user_id']);
        $checkOwnerUser = $UserUserModel->findActiveById($check['PosCheck']['chks_owner_user_id']);
        $checkCloseUser = null;
        if ($type == 2)
            $checkCloseUser = $UserUserModel->findActiveById($check['PosCheck']['chks_close_user_id']);

        //get check table info
        $checkTable = $PosCheckModel->PosCheckTable->findByCheckId($check['PosCheck']['chks_id']);

        //get station info
        $stationId = ($preview || $check['PosCheck']['chks_ordering_mode'] == 'f') ? $check['PosCheck']['chks_open_stat_id'] : $check['PosCheck']['chks_print_stat_id'];
        if ($type == 2 && $reprintReceipt)
            $stationId = $check['PosCheck']['chks_close_stat_id'];
        $station = $PosStationModel->find('first', array(
                'conditions' => array('stat_id' => $stationId),
                'recursive' => -1
            )
        );

        //get outlet info
        $outlet = $OutShopModel->OutOutlet->find('first', array(
                'conditions' => array('olet_id' => $check['PosCheck']['chks_olet_id']),
                'recursive' => -1
            )
        );


        //get outlet logo
        $outletLogo = "";
        if (!empty($outlet))
            $outletLogoMedia = $OutMediaObjectModel->findMediasByObject($outlet['OutOutlet']['olet_id'], 'outlet', 'l');
        if (!empty($outletLogoMedia) && count($outletLogoMedia) > 0) {
            $medConfigs = array(
                'med_path' => $this->controller->Common->getDataPath(array('media_files'), true),
                'med_url' => $this->controller->Common->getDataUrl('media_files/'),
            );

            $media = $MedMediaModel->findActiveById($outletLogoMedia[0]['OutMediaObject']['omed_medi_id']);
            if (!empty($media))
                $outletLogo = Router::url($medConfigs['med_url'] . $media['MedMedia']['medi_filename'], array('full' => true, 'escape' => true));
            else
                $outletLogo = "";

        }

        //get shop info
        if (!empty($outlet))
            $shop = $OutShopModel->find('first', array(
                    'conditions' => array('shop_id' => $outlet['OutOutlet']['olet_shop_id']),
                    'recursive' => -1
                )
            );
        else
            $shop = array();


        //check whether support TaiWan GUI
        $supportTaiWanGUI = false;
        $taiWanGuiGenerateBy = "";
        $taiWanGuiMode = "";
        $taiwanGuiPfmtId = 0;
        if (isset($station['PosStation']['stat_params']) && !empty($station['PosStation']['stat_params'])) {
            $stationParams = json_decode($station['PosStation']['stat_params'], true);
            if (isset($stationParams['tgui'])) {
                $supportTaiWanGUI = true;
                if (isset($stationParams['tgui']['generate_by']))
                    $taiWanGuiGenerateBy = $stationParams['tgui']['generate_by'];
                if (isset($stationParams['tgui']['mode']))
                    $taiWanGuiMode = $stationParams['tgui']['mode'];
                if (isset($stationParams['tgui']['pfmt_id']))
                    $taiwanGuiPfmtId = $stationParams['tgui']['pfmt_id'];
            }
        }

        //get taxes and service charges type
        $scTypes = $PosTaxScTypeModel->findAllActiveSC();
        $taxTypes = $PosTaxScTypeModel->findAllActiveTaxes();

        //get discount type
        $discountTypes = array();

        //get table information
        if (!empty($outlet))
            $floorPlan = $OutFloorPlanModel->findFloorPlanByOutlet($outlet['OutOutlet']['olet_id'], 1);
        if (!empty($floorPlan))
            $floorPlanId = $floorPlan[0]['OutFloorPlan']['flrp_id'];
        else
            $floorPlanId = 0;
        $floorPlanMapId = array();
        foreach ($floorPlan[0]['OutFloorPlanMap'] as $floorPlanMap)
            $floorPlanMapId[] = $floorPlanMap['flrm_id'];
        $tableInfo = $OutFloorPlanTableModel->find('all', array(
                'fields' => array(
                    'flrt_name_l1',
                    'flrt_name_l2',
                    'flrt_name_l3',
                    'flrt_name_l4',
                    'flrt_name_l5'
                ),
                'conditions' => array(
                    'flrt_flrp_id' => $floorPlanId,
                    'flrt_flrm_id' => $floorPlanMapId,
                    'flrt_table' => $checkTable['PosCheckTable']['ctbl_table'],
                    'flrt_table_ext' => $checkTable['PosCheckTable']['ctbl_table_ext'],
                    'flrt_status' => ''
                ),
                'recursive' => -1
            )
        );

        //get business day and period information
        if (!empty($bdayId))
            $businessDay = $PosBusinessDayModel->findById($bdayId);
        else if (!empty($outlet))
            $businessDay = $PosBusinessDayModel->findActiveByOutletId($outlet['OutOutlet']['olet_id']);

        if (empty($businessDay))
            return 'missing_business_day';
        if (!empty($shop))
            $timeNow = date('H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));
        else
            $timeNow = date('H:i:s');
        $businessPeriod = $PosBusinessPeriodModel->findById($check['PosCheck']['chks_bper_id']);

        //get print queue override condition
        $printQueueOverrideConditions = $PosOverrideConditionModel->findAllActivePrtqConditionsByOutletAndFromPrtqId($outlet['OutOutlet']['olet_id'], $prtqId);

        //check isHoliday, isDayBeforeHoliday, isSpecialDay, isDayBeforeSpecialDay
        $isHoliday = false;
        $isSpecialDay = false;
        $isDayBeforeHoliday = false;
        $isDayBeforeSpecialDay = false;
        $weekday = date("w", mktime(0, 0, 0, substr($businessDay['PosBusinessDay']['bday_date'], 5, 2), substr($businessDay['PosBusinessDay']['bday_date'], 8, 2), substr($businessDay['PosBusinessDay']['bday_date'], 0, 4)));
        $this->__checkCalendarHolidaySpecialDay($businessDay['PosBusinessDay']['bday_date'], $shop['OutShop']['shop_id'], $outlet['OutOutlet']['olet_id'], $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay);

        //from the check discount value base on item
        $checkDiscs = array();
        $checkDiscsValuePerItem = array();
        if ($check['PosCheck']['chks_pre_disc'] != 0 || $check['PosCheck']['chks_mid_disc'] != 0 || $check['PosCheck']['chks_post_disc'] != 0) {
            $checkDiscs = $PosCheckModel->PosCheckDiscount->findAllByChksIdCitmId($check['PosCheck']['chks_id'], array("", 0), 1);

            //pre-handle for each check discount for item
            if (!empty($checkDiscs)) {
                for ($discIndex = 0; $discIndex < count($checkDiscs); $discIndex++) {
                    for ($discItemIndex = 0; $discItemIndex < count($checkDiscs[$discIndex]['PosCheckDiscountItem']); $discItemIndex++) {
                        for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                            if ($checkItems[$itemIndex]['PosCheckItem']['citm_id'] == $checkDiscs[$discIndex]['PosCheckDiscountItem'][$discItemIndex]['cdit_citm_id']) {
                                if (!isset($checkItems[$itemIndex]['PosCheckItem']['PosCheckDiscountItem']))
                                    $checkItems[$itemIndex]['PosCheckItem']['PosCheckDiscountItem'] = array();
                                $checkItems[$itemIndex]['PosCheckItem']['PosCheckDiscountItem'][] = $checkDiscs[$discIndex]['PosCheckDiscountItem'][$discItemIndex];
                                break;
                            }
                        }

                        $checkDiscsValuePerItem[$checkDiscs[$discIndex]['PosCheckDiscountItem'][$discItemIndex]['cdit_citm_id']][] = $checkDiscs[$discIndex]['PosCheckDiscountItem'][$discItemIndex]['cdit_round_total'];
                    }

                    //check discount's extra infos
                    $checkDiscExtraInfos = $PosCheckExtraInfoModel->findAllByCheckDiscountId($checkDiscs[$discIndex]['PosCheckDiscount']['cdis_id']);
                    if (!empty($checkDiscExtraInfos)) {
                        foreach ($checkDiscExtraInfos as $checkDiscExtraInfo)
                            $checkDiscs[$discIndex]['checkExtraInfos'][] = $checkDiscExtraInfo['PosCheckExtraInfo'];
                    }
                }
            }
        }

        //swipe breakdown value
        if (isset($printInfo['isBreakdownFromInclusiveNoBreakdown']) && $printInfo['isBreakdownFromInclusiveNoBreakdown'] == 1) {
            $chkLevelExtraInfos = array();
            for ($i = 0; $i < count($checkExtraInfos); $i++) {
                if ($checkExtraInfos[$i]['PosCheckExtraInfo']['ckei_by'] == 'check' && empty($checkExtraInfos[$i]['PosCheckExtraInfo']['ckei_citm_id']))
                    $chkLevelExtraInfos[] = $checkExtraInfos[$i]['PosCheckExtraInfo'];
                else if ($checkExtraInfos[$i]['PosCheckExtraInfo']['ckei_by'] == 'item' && !empty($checkExtraInfos[$i]['PosCheckExtraInfo']['ckei_citm_id'])) {
                    $tempItemExtraInfo = array();
                    for ($j = 0; $j < count($checkItems); $j++) {
                        if ($checkItems[$j]['PosCheckItem']['citm_id'] == $checkExtraInfos[$i]['PosCheckExtraInfo']['ckei_citm_id']) {
                            $tempItemExtraInfo[] = $checkExtraInfos[$i]['PosCheckExtraInfo'];
                            $this->__swipeItemBreakdownValue($checkItems[$j]['PosCheckItem'], $tempItemExtraInfo);
                            break;
                        }
                    }
                }
            }

            $tempItem = array();
            $this->__swipeBreakdownValue($check, $chkLevelExtraInfos, $tempItem);
        }

        //pre-handling for Malaysia tax analysis
        $this->__calculateMalaysiaTaxAnaylsis($check, $checkItems, $businessDay, $taxTypes);

        //construct the var array for render view
        $scTotal = 0;
        $taxTotal = 0;
        $vars = array();
        $DiscTotalByDiscType = array();
        $departmentTotals = array();
        $Departments = array();
        $cateogriesTotals = array();
        $Categories = array();

        $wohAwardSettingLists = array();
        $isWohModelExist = false;

        //Woh model existence checking
        if ($this->controller->importModel('Woh.WohAwardSetting')) {
            $isWohModelExist = true;
            // get Woh award list setup
            App::import('Component', 'Woh.WohApiGeneral');
            $wohApiGeneralComponent = new WohApiGeneralComponent(new ComponentCollection());
            $wohApiGeneralComponent->startup($this->controller);
            $shopId = $shop['OutShop']['shop_id'];
            $outletId = $outlet['OutOutlet']['olet_id'];
            $info = array('shopId' => $shopId, 'outletId' => $outletId);
            $wohApiGeneralComponent->getAwardSettingListsByShopOutletId($info, $wohAwardSettingLists);
        }

        $departmentTotals[0] = 0;
        $depts = $MenuItemDeptModel->createTree();

        if (!empty($depts)) {
            foreach ($depts as $dept) {
                $Department = array(
                    'DepartmentId' => $dept['MenuItemDept']['idep_id'],
                    'DepartmentName' => $dept['MenuItemDept']['idep_name_l' . $prtFmtDefaultLang],
                    'DepartmentNameL1' => $dept['MenuItemDept']['idep_name_l1'],
                    'DepartmentNameL2' => $dept['MenuItemDept']['idep_name_l2'],
                    'DepartmentNameL3' => $dept['MenuItemDept']['idep_name_l3'],
                    'DepartmentNameL4' => $dept['MenuItemDept']['idep_name_l4'],
                    'DepartmentNameL5' => $dept['MenuItemDept']['idep_name_l5'],
                    'DepartmentTotal' => 0
                );
                $departmentTotals[$dept['MenuItemDept']['idep_id']] = 0;

                // Department Eligibile printing variable
                if ($isWohModelExist && !empty($wohAwardSettingLists)) {
                    // Hyatt Point Redemption handling
                    if (in_array($dept['MenuItemDept']['idep_id'], $wohAwardSettingLists['wohEligibleAwdItemDepartmentIds']))
                        $Department['DepartmentEligibleForPointRedemption'] = true;
                    else
                        $Department['DepartmentEligibleForPointRedemption'] = false;

                    // Hyatt Earn Point handling
                    if (in_array($dept['MenuItemDept']['idep_id'], $wohAwardSettingLists['wohEligibleEarningItemDepartmentIds']))
                        $Department['DepartmentEligibleForEarnPoint'] = true;
                    else
                        $Department['DepartmentEligibleForEarnPoint'] = false;
                }
                //push the array to $Departments
                $Departments[] = $Department;
            }
        }

        //initialize the Category loop
        $Categories[] = array(
            'CategoryId' => 0,
            'CategoryName' => '',
            'CategoryNameL1' => '',
            'CategoryNameL2' => '',
            'CategoryNameL3' => '',
            'CategoryNameL4' => '',
            'CategoryNameL5' => '',
            'CategoryTotal' => 0
        );
        $categoryTotals[0] = 0;
        $itemCategories = $MenuItemCategoryModel->createTree();
        if (!empty($itemCategories)) {
            foreach ($itemCategories as $category) {
                $Categories[] = array(
                    'CategoryId' => $category['MenuItemCategory']['icat_id'],
                    'CategoryName' => $category['MenuItemCategory']['icat_name_l' . $prtFmtDefaultLang],
                    'CategoryNameL1' => $category['MenuItemCategory']['icat_name_l1'],
                    'CategoryNameL2' => $category['MenuItemCategory']['icat_name_l2'],
                    'CategoryNameL3' => $category['MenuItemCategory']['icat_name_l3'],
                    'CategoryNameL4' => $category['MenuItemCategory']['icat_name_l4'],
                    'CategoryNameL5' => $category['MenuItemCategory']['icat_name_l5'],
                    'CategoryTotal' => 0
                );
                $categoryTotals[$category['MenuItemCategory']['icat_id']] = 0;
            }
        }

        //initialize the variable $vars
        $this->__initializeCheckVars($vars, $type, $supportTaiWanGUI);

        //assign general information to $var
        if (!empty($checkTable)) {
            $vars['TableNumber'] = (($checkTable['PosCheckTable']['ctbl_table'] == 0) ? "" : $checkTable['PosCheckTable']['ctbl_table']) . $checkTable['PosCheckTable']['ctbl_table_ext'];
        }
        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($station))
                    $vars['StationName'] = $station['PosStation']['stat_name_l' . $langIndex];
                if (!empty($shop))
                    $vars['ShopName'] = $shop['OutShop']['shop_name_l' . $langIndex];
                if (!empty($tableInfo))
                    $vars['TableName'] = $tableInfo[0]['OutFloorPlanTable']['flrt_name_l' . $langIndex];
            } else {
                if (!empty($station))
                    $vars['StationNameL' . $langIndex] = $station['PosStation']['stat_name_l' . $langIndex];
                if (!empty($shop))
                    $vars['ShopNameL' . $langIndex] = $shop['OutShop']['shop_name_l' . $langIndex];
                if (!empty($tableInfo))
                    $vars['TableNameL' . $langIndex] = $tableInfo[0]['OutFloorPlanTable']['flrt_name_l' . $langIndex];
            }
        }

        //assign outlet information to $var
        $vars['OutletLogo'] = $outletLogo;
        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($outlet)) {
                    $vars['OutletName'] = $outlet['OutOutlet']['olet_name_l' . $langIndex];
                    $vars['Address'] = $outlet['OutOutlet']['olet_addr_l' . $langIndex];
                }
                if (!empty($businessPeriod))
                    $vars['Greeting'] = $businessPeriod['PosBusinessPeriod']['bper_greeting_l' . $langIndex];
            } else {
                if (!empty($outlet)) {
                    $vars['OutletNameL' . $langIndex] = $outlet['OutOutlet']['olet_name_l' . $langIndex];
                    $vars['AddressL' . $langIndex] = $outlet['OutOutlet']['olet_addr_l' . $langIndex];
                }
                if (!empty($businessPeriod))
                    $vars['GreetingL' . $langIndex] = $businessPeriod['PosBusinessPeriod']['bper_greeting_l' . $langIndex];
            }
        }
        if (!empty($outlet)) {
            $vars['OutletCode'] = $outlet['OutOutlet']['olet_code'];
            $vars['OutletCurrencyCode'] = $outlet['OutOutlet']['olet_currency_code'];
            $vars['Phone'] = $outlet['OutOutlet']['olet_phone'];
            $vars['Fax'] = $outlet['OutOutlet']['olet_fax'];
            $vars['DollarSign'] = $outlet['OutOutlet']['olet_currency_sign'];
        }

        // assign check meal period name to $var
        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($businessPeriod)) {
                    $vars['CheckMealPeriodName'] = $businessPeriod['PosBusinessPeriod']['bper_name_l' . $langIndex];
                    $vars['CheckMealShortPeriodName'] = $businessPeriod['PosBusinessPeriod']['bper_short_name_l' . $langIndex];
                }
            } else {
                if (!empty($businessPeriod)) {
                    $vars['CheckMealPeriodNameL' . $langIndex] = $businessPeriod['PosBusinessPeriod']['bper_name_l' . $langIndex];
                    $vars['CheckMealShortPeriodNameL'] = $businessPeriod['PosBusinessPeriod']['bper_short_name_l' . $langIndex];
                }
            }
        }

        //assign tax and sc information to $var
        if (!empty($scTypes)) {
            foreach ($scTypes as $scType) {
                if ($scType['PosTaxScType']['txsc_number'] >= 1 || $scType['PosTaxScType']['txsc_number'] <= $scCount) {
                    if ($prtFmtDefaultLang > 0) {
                        $vars['SCName' . $scType['PosTaxScType']['txsc_number']] = $this->__checkStringExist($scType['PosTaxScType'], 'txsc_name_l' . $prtFmtDefaultLang);
                        $vars['SCShortName' . $scType['PosTaxScType']['txsc_number']] = $this->__checkStringExist($scType['PosTaxScType'], 'txsc_short_name_l' . $prtFmtDefaultLang);
                    }
                    for ($langIndex = 1; $langIndex <= 5; $langIndex++) {
                        $vars['SCName' . $scType['PosTaxScType']['txsc_number'] . 'L' . $langIndex] = $this->__checkStringExist($scType['PosTaxScType'], 'txsc_name_l' . $langIndex);
                        $vars['SCShortName' . $scType['PosTaxScType']['txsc_number'] . 'L' . $langIndex] = $this->__checkStringExist($scType['PosTaxScType'], 'txsc_short_name_l' . $langIndex);
                    }
                    // Service Charge Eligibile printing variable
                    if ($isWohModelExist && !empty($wohAwardSettingLists)) {
                        // Point redemption handling
                        if (in_array($scType['PosTaxScType']['txsc_id'], $wohAwardSettingLists['wohEligibleAwdServiceChargeIds']))
                            $vars['SCEligibleForPointRedemption' . $scType['PosTaxScType']['txsc_number']] = true;
                        else
                            $vars['SCEligibleForPointRedemption' . $scType['PosTaxScType']['txsc_number']] = false;

                        // Earn point handling
                        if (in_array($scType['PosTaxScType']['txsc_id'], $wohAwardSettingLists['wohEligibleEarningServiceChargeIds']))
                            $vars['SCEligibleForEarnPoint' . $scType['PosTaxScType']['txsc_number']] = true;
                        else
                            $vars['SCEligibleForEarnPoint' . $scType['PosTaxScType']['txsc_number']] = false;
                    }
                }
            }
        }

        if (!empty($taxTypes)) {
            foreach ($taxTypes as $taxType) {
                if ($taxType['PosTaxScType']['txsc_number'] >= 1 || $taxType['PosTaxScType']['txsc_number'] <= $taxCount) {
                    if ($prtFmtDefaultLang > 0) {
                        $vars['TaxName' . $taxType['PosTaxScType']['txsc_number']] = $this->__checkStringExist($taxType['PosTaxScType'], 'txsc_name_l' . $prtFmtDefaultLang);
                        $vars['TaxShortName' . $taxType['PosTaxScType']['txsc_number']] = $this->__checkStringExist($taxType['PosTaxScType'], 'txsc_short_name_l' . $prtFmtDefaultLang);
                    }
                    for ($langIndex = 1; $langIndex <= 5; $langIndex++) {
                        $vars['TaxName' . $taxType['PosTaxScType']['txsc_number'] . 'L' . $langIndex] = $this->__checkStringExist($taxType['PosTaxScType'], 'txsc_name_l' . $langIndex);
                        $vars['TaxShortName' . $taxType['PosTaxScType']['txsc_number'] . 'L' . $langIndex] = $this->__checkStringExist($taxType['PosTaxScType'], 'txsc_short_name_l' . $langIndex);
                    }
                    $vars['TaxRate' . $taxType['PosTaxScType']['txsc_number']] = $taxType['PosTaxScType']['txsc_rate'] * 100; // calculate back in %
                }
            }
        }

        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($checkOpenUser)) {
                    $vars['CheckOpenEmployeeFirstName'] = $checkOpenUser['UserUser']['user_first_name_l' . $langIndex];
                    $vars['CheckOpenEmployeeLastName'] = $checkOpenUser['UserUser']['user_last_name_l' . $langIndex];
                    $vars['CheckOpenEmployee'] = $vars['CheckOpenEmployeeLastName'] . ' ' . $vars['CheckOpenEmployeeFirstName'];
                }
                if (!empty($checkOwnerUser)) {
                    $vars['CheckOwnerEmployeeFirstName'] = $checkOwnerUser['UserUser']['user_first_name_l' . $langIndex];
                    $vars['CheckOwnerEmployeeLastName'] = $checkOwnerUser['UserUser']['user_last_name_l' . $langIndex];
                    $vars['CheckOwnerEmployee'] = $checkOwnerUser['UserUser']['user_last_name_l' . $langIndex] . ' ' . $checkOwnerUser['UserUser']['user_first_name_l' . $langIndex];
                }
            } else {
                if (!empty($checkOpenUser)) {
                    $vars['CheckOpenEmployeeFirstNameL' . $langIndex] = $checkOpenUser['UserUser']['user_first_name_l' . $langIndex];
                    $vars['CheckOpenEmployeeLastNameL' . $langIndex] = $checkOpenUser['UserUser']['user_last_name_l' . $langIndex];
                    $vars['CheckOpenEmployeeL' . $langIndex] = $vars['CheckOpenEmployeeLastNameL' . $langIndex] . ' ' . $vars['CheckOpenEmployeeFirstNameL' . $langIndex];
                }
                if (!empty($checkOwnerUser)) {
                    $vars['CheckOwnerEmployeeFirstNameL' . $langIndex] = $checkOwnerUser['UserUser']['user_first_name_l' . $langIndex];
                    $vars['CheckOwnerEmployeeLastNameL' . $langIndex] = $checkOwnerUser['UserUser']['user_last_name_l' . $langIndex];
                    $vars['CheckOwnerEmployeeL' . $langIndex] = $vars['CheckOwnerEmployeeLastNameL' . $langIndex] . ' ' . $vars['CheckOwnerEmployeeFirstNameL' . $langIndex];
                }
            }
        }
        $vars['CheckNumber'] = $check['PosCheck']['chks_check_prefix_num'];
        $vars['CheckTitle'] = "";
        $vars['CheckGuests'] = $check['PosCheck']['chks_guests'];
        $vars['CheckOpenTime'] = date('H:i:s', strtotime($check['PosCheck']['chks_open_loctime']));
        if (!empty($checkOpenUser))
            $vars['CheckOpenEmployeeNum'] = $checkOpenUser['UserUser']['user_number'];
        if ($check['PosCheck']['chks_memb_id'] > 0 && $MemMemberModel != null) {
            $checkMember = $MemMemberModel->findActiveById($check['PosCheck']['chks_memb_id'], 1);
            if (!empty($checkMember)) {
                $vars['CheckMemberNum'] = $checkMember['MemMember']['memb_number'];
                for ($index = 1; $index <= 2; $index++)
                    $vars['CheckMemberName' . $index] = $checkMember['MemMember']['memb_last_name_l' . $index] . ' ' . $checkMember['MemMember']['memb_first_name_l' . $index];
                $vars['CheckMemberDisplayName'] = $checkMember['MemMember']['memb_display_name'];

                if (isset($checkMember['MemMemberModuleInfo']) && !empty($checkMember['MemMemberModuleInfo'])) {
                    foreach ($checkMember['MemMemberModuleInfo'] as $checkMemberModuleInfo) {
                        if (strcmp($checkMemberModuleInfo['minf_module_alias'], "pos") == 0 && strcmp($checkMemberModuleInfo['minf_variable'], "life_time_spending") == 0) {
                            $vars['CheckMemberSpending'] = $checkMemberModuleInfo['minf_value'];
                            break;
                        }
                    }
                }
            }
        }
        if (strcmp($check['PosCheck']['chks_ordering_type'], "t") == 0)
            $vars['CheckTakeout'] = 1;
        if (strcmp($check['PosCheck']['chks_non_revenue'], "y") == 0)
            $vars['CheckNonRevenue'] = 1;
        if (strcmp($check['PosCheck']['chks_non_revenue'], "a") == 0)
            $vars['CheckAdvanceOrder'] = 1;
        $vars['CheckOrderMode'] = $check['PosCheck']['chks_ordering_mode'];
        $vars['Barcode'] = $check['PosCheck']['chks_check_num'];
        $vars['QRCode'] = $check['PosCheck']['chks_check_num'];

        // Csontruct payment inferface info
        if (!empty($paymentInterfaceArray)) {
            $vars['OgsPayUrl'] = isset($paymentInterfaceArray['payUrl']) ? $paymentInterfaceArray['payUrl'] : "";
            $vars['OgsPayMatchNumber'] = isset($paymentInterfaceArray['matchNumber']) ? $paymentInterfaceArray['matchNumber'] : "";
            if ($type == 2) {
                if (!empty($paymentInterfaceArray['receipt_url'])) {
                    $vars['OgsEInvoiceQRCode'] = $paymentInterfaceArray['receipt_url'];
                    $vars['OgsPayReceiptUrl'] = $paymentInterfaceArray['receipt_url'];
                }
                $vars['OgsEInvoiceQRCodeError'] = (isset($paymentInterfaceArray['receipt_url_error'])) ? $paymentInterfaceArray['receipt_url_error'] : 0;
            }
        }

        // For voided check printing
        if ($checkVoided == true) {
            $vars['CheckVoided'] = 1;
            $voidReasonRecord = $PosVoidReasonModel->findActiveById($checkVoidedReasonId, -1);
            if (!empty($voidReasonRecord)) {
                for ($voidReasonIndex = 1; $voidReasonIndex <= 5; $voidReasonIndex++)
                    $vars['CheckVoidedReasonL' . $voidReasonIndex] = $this->__checkStringExist($voidReasonRecord['PosVoidReason'], 'vdrs_name_l' . $voidReasonIndex);

                foreach ($langIndexArray as $key => $langIndex) {
                    if ($key == 1) {
                        if (!empty($voidReasonRecord))
                            $vars['CheckVoidedReason'] = $this->__checkStringExist($voidReasonRecord['PosVoidReason'], 'vdrs_name_l' . $langIndex);
                    } else {
                        if (!empty($voidReasonRecord))
                            $vars['CheckVoidedReasonL' . $langIndex] = $this->__checkStringExist($voidReasonRecord['PosVoidReason'], 'vdrs_name_l' . $langIndex);
                    }
                }
            }
        }

        $DefaultPayments = array();
        $tempDefaultPayments = array();
        $tempDefaultPaymentSortKey = array();
        $defaultPaymentTotal = 0.0;
        $defaultPaymentCnt = 0;
        if (!empty($checkExtraInfos)) {
            foreach ($checkExtraInfos as $checkExtraInfo) {
                switch ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_variable')) {
                    case 'call_no':
                        $vars['CheckCallNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                        break;
                    case 'check_info':
                        $vars['CheckInfo' . $checkExtraInfo['PosCheckExtraInfo']['ckei_index']] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                        break;
                    case 'paytype':
                        $vars['OgsPaytype'] = $checkExtraInfo['PosCheckExtraInfo']['ckei_value'];
                        break;
                    default:
                        break;
                }

                // for section membership interface
                if ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_section') == "membership_interface") {
                    switch ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_variable')) {
                        case 'account_name':
                        case 'member_name':
                            $vars['CheckMembershipIntfAccountName'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'other_name':
                            $vars['CheckMembershipIntfMemberOtherName'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'account_number':
                            $vars['CheckMembershipIntfAccountNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'member_number':
                            $vars['CheckMembershipIntfMemberNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'nric_number':
                            $vars['CheckMembershipIntfNric'] = "****" . substr($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value'), 4, 5);
                            break;
                        case 'points_balance':
                            $vars['CheckMembershipIntfPointBalance'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'points_earn':
                            $vars['CheckMembershipIntfPointEarn'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'amount_for_earn_point':
                            $vars['CheckMembershipIntfAmountForEarnPoint'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'card_no':
                            $vars['CheckMembershipIntfCardNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'available_voucher_list':
                            if ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value') != "") {
                                $voucherListJson = json_decode($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value'), true);
                                $voucherList = "";
                                if (isset($voucherListJson['voucherList'])) {
                                    $voucherCnt = 0;
                                    $vars['MembershipIntfAvailableVouchers'][] = array();
                                    foreach ($voucherListJson['voucherList'] as $voucher)
                                        $vars['MembershipIntfAvailableVouchers'][] = array("MembershipIntfAvailableVoucher" => $voucher['voucherNumber']);
                                }
                            }
                            break;
                        case 'event_order_number_for_add':
                            $vars['CheckMembershipIntfEventOrderNumberForAdd'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'event_order_deposit_for_add':
                            $vars['CheckMembershipIntfEventOrderDepositForAdd'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'event_order_deposit_balance':
                            $vars['CheckMembershipIntfEventOrderDepositBalance'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'event_order_number_for_use':
                            $vars['CheckMembershipIntfEventOrderNumberForUse'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'event_order_deposit_for_use':
                            $vars['CheckMembershipIntfEventOrderDepositForUse'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'card_store_value':
                            $vars['CheckMembershipIntfStoreValueBalance'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'exp_date':
                            $vars['CheckMembershipIntfExpiryDate'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'original_points':
                            $vars['CheckMembershipIntfOriginalPoints'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'posted_points_use':
                            $vars['CheckMembershipIntfPostedPointsUse'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'posted_amount_use':
                            $vars['CheckMembershipIntfPostedAmountUse'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'member_type':
                            $vars['CheckMembershipIntfMemberType'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'english_name':
                            $vars['CheckMembershipIntfMemberSurname'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'local_balance':
                            $vars['CheckMembershipIntfLocalBalance'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'points_available':
                            $vars['CheckMembershipIntfPointsAvailable'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'total_points_balance':
                            $vars['CheckMembershipIntfTotalPointsBalance'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'max_redempt_points':
                            $vars['CheckMembershipIntfMaxRedemptionPoints'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'points_use':
                            $vars['CheckMembershipIntfPointsToUse'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'max_redempt_amount':
                            $vars['CheckMembershipIntfMaxRedemptionAmount'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'amount_use':
                            $vars['CheckMembershipIntfAmountToUse'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'refund_amount':
                            $vars['CheckMembershipIntfRefundAmount'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'refund_points':
                            $vars['CheckMembershipIntfRefundPoints'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'voucher_value':
                            $vars['CheckMembershipIntfVoucherValue'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'no_redemption':
                            $vars['CheckMembershipIntfNoRedemption'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'member_info':
                            if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 1)
                                $vars['CheckMembershipIntfMemberInfo1'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 2)
                                $vars['CheckMembershipIntfMemberInfo2'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 3)
                                $vars['CheckMembershipIntfMemberInfo3'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 4)
                                $vars['CheckMembershipIntfMemberInfo4'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 5)
                                $vars['CheckMembershipIntfMemberInfo5'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 6)
                                $vars['CheckMembershipIntfMemberInfo6'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 7)
                                $vars['CheckMembershipIntfMemberInfo7'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 8)
                                $vars['CheckMembershipIntfMemberInfo8'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'bonus_code':
                            if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 1)
                                $vars['BonusCode1'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 2)
                                $vars['BonusCode2'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 3)
                                $vars['BonusCode3'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 4)
                                $vars['BonusCode4'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 5)
                                $vars['BonusCode5'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 6)
                                $vars['BonusCode6'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 7)
                                $vars['BonusCode7'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 8)
                                $vars['BonusCode8'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 9)
                                $vars['BonusCode9'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            else if ($checkExtraInfo['PosCheckExtraInfo']['ckei_index'] == 10)
                                $vars['BonusCode10'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'reference':
                            $vars['CheckMembershipIntfMemberRef'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                //Loyalty interface
                if ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_section') == "loyalty") {
                    switch ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_variable')) {
                        case 'member_name':
                            $vars['CheckLoyaltyMemberName'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'member_number':
                            $vars['CheckLoyaltyMemberNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'card_no':
                            $vars['CheckLoyaltyCardNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'points_earn':
                            $vars['CheckLoyaltyPointEarn'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'bonus_balance':
                            $vars['CheckLoyaltyPointBalance'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'exp_date':
                            $vars['CheckLoyaltyPointExpiryDate'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'point_redeem':
                            $vars['CheckLoyaltyPointRedeem'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                //Gaming Interface - Check Extra Info
                if ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_section') == "gaming_interface") {
                    switch ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_variable')) {
                        case 'member_name':
                            $vars['CheckGamingIntfMemberName'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            $vars['CheckGamingIntfMemberFirstName'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'member_last_name':
                            $vars['CheckGamingIntfMemberLastName'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'member_number':
                            $vars['CheckGamingIntfMemberNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'card_no':
                            $vars['CheckGamingIntfMemberCardNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'input_type':
                            $vars['CheckGamingIntfInputMethod'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'points_balance':
                            $vars['CheckGamingIntfPointBalance'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'points_department':
                            $vars['CheckGamingIntfPointDepartment'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'account_number':
                            $vars['CheckGamingIntfAccountNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'card_type_name':
                            $vars['CheckGamingIntfCardType'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }
                //Gaming Interface - Discount Extra Info
                if ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_by') == "discount" && $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_section') == "gaming_interface") {
                    switch ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_variable')) {
                        case 'discount_rate':
                            $vars['CheckGamingIntfDiscountRate'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                // PMS interface - Check Extra Info
                if ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_section') == "pms") {
                    switch ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_variable')) {
                        case 'room':
                            $vars['CheckPmsIntfRoomNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'guest_name':
                            $vars['CheckPmsIntfRoomGuestName'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                // Add advance order information
                if ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_section') == "advance_order") {
                    switch ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_variable')) {
                        case 'reference':
                            $vars['CheckReferenceNumber'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'pickup_date':
                            $vars['CheckPickupDate'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'phone':
                            $vars['CheckGuestPhone'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'guest_name':
                            $vars['CheckGuestName'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'fax':
                            $vars['CheckGuestFax'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'note1':
                            $vars['CheckGuestNote' . '1'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'note2':
                            $vars['CheckGuestNote' . '2'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        case 'deposit_amount':
                            $vars['CheckDepositAmount'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                // for section payment interface
                if ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_section') == "payment_interface") {
                    switch ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_variable')) {
                        case 'qr_code':
                            $vars['QRCode'] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                if ($type != 2) {
                    if ($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_section') == "default_payment" && strcmp($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value'), "") != 0) {
                        $defaultPaymentInfo = json_decode($this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_value'), true);
                        if ($defaultPaymentInfo != NULL && isset($defaultPaymentInfo['paym_id']) && $defaultPaymentInfo['paym_id'] > 0) {
                            $paymentMethod = $PosPaymentMethodModel->findActiveById($defaultPaymentInfo['paym_id'], -1);
                            if (!empty($paymentMethod)) {
                                $defaultPayAmount = isset($defaultPaymentInfo['amount']) ? $defaultPaymentInfo['amount'] : 0;
                                $defaultPaymentCnt++;
                                $defaultPaymentTotal += $defaultPayAmount;
                                $tempDefaultPaymentSortKey[$defaultPaymentCnt] = $this->__checkStringExist($checkExtraInfo['PosCheckExtraInfo'], 'ckei_index');
                                $tempDefaultPayments[] = array(
                                    'DefaultPaymentTempIdx' => $defaultPaymentCnt,
                                    'DefaultPaymentName' => $paymentMethod['PosPaymentMethod']['paym_name_l' . $prtFmtDefaultLang],
                                    'DefaultPaymentNameL1' => $paymentMethod['PosPaymentMethod']['paym_name_l1'],
                                    'DefaultPaymentNameL2' => $paymentMethod['PosPaymentMethod']['paym_name_l2'],
                                    'DefaultPaymentNameL3' => $paymentMethod['PosPaymentMethod']['paym_name_l3'],
                                    'DefaultPaymentNameL4' => $paymentMethod['PosPaymentMethod']['paym_name_l4'],
                                    'DefaultPaymentNameL5' => $paymentMethod['PosPaymentMethod']['paym_name_l5'],
                                    'DefaultPaymentAmount' => number_format($defaultPayAmount, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "")
                                );
                            }
                        }
                    }
                }
            }
        }

        //sorting default payment list by key
        if (count($tempDefaultPayments) > 0) {
            asort($tempDefaultPaymentSortKey);
            foreach ($tempDefaultPaymentSortKey as $key => $extraInfoIndex) {
                foreach ($tempDefaultPayments as $tempDefaultPayment) {
                    if ($tempDefaultPayment['DefaultPaymentTempIdx'] == $key) {
                        $DefaultPayments[] = $tempDefaultPayment;
                        break;
                    }
                }
            }
            $vars['CheckDefaultPaymentTotal'] = number_format($defaultPaymentTotal, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        }

        //assign printing information in $var
        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($printUser)) {
                    $vars['PrintEmployeeFirstName'] = $printUser['UserUser']['user_first_name_l' . $langIndex];
                    $vars['PrintEmployeeLastName'] = $printUser['UserUser']['user_last_name_l' . $langIndex];
                    $vars['PrintEmployee'] = $vars['PrintEmployeeLastName'] . ' ' . $vars['PrintEmployeeFirstName'];
                }
            } else {
                if (!empty($printUser)) {
                    $vars['PrintEmployeeFirstNameL' . $langIndex] = $printUser['UserUser']['user_first_name_l' . $langIndex];
                    $vars['PrintEmployeeLastNameL' . $langIndex] = $printUser['UserUser']['user_last_name_l' . $langIndex];
                    $vars['PrintEmployeeL' . $langIndex] = $vars['PrintEmployeeLastNameL' . $langIndex] . ' ' . $vars['PrintEmployeeFirstNameL' . $langIndex];
                }
            }
        }
        if ($preview == 1)
            $vars['PrintTime'] = date('H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));
        else
            $vars['PrintTime'] = date('H:i:s', strtotime($check['PosCheck']['chks_print_loctime']));
        if (!empty($printUser))
            $vars['PrintEmployeeNum'] = $printUser['UserUser']['user_number'];
        $vars['PrintCount'] = $check['PosCheck']['chks_print_count'];
        $vars['ReceiptPrintCount'] = $check['PosCheck']['chks_receipt_print_count'];
        if ($type == 2 && $reprintReceipt == 1)
            $vars['ReprintReceipt'] = 1;

        //assign check Total information in $var
        $vars['CheckTotal'] = number_format($check['PosCheck']['chks_check_total'], $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");
        for ($index = 1; $index <= 5; $index++) {
            $vars['SC' . $index] = number_format($check['PosCheck']['chks_sc' . $index], $businessDay['PosBusinessDay']['bday_sc_decimal'], ".", "");
            $scTotal += $check['PosCheck']['chks_sc' . $index];
        }
        $vars['SCTotal'] = number_format($scTotal, $businessDay['PosBusinessDay']['bday_sc_decimal'], ".", "");
        $discountForTaxTotal = 0;
        for ($index = 1; $index <= 25; $index++) {
            $vars['Tax' . $index] = number_format($check['PosCheck']['chks_tax' . $index], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", "");
            $discountForTaxTotal += $check['PosCheck']['discountOnTax'][$index];
            $vars['DiscountForTax' . $index] = number_format($check['PosCheck']['discountOnTax'][$index], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", "");
            $taxTotal += $check['PosCheck']['chks_tax' . $index];
        }
        for ($index = 1; $index <= 4; $index++) {
            $vars['InclusiveTaxRef' . $index] = number_format($check['PosCheck']['chks_incl_tax_ref' . $index], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", "");
        }
        $vars['TaxTotal'] = number_format($taxTotal, $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", "");
        $vars['DiscountForTaxTotal'] = number_format($discountForTaxTotal, $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", "");

        //get discount on sc / tax
        $this->__handleOnCheckTaxScRefs($vars, $businessDay['PosBusinessDay'], $checkTaxScRefs);

        //assign check surcharge total in $var
        $vars['CheckSurchargeTotal'] = isset($check['PosCheck']['chks_surcharge_total']) ? number_format($check['PosCheck']['chks_surcharge_total'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "") : 0;

        //get reservation payment total
        if ($ResvResvModel != null && $check['PosCheck']['chks_resv_book_date'] != null && !empty($check['PosCheck']['chks_resv_book_date']) && !empty($check['PosCheck']['chks_resv_refno_with_prefix'])) {
            $reservation = $ResvResvModel->findActiveByOutletDateConfirmNo($outlet['OutOutlet']['olet_id'], $check['PosCheck']['chks_resv_book_date'], $check['PosCheck']['chks_resv_refno_with_prefix']);
            if (!empty($reservation['ResvResv']) && isset($reservation['ResvResv']['resv_payment_total'])) {
                $vars['ReservationPaymentTotal'] = $reservation['ResvResv']['resv_payment_total'];
            }
        }
        $vars['ReservationPaymentTotal'] = number_format($vars['ReservationPaymentTotal'], $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");

        //generate the value of TaiWan GUI
        if ($supportTaiWanGUI && $type == 2) {
            $businessDayTaxRound = $this->__checkStringExist($businessDay, 'bday_tax_round');
            $businessDayTaxDecimal = $this->__checkNumericExist($businessDay, 'bday_tax_decimal');
            $businessDayItemDecimal = $this->__checkNumericExist($businessDay, 'bday_item_decimal');
            $tempBusinessDay['bday_tax_round'] = $businessDayTaxRound;
            $tempBusinessDay['bday_tax_decimal'] = $businessDayTaxDecimal;
            $tempBusinessDay['bday_item_decimal'] = $businessDayItemDecimal;
            $tempBusinessDay['bday_date'] = $businessDay['PosBusinessDay']['bday_date'];

            $this->__generateTaiWanGuiPrintVariable($vars, $station['PosStation']['stat_id'], $station['PosStation']['stat_params'], $shop['OutShop']['shop_timezone'] . " min", $prtFmtDefaultLang, $taxTypes, $tempBusinessDay, $check['PosCheck'], $checkItems);
        }

        //Check item handling
        $Items = array();
        $totalItem = 0;
        $totalAmount = 0;
        $totalAmountUseItemOriPrice = 0;
        $checkItemDiscTotal = 0;
        $menuItemArray = array();
        foreach ($checkItems as $checkItem) {
            $checkItem = $checkItem['PosCheckItem'];
            if (!empty($checkItem) && $checkItem['citm_status'] != "d") {
                if ($checkItem['citm_child_count'] > 0) {
                    $childItems = $PosCheckModel->PosCheckItem->find('all', array(
                            'conditions' => array(
                                'citm_parent_citm_id' => $checkItem['citm_id'],
                                'citm_role' => 'c',
                                'citm_status <>' => 'd'
                            ),
                        )
                    );
                    $checkItem['ChildItemList'] = array();
                    if (!empty($childItems)) {
                        $childModiIds = array();
                        foreach ($childItems as $childItem) {
                            $childModiIds[] = $childItem['PosCheckItem']['citm_id'];
                            $childItem['PosCheckItem']['PosCheckExtraInfo'] = array();
                            $childItem['PosCheckItem']['PosCheckDiscount'] = array();
                            if ($childItem['PosCheckItem']['citm_pre_disc'] != 0 || $childItem['PosCheckItem']['citm_mid_disc'] != 0 || $childItem['PosCheckItem']['citm_post_disc'] != 0) {
                                $childItemDiscs = $PosCheckModel->PosCheckDiscount->findAllByCitmId($childItem['PosCheckItem']['citm_id']);
                                if (!empty($childItemDiscs)) {
                                    foreach ($childItemDiscs as $childItemDisc)
                                        $childItem['PosCheckItem']['PosCheckDiscount'][] = $childItemDisc['PosCheckDiscount'];
                                }
                            }

                            $childItem['PosCheckItem']['ModifierList'] = array();
                            if ($childItem['PosCheckItem']['citm_modifier_count'] > 0) {
                                $modifierItems = $PosCheckModel->PosCheckItem->findModifierByParentItemId($childItem['PosCheckItem']['citm_id']);
                                if (!empty($modifierItems)) {
                                    foreach ($modifierItems as $modifierItem) {
                                        $childModiIds[] = $modifierItem['PosCheckItem']['citm_id'];
                                        $childItem['PosCheckItem']['ModifierList'][] = $modifierItem['PosCheckItem'];
                                    }
                                }
                            }
                            $checkItem['ChildItemList'][] = $childItem['PosCheckItem'];
                        }

                        if (isset($printInfo['isBreakdownFromInclusiveNoBreakdown']) && $printInfo['isBreakdownFromInclusiveNoBreakdown'] == 1) {
                            $childModiExtraInfos = $PosCheckExtraInfoModel->findAllByItemIds($childModiIds);
                            for ($i = 0; $i < count($childModiExtraInfos); $i++) {
                                $bRecordFound = false;
                                for ($j = 0; $j < count($checkItem['ChildItemList']); $j++) {
                                    if ($checkItem['ChildItemList'][$j]['citm_id'] == $childModiExtraInfos[$i]['PosCheckExtraInfo']['ckei_citm_id']) {
                                        $checkItem['ChildItemList'][$j]['PosCheckExtraInfo'][] = $childModiExtraInfos[$i]['PosCheckExtraInfo'];
                                        $bRecordFound = true;
                                        break;
                                    }

                                    if ($bRecordFound)
                                        continue;
                                    for ($k = 0; $k < count($checkItem['ChildItemList'][$j]['ModifierList']); $k++) {
                                        if ($checkItem['ChildItemList'][$j]['ModifierList'][$k]['PosCheckItem']['citm_id'] == $childModiExtraInfos[$i]['PosCheckExtraInfo']['ckei_citm_id']) {
                                            $checkItem['ChildItemList'][$j]['ModifierList'][$k]['PosCheckExtraInfo'][] = $childModiExtraInfos[$i]['PosCheckExtraInfo'];
                                            break;
                                        }
                                    }
                                }
                            }

                            for ($i = 0; $i < count($checkItem['ChildItemList']); $i++) {
                                if (isset($checkItem['ChildItemList'][$i]['PosCheckExtraInfo']) && count($checkItem['ChildItemList'][$i]['PosCheckExtraInfo']) > 0)
                                    $this->__swipeItemBreakdownValue($checkItem['ChildItemList'][$i], $checkItem['ChildItemList'][$i]['PosCheckExtraInfo']);

                                if ($checkItem['ChildItemList'][$i]['citm_modifier_count'] > 0) {
                                    for ($j = 0; $j < count($checkItem['ChildItemList'][$i]['ModifierList']); $j++) {
                                        if (!isset($checkItem['ChildItemList'][$i]['ModifierList'][$j]['PosCheckExtraInfo']) || count($checkItem['ChildItemList'][$i]['ModifierList'][$j]['PosCheckExtraInfo']) == 0)
                                            continue;
                                        $this->__swipeItemBreakdownValue($checkItem['ChildItemList'][$i]['ModifierList'][$j], $checkItem['ChildItemList'][$i]['ModifierList'][$j]['PosCheckExtraInfo']);
                                    }
                                }

                                if ($checkItem['ChildItemList'][$i]['citm_pre_disc'] != 0) {
                                    for ($j = 0; $j < count($checkItem['ChildItemList'][$i]['PosCheckDiscount']); $j++) {
                                        $tempChildDiscExtraInfos = $PosCheckExtraInfoModel->findAllByCheckDiscountId($checkItem['ChildItemList'][$i]['PosCheckDiscount'][$j]['cdis_id']);

                                        if (empty($tempChildDiscExtraInfos))
                                            continue;
                                        $childDiscExtraInfos = array();
                                        foreach ($tempChildDiscExtraInfos as $tempChildDiscExtraInfo)
                                            $childDiscExtraInfos[] = $tempChildDiscExtraInfo['PosCheckExtraInfo'];
                                        $this->__swipeDiscBreakdownValue($checkItem['ChildItemList'][$i]['PosCheckDiscount'][$j], $childDiscExtraInfos);
                                    }
                                }
                            }
                        }
                    }
                }

                $checkItem['ModifierList'] = array();
                if ($checkItem['citm_modifier_count'] > 0) {
                    $modifierItems = $PosCheckModel->PosCheckItem->findModifierByParentItemId($checkItem['citm_id']);
                    if (!empty($modifierItems)) {
                        $checkItem['ModifierList'] = $modifierItems;

                        if (isset($printInfo['isBreakdownFromInclusiveNoBreakdown']) && $printInfo['isBreakdownFromInclusiveNoBreakdown'] == 1) {
                            $modifierIds = array();
                            foreach ($checkItem['ModifierList'] as $modifierItem)
                                $modifierIds[] = $modifierItem['PosCheckItem']['citm_id'];
                            $modifierExtraInfos = $PosCheckExtraInfoModel->findAllByItemIds($modifierIds);
                            for ($i = 0; $i < count($modifierExtraInfos); $i++) {
                                for ($j = 0; $j < count($checkItem['ModifierList']); $j++) {
                                    if ($checkItem['ModifierList'][$j]['PosCheckItem']['citm_id'] == $modifierExtraInfos[$i]['PosCheckExtraInfo']['ckei_citm_id']) {
                                        $checkItem['ModifierList'][$j]['PosCheckExtraInfo'][] = $modifierExtraInfos[$i]['PosCheckExtraInfo'];
                                        break;
                                    }
                                }
                            }
                            for ($i = 0; $i < count($checkItem['ModifierList']); $i++) {
                                if (!isset($checkItem['ModifierList'][$i]['PosCheckExtraInfo']) || count($checkItem['ModifierList'][$i]['PosCheckExtraInfo']) == 0)
                                    continue;
                                $this->__swipeItemBreakdownValue($checkItem['ModifierList'][$i]['PosCheckItem'], $checkItem['ModifierList'][$i]['PosCheckExtraInfo']);
                            }
                        }
                    }
                }

                //Check no-print item
                if ($checkItem['citm_no_print'] == 'y' && $checkItem['citm_round_total'] == 0)
                    continue;

                //Get item extra info
                $onlineCoupon = array("number" => "");
                $voucher = array("number" => "");
                $itemReference = "";
                $itemLoyaltyPointBalance = "";
                $itemLoyaltyPointAddValue = "";
                $itemLoyaltyCardNumber = "";
                $itemExtraInfos = $this->__getExtraInfo($checkExtraInfos, 0, $checkItem['citm_id']);
                if (!empty($itemExtraInfos)) {
                    foreach ($itemExtraInfos as $itemExtraInfo) {
                        if ($itemExtraInfo['ckei_by'] == "item" && ($itemExtraInfo['ckei_section'] == "online_coupon")) {
                            switch ($itemExtraInfo['ckei_variable']) {
                                case "svc_coupon_number":
                                    $onlineCoupon['number'] = (isset($itemExtraInfo['ckei_value'])) ? $itemExtraInfo['ckei_value'] : "";
                                    break;
                                default:
                                    break;
                            }
                        }


                        if ($itemExtraInfo['ckei_by'] == "item" && ($itemExtraInfo['ckei_section'] == "membership_interface")) {
                            switch ($itemExtraInfo['ckei_variable']) {
                                case "voucher_number":
                                    $voucher['number'] = (isset($itemExtraInfo['ckei_value'])) ? $itemExtraInfo['ckei_value'] : "";
                                    break;
                                default:
                                    break;
                            }
                        }

                        if ($itemExtraInfo['ckei_by'] == "item" && ($itemExtraInfo['ckei_section'] == "loyalty")) {
                            switch ($itemExtraInfo['ckei_variable']) {
                                case "points_balance":
                                    $itemLoyaltyPointBalance = (isset($itemExtraInfo['ckei_value'])) ? $itemExtraInfo['ckei_value'] : "";
                                    break;
                                case "points_earn":
                                    $itemLoyaltyPointAddValue = (isset($itemExtraInfo['ckei_value'])) ? $itemExtraInfo['ckei_value'] : "";
                                    break;
                                case "card_no":
                                    $itemLoyaltyCardNumber = (isset($itemExtraInfo['ckei_value'])) ? $itemExtraInfo['ckei_value'] : "";
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                }

                // Get loyalty info form extra info in item level
                $loyaltyItemInfo = array("svcCardNumber" => "", "memberNumber" => "", "svcCardExpiryDate" => "", "svcRemark" => "");
                if (!empty($itemExtraInfos)) {
                    foreach ($itemExtraInfos as $itemExtraInfo) {
                        if ($itemExtraInfo['ckei_by'] == "item" && ($itemExtraInfo['ckei_section'] == "loyalty")) {
                            switch ($itemExtraInfo['ckei_variable']) {
                                case "svc_card_number":
                                    $loyaltyItemInfo['svcCardNumber'] = (isset($itemExtraInfo['ckei_value'])) ? $itemExtraInfo['ckei_value'] : "";
                                    break;
                                case "member_number":
                                    $loyaltyItemInfo['memberNumber'] = (isset($itemExtraInfo['ckei_value'])) ? $itemExtraInfo['ckei_value'] : "";
                                    break;
                                case "member_valid_through":
                                    $loyaltyItemInfo['svcCardExpiryDate'] = (isset($itemExtraInfo['ckei_value'])) ? $itemExtraInfo['ckei_value'] : "";
                                    break;
                                case "remark":
                                    $loyaltyItemInfo['svcRemark'] = (isset($itemExtraInfo['ckei_value'])) ? $itemExtraInfo['ckei_value'] : "";
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                }

                //Checking for grouping items
                if (!empty($printFormat) && $printFormat['PosPrintFormat']['pfmt_group_item_by'] == 'c') {
                    if (count($Items) > 0) {
                        $itemGrouped = false;

                        if ($checkItem['citm_ordering_type'] == 't')
                            $itemTakeoutChecking = 1;
                        else
                            $itemTakeoutChecking = 0;
                        $itemGrossPriceChecking = $checkItem['citm_price'];

                        $checkItemChecking['Modifiers'] = array();
                        $modifiersChecking = array();
                        if ($checkItem['citm_modifier_count'] > 0) {
                            $modifiersChecking = $checkItem['ModifierList'];
                            if (!empty($modifiersChecking)) {
                                foreach ($modifiersChecking as $modifierChecking) {
                                    $itemGrossPriceChecking += $modifierChecking['PosCheckItem']['citm_price'];
                                    if (strcmp($modifierChecking['PosCheckItem']['citm_no_print'], '') == 0) {
                                        // count number of modifiers
                                        if (!array_key_exists($modifierChecking['PosCheckItem']['citm_item_id'], $checkItemChecking['Modifiers']))
                                            $checkItemChecking['Modifiers'][$modifierChecking['PosCheckItem']['citm_item_id']] = 1;
                                        else
                                            $checkItemChecking['Modifiers'][$modifierChecking['PosCheckItem']['citm_item_id']] += 1;
                                    }
                                }
                            }
                        }

                        $childItemAppliedDiscTotal = 0;
                        $childItemTotal = 0;
                        $childItemsChecking = array();

                        $checkItemChecking['ChildItems'] = array();
                        $checkItemChecking['ChildDetail'] = array();

                        if ($checkItem['citm_child_count'] > 0) {
                            $childItemsChecking = $checkItem['ChildItemList'];
                            if (!empty($childItemsChecking)) {
                                foreach ($childItemsChecking as $childItemChecking) {
                                    $childItemTotal += $this->__checkNumericExist($childItemChecking, "citm_round_total");

                                    if (!array_key_exists($childItemChecking['citm_item_id'], $checkItemChecking['ChildItems'])) {
                                        $checkItemChecking['ChildItems'][$childItemChecking['citm_item_id']] = 1;
                                        $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Modifiers'] = array();
                                        if (isset($childItemChecking['ModifierList']) && count($childItemChecking['ModifierList']) > 0) {
                                            $childItemModifiersChecking = $childItemChecking['ModifierList'];
                                            foreach ($childItemModifiersChecking as $childItemModifierChecking) {
                                                // skip no print modifier
                                                if (strcmp($childItemModifierChecking['citm_no_print'], 'y') == 0)
                                                    continue;

                                                if (!array_key_exists($childItemModifierChecking['citm_item_id'], $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Modifiers']))
                                                    $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Modifiers'][$childItemModifierChecking['citm_item_id']] = 1;
                                                else
                                                    $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Modifiers'][$childItemModifierChecking['citm_item_id']] += 1;
                                            }
                                        }
                                        $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Discounts'] = array();
                                        if (isset($childItemChecking['PosCheckDiscount']) && count($childItemChecking['PosCheckDiscount']) > 0) {
                                            $childItemDiscountsChecking = $childItemChecking['PosCheckDiscount'];
                                            foreach ($childItemDiscountsChecking as $childItemDiscountChecking) {
                                                if (!array_key_exists($childItemDiscountChecking['cdis_dtyp_id'], $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Discounts']))
                                                    $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Discounts'][$childItemDiscountChecking['cdis_dtyp_id']] = 1;
                                                else
                                                    $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Discounts'][$childItemDiscountChecking['cdis_dtyp_id']] += 1;
                                            }
                                        }
                                    } else
                                        $checkItemChecking['ChildItems'][$childItemChecking['citm_item_id']] += 1;
                                }
                            }
                        }

                        $itemAppliedDiscTotal = 0;
                        $checkItemChecking['Discounts'] = array();

                        $itemDiscsChecking = array();
                        if ($checkItem['citm_pre_disc'] != 0 || $checkItem['citm_mid_disc'] != 0 || $checkItem['citm_post_disc'] != 0) {
                            $itemDiscsChecking = $PosCheckModel->PosCheckDiscount->findAllByCitmId($checkItem['citm_id']);
                            if (!empty($itemDiscsChecking)) {
                                for ($itemDiscIndex = 0; $itemDiscIndex < count($itemDiscsChecking); $itemDiscIndex++) {
                                    $itemDiscExtraInfos = $PosCheckExtraInfoModel->findAllByCheckDiscountId($itemDiscsChecking[$itemDiscIndex]['PosCheckDiscount']['cdis_id']);
                                    if (isset($printInfo['isBreakdownFromInclusiveNoBreakdown']) && $printInfo['isBreakdownFromInclusiveNoBreakdown'] == 1) {
                                        $tmpItemDiscExtraInfos = array();
                                        foreach ($itemDiscExtraInfos as $tempItemDiscExtraInfo)
                                            $tmpItemDiscExtraInfos[] = $tempItemDiscExtraInfo['PosCheckExtraInfo'];
                                        if (isset($printInfo['isBreakdownFromInclusiveNoBreakdown']) && $printInfo['isBreakdownFromInclusiveNoBreakdown'] == 1)
                                            $this->__swipeDiscBreakdownValue($itemDiscsChecking[$itemDiscIndex]['PosCheckDiscount'], $tmpItemDiscExtraInfos);
                                    }

                                    $itemAppliedDiscTotal += $itemDiscsChecking[$itemDiscIndex]['PosCheckDiscount']['cdis_round_total'];
                                    if (!array_key_exists($itemDiscsChecking[$itemDiscIndex]['PosCheckDiscount']['cdis_dtyp_id'], $checkItemChecking['Discounts']))
                                        $checkItemChecking['Discounts'][$itemDiscsChecking[$itemDiscIndex]['PosCheckDiscount']['cdis_dtyp_id']] = 1;
                                    else
                                        $checkItemChecking['Discounts'][$itemDiscsChecking[$itemDiscIndex]['PosCheckDiscount']['cdis_dtyp_id']] += 1;

                                    if (!empty($itemDiscExtraInfos)) {
                                        $itemDiscsChecking[$itemDiscIndex]['PosCheckDiscount']['checkExtraInfos'][] = array();
                                        foreach ($itemDiscExtraInfos as $itemDiscExtraInfo)
                                            $itemDiscsChecking[$itemDiscIndex]['PosCheckDiscount']['checkExtraInfos'][] = $itemDiscExtraInfo['PosCheckExtraInfo'];
                                    }
                                }
                            }
                        }

                        $childDetailIds = array();
                        $itemsCheckingList = $this->__formItemsCheckingArray($Items, $childDetailIds);

                        for ($itemIndex = 0; $itemIndex < count($Items); $itemIndex++) {
                            //whether same menu item ID
                            if ($Items[$itemIndex]['ItemId'] != $checkItem['citm_item_id'])
                                continue;

                            //whether same item description
                            if ($Items[$itemIndex]['ItemName'] != $checkItem['citm_name_l' . $prtFmtDefaultLang] || $Items[$itemIndex]['ItemNameL1'] != $checkItem['citm_name_l1'] || $Items[$itemIndex]['ItemNameL2'] != $checkItem['citm_name_l2'] || $Items[$itemIndex]['ItemNameL3'] != $checkItem['citm_name_l3'] || $Items[$itemIndex]['ItemNameL4'] != $checkItem['citm_name_l4'] || $Items[$itemIndex]['ItemNameL5'] != $checkItem['citm_name_l5'])
                                continue;

                            //whether same takeout status
                            if ($Items[$itemIndex]['ItemTakeout'] != $itemTakeoutChecking)
                                continue;

                            //whether same gross price
                            if ($Items[$itemIndex]['ItemGrossPrice'] != $itemGrossPriceChecking)
                                continue;

                            //whether same printing modifiers
                            $diffItemPrtModi = array_diff_assoc($itemsCheckingList[$itemIndex]['Modifiers'], $checkItemChecking['Modifiers']);
                            if (!empty($diffItemPrtModi) || count($itemsCheckingList[$itemIndex]['Modifiers']) != count($checkItemChecking['Modifiers']))
                                continue;

                            //whether same printing childitems
                            $diffItemPrtChild = array_diff_assoc($itemsCheckingList[$itemIndex]['ChildItems'], $checkItemChecking['ChildItems']);
                            if (!empty($diffItemPrtChild) || count($itemsCheckingList[$itemIndex]['ChildItems']) != count($checkItemChecking['ChildItems']))
                                continue;
                            $sameChildItemDetail = true;
                            foreach ($checkItemChecking['ChildDetail'] as $id => $childItemDetailChecking) {
                                foreach ($childItemDetailChecking as $detail => $arrayIds) {
                                    if (!empty(array_diff_assoc($itemsCheckingList[$itemIndex]['ChildDetail'][$id][$detail], $arrayIds)) || count($itemsCheckingList[$itemIndex]['ChildDetail'][$id][$detail]) != count($arrayIds)) {
                                        $sameChildItemDetail = false;
                                        break;
                                    }
                                }
                                if (!$sameChildItemDetail)
                                    break;
                            }
                            if (!$sameChildItemDetail)
                                continue;

                            //whether same applied discount
                            $diffItemAppliedDisc = array_diff_assoc($itemsCheckingList[$itemIndex]['Discounts'], $checkItemChecking['Discounts']);
                            if (!empty($diffItemAppliedDisc) || count($itemsCheckingList[$itemIndex]['Discounts']) != count($checkItemChecking['Discounts']))
                                continue;

                            //whether same coupon number
                            if ($Items[$itemIndex]['ItemCouponNumber'] != $onlineCoupon['number'])
                                continue;

                            $itemGrouped = true;

                            if ($checkItem['citm_child_count'] > 0) {
                                $childItemsChecking = $checkItem['ChildItemList'];
                                if (!empty($childItemsChecking)) {
                                    foreach ($childItemsChecking as $childItemChecking) {
                                        for ($index = 0; $index < count($Items); $index++) {
                                            if (count($childItemsChecking) != count($Items[$index]['ChildItems']))
                                                continue;
                                            $reply = array();
                                            $childItemGrouped = $this->groupChildItems($childItemChecking, $Items[$index]['ChildItems'], $checkItemChecking['ChildDetail'], $itemsCheckingList[$index]['ChildDetail'], $prtFmtDefaultLang, $businessDay, $DiscTotalByDiscType, $departmentTotals, $depts, $categoryTotals, $itemCategories, $reply);
                                            if ($childItemGrouped) {
                                                $childItemAppliedDiscTotal += $reply['itemAppliedDiscTotal'];
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            $itemAppliedDiscTotal += $childItemAppliedDiscTotal;

                            //update Items list
                            $Items[$itemIndex]['ItemQuantity'] += $checkItem['citm_qty'];
                            $Items[$itemIndex]['ItemPrice'] += ($checkItem['citm_round_total'] + $itemAppliedDiscTotal);
                            $Items[$itemIndex]['ItemTotal'] += $checkItem['citm_round_total'] + $childItemTotal;
                            $Items[$itemIndex]['ItemDiscountTotal'] += $itemAppliedDiscTotal;
                            for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++) {
                                $itemTax[$taxIndex] = 0;
                                $itemTax[$taxIndex] = ($checkItem['tax' . $taxIndex . '_on_citm_round_total'] + $checkItem['tax' . $taxIndex . '_on_citm_sc1'] + $checkItem['tax' . $taxIndex . '_on_citm_sc2'] + $checkItem['tax' . $taxIndex . '_on_citm_sc3'] + $checkItem['tax' . $taxIndex . '_on_citm_sc4'] + $checkItem['tax' . $taxIndex . '_on_citm_sc5']);
                                $Items[$itemIndex]['ItemTaxTotal'] += $itemTax[$taxIndex];
                                $Items[$itemIndex]['ItemTax' . $taxIndex] += $itemTax[$taxIndex];

                                if ($itemTax[$taxIndex] > 0) {
                                    $checkDiscountTotalForItem = 0;
                                    // get the check discount round total for item
                                    if (!empty($checkDiscsValuePerItem) && isset($checkDiscsValuePerItem[$checkItem['citm_id']])) {
                                        foreach ($checkDiscsValuePerItem[$checkItem['citm_id']] as $discountRoundTotal)
                                            $checkDiscountTotalForItem += $discountRoundTotal;
                                    }

                                    if ($checkItem['citm_charge_tax' . $taxIndex] == "c" || $checkItem['citm_charge_tax' . $taxIndex] == "i") {
                                        $vars['SCTotalWithTax' . $taxIndex] += ($checkItem['citm_sc1_round'] + $checkItem['citm_sc2_round'] + $checkItem['citm_sc3_round'] + $checkItem['citm_sc4_round'] + $checkItem['citm_sc5_round']);
                                    }
                                }
                            }
                            $Items[$itemIndex]['ItemOriginalPrice'] = number_format($Items[$itemIndex]['ItemOriginalPrice'], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
                            $Items[$itemIndex]['ItemPrice'] = number_format($Items[$itemIndex]['ItemPrice'], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
                            $Items[$itemIndex]['ItemTaxTotal'] = number_format($Items[$itemIndex]['ItemTaxTotal'], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", "");
                            for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++)
                                $Items[$itemIndex]['ItemTax' . $taxIndex] = number_format($Items[$itemIndex]['ItemTax' . $taxIndex], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", "");
                            $Items[$itemIndex]['ItemDiscountTotal'] = number_format($Items[$itemIndex]['ItemDiscountTotal'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");

                            //update discount value
                            foreach ($itemDiscsChecking as $discChecking) {
                                $iDiscTypeId = $discChecking['PosCheckDiscount']['cdis_dtyp_id'];
                                for ($iItmDiscIndex = 0; $iItmDiscIndex < count($Items[$itemIndex]['Discounts']); $iItmDiscIndex++) {
                                    if ($Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountId'] == $iDiscTypeId) {
                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountAmount'] += $discChecking['PosCheckDiscount']['cdis_round_total'];
                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountAmount'] = number_format($Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountAmount'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");

                                        //Update voucher numbers
                                        $membershipIntfVars = array('voucherNumber' => '', 'pointUsed' => '');
                                        if (isset($discChecking['PosCheckDiscount']['checkExtraInfos'])) {
                                            foreach ($discChecking['PosCheckDiscount']['checkExtraInfos'] as $itemDiscExtraInfo) {
                                                if ($itemDiscExtraInfo['PosCheckExtraInfo']['ckei_by'] == "discount" && $itemDiscExtraInfo['PosCheckExtraInfo']['ckei_section'] == "membership_interface" && $itemDiscExtraInfo['PosCheckExtraInfo']['ckei_variable'] == "voucher_number") {
                                                    if (strlen($Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfVoucherNumbers']) > 0)
                                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfVoucherNumbers'] .= "," . $itemDiscExtraInfo['PosCheckExtraInfo']['ckei_value'];
                                                    else
                                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfVoucherNumbers'] = $itemDiscExtraInfo['PosCheckExtraInfo']['ckei_value'];
                                                } else if ($itemDiscExtraInfo['PosCheckExtraInfo']['ckei_by'] == "discount" && $itemDiscExtraInfo['PosCheckExtraInfo']['ckei_section'] == "membership_interface" && $itemDiscExtraInfo['PosCheckExtraInfo']['ckei_variable'] == "points_use") {
                                                    if (strlen($Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfPointUsed']) > 0)
                                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfPointUsed'] .= "," . $itemDiscExtraInfo['PosCheckExtraInfo']['ckei_value'];
                                                    else
                                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfPointUsed'] = $itemDiscExtraInfo['PosCheckExtraInfo']['ckei_value'];
                                                }
                                            }
                                        }
                                        break;
                                    }
                                }
                            }

                            //update discount total by discount type
                            foreach ($itemDiscsChecking as $discChecking) {
                                $iDiscTypeId = $discChecking['PosCheckDiscount']['cdis_dtyp_id'];
                                for ($iItmDiscIndex = 0; $iItmDiscIndex < count($DiscTotalByDiscType); $iItmDiscIndex++) {
                                    if ($DiscTotalByDiscType[$iItmDiscIndex]['DiscountId'] == $iDiscTypeId) {
                                        $DiscTotalByDiscType[$iItmDiscIndex]['DiscountAmount'] += $discChecking['PosCheckDiscount']['cdis_round_total'];
                                        $DiscTotalByDiscType[$iItmDiscIndex]['DiscountAmount'] = number_format($DiscTotalByDiscType[$iItmDiscIndex]['DiscountAmount'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                                        break;
                                    }
                                }
                            }

                            //update department totals
                            $departmentFirstLevelId = $this->__getHighestLevelDepartmentId($depts, $checkItem['citm_idep_id']);
                            if (isset($departmentTotals[$departmentFirstLevelId]))
                                $departmentTotals[$departmentFirstLevelId] += $checkItem['citm_round_total'];
                            else
                                $departmentTotals[0] = $checkItem['citm_round_total'];

                            //category total handling
                            $categoryFirstLevelId = $this->__getHighestLevelCategoryId($itemCategories, $checkItem['citm_icat_id']);
                            if (isset($categoryTotals[$categoryFirstLevelId]))
                                $categoryTotals[$categoryFirstLevelId] += $checkItem['citm_round_total'];
                            else
                                $categoryTotals[0] += $checkItem['citm_round_total'];

                            //update vars list
                            $totalItem += $checkItem['citm_qty'];
                            $totalAmount += $checkItem['citm_round_total'];
                            $totalAmountUseItemOriPrice += ($checkItem['citm_qty'] * $Items[$itemIndex]['ItemOriginalPrice']);
                            $checkItemDiscTotal += $itemAppliedDiscTotal;
                            break;
                        }

                        if ($itemGrouped)
                            continue;
                    }
                }

                //order user
                $itemOrderUser = $UserUserModel->findActiveById($checkItem['citm_order_user_id']);

                //item round total
                $itemTotal = $this->__checkNumericExist($checkItem, "citm_round_total");

                //item gross price
                $itemGrossPrice = $checkItem['citm_price'];

                //item original price
                $itemOriginalPrice = $checkItem['citm_original_price'];

                //get item category
                $itemCatName[0] = "";
                for ($index = 1; $index <= 5; $index++)
                    $itemCatName[$index] = "";
                if ($checkItem['citm_icat_id'] > 0) {
                    $itemCategory = $MenuItemCategoryModel->findActiveById($checkItem['citm_icat_id']);
                    if (!empty($itemCategory)) {
                        for ($index = 1; $index <= 5; $index++)
                            $itemCatName[$index] = $itemCategory['MenuItemCategory']['icat_name_l' . $index];
                    }
                }

                //get item department
                $itemDeptName[0] = "";
                for ($index = 1; $index <= 5; $index++)
                    $itemDeptName[$index] = "";

                if ($checkItem['citm_idep_id'] > 0) {
                    $itemDept = $MenuItemDeptModel->findActiveById($checkItem['citm_idep_id']);
                    if (!empty($itemDept)) {
                        for ($index = 1; $index <= 5; $index++)
                            $itemDeptName[$index] = $itemDept['MenuItemDept']['idep_name_l' . $index];
                    }
                }
                $menuItem = array();
                if ($checkItem['citm_item_id'] > 0) {
                    if (!isset($menuItemArray[$checkItem['citm_item_id']])) {
                        $menuItem = $MenuItemModel->findActiveById($checkItem['citm_item_id']);
                        $menuItemArray[$checkItem['citm_item_id']] = $menuItem;
                    } else
                        $menuItem = $menuItemArray[$checkItem['citm_item_id']];
                    if (!empty($menuItem)) {
                        for ($index = 1; $index <= 5; $index++)
                            $menuItem[$index] = $menuItem['MenuItem']['item_info_l' . $index];
                    }
                }

                //get item course
                $itemCourseName[0] = "";
                for ($index = 1; $index <= 5; $index++)
                    $itemCourseName[$index] = "";
                if ($checkItem['citm_icou_id'] > 0) {
                    $itemCourse = $MenuItemCourseModel->findActiveById($checkItem['citm_icou_id']);
                    if (!empty($itemCourse)) {
                        for ($index = 1; $index <= 5; $index++)
                            $itemCourseName[$index] = $itemCourse['MenuItemCourse']['icou_name_l' . $index];
                    }
                }

                //get item discount
                $itemDiscountTotal = 0;
                $itemDiscounts = array();
                if ($checkItem['citm_pre_disc'] != 0 || $checkItem['citm_mid_disc'] != 0 || $checkItem['citm_post_disc'] != 0) {
                    $itemDisc = $PosCheckModel->PosCheckDiscount->findAllByCitmId($checkItem['citm_id']);
                    if (!empty($itemDisc)) {
                        foreach ($itemDisc as $disc) {
                            $tempItemDiscExtraInfos = $PosCheckExtraInfoModel->findAllByCheckDiscountId($disc['PosCheckDiscount']['cdis_id']);
                            $itemDiscExtraInfos = array();
                            foreach ($tempItemDiscExtraInfos as $tempItemDiscExtraInfo)
                                $itemDiscExtraInfos[] = $tempItemDiscExtraInfo['PosCheckExtraInfo'];
                            if (isset($printInfo['isBreakdownFromInclusiveNoBreakdown']) && $printInfo['isBreakdownFromInclusiveNoBreakdown'] == 1)
                                $this->__swipeDiscBreakdownValue($disc['PosCheckDiscount'], $itemDiscExtraInfos);

                            if (empty($discountTypes) || !isset($discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']])) {
                                $discountType = $PosDiscountTypeModel->findActiveById($disc['PosCheckDiscount']['cdis_dtyp_id']);
                                if (!empty($discountType))
                                    $discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']] = $discountType['PosDiscountType']['dtyp_code'];
                                else
                                    $discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']] = "";
                            }
                            $itemDiscountTotal += $disc['PosCheckDiscount']['cdis_round_total'];

                            //handle extra info of check discount
                            $membershipIntfVars = array('voucherNumber' => '', 'pointUsed' => '');
                            $discountEmployee = array();
                            $discountMemberNo = $discountReference = $discountMemberExpiryDate = '';
                            if (!empty($itemDiscExtraInfos)) {
                                foreach ($itemDiscExtraInfos as $itemDiscExtraInfo) {
                                    if ($itemDiscExtraInfo['ckei_by'] == "discount" && $itemDiscExtraInfo['ckei_section'] == "membership_interface" && $itemDiscExtraInfo['ckei_variable'] == "voucher_number")
                                        $membershipIntfVars['voucherNumber'] = $itemDiscExtraInfo['ckei_value'];
                                    if ($itemDiscExtraInfo['ckei_by'] == "discount" && $itemDiscExtraInfo['ckei_section'] == "membership_interface" && $itemDiscExtraInfo['ckei_variable'] == "points_use")
                                        $membershipIntfVars['pointUsed'] = $itemDiscExtraInfo['ckei_value'];
                                    if ($itemDiscExtraInfo['ckei_by'] == "discount" && $itemDiscExtraInfo['ckei_section'] == "discount") {
                                        if ($itemDiscExtraInfo['ckei_variable'] == "user_id")
                                            $discountEmployee = $UserUserModel->findActiveById($itemDiscExtraInfo['ckei_value'], -1);
                                        else if ($itemDiscExtraInfo['ckei_variable'] == "member_number")
                                            $discountMemberNo = $itemDiscExtraInfo['ckei_value'];
                                        else if ($itemDiscExtraInfo['ckei_variable'] == "reference")
                                            $discountReference = $itemDiscExtraInfo['ckei_value'];
                                        else if ($itemDiscExtraInfo['ckei_variable'] == "exp_date")
                                            $discountMemberExpiryDate = $itemDiscExtraInfo['ckei_value'];
                                    }
                                }
                            }

                            $itemDiscounts[] = array(
                                'DiscountId' => $disc['PosCheckDiscount']['cdis_dtyp_id'],
                                'DiscountCode' => $discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']],
                                'DiscountName' => $disc['PosCheckDiscount']['cdis_name_l' . $prtFmtDefaultLang],
                                'DiscountNameL1' => $disc['PosCheckDiscount']['cdis_name_l1'],
                                'DiscountNameL2' => $disc['PosCheckDiscount']['cdis_name_l2'],
                                'DiscountNameL3' => $disc['PosCheckDiscount']['cdis_name_l3'],
                                'DiscountNameL4' => $disc['PosCheckDiscount']['cdis_name_l4'],
                                'DiscountNameL5' => $disc['PosCheckDiscount']['cdis_name_l5'],
                                'DiscountAmount' => number_format($disc['PosCheckDiscount']['cdis_round_total'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                                'DiscountMembershipIntfVoucherNumbers' => $membershipIntfVars['voucherNumber'],
                                'DiscountMembershipIntfPointUsed' => $membershipIntfVars['pointUsed'],
                                'DiscountAppliedEmployee' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] . ' ' . $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang]) : "",
                                'DiscountAppliedEmployeeL1' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l1'] . ' ' . $discountEmployee['UserUser']['user_first_name_l1']) : "",
                                'DiscountAppliedEmployeeL2' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l2'] . ' ' . $discountEmployee['UserUser']['user_first_name_l2']) : "",
                                'DiscountAppliedEmployeeL3' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l3'] . ' ' . $discountEmployee['UserUser']['user_first_name_l3']) : "",
                                'DiscountAppliedEmployeeL4' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l4'] . ' ' . $discountEmployee['UserUser']['user_first_name_l4']) : "",
                                'DiscountAppliedEmployeeL5' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l5'] . ' ' . $discountEmployee['UserUser']['user_first_name_l5']) : "",
                                'DiscountAppliedEmployeeFirstName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang] : "",
                                'DiscountAppliedEmployeeFirstNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l1'] : "",
                                'DiscountAppliedEmployeeFirstNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l2'] : "",
                                'DiscountAppliedEmployeeFirstNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l3'] : "",
                                'DiscountAppliedEmployeeFirstNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l4'] : "",
                                'DiscountAppliedEmployeeFirstNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l5'] : "",
                                'DiscountAppliedEmployeeLastName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] : "",
                                'DiscountAppliedEmployeeLastNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l1'] : "",
                                'DiscountAppliedEmployeeLastNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l2'] : "",
                                'DiscountAppliedEmployeeLastNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l3'] : "",
                                'DiscountAppliedEmployeeLastNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l4'] : "",
                                'DiscountAppliedEmployeeLastNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l5'] : "",
                                'DiscountMemberNumber' => $discountMemberNo,
                                'DiscountReference' => $discountReference,
                                'DiscountMemberExpiryDate' => $discountMemberExpiryDate
                            );

                            $isExist = false;
                            for ($index = 0; $index < count($DiscTotalByDiscType); $index++) {
                                if ($DiscTotalByDiscType[$index]['DiscountId'] == $disc['PosCheckDiscount']['cdis_dtyp_id']) {
                                    $isExist = true;
                                    $newDiscTotal = $DiscTotalByDiscType[$index]['DiscountAmount'] + $disc['PosCheckDiscount']['cdis_round_total'];
                                    $DiscTotalByDiscType[$index]['DiscountAmount'] = number_format($newDiscTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                                    break;
                                }
                            }
                            if (!$isExist)
                                $DiscTotalByDiscType[] = array(
                                    'DiscountId' => $disc['PosCheckDiscount']['cdis_dtyp_id'],
                                    'DiscountCode' => $discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']],
                                    'DiscountName' => $disc['PosCheckDiscount']['cdis_name_l' . $prtFmtDefaultLang],
                                    'DiscountNameL1' => $disc['PosCheckDiscount']['cdis_name_l1'],
                                    'DiscountNameL2' => $disc['PosCheckDiscount']['cdis_name_l2'],
                                    'DiscountNameL3' => $disc['PosCheckDiscount']['cdis_name_l3'],
                                    'DiscountNameL4' => $disc['PosCheckDiscount']['cdis_name_l4'],
                                    'DiscountNameL5' => $disc['PosCheckDiscount']['cdis_name_l5'],
                                    'DiscountAmount' => number_format($disc['PosCheckDiscount']['cdis_round_total'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                                    'DiscountMemberNumber' => $discountMemberNo,
                                    'DiscountReference' => $discountReference,
                                    'DiscountMemberExpiryDate' => $discountMemberExpiryDate
                                );
                        }
                    }
                }

                //Item Tax
                $itemTaxTotal = 0;
                for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++) {
                    $itemTax[$taxIndex] = 0;
                    $itemTax[$taxIndex] = ($checkItem['tax' . $taxIndex . '_on_citm_round_total'] + $checkItem['tax' . $taxIndex . '_on_citm_sc1'] + $checkItem['tax' . $taxIndex . '_on_citm_sc2'] + $checkItem['tax' . $taxIndex . '_on_citm_sc3'] + $checkItem['tax' . $taxIndex . '_on_citm_sc4'] + $checkItem['tax' . $taxIndex . '_on_citm_sc5']);
                    $itemTaxTotal += $itemTax[$taxIndex];

                    if ($itemTax[$taxIndex] > 0) {
                        $checkDiscountTotalForItem = 0;
                        if (!empty($checkDiscsValuePerItem) && isset($checkDiscsValuePerItem[$checkItem['citm_id']])) {
                            foreach ($checkDiscsValuePerItem[$checkItem['citm_id']] as $discountRoundTotal)
                                $checkDiscountTotalForItem += $discountRoundTotal;
                        }

                        if ($checkItem['citm_charge_tax' . $taxIndex] == "c" || $checkItem['citm_charge_tax' . $taxIndex] == "i") {
                            $vars['SCTotalWithTax' . $taxIndex] += ($checkItem['citm_sc1_round'] + $checkItem['citm_sc2_round'] + $checkItem['citm_sc3_round'] + $checkItem['citm_sc4_round'] + $checkItem['citm_sc5_round']);
                        }
                    }
                }

                //department total handling
                $departmentFirstLevelId = $this->__getHighestLevelDepartmentId($depts, $checkItem['citm_idep_id']);
                if (isset($departmentTotals[$departmentFirstLevelId]))
                    $departmentTotals[$departmentFirstLevelId] += $checkItem['citm_round_total'];
                else
                    $departmentTotals[0] += $checkItem['citm_round_total'];

                //category total handling
                $categoryFirstLevelId = $this->__getHighestLevelCategoryId($itemCategories, $checkItem['citm_icat_id']);
                if (isset($categoryTotals[$categoryFirstLevelId]))
                    $categoryTotals[$categoryFirstLevelId] += $checkItem['citm_round_total'];
                else
                    $categoryTotals[0] += $checkItem['citm_round_total'];

                //handle modifier's if necessary
                $modifierList = array();
                if ($checkItem['citm_modifier_count'] > 0) {
                    $modifiers = $checkItem['ModifierList'];
                    if (!empty($modifiers)) {
                        foreach ($modifiers as $modifier) {
                            $itemGrossPrice += $modifier['PosCheckItem']['citm_price'];
                            $itemOriginalPrice += $modifier['PosCheckItem']['citm_original_price'];

                            if ($modifier['PosCheckItem']['citm_no_print'] == '')
                                $modifierList[] = array(
                                    'ModifierId' => $modifier['PosCheckItem']['citm_item_id'],
                                    'ModifierCode' => $modifier['PosCheckItem']['citm_code'],
                                    'ModifierName' => $modifier['PosCheckItem']['citm_name_l' . $prtFmtDefaultLang],
                                    'ModifierNameL1' => $modifier['PosCheckItem']['citm_name_l1'],
                                    'ModifierNameL2' => $modifier['PosCheckItem']['citm_name_l2'],
                                    'ModifierNameL3' => $modifier['PosCheckItem']['citm_name_l3'],
                                    'ModifierNameL4' => $modifier['PosCheckItem']['citm_name_l4'],
                                    'ModifierNameL5' => $modifier['PosCheckItem']['citm_name_l5'],
                                    'ModifierShortName' => $modifier['PosCheckItem']['citm_short_name_l' . $prtFmtDefaultLang],
                                    'ModifierShortNameL1' => $modifier['PosCheckItem']['citm_short_name_l1'],
                                    'ModifierShortNameL2' => $modifier['PosCheckItem']['citm_short_name_l2'],
                                    'ModifierShortNameL3' => $modifier['PosCheckItem']['citm_short_name_l3'],
                                    'ModifierShortNameL4' => $modifier['PosCheckItem']['citm_short_name_l4'],
                                    'ModifierShortNameL5' => $modifier['PosCheckItem']['citm_short_name_l5'],
                                    'ModifierQuantity' => $modifier['PosCheckItem']['citm_qty'],
                                    'ModifierPrice' => number_format(($modifier['PosCheckItem']['citm_original_price'] * $modifier['PosCheckItem']['citm_qty']), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "")
                                );
                        }
                    }
                }

                //handle child item's if necessary
                $childItemList = array();
                if ($bPutItemInMainList == false && $checkItem['citm_child_count'] > 0) {
                    $childItems = $checkItem['ChildItemList'];
                    if (!empty($childItems)) {
                        foreach ($childItems as $childItem) {
                            $childItemPrice = $childItem['citm_original_price'] * $childItem['citm_qty'];
                            $itemOriginalPrice += $childItem['citm_original_price'];

                            //get child item category
                            $childCatName[0] = "";
                            for ($index = 1; $index <= 5; $index++)
                                $childCatName[$index] = "";
                            if ($childItem['citm_icat_id'] > 0) {
                                $childItemCat = $MenuItemCategoryModel->findActiveById($childItem['citm_icat_id']);
                                if (!empty($childItemCat))
                                    for ($index = 1; $index <= 5; $index++)
                                        $childCatName[$index] = $childItemCat['MenuItemCategory']['icat_name_l' . $index];
                            }

                            //get child item department
                            $childDeptName[0] = "";
                            for ($index = 1; $index <= 5; $index++)
                                $childDeptName[$index] = "";
                            if ($childItem['citm_idep_id'] > 0) {
                                $childItemDept = $MenuItemDeptModel->findActiveById($childItem['citm_idep_id']);
                                if (!empty($childItemDept))
                                    for ($index = 1; $index <= 5; $index++)
                                        $childDeptName[$index] = $childItemDept['MenuItemDept']['idep_name_l' . $index];
                            }

                            $childModifierList = array();
                            if ($childItem['citm_modifier_count'] > 0) {
                                $childModifiers = $childItem['ModifierList'];
                                if (!empty($childModifiers)) {
                                    foreach ($childModifiers as $childModi) {
                                        $itemOriginalPrice += $childModi['citm_original_price'];
                                        $childItemPrice += ($childModi['citm_original_price'] * $childModi['citm_qty']);

                                        $childModifierList[] = array(
                                            'ModifierId' => $childModi['citm_item_id'],
                                            'ModifierCode' => $childModi['citm_code'],
                                            'ModifierName' => $childModi['citm_name_l' . $prtFmtDefaultLang],
                                            'ModifierNameL1' => $childModi['citm_name_l1'],
                                            'ModifierNameL2' => $childModi['citm_name_l2'],
                                            'ModifierNameL3' => $childModi['citm_name_l3'],
                                            'ModifierNameL4' => $childModi['citm_name_l4'],
                                            'ModifierNameL5' => $childModi['citm_name_l5'],
                                            'ModifierShortName' => $childModi['citm_short_name_l' . $prtFmtDefaultLang],
                                            'ModifierShortNameL1' => $childModi['citm_short_name_l1'],
                                            'ModifierShortNameL2' => $childModi['citm_short_name_l2'],
                                            'ModifierShortNameL3' => $childModi['citm_short_name_l3'],
                                            'ModifierShortNameL4' => $childModi['citm_short_name_l4'],
                                            'ModifierShortNameL5' => $childModi['citm_short_name_l5'],
                                            'ModifierQuantity' => $childModi['citm_qty'],
                                            'ModifierPrice' => number_format(($childModi['citm_original_price'] * $childModi['citm_qty']), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "")
                                        );
                                    }
                                }
                            }

                            $childDiscountList = array();
                            if ($childItem['citm_pre_disc'] != 0 || $childItem['citm_mid_disc'] != 0 || $childItem['citm_post_disc'] != 0) {
                                $itemDisc = (isset($childItem['PosCheckDiscount'])) ? $childItem['PosCheckDiscount'] : array();
                                if (!empty($itemDisc)) {
                                    foreach ($itemDisc as $disc) {
                                        if (empty($discountTypes) || !array_key_exists($this->__checkNumericExist($disc, "cdis_dtyp_id"), $discountTypes)) {
                                            $discountType = $PosDiscountTypeModel->findActiveById($disc['cdis_dtyp_id']);
                                            if (!empty($discountType))
                                                $discountTypes[$disc['cdis_dtyp_id']] = $discountType['PosDiscountType']['dtyp_code'];
                                            else
                                                $discountTypes[$disc['cdis_dtyp_id']] = "";
                                        }

                                        $itemDiscountTotal += $this->__checkNumericExist($disc, "cdis_round_total");
                                        $childDiscountList[] = array(
                                            'DiscountId' => $this->__checkNumericExist($disc, "cdis_dtyp_id"),
                                            'DiscountCode' => $discountTypes[$disc['cdis_dtyp_id']],
                                            'DiscountName' => $this->__checkStringExist($disc, "cdis_name_l" . $prtFmtDefaultLang),
                                            'DiscountNameL1' => $this->__checkStringExist($disc, "cdis_name_l1"),
                                            'DiscountNameL2' => $this->__checkStringExist($disc, "cdis_name_l2"),
                                            'DiscountNameL3' => $this->__checkStringExist($disc, "cdis_name_l3"),
                                            'DiscountNameL4' => $this->__checkStringExist($disc, "cdis_name_l4"),
                                            'DiscountNameL5' => $this->__checkStringExist($disc, "cdis_name_l5"),
                                            'DiscountAmount' => number_format($this->__checkNumericExist($disc, "cdis_round_total"), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "")
                                        );

                                        $isExist = false;
                                        for ($index = 0; $index < count($DiscTotalByDiscType); $index++) {
                                            if ($DiscTotalByDiscType[$index]['DiscountId'] == $disc['cdis_dtyp_id']) {
                                                $isExist = true;
                                                $newDiscTotal = $DiscTotalByDiscType[$index]['DiscountAmount'] + $this->__checkNumericExist($disc, "cdis_round_total");
                                                $DiscTotalByDiscType[$index]['DiscountAmount'] = number_format($newDiscTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                                                break;
                                            }
                                        }
                                        if (!$isExist)
                                            $DiscTotalByDiscType[] = array(
                                                'DiscountId' => $disc['cdis_dtyp_id'],
                                                'DiscountCode' => $discountTypes[$disc['cdis_dtyp_id']],
                                                'DiscountName' => $this->__checkStringExist($disc, "cdis_name_l" . $prtFmtDefaultLang),
                                                'DiscountNameL1' => $this->__checkStringExist($disc, "cdis_name_l1"),
                                                'DiscountNameL2' => $this->__checkStringExist($disc, "cdis_name_l2"),
                                                'DiscountNameL3' => $this->__checkStringExist($disc, "cdis_name_l3"),
                                                'DiscountNameL4' => $this->__checkStringExist($disc, "cdis_name_l4"),
                                                'DiscountNameL5' => $this->__checkStringExist($disc, "cdis_name_l5"),
                                                'DiscountAmount' => number_format($this->__checkNumericExist($disc, "cdis_round_total"), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "")
                                            );
                                    }
                                }
                            }

                            //department total handling
                            $departmentFirstLevelId = $this->__getHighestLevelDepartmentId($depts, $childItem['citm_idep_id']);
                            if (isset($departmentTotals[$departmentFirstLevelId]))
                                $departmentTotals[$departmentFirstLevelId] += $childItem['citm_round_total'];
                            else
                                $departmentTotals[0] += $childItem['citm_round_total'];

                            //category total handling
                            $categoryFirstLevelId = $this->__getHighestLevelCategoryId($itemCategories, $childItem['citm_icat_id']);
                            if (isset($categoryTotals[$categoryFirstLevelId]))
                                $categoryTotals[$categoryFirstLevelId] += $childItem['citm_round_total'];
                            else
                                $categoryTotals[0] += $childItem['citm_round_total'];

                            //add the child total to item total if parent's basic calculate method is 'c'
                            if ($checkItem['citm_basic_calculate_method'] == "c")
                                $itemTotal += $childItem['citm_round_total'];

                            if ($childItem['citm_no_print'] == '')
                                $childItemList[] = array(
                                    'ChildItemId' => $childItem['citm_item_id'],
                                    'ChildItemCode' => $childItem['citm_code'],
                                    'ChildItemName' => $childItem['citm_name_l' . $prtFmtDefaultLang],
                                    'ChildItemNameL1' => $childItem['citm_name_l1'],
                                    'ChildItemNameL2' => $childItem['citm_name_l2'],
                                    'ChildItemNameL3' => $childItem['citm_name_l3'],
                                    'ChildItemNameL4' => $childItem['citm_name_l4'],
                                    'ChildItemNameL5' => $childItem['citm_name_l5'],
                                    'ChildItemShortName' => $childItem['citm_short_name_l' . $prtFmtDefaultLang],
                                    'ChildItemShortNameL1' => $childItem['citm_short_name_l1'],
                                    'ChildItemShortNameL2' => $childItem['citm_short_name_l2'],
                                    'ChildItemShortNameL3' => $childItem['citm_short_name_l3'],
                                    'ChildItemShortNameL4' => $childItem['citm_short_name_l4'],
                                    'ChildItemShortNameL5' => $childItem['citm_short_name_l5'],
                                    'ChildItemQuantity' => $childItem['citm_qty'],
                                    'ChildItemPrice' => number_format($childItemPrice, $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                                    'ChildItemTakeout' => ($childItem['citm_ordering_type'] == 't') ? 1 : 0,
                                    'Modifiers' => $childModifierList,
                                    'Discounts' => $childDiscountList
                                );
                        }
                    }
                }

                $itemName = $checkItem['citm_name_l' . $prtFmtDefaultLang];
                $itemShortName = $checkItem['citm_short_name_l' . $prtFmtDefaultLang];
                if ($checkItem['citm_item_id'] > 0 && !empty($menuItem))
                    $itemInfo = $menuItem['MenuItem']['item_info_l' . $prtFmtDefaultLang];
                else
                    $itemInfo = "";

                if (!empty($itemOrderUser)) {
                    $itemOrderEmployeeFirstName = $itemOrderUser['UserUser']['user_first_name_l' . $prtFmtDefaultLang];
                    $itemOrderEmployeeLastName = $itemOrderUser['UserUser']['user_last_name_l' . $prtFmtDefaultLang];
                    $itemOrderEmployee = $itemOrderEmployeeLastName . ' ' . $itemOrderEmployeeFirstName;
                } else {
                    $itemOrderEmployeeFirstName = "";
                    $itemOrderEmployeeLastName = "";
                    $itemOrderEmployee = "";
                }
                if ($checkItem['citm_icou_id'] > 0 && !empty($itemCourse))
                    $itemCourseName[0] = $itemCourse['MenuItemCourse']['icou_name_l' . $prtFmtDefaultLang];
                else
                    $itemCourseName[0] = "";
                if ($checkItem['citm_icat_id'] > 0 && !empty($itemCategory))
                    $itemCatName[0] = $itemCategory['MenuItemCategory']['icat_name_l' . $prtFmtDefaultLang];
                else
                    $itemCatName[0] = "";
                if ($checkItem['citm_idep_id'] > 0 && !empty($itemDept))
                    $itemDeptName[0] = $itemDept['MenuItemDept']['idep_name_l' . $prtFmtDefaultLang];
                else
                    $itemDeptName[0] = "";
                $Items[] = array(
                    'ItemId' => $checkItem['citm_item_id'],
                    'ItemCode' => $checkItem['citm_code'],
                    'ItemMenuId' => $checkItem['citm_item_id'],
                    'ItemName' => $itemName,
                    'ItemNameL1' => $checkItem['citm_name_l1'],
                    'ItemNameL2' => $checkItem['citm_name_l2'],
                    'ItemNameL3' => $checkItem['citm_name_l3'],
                    'ItemNameL4' => $checkItem['citm_name_l4'],
                    'ItemNameL5' => $checkItem['citm_name_l5'],
                    'ItemShortName' => $itemShortName,
                    'ItemShortNameL1' => $checkItem['citm_short_name_l1'],
                    'ItemShortNameL2' => $checkItem['citm_short_name_l2'],
                    'ItemShortNameL3' => $checkItem['citm_short_name_l3'],
                    'ItemShortNameL4' => $checkItem['citm_short_name_l4'],
                    'ItemShortNameL5' => $checkItem['citm_short_name_l5'],
                    'ItemQuantity' => $checkItem['citm_qty'],
                    'ItemInfo' => $itemInfo,
                    'ItemInfoL1' => $checkItem['citm_short_name_l1'],
                    'ItemInfoL2' => $checkItem['citm_short_name_l2'],
                    'ItemInfoL3' => $checkItem['citm_short_name_l3'],
                    'ItemInfoL4' => $checkItem['citm_short_name_l4'],
                    'ItemInfoL5' => $checkItem['citm_short_name_l5'],
                    'ItemOriginalPrice' => number_format($itemOriginalPrice, $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                    'ItemPrice' => number_format(($checkItem['citm_round_total'] + $itemDiscountTotal), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                    'ItemCost' => number_format($checkItem['citm_unit_cost'], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                    'TotalItemCost' => number_format($checkItem['citm_unit_cost'] * $checkItem['citm_qty'], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                    'ItemGrossPrice' => $itemGrossPrice,
                    'ItemTotal' => number_format($itemTotal, $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                    'ItemTaxTotal' => number_format($itemTaxTotal, $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax1' => number_format($itemTax[1], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax2' => number_format($itemTax[2], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax3' => number_format($itemTax[3], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax4' => number_format($itemTax[4], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax5' => number_format($itemTax[5], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax6' => number_format($itemTax[6], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax7' => number_format($itemTax[7], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax8' => number_format($itemTax[8], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax9' => number_format($itemTax[9], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax10' => number_format($itemTax[10], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax11' => number_format($itemTax[11], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax12' => number_format($itemTax[12], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax13' => number_format($itemTax[13], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax14' => number_format($itemTax[14], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax15' => number_format($itemTax[15], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax16' => number_format($itemTax[16], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax17' => number_format($itemTax[17], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax18' => number_format($itemTax[18], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax19' => number_format($itemTax[19], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax20' => number_format($itemTax[20], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax21' => number_format($itemTax[21], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax22' => number_format($itemTax[22], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax23' => number_format($itemTax[23], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax24' => number_format($itemTax[24], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemTax25' => number_format($itemTax[25], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                    'ItemCourseName' => $itemCourseName[0],
                    'ItemCourseNameL1' => $itemCourseName[1],
                    'ItemCourseNameL2' => $itemCourseName[2],
                    'ItemCourseNameL3' => $itemCourseName[3],
                    'ItemCourseNameL4' => $itemCourseName[4],
                    'ItemCourseNameL5' => $itemCourseName[5],
                    'ItemCatName' => $itemCatName[0],
                    'ItemCatNameL1' => $itemCatName[1],
                    'ItemCatNameL2' => $itemCatName[2],
                    'ItemCatNameL3' => $itemCatName[3],
                    'ItemCatNameL4' => $itemCatName[4],
                    'ItemCatNameL5' => $itemCatName[5],
                    'ItemDeptId' => $checkItem['citm_idep_id'],
                    'ItemDept' => $itemDeptName[0],
                    'ItemDeptL1' => $itemDeptName[1],
                    'ItemDeptL2' => $itemDeptName[2],
                    'ItemDeptL3' => $itemDeptName[3],
                    'ItemDeptL4' => $itemDeptName[4],
                    'ItemDeptL5' => $itemDeptName[5],
                    'ItemOrderTime' => substr($checkItem['citm_order_loctime'], 11, 8),
                    'ItemOrderEmployee' => $itemOrderEmployee,
                    'ItemOrderEmployeeL1' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l1'] . ' ' . $itemOrderUser['UserUser']['user_first_name_l1'] : "",
                    'ItemOrderEmployeeL2' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l2'] . ' ' . $itemOrderUser['UserUser']['user_first_name_l2'] : "",
                    'ItemOrderEmployeeL3' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l3'] . ' ' . $itemOrderUser['UserUser']['user_first_name_l3'] : "",
                    'ItemOrderEmployeeL4' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l4'] . ' ' . $itemOrderUser['UserUser']['user_first_name_l4'] : "",
                    'ItemOrderEmployeeL5' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l5'] . ' ' . $itemOrderUser['UserUser']['user_first_name_l5'] : "",
                    'ItemOrderEmployeeFirstName' => $itemOrderEmployeeFirstName,
                    'ItemOrderEmployeeFirstNameL1' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_first_name_l1'] : "",
                    'ItemOrderEmployeeFirstNameL2' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_first_name_l2'] : "",
                    'ItemOrderEmployeeFirstNameL3' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_first_name_l3'] : "",
                    'ItemOrderEmployeeFirstNameL4' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_first_name_l4'] : "",
                    'ItemOrderEmployeeFirstNameL5' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_first_name_l5'] : "",
                    'ItemOrderEmployeeLastName' => $itemOrderEmployeeLastName,
                    'ItemOrderEmployeeLastNameL1' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l1'] : "",
                    'ItemOrderEmployeeLastNameL2' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l2'] : "",
                    'ItemOrderEmployeeLastNameL3' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l3'] : "",
                    'ItemOrderEmployeeLastNameL4' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l4'] : "",
                    'ItemOrderEmployeeLastNameL5' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_last_name_l5'] : "",
                    'ItemOrderEmployeeNum' => (!empty($itemOrderUser)) ? $itemOrderUser['UserUser']['user_number'] : "",
                    'ItemDiscountTotal' => number_format($itemDiscountTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                    'ItemSeatNum' => $checkItem['citm_seat'],
                    'ItemCourseNum' => $checkItem['citm_icou_id'],
                    'ItemGroupStart' => 0,
                    'ItemGroupEnd' => 0,
                    'ItemTakeout' => ($checkItem['citm_ordering_type'] == 't') ? 1 : 0,
                    'ItemCouponNumber' => $onlineCoupon['number'],
                    'ItemReference' => $itemReference,
                    'ItemLoyaltySVCCardNumber' => $loyaltyItemInfo['svcCardNumber'],
                    'ItemLoyaltyMemberNumber' => $loyaltyItemInfo['memberNumber'],
                    'ItemLoyaltySVCCardExpiryDate' => $loyaltyItemInfo['svcCardExpiryDate'],
                    'ItemLoyaltySVCRemark' => $loyaltyItemInfo['svcRemark'],
                    'ChildItems' => $childItemList,
                    'Modifiers' => $modifierList,
                    'Discounts' => $itemDiscounts,
                    'ItemLoyaltyPointBalance' => $itemLoyaltyPointBalance,
                    'ItemLoyaltyPointAddValue' => $itemLoyaltyPointAddValue,
                    'ItemLoyaltyCardNumber' => $itemLoyaltyCardNumber,
                    'ItemMembershipIntfVoucherNumber' => $voucher['number']
                );
                $totalItem += $checkItem['citm_qty'];
                $totalAmount += $checkItem['citm_round_total'];
                $totalAmountUseItemOriPrice += ($checkItem['citm_qty'] * $itemOriginalPrice);
                $checkItemDiscTotal += $itemDiscountTotal;
            }
        }

        $vars['TotalItem'] = $totalItem;
        $vars['CheckItemGrossTotal'] = number_format($totalAmount, $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");
        $vars['CheckItemDiscountTotal'] = number_format($checkItemDiscTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
        $vars['DiscountTotal'] = number_format(($check['PosCheck']['chks_pre_disc'] + $check['PosCheck']['chks_mid_disc'] + $check['PosCheck']['chks_post_disc']), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
        $vars['CheckItemTotal'] = number_format(($check['PosCheck']['chks_item_total'] + $checkItemDiscTotal), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
        $vars['CheckItemTotalUseItemOriPrice'] = number_format($totalAmountUseItemOriPrice, $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
        $vars['CheckRoundTotal'] = $check['PosCheck']['chks_round_amount'];
        for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++)
            $vars['CheckItemTotalWithTax' . $taxIndex] = number_format($check['PosCheck']['checkItemNetTotalWithTax'][$taxIndex], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");

        //Update departments loop
        for ($index = 0; $index < count($Departments); $index++) {
            $deptId = $Departments[$index]['DepartmentId'];
            if (isset($departmentTotals[$deptId]))
                $Departments[$index]['DepartmentTotal'] = number_format($departmentTotals[$deptId], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
        }

        //Update categories loop
        for ($index = 0; $index < count($Categories); $index++) {
            $catId = $Categories[$index]['CategoryId'];
            if (isset($categoryTotals[$catId]))
                $Categories[$index]['CategoryTotal'] = number_format($categoryTotals[$catId], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
        }

        //Retrieve check discounts
        $CheckDiscounts = array();
        $checkDiscountTotal = 0;
        $CheckExtraCharges = array();
        $checkExtraChargeTotal = 0;
        if ($check['PosCheck']['chks_pre_disc'] != 0 || $check['PosCheck']['chks_mid_disc'] != 0 || $check['PosCheck']['chks_post_disc'] != 0) {
            if (!empty($checkDiscs)) {
                //get discount applied employee
                $discAppliedEmpIds = array();
                $discAppliedEmployees = array();
                foreach ($checkDiscs as $disc) {
                    if ($disc['PosCheckDiscount']['cdis_apply_user_id'] > 0 && !in_array($disc['PosCheckDiscount']['cdis_apply_user_id'], $discAppliedEmpIds))
                        $discAppliedEmpIds[] = $disc['PosCheckDiscount']['cdis_apply_user_id'];
                }
                if (!empty($discAppliedEmpIds)) {
                    $tempArray = $UserUserModel->findMultipleByIds($discAppliedEmpIds);
                    foreach ($tempArray as $temp)
                        $discAppliedEmployees[$temp['UserUser']['user_id']] = $temp;
                }

                foreach ($checkDiscs as $disc) {
                    if (empty($discountTypes) || !isset($discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']])) {
                        $discountType = $PosDiscountTypeModel->findActiveById($disc['PosCheckDiscount']['cdis_dtyp_id']);
                        if (!empty($discountType))
                            $discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']] = $discountType['PosDiscountType']['dtyp_code'];
                        else
                            $discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']] = "";
                    }
                    $discExtraInfo = (isset($disc['checkExtraInfos'])) ? $disc['checkExtraInfos'] : array();
                    $this->__swipeDiscBreakdownValue($disc['PosCheckDiscount'], $discExtraInfo);

                    //get extra info of check discount
                    $membershipIntfVars = array('voucherNumber' => '', 'pointUsed' => '');
                    $discountEmployee = array();
                    $discountMemberNo = $discountReference = $discountMemberExpiryDate = "";
                    if (isset($disc['checkExtraInfos'])) {
                        foreach ($disc['checkExtraInfos'] as $checkDiscExtraInfo) {
                            // Section: membership_interface
                            if ($checkDiscExtraInfo['ckei_by'] == "discount" && $checkDiscExtraInfo['ckei_section'] == "membership_interface") {
                                if ($checkDiscExtraInfo['ckei_variable'] == "voucher_number")
                                    $membershipIntfVars['voucherNumber'] = $checkDiscExtraInfo['ckei_value'];
                                if ($checkDiscExtraInfo['ckei_variable'] == "points_use")
                                    $membershipIntfVars['pointUsed'] = $checkDiscExtraInfo['ckei_value'];
                                else if ($checkDiscExtraInfo['ckei_variable'] == "reference")
                                    $discountReference = $checkDiscExtraInfo['ckei_value'];
                                else if ($checkDiscExtraInfo['ckei_variable'] == "member_number")
                                    $discountMemberNo = $checkDiscExtraInfo['ckei_value'];
                            }
                            // Section: discount
                            if ($checkDiscExtraInfo['ckei_by'] == "discount" && $checkDiscExtraInfo['ckei_section'] == "discount") {
                                if ($checkDiscExtraInfo['ckei_variable'] == "user_id")
                                    $discountEmployee = $UserUserModel->findActiveById($checkDiscExtraInfo['ckei_value'], -1);
                                else if ($checkDiscExtraInfo['ckei_variable'] == "member_number")
                                    $discountMemberNo = $checkDiscExtraInfo['ckei_value'];
                                else if ($checkDiscExtraInfo['ckei_variable'] == "reference")
                                    $discountReference = $checkDiscExtraInfo['ckei_value'];
                                else if ($checkDiscExtraInfo['ckei_variable'] == "exp_date")
                                    $discountMemberExpiryDate = $checkDiscExtraInfo['ckei_value'];
                            }
                        }
                    }

                    if (empty($discountEmployee) && array_key_exists($disc['PosCheckDiscount']['cdis_apply_user_id'], $discAppliedEmployees))
                        $discountEmployee = $discAppliedEmployees[$disc['PosCheckDiscount']['cdis_apply_user_id']];

                    if (isset($disc['PosCheckDiscount']['cdis_used_for']) && strcmp($disc['PosCheckDiscount']['cdis_used_for'], "c") == 0) {
                        $CheckExtraCharges[] = array(
                            'ExtraChargeId' => $disc['PosCheckDiscount']['cdis_dtyp_id'],
                            'ExtraChargeCode' => $discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']],
                            'ExtraChargeName' => $disc['PosCheckDiscount']['cdis_name_l' . $prtFmtDefaultLang],
                            'ExtraChargeNameL1' => $disc['PosCheckDiscount']['cdis_name_l1'],
                            'ExtraChargeNameL2' => $disc['PosCheckDiscount']['cdis_name_l2'],
                            'ExtraChargeNameL3' => $disc['PosCheckDiscount']['cdis_name_l3'],
                            'ExtraChargeNameL4' => $disc['PosCheckDiscount']['cdis_name_l4'],
                            'ExtraChargeNameL5' => $disc['PosCheckDiscount']['cdis_name_l5'],
                            'ExtraChargeAmount' => number_format($disc['PosCheckDiscount']['cdis_round_total'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                            'ExtraChargeMembershipIntfVoucherNumbers' => $membershipIntfVars['voucherNumber'],
                            'ExtraChargeAppliedEmployee' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] . ' ' . $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang]) : "",
                            'ExtraChargeAppliedEmployeeL1' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l1'] . ' ' . $discountEmployee['UserUser']['user_first_name_l1']) : "",
                            'ExtraChargeAppliedEmployeeL2' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l2'] . ' ' . $discountEmployee['UserUser']['user_first_name_l2']) : "",
                            'ExtraChargeAppliedEmployeeL3' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l3'] . ' ' . $discountEmployee['UserUser']['user_first_name_l3']) : "",
                            'ExtraChargeAppliedEmployeeL4' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l4'] . ' ' . $discountEmployee['UserUser']['user_first_name_l4']) : "",
                            'ExtraChargeAppliedEmployeeL5' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l5'] . ' ' . $discountEmployee['UserUser']['user_first_name_l5']) : "",
                            'ExtraChargeAppliedEmployeeFirstName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l1'] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l2'] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l3'] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l4'] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l5'] : "",
                            'ExtraChargeAppliedEmployeeLastName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] : "",
                            'ExtraChargeAppliedEmployeeLastNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l1'] : "",
                            'ExtraChargeAppliedEmployeeLastNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l2'] : "",
                            'ExtraChargeAppliedEmployeeLastNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l3'] : "",
                            'ExtraChargeAppliedEmployeeLastNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l4'] : "",
                            'ExtraChargeAppliedEmployeeLastNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l5'] : "",
                            'ExtraChargeMemberNumber' => $discountMemberNo,
                            'ExtraChargeReference' => $discountReference
                        );
                        $checkExtraChargeTotal += $disc['PosCheckDiscount']['cdis_round_total'];
                    } else {
                        $CheckDiscounts[] = array(
                            'DiscountId' => $disc['PosCheckDiscount']['cdis_dtyp_id'],
                            'DiscountCode' => $discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']],
                            'DiscountName' => $disc['PosCheckDiscount']['cdis_name_l' . $prtFmtDefaultLang],
                            'DiscountNameL1' => $disc['PosCheckDiscount']['cdis_name_l1'],
                            'DiscountNameL2' => $disc['PosCheckDiscount']['cdis_name_l2'],
                            'DiscountNameL3' => $disc['PosCheckDiscount']['cdis_name_l3'],
                            'DiscountNameL4' => $disc['PosCheckDiscount']['cdis_name_l4'],
                            'DiscountNameL5' => $disc['PosCheckDiscount']['cdis_name_l5'],
                            'DiscountAmount' => number_format($disc['PosCheckDiscount']['cdis_round_total'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                            'DiscountMembershipIntfVoucherNumbers' => $membershipIntfVars['voucherNumber'],
                            'DiscountMembershipIntfPointUsed' => $membershipIntfVars['pointUsed'],
                            'DiscountAppliedEmployee' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] . ' ' . $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang]) : "",
                            'DiscountAppliedEmployeeL1' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l1'] . ' ' . $discountEmployee['UserUser']['user_first_name_l1']) : "",
                            'DiscountAppliedEmployeeL2' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l2'] . ' ' . $discountEmployee['UserUser']['user_first_name_l2']) : "",
                            'DiscountAppliedEmployeeL3' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l3'] . ' ' . $discountEmployee['UserUser']['user_first_name_l3']) : "",
                            'DiscountAppliedEmployeeL4' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l4'] . ' ' . $discountEmployee['UserUser']['user_first_name_l4']) : "",
                            'DiscountAppliedEmployeeL5' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l5'] . ' ' . $discountEmployee['UserUser']['user_first_name_l5']) : "",
                            'DiscountAppliedEmployeeFirstName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang] : "",
                            'DiscountAppliedEmployeeFirstNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l1'] : "",
                            'DiscountAppliedEmployeeFirstNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l2'] : "",
                            'DiscountAppliedEmployeeFirstNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l3'] : "",
                            'DiscountAppliedEmployeeFirstNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l4'] : "",
                            'DiscountAppliedEmployeeFirstNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l5'] : "",
                            'DiscountAppliedEmployeeLastName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] : "",
                            'DiscountAppliedEmployeeLastNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l1'] : "",
                            'DiscountAppliedEmployeeLastNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l2'] : "",
                            'DiscountAppliedEmployeeLastNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l3'] : "",
                            'DiscountAppliedEmployeeLastNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l4'] : "",
                            'DiscountAppliedEmployeeLastNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l5'] : "",
                            'DiscountMemberNumber' => $discountMemberNo,
                            'DiscountReference' => $discountReference,
                            'DiscountMemberExpiryDate' => $discountMemberExpiryDate
                        );
                        $checkDiscountTotal += $disc['PosCheckDiscount']['cdis_round_total'];
                    }

                    $isExist = false;
                    for ($index = 0; $index < count($DiscTotalByDiscType); $index++) {
                        if ($DiscTotalByDiscType[$index]['DiscountId'] == $disc['PosCheckDiscount']['cdis_dtyp_id']) {
                            $isExist = true;
                            $newDiscTotal = $DiscTotalByDiscType[$index]['DiscountAmount'] + $disc['PosCheckDiscount']['cdis_round_total'];
                            $DiscTotalByDiscType[$index]['DiscountAmount'] = number_format($newDiscTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                            break;
                        }
                    }
                    if (!$isExist) {
                        $DiscTotalByDiscType[] = array(
                            'DiscountId' => $disc['PosCheckDiscount']['cdis_dtyp_id'],
                            'DiscountCode' => $discountTypes[$disc['PosCheckDiscount']['cdis_dtyp_id']],
                            'DiscountName' => $disc['PosCheckDiscount']['cdis_name_l' . $prtFmtDefaultLang],
                            'DiscountNameL1' => $disc['PosCheckDiscount']['cdis_name_l1'],
                            'DiscountNameL2' => $disc['PosCheckDiscount']['cdis_name_l2'],
                            'DiscountNameL3' => $disc['PosCheckDiscount']['cdis_name_l3'],
                            'DiscountNameL4' => $disc['PosCheckDiscount']['cdis_name_l4'],
                            'DiscountNameL5' => $disc['PosCheckDiscount']['cdis_name_l5'],
                            'DiscountAmount' => number_format($disc['PosCheckDiscount']['cdis_round_total'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                            'DiscountMemberNumber' => $discountMemberNo,
                            'DiscountReference' => $discountReference,
                            'DiscountMemberExpiryDate' => $discountMemberExpiryDate
                        );
                    }
                }
            }
        }
        $vars['CheckDiscountTotal'] = number_format($checkDiscountTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
        $vars['CheckExtraChargeTotal'] = number_format($checkExtraChargeTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");

        // Retrieve the payment
        $Payments = array();
        $AllReleasePaymentRunningNumber = array();
        $LastReleasePaymentRunningNumber = array();
        $paymentAmountTotal = 0;
        $totalTips = 0;
        $totalChange = 0;

        if ($type == 2) {
            //get duty meal reset period
            $dutyMealLimitReset = 'm';
            if ($this->__getConfigByLocationValue($check['PosCheck']['chks_close_stat_id'], $check['PosCheck']['chks_olet_id'], $check['PosCheck']['chks_shop_id'], 'system', 'dutymeal_limit_reset_period') == 'd')
                $dutyMealLimitReset = 'd';

            //get on credit reset period
            $onCreditLimitReset = 'm';
            if ($this->__getConfigByLocationValue($check['PosCheck']['chks_close_stat_id'], $check['PosCheck']['chks_olet_id'], $check['PosCheck']['chks_shop_id'], 'system', 'on_credit_limit_reset_period') == 'd')
                $onCreditLimitReset = 'd';

            foreach ($check['PosCheckPayment'] as $checkPayment) {
                if (!empty($checkPayment)) {
                    $paymentAmountTotal += $checkPayment['cpay_pay_total'];
                    $totalTips += $checkPayment['cpay_pay_tips'];
                    $totalChange += $checkPayment['cpay_pay_change'];

                    //get curreny information
                    $paymentMethod = null;
                    if ($checkPayment['cpay_paym_id'] > 0)
                        $paymentMethod = $PosPaymentMethodModel->findActiveById($checkPayment['cpay_paym_id'], -1);

                    //get payment tips / residue
                    if ($paymentMethod != null && !empty($paymentMethod) && isset($paymentMethod['PosPaymentMethod']['paym_tips']) && $paymentMethod['PosPaymentMethod']['paym_tips'] == "r") {
                        $paymentTips = 0.0;
                        $paymentResidue = $checkPayment['cpay_pay_tips'];
                        $paymentTipsInForeignCurrency = number_format(0.0, $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "");
                        $paymentResidueInForeignCurrency = number_format($checkPayment['cpay_pay_foreign_tips'], $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "");
                    } else {
                        $paymentTips = $checkPayment['cpay_pay_tips'];
                        $paymentResidue = 0.0;
                        $paymentTipsInForeignCurrency = ($paymentMethod != null) ? number_format($checkPayment['cpay_pay_foreign_tips'], $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format($checkPayment['cpay_pay_foreign_tips'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
                        $paymentResidueInForeignCurrency = ($paymentMethod != null) ? number_format(0.0, $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format(0.0, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
                    }

                    if ($paymentMethod != null && !empty($paymentMethod) && isset($paymentMethod['PosPaymentMethod']))
                        $paymentCode = $paymentMethod['PosPaymentMethod']['paym_code'];

                    $paymentMember = array();
                    $paymentMemberSpending = 0;
                    if ($checkPayment['cpay_memb_id'] > 0 && $MemMemberModel != null)
                        $paymentMember = $MemMemberModel->findActiveById($checkPayment['cpay_memb_id'], 1);
                    if (!empty($checkPayment) && isset($checkPayment['MemMemberModuleInfo']) && !empty($checkPayment['MemMemberModuleInfo'])) {
                        foreach ($checkPayment['MemMemberModuleInfo'] as $checkPaymentMemberModuleInfo) {
                            if (strcmp($checkPaymentMemberModuleInfo['minf_module_alias'], "pos") == 0 && strcmp($checkPaymentMemberModuleInfo['minf_variable'], "life_time_spending") == 0) {
                                $paymentMemberSpending = $checkPaymentMemberModuleInfo['minf_value'];
                                break;
                            }
                        }
                    }

                    $paymentEmployee = array();
                    $dutyMealLimit = 0;
                    $onCreditLimit = 0;
                    $remainingCreditLimit = 0;
                    $employeeMaximumCreditLimit = 0;
                    if ($checkPayment['cpay_meal_user_id'] > 0) {
                        $paymentEmployee = $UserUserModel->findActiveById($checkPayment['cpay_meal_user_id'], 1);
                        if (!empty($paymentEmployee) && isset($paymentEmployee['UserUserModuleInfo']) && count($paymentEmployee['UserUserModuleInfo']) > 0) {
                            foreach ($paymentEmployee['UserUserModuleInfo'] as $userModuleInfo) {
                                if (strcmp($userModuleInfo['uinf_module_alias'], "pos") == 0 && strcmp($userModuleInfo['uinf_variable'], "duty_meal_limit") == 0)
                                    $dutyMealLimit = $userModuleInfo['uinf_value'];
                                else if (strcmp($userModuleInfo['uinf_module_alias'], "pos") == 0 && strcmp($userModuleInfo['uinf_variable'], "on_credit_limit") == 0)
                                    $onCreditLimit = $userModuleInfo['uinf_value'];
                                $remainingCreditLimit = $userModuleInfo['uinf_value'];
                            }
                        }

                        if ((strcmp($checkPayment['cpay_payment_type'], "duty_meal") == 0 && $dutyMealLimit > 0) || (strcmp($checkPayment['cpay_payment_type'], "on_credit") == 0 && $onCreditLimit > 0)) {
                            $businessMonth = substr($businessDay['PosBusinessDay']['bday_date'], 0, 8) . "%";
                            if ((strcmp($checkPayment['cpay_payment_type'], "duty_meal") == 0 && $dutyMealLimitReset == 'd') || (strcmp($checkPayment['cpay_payment_type'], "on_credit") == 0 && $onCreditLimitReset == 'd'))
                                $businessMonth = $businessDay['PosBusinessDay']['bday_date'];
                            $businessDayList = $PosBusinessDayModel->find('all', array(
                                    'conditions' => array(
                                        'PosBusinessDay.bday_olet_id' => $check['PosCheck']['chks_olet_id'],
                                        'PosBusinessDay.bday_shop_id' => $check['PosCheck']['chks_shop_id'],
                                        'PosBusinessDay.bday_date LIKE' => $businessMonth
                                    ),
                                    'recursive' => -1
                                )
                            );

                            if (!empty($businessDayList)) {
                                $bdayIds = array();
                                foreach ($businessDayList as $singleBusinessDay)
                                    $bdayIds[] = $singleBusinessDay['PosBusinessDay']['bday_id'];

                                $employeeCheckPayments = $PosCheckModel->PosCheckPayment->find('all', array(
                                        'conditions' => array(
                                            'PosCheckPayment.cpay_olet_id' => $check['PosCheck']['chks_olet_id'],
                                            'PosCheckPayment.cpay_shop_id' => $check['PosCheck']['chks_shop_id'],
                                            'PosCheckPayment.cpay_payment_type' => $checkPayment['cpay_payment_type'],
                                            'PosCheckPayment.cpay_meal_user_id' => $checkPayment['cpay_meal_user_id'],
                                            'PosCheckPayment.cpay_bday_id' => $bdayIds,
                                            'PosCheckPayment.cpay_pay_time <' => $checkPayment['cpay_pay_time'],
                                            'PosCheckPayment.cpay_status' => '',
                                        ),
                                        'recursive' => -1
                                    )
                                );

                                $employeePayTypeTotal = 0;
                                if (!empty($employeeCheckPayments)) {
                                    foreach ($employeeCheckPayments as $employeeCheckPayment)
                                        $employeePayTypeTotal += $employeeCheckPayment['PosCheckPayment']['cpay_pay_total'];

                                    if (strcmp($checkPayment['cpay_payment_type'], "duty_meal") == 0) {
                                        $employeeMaximumCreditLimit = $dutyMealLimit;
                                        $remainingCreditLimit = $dutyMealLimit - $employeePayTypeTotal;
                                    } else if (strcmp($checkPayment['cpay_payment_type'], "on_credit") == 0) {
                                        $employeeMaximumCreditLimit = $onCreditLimit;
                                        $remainingCreditLimit = $onCreditLimit - $employeePayTypeTotal;
                                    }

                                    if ($remainingCreditLimit < 0)
                                        $remainingCreditLimit = 0;
                                } else {
                                    if (strcmp($checkPayment['cpay_payment_type'], "duty_meal") == 0)
                                        $employeeMaximumCreditLimit = $dutyMealLimit;
                                    else if (strcmp($checkPayment['cpay_payment_type'], "on_credit") == 0)
                                        $employeeMaximumCreditLimit = $onCreditLimit;
                                }
                            }
                        }
                    }

                    $paymentVoucherNum = "";
                    $paymentOctopusCardType = "";
                    $paymentOctopusTransactionAmount = "";
                    $paymentOctopusUdsn = "";
                    $paymentOctopusOriginalAmount = "";
                    $paymentOctopusDeviceId = "";
                    $paymentOctopusCurrentAmount = "";
                    $paymentOctopusCardId = "";
                    $paymentOctopusLastAddValueType = "";
                    $paymentOctopusLastAddValueDate = "";
                    $paymentOctopusTransactionTime = "";
                    $paymentRewriteCardOriginalAmount = "";
                    $paymentRewriteCardCurrentAmount = "";
                    $paymentRewriteCardCardNumber = "";
                    $paymentReferenceNum = array("1" => "", "2" => "", "3" => "");
                    if (strcmp($checkPayment['cpay_ref_data1'], "") != 0 && json_decode($checkPayment['cpay_ref_data1']) != NULL) {
                        $refData1 = json_decode($checkPayment['cpay_ref_data1'], true);
                        if (strcmp($checkPayment['cpay_payment_type'], "voucher") == 0) {
                            //get voucher number if payment type is "voucher"
                            if (isset($refData1['voucher_number']))
                                $paymentVoucherNum = $refData1['voucher_number'];
                        } else if (strcmp($checkPayment['cpay_payment_type'], "octopus") == 0) {
                            //get Octopus payment information if payment type is "octopus"
                            if (isset($refData1['card_type']))
                                $paymentOctopusCardType = $refData1['card_type'];
                            if (isset($refData1['transaction_amount']))
                                $paymentOctopusTransactionAmount = $refData1['transaction_amount'];
                            if (isset($refData1['udsn']))
                                $paymentOctopusUdsn = $refData1['udsn'];
                            if (isset($refData1['original_remain_amount']))
                                $paymentOctopusOriginalAmount = $refData1['original_remain_amount'];
                            if (isset($refData1['device_id']))
                                $paymentOctopusDeviceId = $refData1['device_id'];
                            if (isset($refData1['current_remain_amount']))
                                $paymentOctopusCurrentAmount = $refData1['current_remain_amount'];
                            if (isset($refData1['card_id']))
                                $paymentOctopusCardId = $refData1['card_id'];
                            if (isset($refData1['last_add_value_type']))
                                $paymentOctopusLastAddValueType = $refData1['last_add_value_type'];
                            if (isset($refData1['last_add_value_date']))
                                $paymentOctopusLastAddValueDate = $refData1['last_add_value_date'];
                            if (isset($refData1['transaction_time']))
                                $paymentOctopusTransactionTime = $refData1['transaction_time'];
                        } else if (strcmp($checkPayment['cpay_payment_type'], "rewrite_card") == 0) {
                            if (isset($refData1['card_number']))
                                $paymentRewriteCardCardNumber = $refData1['card_number'];
                            if (isset($refData1['original_remain_amount']))
                                $paymentRewriteCardOriginalAmount = $refData1['original_remain_amount'];
                            if (isset($refData1['current_remain_amount']))
                                $paymentRewriteCardCurrentAmount = $refData1['current_remain_amount'];
                        }
                    } else if ($checkPayment['cpay_ref_data1'] != null && strcmp($checkPayment['cpay_ref_data1'], "") != 0)
                        $paymentReferenceNum['1'] = $checkPayment['cpay_ref_data1'];
                    if ($this->__checkNumericExist($checkPayment, "cpay_ref_data1") != 0)
                        $paymentReferenceNum['1'] = $checkPayment['cpay_ref_data1'];

                    if ($checkPayment['cpay_ref_data2'] != null && strcmp($checkPayment['cpay_ref_data2'], "") != 0 || $this->__checkNumericExist($checkPayment, "cpay_ref_data2"))
                        $paymentReferenceNum['2'] = $checkPayment['cpay_ref_data2'];

                    if ($checkPayment['cpay_ref_data3'] != null && strcmp($checkPayment['cpay_ref_data3'], "") != 0 || $this->__checkNumericExist($checkPayment, "cpay_ref_data3"))
                        $paymentReferenceNum['3'] = $checkPayment['cpay_ref_data3'];

                    $paymentRunningNum = "";


                    $paymentEsignatureWithIndexArray = array();
                    $paymentPms = array('roomNumber' => "", 'guestNumber' => "", 'guestName' => "");
                    $paymentCreditCard = array('cardNo' => "", 'expDate' => "", 'holderName' => "", 'cardTypeName' => "", 'merchantNumber' => "", 'batchNumber' => '', 'terminalNumber' => '', 'approvalCode' => '', 'referenceNumber' => '');
                    $paymentMembershipIntf = array('accountNumber' => "", 'traceId' => "", 'authCode' => "", 'localBalance' => "", 'accountName' => "", 'englishName' => "", 'cardTypeName' => "", 'printLine1' => "",
                        'printLine2' => "", 'pointsBalance' => "", 'pointsEarn' => "", 'cardSn' => "", 'expiryDate' => "", 'cardLevelName' => "", "memberNumber" => "", "memberName" => "", "couponNumber" => "", "couponFaceAmount" => "",
                        'pointsUsed' => "", 'cardStoreValueUsed' => "", 'cardNo' => "", 'awardCode' => "", 'pointRedeem' => "", 'pointsReturned' => "", 'cancelAwardNumber' => "", 'memberType' => "");
                    $paymentGamingIntf = array("memberNumber" => "", "memberName" => "", 'cardNo' => "", 'inputMethod' => "", 'staffId' => "", 'remark' => "", 'userNumber' => "", 'pointsBalance' => "", "pointsDepartment" => "", "giftCertId" => "", "couponId" => "", 'accountNumber' => "", 'cardType' => "", 'firstName' => "", 'lastName' => "", 'referenceNo' => "", 'compNumber' => "", 'transactionKey' => "");
                    $paymentLoyaltyIntf = array('cardNo' => "", 'originalAmount' => "", 'currentAmount' => "");
                    $paymentPaymentIntf = array('merchantName' => "", 'merchantNumber' => "", 'transactionTime' => "", 'transactionNum' => "", 'payAmount' => 0, 'invoiceAmount' => 0, 'channelTransactionNum' => "", 'accountNumber' => "", 'pointsBalance' => "", 'platformTransactionNum' => "", 'cardNo' => "");
                    $paymentVoucherIntf = array('voucherNumber' => "");
                    $paymentLoyaltySVCInfo = array('svcCardNumber' => "", 'svcRemainingBalance' => "");
                    $paymentExtraInfos = $this->__getExtraInfo($checkExtraInfos, $checkPayment['cpay_id'], 0);
                    $oddEvenAmount = "";
                    if (!empty($paymentExtraInfos)) {
                        foreach ($paymentExtraInfos as $paymentExtraInfo) {
                            if ($paymentExtraInfo['ckei_by'] == "payment" && (isset($paymentExtraInfo['ckei_section']) && ($paymentExtraInfo['ckei_section'] == "pms" || $paymentExtraInfo['ckei_section'] == "credit_card" || $paymentExtraInfo['ckei_section'] == "membership_interface" || $paymentExtraInfo['ckei_section'] == "payment_interface" || $paymentExtraInfo['ckei_section'] == "loyalty_svc" || $paymentExtraInfo['ckei_section'] == "loyalty" || $paymentExtraInfo['ckei_section'] == "gaming_interface" || $paymentExtraInfo['ckei_section'] == "voucher_interface"))) {
                                switch ($paymentExtraInfo['ckei_variable']) {
                                    case "room":
                                        $paymentPms['roomNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "guest_no":
                                        $paymentPms['guestNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "guest_name":
                                        $paymentPms['guestName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "card_no":
                                        if ($paymentExtraInfo['ckei_section'] == "loyalty")
                                            $paymentLoyaltyIntf['cardNo'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else if ($paymentExtraInfo['ckei_section'] == "membership_interface") {
                                            $paymentMembershipIntf["cardNo"] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            if (!empty($paymentMembershipIntf["cardNo"]))
                                                $vars['CheckMembershipIntfAttachedAtPayment'] = 1;
                                        } else if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                            $paymentGamingIntf['cardNo'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else
                                            $paymentCreditCard["cardNo"] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "exp_date":
                                        $paymentCreditCard["expDate"] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "holder_name":
                                        $paymentCreditCard["holderName"] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "account_number":
                                        if ($paymentExtraInfo['ckei_section'] == "payment_interface")
                                            $paymentPaymentIntf['accountNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                            $paymentGamingIntf['accountNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else
                                            $paymentMembershipIntf['accountNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "trace_id":
                                        if ($paymentExtraInfo['ckei_section'] == "payment_interface")
                                            $paymentPaymentIntf['transactionNum'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else
                                            $paymentMembershipIntf['traceId'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "auth_code":
                                        $paymentMembershipIntf['authCode'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "spa_standard_masked_pan":
                                        $paymentPaymentIntf['cardNo'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "local_balance":
                                        $paymentMembershipIntf['localBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "account_name":
                                        $paymentMembershipIntf['accountName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "english_name":
                                        $paymentMembershipIntf['englishName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "card_type_name":
                                        if ($paymentExtraInfo['ckei_section'] == "credit_card")
                                            $paymentCreditCard['cardTypeName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                            $paymentGamingIntf['cardType'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else
                                            $paymentMembershipIntf['cardTypeName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "print_line":
                                        if ($paymentExtraInfo['ckei_index'] == 1)
                                            $paymentMembershipIntf['printLine1'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else if ($paymentExtraInfo['ckei_index'] == 2)
                                            $paymentMembershipIntf['printLine2'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "points_balance":
                                        if ($paymentExtraInfo['ckei_section'] == "payment_interface")
                                            $paymentPaymentIntf['pointsBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else if ($paymentExtraInfo['ckei_section'] == "loyalty")
                                            $paymentLoyaltyIntf['originalAmount'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                            $paymentGamingIntf['pointsBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else
                                            $paymentMembershipIntf['pointsBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "points_earn":
                                        $paymentMembershipIntf['pointsEarn'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "card_sn":
                                        $paymentMembershipIntf['cardSn'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "expiry_date":
                                        $paymentMembershipIntf['expiryDate'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "card_level_name":
                                        $paymentMembershipIntf['cardLevelName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "member_number":
                                        if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                            $paymentGamingIntf['memberNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else {
                                            $paymentMembershipIntf['memberNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            if (!empty($paymentMembershipIntf['memberNumber']))
                                                $vars['CheckMembershipIntfAttachedAtPayment'] = 1;
                                        }
                                        break;
                                    case "member_name":
                                        if ($paymentExtraInfo['ckei_section'] == "gaming_interface") {
                                            $paymentGamingIntf['memberName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            $paymentGamingIntf['firstName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        } else
                                            $paymentMembershipIntf['memberName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "svc_coupon_number":
                                    case "voucher_number":
                                        if ($paymentExtraInfo['ckei_section'] == "voucher_interface")
                                            $paymentVoucherIntf['voucherNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else
                                            $paymentMembershipIntf['couponNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "svc_coupon_amount":
                                    case "voucher_value":
                                        $paymentMembershipIntf['couponFaceAmount'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "terminal_number":
                                        $paymentCreditCard['terminalNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "merchant_number":
                                        $paymentCreditCard['merchantNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "batch_number":
                                        $paymentCreditCard['batchNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "approval_code":
                                        $paymentCreditCard['approvalCode'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "reference":
                                        if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                            $paymentGamingIntf['referenceNo'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        else
                                            $paymentCreditCard['referenceNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "internal_use":
                                        $internalUse = (isset($paymentExtraInfo['ckei_value'])) ? json_decode($paymentExtraInfo['ckei_value'], true) : "";
                                        if (empty($internalUse))
                                            break;

                                        if ($paymentExtraInfo['ckei_section'] == "payment_interface") {
                                            if (isset($internalUse['merchantName']))
                                                $paymentPaymentIntf['merchantName'] = $internalUse['merchantName'];
                                            if (isset($internalUse['merchantId']))
                                                $paymentPaymentIntf['merchantNumber'] = $internalUse['merchantId'];
                                            if (isset($internalUse['transactionTime']))
                                                $paymentPaymentIntf['transactionTime'] = $internalUse['transactionTime'];
                                            if (isset($internalUse['transactionPayTotal']))
                                                $paymentPaymentIntf['payAmount'] = $internalUse['transactionPayTotal'];
                                            if (isset($internalUse['invoiceTotal']))
                                                $paymentPaymentIntf['invoiceAmount'] = $internalUse['invoiceTotal'];
                                            if (isset($internalUse['channelTransactionNum']))
                                                $paymentPaymentIntf['channelTransactionNum'] = $internalUse['channelTransactionNum'];
                                            if (isset($internalUse['platformTransactionNum']))
                                                $paymentPaymentIntf['platformTransactionNum'] = $internalUse['platformTransactionNum'];
                                        }
                                        break;
                                    case "e_signature":
                                        $paymentEsignatureWithIndexArray[$paymentExtraInfo['ckei_index']] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "points_use":
                                        $paymentMembershipIntf['pointsUsed'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "card_store_value_used":
                                        $paymentMembershipIntf['cardStoreValueUsed'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "svc_card_number":
                                        $paymentLoyaltySVCInfo['svcCardNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "svc_remaining_balance":
                                        $paymentLoyaltySVCInfo['svcRemainingBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "total_points_balance":
                                        if ($paymentExtraInfo['ckei_section'] == "loyalty")
                                            $paymentLoyaltyIntf['currentAmount'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "input_type":
                                        $paymentGamingIntf['inputMethod'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "user_id":
                                        $paymentGamingIntf['userNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "staff_id":
                                        $paymentGamingIntf['staffId'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "remark":
                                        $paymentGamingIntf['remark'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "points_department":
                                        $paymentGamingIntf['pointsDepartment'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "gift_cert_id":
                                        $paymentGamingIntf['giftCertId'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "coupon":
                                        $paymentGamingIntf['couponId'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "member_last_name":
                                        if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                            $paymentGamingIntf['lastName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "payment_info":
                                        if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                            $paymentGamingIntf['compNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "posting_key":
                                        if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                            $paymentGamingIntf['transactionKey'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "award_code":
                                        $paymentMembershipIntf['awardCode'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "point_redeem":
                                        $paymentMembershipIntf['pointRedeem'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "points_returned":
                                        $paymentMembershipIntf['pointsReturned'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "cancel_award_number":
                                        $paymentMembershipIntf['cancelAwardNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    case "member_type":
                                        $paymentMembershipIntf['memberType'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                        break;
                                    default:
                                        break;
                                }
                            } else if ($paymentExtraInfo['ckei_by'] == "payment" && $paymentExtraInfo['ckei_variable'] == "running_number")
                                $paymentRunningNum = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                            else if ($paymentExtraInfo['ckei_by'] == "payment" && $paymentExtraInfo['ckei_variable'] == "payment_info")
                                $oddEvenAmount = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";

                        }
                    }

                    //concatenate payment E-signature
                    $paymentEsignature = "";
                    if (!empty($paymentEsignatureWithIndexArray)) {
                        foreach ($paymentEsignatureWithIndexArray as $paymentEsignatureValue) {
                            $paymentEsignature .= $paymentEsignatureValue;
                        }

                        $secretKey = substr($this->controller->encryptKey, 0, 16);
                        $iv = substr($this->controller->encryptKey, 1, 16);
                        $paymentEsignature = hex2bin($paymentEsignature);
                        $paymentEsignature = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $secretKey, $paymentEsignature, MCRYPT_MODE_CBC, $iv);
                        $paymentEsignature = str_replace("\0", "", $paymentEsignature);
                    }

                    $Payments[] = array(
                        'PaymentCode' => $paymentCode,
                        'PaymentName' => $checkPayment['cpay_name_l' . $prtFmtDefaultLang],
                        'PaymentNameL1' => $checkPayment['cpay_name_l1'],
                        'PaymentNameL2' => $checkPayment['cpay_name_l2'],
                        'PaymentNameL3' => $checkPayment['cpay_name_l3'],
                        'PaymentNameL4' => $checkPayment['cpay_name_l4'],
                        'PaymentNameL5' => $checkPayment['cpay_name_l5'],
                        'PaymentAmount' => number_format($checkPayment['cpay_pay_total'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentTotal' => number_format(($checkPayment['cpay_pay_total'] + $checkPayment['cpay_pay_tips']), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentTips' => number_format($paymentTips, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentResidue' => number_format($paymentResidue, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentChanges' => number_format($checkPayment['cpay_pay_change'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentSurcharge' => isset($checkPayment['cpay_pay_surcharge']) ? number_format($checkPayment['cpay_pay_surcharge'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "") : 0,
                        'PaymentType' => $checkPayment['cpay_payment_type'],
                        'PaymentMemberNum' => (!empty($paymentMember)) ? $paymentMember['MemMember']['memb_number'] : "",
                        'PaymentMemberName1' => (!empty($paymentMember)) ? ($paymentMember['MemMember']['memb_last_name_l1'] . ' ' . $paymentMember['MemMember']['memb_first_name_l1']) : "",
                        'PaymentMemberName2' => (!empty($paymentMember)) ? ($paymentMember['MemMember']['memb_last_name_l2'] . ' ' . $paymentMember['MemMember']['memb_first_name_l2']) : "",
                        'PaymentMemberDisplayName' => (!empty($paymentMember)) ? $paymentMember['MemMember']['memb_display_name'] : "",
                        'PaymentMemberSpending' => $paymentMemberSpending,
                        'PaymentEmployeeNum' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_number'] : "",
                        'PaymentEmployeeName' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] . ' ' . $paymentEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang]) : "",
                        'PaymentEmployeeNameL1' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l1'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l1']) : "",
                        'PaymentEmployeeNameL2' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l2'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l2']) : "",
                        'PaymentEmployeeNameL3' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l3'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l3']) : "",
                        'PaymentEmployeeNameL4' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l4'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l4']) : "",
                        'PaymentEmployeeNameL5' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l5'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l5']) : "",
                        'PaymentEmployeeFirstName' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang] : "",
                        'PaymentEmployeeFirstNameL1' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l1'] : "",
                        'PaymentEmployeeFirstNameL2' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l2'] : "",
                        'PaymentEmployeeFirstNameL3' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l3'] : "",
                        'PaymentEmployeeFirstNameL4' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l4'] : "",
                        'PaymentEmployeeFirstNameL5' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l5'] : "",
                        'PaymentEmployeeLastName' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] : "",
                        'PaymentEmployeeLastNameL1' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l1'] : "",
                        'PaymentEmployeeLastNameL2' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l2'] : "",
                        'PaymentEmployeeLastNameL3' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l3'] : "",
                        'PaymentEmployeeLastNameL4' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l4'] : "",
                        'PaymentEmployeeLastNameL5' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l5'] : "",
                        'PaymentEmployeeRemainingLimit' => $remainingCreditLimit,
                        'PaymentEmployeeMaxLimit' => $employeeMaximumCreditLimit,
                        'PaymentVoucherNum' => $paymentVoucherNum,
                        'PaymentOctopusCardType' => $paymentOctopusCardType,
                        'PaymentOctopusTransactionAmount' => $paymentOctopusTransactionAmount,
                        'PaymentOctopusUdsn' => $paymentOctopusUdsn,
                        'PaymentOctopusOriginalAmount' => $paymentOctopusOriginalAmount,
                        'PaymentOctopusDeviceId' => $paymentOctopusDeviceId,
                        'PaymentOctopusCurrentAmount' => $paymentOctopusCurrentAmount,
                        'PaymentOctopusCardId' => $paymentOctopusCardId,
                        'PaymentOctopusLastAddValueType' => $paymentOctopusLastAddValueType,
                        'PaymentOctopusLastAddValueDate' => $paymentOctopusLastAddValueDate,
                        'PaymentOctopusTransactionTime' => $paymentOctopusTransactionTime,
                        'PaymentPmsRoomNumber' => $paymentPms['roomNumber'],
                        'PaymentPmsGuestNumber' => $paymentPms['guestNumber'],
                        'PaymentPmsGuestName' => $paymentPms['guestName'],
                        'PaymentNonGUI' => (isset($checkPayment['cpay_special']) && $checkPayment['cpay_special'] == 'g') ? 1 : 0,
                        'PaymentCreditCardNum' => $paymentCreditCard['cardNo'],
                        'PaymentCreditCardExpDate' => $paymentCreditCard['expDate'],
                        'PaymentCreditCardHolderName' => $paymentCreditCard['holderName'],
                        'PaymentCreditCardTerminalNumber' => $paymentCreditCard['terminalNumber'],
                        'PaymentCreditCardBatchNumber' => $paymentCreditCard['batchNumber'],
                        'PaymentCreditCardApprovalCode' => $paymentCreditCard['approvalCode'],
                        'PaymentCreditCardMerchantNumber' => $paymentCreditCard['merchantNumber'],
                        'PaymentCreditCardReferenceNumber' => $paymentCreditCard['referenceNumber'],
                        'PaymentCreditCardTypeName' => $paymentCreditCard['cardTypeName'],
                        'PaymentRewriteCardOriginalAmount' => $paymentRewriteCardOriginalAmount,
                        'PaymentRewriteCardCurrentAmount' => $paymentRewriteCardCurrentAmount,
                        'PaymentRewriteCardCardNumber' => $paymentRewriteCardCardNumber,
                        'PaymentMembershipIntfAccountNumber' => $paymentMembershipIntf['accountNumber'],
                        'PaymentMembershipIntfTraceID' => $paymentMembershipIntf['traceId'],
                        'PaymentMembershipIntfAuthorityCode' => $paymentMembershipIntf['authCode'],
                        'PaymentMembershipIntfAwardCode' => $paymentMembershipIntf['awardCode'],
                        'PaymentMembershipIntfCancelAwardNumber' => $paymentMembershipIntf['cancelAwardNumber'],
                        'PaymentMembershipIntfLocalBalance' => $paymentMembershipIntf['localBalance'],
                        'PaymentMembershipIntfAccountName' => $paymentMembershipIntf['accountName'],
                        'PaymentMembershipIntfEnglishName' => $paymentMembershipIntf['englishName'],
                        'PaymentMembershipIntfCardTypeName' => $paymentMembershipIntf['cardTypeName'],
                        'PaymentMembershipIntfPrintLine1' => $paymentMembershipIntf['printLine1'],
                        'PaymentMembershipIntfPrintLine2' => $paymentMembershipIntf['printLine2'],
                        'PaymentMembershipIntfPointBalance' => $paymentMembershipIntf['pointsBalance'],
                        'PaymentMembershipIntfPointEarn' => $paymentMembershipIntf['pointsEarn'],
                        'PaymentMembershipIntfPointsReturned' => $paymentMembershipIntf['pointsReturned'],
                        'PaymentMembershipIntfCardSN' => $paymentMembershipIntf['cardSn'],
                        'PaymentMembershipIntfExpiryDate' => $paymentMembershipIntf['expiryDate'],
                        'PaymentMembershipIntfCardLevelName' => $paymentMembershipIntf['cardLevelName'],
                        'PaymentMembershipIntfCardNumber' => $paymentMembershipIntf['cardNo'],
                        'PaymentMembershipIntfMemberNumber' => $paymentMembershipIntf['memberNumber'],
                        'PaymentMembershipIntfMemberName' => $paymentMembershipIntf['memberName'],
                        'PaymentMembershipIntfMemberType' => $paymentMembershipIntf['memberType'],
                        'PaymentMembershipIntfCouponNumber' => $paymentMembershipIntf['couponNumber'],
                        'PaymentMembershipIntfCouponFaceAmt' => $paymentMembershipIntf['couponFaceAmount'],
                        'PaymentMembershipIntfPointRedeem' => $paymentMembershipIntf['pointRedeem'],
                        'PaymentMembershipIntfPointUsed' => $paymentMembershipIntf['pointsUsed'],
                        'PaymentMembershipInfCardStoreValueUsed' => $paymentMembershipIntf['cardStoreValueUsed'],
                        'PaymentByForeignCurrency' => ($checkPayment['cpay_pay_foreign_currency'] == 'y') ? 1 : 0,
                        'PaymentChangesBackForeignCurrency' => ($checkPayment['cpay_change_back_currency'] == 'f') ? 1 : 0,
                        'PaymentAmountInForeignCurrency' => ($paymentMethod != null) ? number_format($checkPayment['cpay_pay_foreign_total'], $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format($checkPayment['cpay_pay_foreign_total'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentTotalInForeignCurrency' => ($paymentMethod != null) ? number_format(($checkPayment['cpay_pay_foreign_total'] + $checkPayment['cpay_pay_foreign_tips']), $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format(($checkPayment['cpay_pay_foreign_total'] + $checkPayment['cpay_pay_foreign_tips']), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentTipsInForeignCurrency' => $paymentTipsInForeignCurrency,
                        'PaymentResidueInForeignCurrency' => $paymentResidueInForeignCurrency,
                        'PaymentChangesInForeignCurrency' => ($paymentMethod != null) ? number_format($checkPayment['cpay_pay_foreign_change'], $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format($checkPayment['cpay_pay_foreign_change'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentSurchargeInForeignCurrency' => isset($checkPayment['cpay_pay_foreign_surcharge']) ? (($paymentMethod != null) ? number_format($checkPayment['cpay_pay_foreign_surcharge'], $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format($checkPayment['cpay_pay_foreign_surcharge'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "")) : "0",
                        'PaymentPayInfMerchantName' => $paymentPaymentIntf['merchantName'],
                        'PaymentPayInfMerchantNumber' => $paymentPaymentIntf['merchantNumber'],
                        'PaymentPayInfTransactionTime' => $paymentPaymentIntf['transactionTime'],
                        'PaymentPayInfTransactionNum' => $paymentPaymentIntf['transactionNum'],
                        'PaymentPayInfPayAmount' => $paymentPaymentIntf['payAmount'],
                        'PaymentPayInfInvoiceAmount' => $paymentPaymentIntf['invoiceAmount'],
                        'PaymentPayInfChannelTransactionNum' => $paymentPaymentIntf['channelTransactionNum'],
                        'PaymentPayInfAccountNum' => $paymentPaymentIntf['accountNumber'],
                        'PaymentPayInfPointsBalance' => $paymentPaymentIntf['pointsBalance'],
                        'PaymentPayInfPlatformTransactionNum' => $paymentPaymentIntf['platformTransactionNum'],
                        'PaymentPayInfCardNumber' => $paymentPaymentIntf['cardNo'],
                        'PaymentVoucherIntfVoucherNumber' => $paymentVoucherIntf['voucherNumber'],
                        'PaymentRunningNumber' => $paymentRunningNum,
                        'PaymentReferenceNum1' => $paymentReferenceNum['1'],
                        'PaymentReferenceNum2' => $paymentReferenceNum['2'],
                        'PaymentReferenceNum3' => $paymentReferenceNum['3'],
                        'PaymentESignature' => $paymentEsignature,
                        'PaymentLoyaltySVCCardNumber' => $paymentLoyaltySVCInfo['svcCardNumber'],
                        'PaymentLoyaltySVCRemainingBalance' => $paymentLoyaltySVCInfo['svcRemainingBalance'],
                        'PaymentLoyaltyCardNumber' => $paymentLoyaltyIntf['cardNo'],
                        'PaymentLoyaltyOriginalAmount' => $paymentLoyaltyIntf['originalAmount'],
                        'PaymentLoyaltyCurrentAmount' => $paymentLoyaltyIntf['currentAmount'],
                        'PaymentGamingIntfMemberName' => $paymentGamingIntf['memberName'],
                        'PaymentGamingIntfMemberNumber' => $paymentGamingIntf['memberNumber'],
                        'PaymentGamingIntfMemberCardNumber' => $paymentGamingIntf['cardNo'],
                        'PaymentGamingIntfInputMethod' => $paymentGamingIntf['inputMethod'],
                        'PaymentGamingIntfStaffId' => $paymentGamingIntf['staffId'],
                        'PaymentGamingIntfRemark' => $paymentGamingIntf['remark'],
                        'PaymentGamingIntfUserNumber' => $paymentGamingIntf['userNumber'],
                        'PaymentGamingIntfPointBalance' => $paymentGamingIntf['pointsBalance'],
                        'PaymentGamingIntfPointDepartment' => $paymentGamingIntf['pointsDepartment'],
                        'PaymentGamingIntfGiftCertId' => $paymentGamingIntf['giftCertId'],
                        'PaymentGamingIntfCouponId' => $paymentGamingIntf['couponId'],
                        'PaymentGamingIntfAccountNumber' => $paymentGamingIntf['accountNumber'],
                        'PaymentGamingIntfCardType' => $paymentGamingIntf['cardType'],
                        'PaymentGamingIntfMemberFirstName' => $paymentGamingIntf['firstName'],
                        'PaymentGamingIntfMemberLastName' => $paymentGamingIntf['lastName'],
                        'PaymentGamingIntfReferenceNumber' => $paymentGamingIntf['referenceNo'],
                        'PaymentGamingIntfCompNumber' => $paymentGamingIntf['compNumber'],
                        'PaymentGamingIntfTransactionKey' => $paymentGamingIntf['transactionKey'],
                        'OddEvenAmount' => $oddEvenAmount
                    );
                }
            }

            //get release payment information
            $voidedPayments = $PosCheckModel->PosCheckPayment->findAllVoidByCheckId($checkId);
            $AllReleasePaymentRunningNumber = array();
            $LastReleasePaymentRunningNumber = array();
            if (!empty($voidedPayments)) {
                $currentVoidTime = "";
                $previousVoidTime = "";
                $releasePaymentCount = 0;
                $releasePaymentTotal = array();
                $posCheckExtraInfoModel = new PosCheckExtraInfo();
                $voidedPaymentExtraInfos = $posCheckExtraInfoModel->findAllByConfigAndCheckId('payment', $checkId, "d", -1);
                $latestVoidTime = "";
                $latestIndex = array();
                for ($index = 0; $index < count($voidedPayments); $index++) {
                    $currentVoidTime = $voidedPayments[$index]['PosCheckPayment']['cpay_void_loctime'];
                    if ($index == 0) {
                        $latestVoidTime = $currentVoidTime;
                        $latestIndex[] = $index;
                    } else {
                        if (strcmp($latestVoidTime, $currentVoidTime) < 0) {
                            $latestVoidTime = $currentVoidTime;
                            unset($latestIndex);
                            $latestIndex[] = $index;
                        } else if (strcmp($latestVoidTime, $currentVoidTime) == 0)
                            $latestIndex[] = $index;
                    }
                    if (strcmp($previousVoidTime, $currentVoidTime) != 0) {
                        $releasePaymentCount++;
                        $releasePaymentTotal[$releasePaymentCount] = 0;
                    }
                    $releasePaymentTotal[$releasePaymentCount] += $voidedPayments[$index]['PosCheckPayment']['cpay_pay_total'];

                    $previousVoidTime = $currentVoidTime;
                    $paymentExtraInfos = $this->__getExtraInfo($voidedPaymentExtraInfos, $voidedPayments[$index]['PosCheckPayment']['cpay_id'], 0);
                    $paymentRunningNum = "";
                    if (!empty($paymentExtraInfos)) {
                        foreach ($paymentExtraInfos as $paymentExtraInfo) {
                            if ($paymentExtraInfo['ckei_by'] == "payment" && $paymentExtraInfo['ckei_variable'] == "running_number")
                                $paymentRunningNum = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                        }
                    }
                    if (empty($paymentRunningNum))
                        continue;
                    $existSameRunningNumber = 0;
                    foreach ($AllReleasePaymentRunningNumber as $eachRunningNumber) {
                        if (in_array($paymentRunningNum, $eachRunningNumber)) {
                            $existSameRunningNumber = 1;
                            break;
                        }
                    }
                    if ($existSameRunningNumber == 0)
                        $AllReleasePaymentRunningNumber[] = array("AllReleasePaymentRunningNumber" => $paymentRunningNum);
                }
                foreach ($latestIndex as $latestEachIndex) {
                    $paymentExtraInfos = $this->__getExtraInfo($voidedPaymentExtraInfos, $voidedPayments[$latestEachIndex]['PosCheckPayment']['cpay_id'], 0);
                    $paymentRunningNum = "";
                    if (!empty($paymentExtraInfos)) {
                        foreach ($paymentExtraInfos as $paymentExtraInfo) {
                            if ($paymentExtraInfo['ckei_by'] == "payment" && $paymentExtraInfo['ckei_variable'] == "running_number")
                                $paymentRunningNum = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                        }
                    }
                    if (empty($paymentRunningNum))
                        continue;
                    $existSameRunningNumber = 0;
                    foreach ($LastReleasePaymentRunningNumber as $eachRunningNumber) {
                        if (in_array($paymentRunningNum, $eachRunningNumber)) {
                            $existSameRunningNumber = 1;
                            break;
                        }
                    }
                    if ($existSameRunningNumber == 0)
                        $LastReleasePaymentRunningNumber[] = array("LastReleasePaymentRunningNumber" => $paymentRunningNum);
                }
                $vars['CheckReleasePaymentCount'] = $releasePaymentCount;
                $vars['CheckLastReleasePaymentTotal'] = number_format($releasePaymentTotal[$releasePaymentCount], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
            }
        }

        $vars['PayAmountTotal'] = number_format($paymentAmountTotal, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        $vars['CheckUnpaidPaymentTotal'] = number_format(0, $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");
        $vars['CheckPreviousPaidPaymentTotal'] = number_format($check['PosCheck']['chks_check_total'], $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");
        $vars['TipsTotal'] = number_format($totalTips, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        $vars['Changes'] = number_format($totalChange, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        if ($prtFmtDefaultLang > 0) {
            if ($checkCloseUser != null) {
                $vars['CheckCloseEmployeeFirstName'] = $checkCloseUser['UserUser']['user_first_name_l' . $prtFmtDefaultLang];
                $vars['CheckCloseEmployeeLastName'] = $checkCloseUser['UserUser']['user_last_name_l' . $prtFmtDefaultLang];
                $vars['CheckCloseEmployee'] = $vars['CheckCloseEmployeeLastName'] . ' ' . $vars['CheckCloseEmployeeFirstName'];
            }
        }
        for ($index = 1; $index <= 5; $index++) {
            if ($checkCloseUser != null) {
                $vars['CheckCloseEmployeeFirstNameL' . $index] = $checkCloseUser['UserUser']['user_first_name_l' . $index];
                $vars['CheckCloseEmployeeLastNameL' . $index] = $checkCloseUser['UserUser']['user_last_name_l' . $index];
                $vars['CheckCloseEmployeeL' . $index] = $vars['CheckCloseEmployeeLastNameL' . $index] . ' ' . $vars['CheckCloseEmployeeFirstNameL' . $index];
            }
        }

        $vars['CheckCloseTime'] = date('H:i:s', strtotime($check['PosCheck']['chks_close_loctime']));

        //get the date related printing variables
        $this->__updateDateFormat($printFormat, $outlet['OutOutlet']['olet_date_format'], $shop['OutShop']['shop_timezone'], $shop['OutShop']['shop_timezone_name'], $businessDay['PosBusinessDay']['bday_date'], $check['PosCheck']['chks_open_loctime'], $check['PosCheck']['chks_print_loctime'], $check['PosCheck']['chks_close_loctime'], $vars, "generateCheckReceiptSlip");

        $gratuityTotal = 0;
        $vars['Gratuity'] = array();
        $posCheckGratuities = $PosCheckGratuityModel->findAllByOutletAndCheckId($check['PosCheck']['chks_olet_id'], $check['PosCheck']['chks_id']);
        foreach ($posCheckGratuities as $posCheckGratuity) {
            $gratuityName = $posCheckGratuity['PosCheckGratuity']['cgra_name_l' . $prtFmtDefaultLang];
            $gratuityNameL1 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l1'];
            $gratuityNameL2 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l2'];
            $gratuityNameL3 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l3'];
            $gratuityNameL4 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l4'];
            $gratuityNameL5 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l5'];
            $gratuityAmount = $posCheckGratuity['PosCheckGratuity']['cgra_round_total'];
            $gratuityTotal += $posCheckGratuity['PosCheckGratuity']['cgra_round_total'];
            $gratuity = array('GratuityName' => $gratuityName, 'GratuityAmount' => $gratuityAmount);

            // Gratuity Eligibile printing variable
            if ($isWohModelExist && !empty($wohAwardSettingLists)) {
                // Hyatt Point Redemption handling
                if (in_array($posCheckGratuity['PosCheckGratuity']['cgra_grat_id'], $wohAwardSettingLists['wohEligibleAwdGratuityIds']))
                    $gratuity['GratuityEligibleForPointRedemption'] = true;
                else
                    $gratuity['GratuityEligibleForPointRedemption'] = false;

                // Hyatt Earn Point handling
                if (in_array($posCheckGratuity['PosCheckGratuity']['cgra_grat_id'], $wohAwardSettingLists['wohEligibleEarningGratuityIds']))
                    $gratuity['GratuityEligibleForEarnPoint'] = true;
                else
                    $gratuity['GratuityEligibleForEarnPoint'] = false;
            }

            $vars['Gratuity'][] = $gratuity;
        }
        $vars['GratuityTotal'] = $gratuityTotal;
        $vars['AuthAcquirerData'] = "";
        $vars['AuthAcquirerDatetime'] = "";
        $vars['AuthAcquirerMerchant'] = "";
        $vars['AuthAcquirerName'] = "";
        $vars['AuthAcquirerTerminal'] = "";
        $vars['AuthAmount'] = "";
        $vars['AuthAmountTotal'] = "";
        $vars['AuthCardNumber'] = "";
        $vars['AuthCode'] = "";
        $vars['AuthCurrencyCode'] = "";
        $vars['AuthCustomerData'] = "";
        $vars['AuthECashBalance'] = "";
        $vars['AuthEmployeeCode'] = "";
        $vars['AuthEmployeeName'] = "";
        $vars['AuthEmployeeNameL1'] = "";
        $vars['AuthEmployeeNameL2'] = "";
        $vars['AuthEmployeeNameL3'] = "";
        $vars['AuthEmployeeNameL4'] = "";
        $vars['AuthEmployeeNameL5'] = "";
        $vars['AuthEmployeeFirstName'] = "";
        $vars['AuthEmployeeFirstNameL1'] = "";
        $vars['AuthEmployeeFirstNameL2'] = "";
        $vars['AuthEmployeeFirstNameL3'] = "";
        $vars['AuthEmployeeFirstNameL4'] = "";
        $vars['AuthEmployeeFirstNameL5'] = "";
        $vars['AuthEmployeeLastName'] = "";
        $vars['AuthEmployeeLastNameL1'] = "";
        $vars['AuthEmployeeLastNameL2'] = "";
        $vars['AuthEmployeeLastNameL3'] = "";
        $vars['AuthEmployeeLastNameL4'] = "";
        $vars['AuthEmployeeLastNameL5'] = "";
        $vars['AuthEmv'] = "";
        $vars['AuthEmvData'] = "";
        $vars['AuthEntryMode'] = "";
        $vars['AuthIcCardSequence'] = "";
        $vars['AuthInvoiceNumber'] = "";
        $vars['AuthIssuer'] = "";
        $vars['AuthIntlCardTraceNum'] = "";
        $vars['AuthMerchantData'] = "";
        $vars['AuthReferenceNumber'] = "";
        $vars['AuthSignFree'] = "";
        $vars['AuthSignFreeData'] = "";
        $vars['AuthSlipType'] = "";
        $vars['AuthTerminalSequence'] = "";
        $vars['AuthTips'] = "";
        $vars['AuthTransactionDateTime'] = "";
        $vars['AuthTraceNumber'] = "";
        $cardAuthorizations = array();

        if (isset($check['PosPaymentGatewayTransaction'])) {
            $TempCardAuthorizations = $check['PosPaymentGatewayTransaction'];
            foreach ($check['PosPaymentGatewayTransaction'] as $cardAuthorization) {
                $acquirerInfo = json_decode($cardAuthorization['pgtx_acquirer_info'], true);
                $otherInfo = json_decode($cardAuthorization['pgtx_other_info'], true);

                // cal the amount total
                $AuthAmountTotal = 0;
                if ($cardAuthorization['pgtx_type_key'] != "credit_card_complete_auth") {
                    foreach ($TempCardAuthorizations as $TempCardAuthorization) {
                        if ($TempCardAuthorization['pgtx_type_key'] == "credit_card_complete_auth")
                            continue;
                        if (isset($TempCardAuthorization['PosPaymentGatewayTransaction']))
                            $TempCardAuthorization = $TempCardAuthorization['PosPaymentGatewayTransaction'];
                        if ($TempCardAuthorization['pgtx_auth_code'] == $cardAuthorization['pgtx_auth_code']
                            || $TempCardAuthorization['pgtx_parent_auth_code'] == $cardAuthorization['pgtx_auth_code']
                            || ($cardAuthorization['pgtx_parent_auth_code'] != ""
                                && ($TempCardAuthorization['pgtx_auth_code'] == $cardAuthorization['pgtx_parent_auth_code']
                                    || $TempCardAuthorization['pgtx_parent_auth_code'] == $cardAuthorization['pgtx_parent_auth_code'])))
                            $AuthAmountTotal += $TempCardAuthorization['pgtx_amount'];
                    }
                }

                // Get the employer name
                $paymentEmployee = array();
                $authEmployeeName = $authEmployeeNameL1 = $authEmployeeNameL2 = $authEmployeeNameL3 = $authEmployeeNameL4 = $authEmployeeNameL5 = "";
                $authEmployeeFirstName = $authEmployeeFirstNameL1 = $authEmployeeFirstNameL2 = $authEmployeeFirstNameL3 = $authEmployeeFirstNameL4 = $authEmployeeFirstNameL5 = "";
                $authEmployeeLastName = $authEmployeeLastNameL1 = $authEmployeeLastNameL2 = $authEmployeeLastNameL3 = $authEmployeeLastNameL4 = $authEmployeeLastNameL5 = "";
                $paymentEmployee = $UserUserModel->findActiveById($cardAuthorization['pgtx_action_user_id'], -1);
                if ($paymentEmployee != null) {
                    $authEmployeeFirstName = $paymentEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang];
                    $authEmployeeFirstNameL1 = $paymentEmployee['UserUser']['user_first_name_l1'];
                    $authEmployeeFirstNameL2 = $paymentEmployee['UserUser']['user_first_name_l2'];
                    $authEmployeeFirstNameL3 = $paymentEmployee['UserUser']['user_first_name_l3'];
                    $authEmployeeFirstNameL4 = $paymentEmployee['UserUser']['user_first_name_l4'];
                    $authEmployeeFirstNameL5 = $paymentEmployee['UserUser']['user_first_name_l5'];
                    $authEmployeeLastName = $paymentEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang];
                    $authEmployeeLastNameL1 = $paymentEmployee['UserUser']['user_last_name_l1'];
                    $authEmployeeLastNameL2 = $paymentEmployee['UserUser']['user_last_name_l2'];
                    $authEmployeeLastNameL3 = $paymentEmployee['UserUser']['user_last_name_l3'];
                    $authEmployeeLastNameL4 = $paymentEmployee['UserUser']['user_last_name_l4'];
                    $authEmployeeLastNameL5 = $paymentEmployee['UserUser']['user_last_name_l5'];
                    $authEmployeeName = $authEmployeeLastName . ' ' . $authEmployeeFirstName;
                    $authEmployeeNameL1 = $authEmployeeLastNameL1 . ' ' . $authEmployeeFirstNameL1;
                    $authEmployeeNameL2 = $authEmployeeLastNameL2 . ' ' . $authEmployeeFirstNameL2;
                    $authEmployeeNameL3 = $authEmployeeLastNameL3 . ' ' . $authEmployeeFirstNameL3;
                    $authEmployeeNameL4 = $authEmployeeLastNameL4 . ' ' . $authEmployeeFirstNameL4;
                    $authEmployeeNameL5 = $authEmployeeLastNameL5 . ' ' . $authEmployeeFirstNameL5;
                }

                $cardAuthorizations[] = array(
                    "AuthAcquirerData" => $acquirerInfo['data'],
                    "AuthAcquirerDatetime" => $acquirerInfo['datetime'],
                    "AuthAcquirerMerchant" => $acquirerInfo['merchant_id'],
                    "AuthAcquirerName" => $acquirerInfo['name'],
                    "AuthAcquirerTerminal" => $acquirerInfo['terminal'],
                    "AuthAmount" => $cardAuthorization['pgtx_amount'],
                    "AuthAmountTotal" => $AuthAmountTotal,
                    "AuthCardNumber" => $cardAuthorization['pgtx_masked_pan'],
                    "AuthCode" => $cardAuthorization['pgtx_auth_code'],
                    "AuthCurrencyCode" => $otherInfo['currency_code'],
                    "AuthCustomerData" => base64_decode($cardAuthorization['pgtx_customer_copy']),
                    "AuthECashBalance" => $otherInfo['ecash_balance'],
                    "AuthEmployeeCode" => $cardAuthorization['pgtx_action_user_id'],
                    "AuthEmployeeName" => $authEmployeeName,
                    "AuthEmployeeNameL1" => $authEmployeeNameL1,
                    "AuthEmployeeNameL2" => $authEmployeeNameL2,
                    "AuthEmployeeNameL3" => $authEmployeeNameL3,
                    "AuthEmployeeNameL4" => $authEmployeeNameL4,
                    "AuthEmployeeNameL5" => $authEmployeeNameL5,
                    "AuthEmployeeFirstName" => $authEmployeeFirstName,
                    "AuthEmployeeFirstNameL1" => $authEmployeeFirstNameL1,
                    "AuthEmployeeFirstNameL2" => $authEmployeeFirstNameL2,
                    "AuthEmployeeFirstNameL3" => $authEmployeeFirstNameL3,
                    "AuthEmployeeFirstNameL4" => $authEmployeeFirstNameL4,
                    "AuthEmployeeFirstNameL5" => $authEmployeeFirstNameL5,
                    "AuthEmployeeLastName" => $authEmployeeLastName,
                    "AuthEmployeeLastNameL1" => $authEmployeeLastNameL1,
                    "AuthEmployeeLastNameL2" => $authEmployeeLastNameL2,
                    "AuthEmployeeLastNameL3" => $authEmployeeLastNameL3,
                    "AuthEmployeeLastNameL4" => $authEmployeeLastNameL4,
                    "AuthEmployeeLastNameL5" => $authEmployeeLastNameL5,
                    "AuthEmv" => $otherInfo['emv'],
                    "AuthEmvData" => $otherInfo['emv_data'],
                    "AuthEntryMode" => $cardAuthorization['pgtx_entry_mode'],
                    "AuthIcCardSequence" => $otherInfo['ic_card_seq'],
                    "AuthInvoiceNumber" => $cardAuthorization['pgtx_invoice_num'],
                    "AuthIssuer" => $cardAuthorization['pgtx_issuer'],
                    "AuthIntlCardTraceNum" => $otherInfo['intl_card_trace_num'],
                    "AuthMerchantData" => base64_decode($cardAuthorization['pgtx_merchant_copy']),
                    "AuthReferenceNumber" => $cardAuthorization['pgtx_ref_num'],
                    "AuthSignFree" => $otherInfo['sign_free'],
                    "AuthSignFreeData" => $otherInfo['sign_free_data'],
                    "AuthTerminalSequence" => $otherInfo['terminal_seq'],
                    "AuthTips" => $cardAuthorization['pgtx_tips'],
                    "AuthTransactionDateTime" => $cardAuthorization['pgtx_action_time'],
                    "AuthTraceNumber" => $cardAuthorization['pgtx_trace_num']
                );
            }
        }
        $vars['AuthSlipType '] = '';
        $vars['CardAuthorizations'] = $cardAuthorizations;

        //need to enhance later
        $bPrint = false;    // Not print @ browser but at print service instead
        $license = "";
        $language = "eng";
        $stationGroupId = 0;
        if (!empty($station))
            $stationGroupId = $station['PosStation']['stat_stgp_id'];
        $printQueue = $this->__checkPrintQueueOverride($printQueueOverrideConditions, $prtqId, $businessDay['PosBusinessDay']['bday_date'], substr($check['PosCheck']['chks_open_loctime'], 11), "", $checkTable['PosCheckTable']['ctbl_table'], $checkTable['PosCheckTable']['ctbl_table_ext'], "", $stationGroupId, $businessPeriod['PosBusinessPeriod']['bper_perd_id'], $check['PosCheck']['chks_ctyp_id'], $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay, $weekday);

        //Add Item Group Start and Group End
        if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "1")
            $groupTypeName = "ItemNameL1";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "2")
            $groupTypeName = "ItemNameL2";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "3")
            $groupTypeName = "ItemNameL3";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "4")
            $groupTypeName = "ItemNameL4";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "5")
            $groupTypeName = "ItemNameL5";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "c")
            $groupTypeName = "ItemCatName";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "d")
            $groupTypeName = "ItemDept";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "u")
            $groupTypeName = "ItemCourseName";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "s")
            $groupTypeName = "ItemSeatNum";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "t")
            $groupTypeName = "ItemId";
        else
            $groupTypeName = "ItemMenuId";
        $currentGroup = "";
        $pastGroup = "";
        for ($index = 0; $index < count($Items); $index++) {
            $currentGroup = $Items[$index][$groupTypeName];
            if (strcmp($currentGroup, $pastGroup) != 0) {
                $Items[$index]['ItemGroupStart'] = 1;
                if (($index - 1) >= 0)
                    $Items[($index - 1)]['ItemGroupEnd'] = 1;
            }
            if ($index == (count($Items) - 1))
                $Items[$index]['ItemGroupEnd'] = 1;
            $pastGroup = $currentGroup;
        }

        // Construct the var array for view vendor
        $vars['Items'] = $Items;
        $vars['CheckDiscounts'] = $CheckDiscounts;
        $vars['DiscTotalByDiscType'] = $DiscTotalByDiscType;
        $vars['CheckExtraCharges'] = $CheckExtraCharges;
        $vars['Departments'] = $Departments;
        $vars['Categories'] = $Categories;
        $vars['Payments'] = $Payments;
        $vars['DefaultPayments'] = $DefaultPayments;
        $vars['AllReleasePaymentRunningNumber'] = $AllReleasePaymentRunningNumber;
        $vars['LastReleasePaymentRunningNumber'] = $LastReleasePaymentRunningNumber;

        // Get the print format template
        $tpls = json_decode($printFormat['PosPrintFormat']['pfmt_format'], true);

        $printCtrls = array(
            'pageOffset' => 0,
            'lineOffset' => 0,
            'mediaUrl' => $this->controller->Common->getDataUrl('media_files/')
        );
        // Output the rendered page into HTML
        if ($type == 3)
            $outputFileName = "serving_list-" . date('YmdHis', time()) . "-" . $check['PosCheck']['chks_check_num'];
        else
            $outputFileName = "bill-" . date('YmdHis', time()) . "-" . $check['PosCheck']['chks_check_num'] . "-" . $pfmtId;

        if ($preview == 0) {
            $isAddToPrintJob = true;

            if (empty($renderFormatType))
                $renderFormatType = $printFormat['PosPrintFormat']['pfmt_render_type'];

            // If is send receipt by email, email address should exist
            if (isset($printInfo['email']['action']) && $printInfo['email']['action'] == "email_receipt") {
                $isAddToPrintJob = false;
                // Override the renderFormatType & Redirect configPath
                $renderFormatType = 'p';
                $pdfExportPath = $this->controller->Common->getDataPath(array('pos_emails'));
            }
            /////////////////////////////////////////////////////////////////////////
            //	Render the output file
            if ($renderFormatType == 't') {
                $viewFile = 'print_format_txt_' . $pfmtId;
                $printFileFmt = 'TXT';
                $printFileExt = '.txt';
            } else if ($renderFormatType == 'h') {
                $viewFile = 'print_format_html_' . $pfmtId;
                $printFileFmt = 'WEBPAGE';
                $printFileExt = '.html';
            } else if ($renderFormatType == 'p') {
                $viewFile = 'print_format_pdf_' . $pfmtId;
                $printFileFmt = 'PDF';
                $printFileExt = '.pdf';
                if (!empty($pdfExportPath))
                    $configPath = $pdfExportPath;
            } else {
                $viewFile = 'print_format_pfi_' . $pfmtId;
                $printFileFmt = 'PFILE';
                $printFileExt = '.pfi';
            }

            $outputFile = $configPath . $outputFileName . $printFileExt;
            $outputDest = 'F';
            $outputView = new View($this->controller, false);

            App::build(array('View' => array($shareDataPath)));
            $outputView->viewPath = '';
            $outputView->set(compact('tpls', 'vars', 'outputFile', 'outputDest', 'printCtrls'));

            $totalPages = 1;
            if ($renderFormatType == 't') {
                $PosPrintFormatModel->checkPrintFormatPlainTextViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewFile, $this->controller->languages);
                $viewContent = @$outputView->render($viewFile, '');
                file_put_contents($outputFile, $viewContent);
            } else if ($renderFormatType == 'h') {
                $PosPrintFormatModel->checkPrintFormatHtmlViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewFile, $this->controller->languages);
                $viewContent = @$outputView->render($viewFile, '');
                file_put_contents($outputFile, $viewContent);
            } else if ($renderFormatType == 'p') {
                $PosPrintFormatModel->checkPrintFormatTcpdfViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewFile, $this->controller->languages);
                $viewContent = @$outputView->render($viewFile, '');
            } else {
                $PosPrintFormatModel->checkPrintFormatPfileViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewFile, $this->controller->languages);
                $viewContent = @$outputView->render($viewFile, '');
                file_put_contents($outputFile, $viewContent);

                $viewContentJson = json_decode($viewContent, true);
                if ($viewContentJson != null && isset($viewContentJson['paper']['total_pages']))
                    $totalPages = $viewContentJson['paper']['total_pages'];
            }

            if ($isAddToPrintJob) {
                App::import('Component', 'Printing.PrintingApiGeneral');
                $this->PrintingApiGeneral = new PrintingApiGeneralComponent(new ComponentCollection());
                $this->PrintingApiGeneral->startup($this->controller);

                $reply = array();
                $param = array();
                $param['printJobFile'] = Router::url($configUrl . $outputFileName . $printFileExt, array('full' => true, 'escape' => true));
                $param['printQ'] = $printQueue;
                $param['printJobFileMediaType'] = $printFileFmt;
                if ($type == 2)
                    $param['printJobFileType'] = 'RECEIPT';
                else if ($type == 3)
                    $param['printJobFileType'] = 'OTHERS';
                else
                    $param['printJobFileType'] = 'BILL';
                $this->PrintingApiGeneral->addPrintJob($param, $reply);
            }

            $resultFile['url'] = Router::url($configUrl . $outputFileName . $printFileExt, array('full' => true, 'escape' => true));

            if ($type == 2 && $supportTaiWanGUI && $taiWanGuiGenerateBy == 's' && $taiWanGuiMode == 'r' && $taiwanGuiPfmtId == $pfmtId) {
                $resultFile['page'] = $totalPages;
                $resultFile['taiwanGuiNeedExtraTrans'] = true;
            }

            // If is send receipt by email, record the params
            if (isset($printInfo['email']['action'])) {
                $emailPdfReceiptFileName = $outputFileName . $printFileExt;
                $this->__constructEmailPrintingParams($printInfo['email'], $reprintReceipt, $emailPdfReceiptFileName, $shop, $outlet, $station, $check, $businessDay, isset($checkMember) ? $checkMember : array(), $checkExtraInfos, $resultFile);
            }

            // save audit log only when the receipt is added to print job
            if ($isAddToPrintJob) {
                App::import('Component', 'Pos.PosApiGeneral');
                $posApiGeneralComponent = new PosApiGeneralComponent(new ComponentCollection());
                $posApiGeneralComponent->startup($this->controller);
                $params2 = array(
                    'outletId' => $check['PosCheck']['chks_olet_id'],
                    'stationId' => intval($check['PosCheck']['chks_print_stat_id']),
                    'section' => 'system',
                    'variable' => 'audit_log_level'
                );
                $posConfig = $posApiGeneralComponent->getConfigBySectionVariable($params2);
                if ($posConfig != null && $posConfig['PosConfig']['scfg_value'] == 1 && array_key_exists("audit_log", $this->controller->plugins)) {
                    App::import('Component', 'AuditLog.AuditLogApiGeneral');
                    if (class_exists('AuditLogApiGeneralComponent')) {
                        $auditLogApiGeneralComponent = new AuditLogApiGeneralComponent(new ComponentCollection());
                        $auditLogApiGeneralComponent->startup($this->controller);

                        if (method_exists($auditLogApiGeneralComponent, 'saveAuditLogs')) {
                            $auditLogArray = array();

                            App::import('Model', 'AuditLog.AlogLog');
                            $alogLogModel = new AlogLog();

                            App::import('Model', 'AuditLog.AlogLogInfo');
                            $alogLogInfoModel = new AlogLogInfo();

                            $typeKey = '';
                            if ($type == 1) {
                                $typeKey = 'print_guest_chk';
                                $desc = 'Print';
                            } else if ($reprintReceipt == 1) {
                                $typeKey = 'reprint_guest_chk';
                                $desc = 'Reprint';
                            }

                            if (!empty($typeKey)) {
                                $auditLogInfoArray = array(
                                    'curr_chk' => array(
                                        'value' => $check['PosCheck']['chks_check_prefix_num'],
                                        'record_id' => $checkId
                                    ),
                                    'prt_job' => array(
                                        'value' => $resultFile['url'],
                                        'record_id' => $reply[0] // pjob_id
                                    )
                                );
                                $auditLogInfos = $alogLogInfoModel->constructAlogInfoArray($auditLogInfoArray);
                                $desc .= ' check at station: (' . $station['PosStation']['stat_name_l1'] . ') Print total: ($' . $check['PosCheck']['chks_check_total'] . ') [' . $check['PosCheck']['chks_print_count'] . ']';
                                $auditLog = $alogLogModel->constructAlogLog("pos", $typeKey, $check['PosCheck']['chks_shop_id'], $check['PosCheck']['chks_olet_id'], $check['PosCheck']['chks_print_user_id'], $check['PosCheck']['chks_bday_id'], $desc, $auditLogInfos, $shop['OutShop']['shop_timezone'], $shop['OutShop']['shop_timezone_name']);

                                $params2 = array(
                                    'AuditLogs' => array(0 => $auditLog)
                                );
                                $reply2 = array();
                                $auditLogApiGeneralComponent->saveAuditLogs($params2, $reply2);
                            }
                        }
                    }
                }
            }
        } else {
            /////////////////////////////////////////////////////////////////////////
            //	Render the HTML file
            $outputFile = $configPath . $outputFileName . ".html";
            $outputView = new View($this->controller, false);
            $outputView->set(compact('tpls', 'vars', 'printCtrls'));
            $outputView->viewPath = '';

            $viewFile = $shareDataPath . 'print_format_html_' . $pfmtId . '.ctp';
            $PosPrintFormatModel->checkPrintFormatHtmlViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $viewFile, $this->controller->languages);
            $viewContent = @$outputView->render('print_format_html_' . $pfmtId);
            file_put_contents($outputFile, $viewContent);

            //	Render the TXT file
            $outputPlainTextView = new View($this->controller, false);
            $outputPlainTextView->set(compact('tpls', 'vars', 'printCtrls'));
            $outputPlainTextView->viewPath = '';

            $viewPlainTextFile = 'print_format_txt_' . $pfmtId;
            $PosPrintFormatModel->checkPrintFormatPlainTextViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewPlainTextFile, $this->controller->languages);
            $viewPlainTextContent = @$outputPlainTextView->render($viewPlainTextFile, '');

            $resultFile['url'] = Router::url($configUrl . $outputFileName . '.html', array('full' => true, 'escape' => true));
            $resultFile['viewContent'] = $viewPlainTextContent;
            $resultFile['path'] = $outputFile;

            if ($preview == 1 && $type == 2)
                $this->__writePiiLog('pos', 'r', 'checks', array($checkId), 'Print past date receipt', null, true);
        }

        return '';
    }

    /**
     * The generate output of the printing slip
     * @param integer $checkId Check ID
     * @      string  $prtFileNamePrefix    Print file name prefix
     * @      integer $type                    1: guest check, 2:receipt, 3:serving list
     */
    public function generateCheckSlip($prtqId = null, $pfmtId = null, $checkInfo, $type = 1, $posLangIndex, $preview = 0,
                                      $reprintReceipt = 0, $checkVoided = false, $checkVoidedReasonId = 0, $paymentInterfaceArray = array(), &$resultFile,
                                      $renderFormatType = '', $sExportPath = '')
    {
        Configure::write('debug', 0);
        $this->controller->layout = 'print_slip';

        //	Check if printing plugin exists
        if (!array_key_exists("printing", $this->controller->plugins))
            return 'load_model_error';

        //	Load required model
        $modelArray = array('Pos.PosStation', 'Pos.PosConfig', 'Pos.PosCheck', 'Pos.PosBusinessDay', 'Pos.PosBusinessPeriod', 'Pos.PosPrintFormat', 'Pos.PosOverrideCondition', 'Pos.PosVoidReason',
            'Pos.PosCheckExtraInfo', 'Pos.PosCheckGratuity', 'Pos.PosTaxScType', 'Pos.PosTaiwanGuiTran', 'Pos.PosPaymentMethod', 'Pos.PosRunningNumber', 'Pos.PosDiscountType', 'Outlet.OutShop', 'Outlet.OutMediaObject', 'Outlet.OutFloorPlan', 'Outlet.OutFloorPlanTable',
            'Outlet.OutCalendar', 'User.UserUser', 'Menu.MenuItemCategory', 'Menu.MenuItemDept', 'Menu.MenuItemCourse', 'Media.MedMedia', 'Pos.PosPaymentGatewayTransaction');
        if ($this->controller->importModels($modelArray) !== true)
            return 'load_model_error';

        $MemMemberModel = null;
        if ($this->controller->importModel('Member.MemMember'))
            $MemMemberModel = $this->controller->MemMember;

        $ResvResvModel = null;
        if ($this->controller->importModel('Reservation.ResvResv'))
            $ResvResvModel = $this->controller->ResvResv;

        $PosStationModel = $this->controller->PosStation;
        $PosConfigModel = $this->controller->PosConfig;
        $PosCheckModel = $this->controller->PosCheck;
        $PosCheckExtraInfoModel = $this->controller->PosCheckExtraInfo;
        $PosCheckGratuityModel = $this->controller->PosCheckGratuity;
        $PosBusinessDayModel = $this->controller->PosBusinessDay;
        $PosBusinessPeriodModel = $this->controller->PosBusinessPeriod;
        $PosPrintFormatModel = $this->controller->PosPrintFormat;
        $PosOverrideConditionModel = $this->controller->PosOverrideCondition;
        $PosTaxScTypeModel = $this->controller->PosTaxScType;
        $PosDiscountTypeModel = $this->controller->PosDiscountType;
        $PosVoidReasonModel = $this->controller->PosVoidReason;
        $PosPaymentMethodModel = $this->controller->PosPaymentMethod;
        $PosRunningNumberModel = $this->controller->PosRunningNumber;
        $OutShopModel = $this->controller->OutShop;
        $OutMediaObjectModel = $this->controller->OutMediaObject;
        $OutFloorPlanModel = $this->controller->OutFloorPlan;
        $OutFloorPlanTableModel = $this->controller->OutFloorPlanTable;
        $UserUserModel = $this->controller->UserUser;
        $MenuItemCategoryModel = $this->controller->MenuItemCategory;
        $MenuItemDeptModel = $this->controller->MenuItemDept;
        $MenuItemCourseModel = $this->controller->MenuItemCourse;
        $MedMediaModel = $this->controller->MedMedia;
        $PosPaymentGatewayTransactionModel = $this->controller->PosPaymentGatewayTransaction;

        // Static variable
        $langCount = 5;
        $scCount = 5;
        $taxCount = 25;
        $checkInfoCount = 5;

        // Prepare the useful cached list
        $userList = array();
        if (isset($checkInfo['currentUser']))
            $userList[$checkInfo['currentUser']['user_id']] = $checkInfo['currentUser'];
        $itemCategoryList = array();
        $itemDepartmentList = array();
        $itemCourseList = array();

        //	Add this data path in View folder and set The viewPath be empty (default is the controller name)
        $shareDataPath = $this->controller->Common->getDataPath(array('pos_print_formats'));
        App::build(array('View' => array($shareDataPath)));

        $configPath = $this->controller->Common->getDataPath(array('pos_print_jobs'), true);
        $configUrl = $this->controller->Common->getDataUrl('pos_print_jobs/');
        if (empty($configPath) || empty($configUrl))
            return 'missing_config_path';

        //get print format information
        if ($pfmtId <= 0)
            return 'missing_format';
        $printFormat = $PosPrintFormatModel->findActiveById($pfmtId);
        if (empty($printFormat))
            return 'missing_format';
        $prtFmtDefaultLang = 1;
        if ($printFormat['PosPrintFormat']['pfmt_lang'] != 0)
            $prtFmtDefaultLang = $printFormat['PosPrintFormat']['pfmt_lang'];
        else if ($posLangIndex != 0)
            $prtFmtDefaultLang = $posLangIndex;
        $langIndexArray = array(1 => $prtFmtDefaultLang, 2 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5);

        //swipe breakdown value
        if (isset($checkInfo['printInfo']['isBreakdownFromInclusiveNoBreakdown']) && $checkInfo['printInfo']['isBreakdownFromInclusiveNoBreakdown'] == 1) {
            $tmpCheckExtraInfo = (isset($checkInfo['checkExtraInfos'])) ? $checkInfo['checkExtraInfos'] : array();
            $this->__swipeBreakdownValue($checkInfo, $tmpCheckExtraInfo, $checkInfo['items']);
        }

        //get check information
        if (isset($checkInfo['posCheck'])) {
            $check['PosCheck'] = $checkInfo['posCheck'];
            if (isset($checkInfo['checkDiscounts']))
                $check['checkDiscounts'] = $checkInfo['checkDiscounts'];

            $ret1 = $this->__checkStringExist($check['PosCheck'], "chks_check_prefix_num");
            $ret2 = $this->__checkNumericExist($check['PosCheck'], "chks_id");
            if (empty($ret1) && $ret2 > 0) {
                $checkNumber = $PosCheckModel->find('first', array(
                        'fields' => array('PosCheck.chks_check_prefix_num'),
                        'conditions' => array('chks_id' => $check['PosCheck']['chks_id']),
                        'recursive' => -1
                    )
                );
                if (!empty($checkNumber))
                    $check['PosCheck']['chks_check_prefix_num'] = $checkNumber['PosCheck']['chks_check_prefix_num'];
            }
        } else
            $check = array();
        if (empty($check))
            return 'missing_check';

        //get check extra infos list(exclude the item related record)
        if (isset($checkInfo['checkExtraInfos'])) {
            for ($i = 0; $i < count($checkInfo['checkExtraInfos']); $i++) {
                //foreach($checkInfo['checkExtraInfos'] as $checkExtraInfo) {
                if ($this->__checkStringExist($checkInfo['checkExtraInfos'][$i], 'ckei_section') && $checkInfo['checkExtraInfos'][$i]['ckei_section'] == 'advance_order'
                    && $this->__checkStringExist($checkInfo['checkExtraInfos'][$i], 'ckei_variable') && $checkInfo['checkExtraInfos'][$i]['ckei_variable'] == 'reference'
                    && empty($checkInfo['checkExtraInfos'][$i]['ckei_value'])) {
                    $checkInfo['checkExtraInfos'][$i]['ckei_value'] = str_replace("-", "", $checkInfo['posBusinessDay']['bday_date']) . $checkNumber['PosCheck']['chks_check_prefix_num'];
                }
            }
        }
        $checkExtraInfos = (isset($checkInfo['checkExtraInfos'])) ? $checkInfo['checkExtraInfos'] : array();

        //sorting the items according to setup
        if (!isset($checkInfo['printInfo']['continuousPrint'])) {
            if ($checkVoided == false && (!isset($checkInfo['items']) || count($checkInfo['items']) == 0))
                return 'missing_items';
        }
        $bPutItemInMainList = false;

        if ($printFormat['PosPrintFormat']['pfmt_sort_item_by2'] == '' || $printFormat['PosPrintFormat']['pfmt_sort_item_by2'] == $printFormat['PosPrintFormat']['pfmt_sort_item_by1'])
            $sortedCheckItem = $this->__sortingItemForReceipt($printFormat['PosPrintFormat']['pfmt_sort_item_by1'], $checkInfo['items']);
        else {
            $preSortedCheckItem = $this->__sortingItemForReceipt($printFormat['PosPrintFormat']['pfmt_sort_item_by2'], $checkInfo['items']);
            $sortedCheckItem = $this->__sortingItemForReceipt($printFormat['PosPrintFormat']['pfmt_sort_item_by1'], $preSortedCheckItem);
        }
        $checkItems = $sortedCheckItem;

        //get user info for open check, print, close and owner check user
        $check['PosCheck']['chks_open_user_id'] = $this->__checkNumericExist($check['PosCheck'], "chks_open_user_id");
        if (!array_key_exists($check['PosCheck']['chks_open_user_id'], $userList)) {
            $checkOpenUser = $UserUserModel->findActiveById($check['PosCheck']['chks_open_user_id']);
            if (!empty($checkOpenUser))
                $userList[$checkOpenUser['UserUser']['user_id']] = $checkOpenUser['UserUser'];
        }
        $checkOpenUser['UserUser'] = $userList[$check['PosCheck']['chks_open_user_id']];

        $check['PosCheck']['chks_owner_user_id'] = $this->__checkNumericExist($check['PosCheck'], "chks_owner_user_id");
        if (!array_key_exists($check['PosCheck']['chks_owner_user_id'], $userList)) {
            $checkOwnerUser = $UserUserModel->findActiveById($check['PosCheck']['chks_owner_user_id']);
            if (!empty($checkOwnerUser))
                $userList[$checkOwnerUser['UserUser']['user_id']] = $checkOwnerUser['UserUser'];
        }
        $checkOwnerUser['UserUser'] = $userList[$check['PosCheck']['chks_open_user_id']];

        $check['PosCheck']['chks_print_user_id'] = $this->__checkNumericExist($check['PosCheck'], "chks_print_user_id");
        if (!array_key_exists($check['PosCheck']['chks_print_user_id'], $userList)) {
            $printUser = $UserUserModel->findActiveById($check['PosCheck']['chks_print_user_id']);
            if (!empty($printUser))
                $userList[$check['PosCheck']['chks_print_user_id']] = $printUser['UserUser'];
        }
        $printUser = ($check['PosCheck']['chks_print_user_id'] > 0) ? $userList[$check['PosCheck']['chks_print_user_id']] : array();

        $checkCloseUser = null;
        if ($type == 2) {
            $check['PosCheck']['chks_close_user_id'] = $this->__checkNumericExist($check['PosCheck'], "chks_close_user_id");
            if ($check['PosCheck']['chks_close_user_id'] > 0) {
                if (!array_key_exists($check['PosCheck']['chks_close_user_id'], $userList)) {
                    $checkCloseUser = $UserUserModel->findActiveById($check['PosCheck']['chks_close_user_id']);
                    if (!empty($checkCloseUser))
                        $userList[$checkCloseUser['UserUser']['user_id']] = $checkCloseUser['UserUser'];
                }

                $checkCloseUser = $userList[$check['PosCheck']['chks_close_user_id']];
            }
        }

        //get station info
        $station['PosStation'] = $checkInfo['posStation'];

        //get outlet info
        $outlet['OutOutlet'] = $checkInfo['outlet'];

        //get outlet logo
        $outletLogo = "";
        if (!empty($outlet))
            $outletLogoMedia = $OutMediaObjectModel->findMediasByObject($outlet['OutOutlet']['olet_id'], 'outlet', 'l');
        if (!empty($outletLogoMedia) && count($outletLogoMedia) > 0) {
            $medConfigs = array(
                'med_path' => $this->controller->Common->getDataPath(array('media_files'), true),
                'med_url' => $this->controller->Common->getDataUrl('media_files/'),
            );

            $media = $MedMediaModel->findActiveById($outletLogoMedia[0]['OutMediaObject']['omed_medi_id']);
            if (!empty($media))
                $outletLogo = Router::url($medConfigs['med_url'] . $media['MedMedia']['medi_filename'], array('full' => true, 'escape' => true));
            else
                $outletLogo = "";
        }

        //get shop info
        $shop['OutShop'] = $checkInfo['shop'];
        if (isset($checkInfo['paymentGatewayTransaction']) && !empty($checkInfo['paymentGatewayTransaction']))
            $check['PosPaymentGatewayTransaction'] = json_decode($checkInfo['paymentGatewayTransaction'], true);

        //check whether support TaiWan GUI
        $supportTaiWanGUI = false;
        $taiWanGuiGenerateBy = "";
        $taiWanGuiMode = "";
        $taiwanGuiPfmtId = 0;
        if (isset($station['PosStation']['stat_params']) && !empty($station['PosStation']['stat_params'])) {
            $stationParams = json_decode($station['PosStation']['stat_params'], true);
            if (isset($stationParams['tgui'])) {
                $supportTaiWanGUI = true;
                if (isset($stationParams['tgui']['generate_by']))
                    $taiWanGuiGenerateBy = $stationParams['tgui']['generate_by'];
                if (isset($stationParams['tgui']['mode']))
                    $taiWanGuiMode = $stationParams['tgui']['mode'];
                if (isset($stationParams['tgui']['pfmt_id']))
                    $taiwanGuiPfmtId = $stationParams['tgui']['pfmt_id'];
            }
        }

        //get taxes and service charges type
        $scTypes = $PosTaxScTypeModel->findAllActiveSC();
        $taxTypes = $PosTaxScTypeModel->findAllActiveTaxes();

        //get discount type
        $discountTypes = array();

        //get business day and period information
        $businessDay['PosBusinessDay'] = $checkInfo['posBusinessDay'];
        if (empty($businessDay))
            return 'missing_business_day';
        $shop['OutShop']['shop_timezone'] = $this->__checkNumericExist($shop['OutShop'], "shop_timezone");
        $shop['OutShop']['shop_timezone_name'] = $this->__checkStringExist($shop['OutShop'], "shop_timezone_name");
        if (!empty($shop))
            $timeNow = date('H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));
        else
            $timeNow = date('H:i:s');
        $businessPeriod['PosBusinessPeriod'] = $checkInfo['posBusinessPeriod'];

        //get print queue override condition
        $printQueueOverrideConditions = $PosOverrideConditionModel->findAllActivePrtqConditionsByOutletAndFromPrtqId($outlet['OutOutlet']['olet_id'], $prtqId);

        //check isHoliday, isDayBeforeHoliday, isSpecialDay, isDayBeforeSpecialDay
        $isHoliday = false;
        $isSpecialDay = false;
        $isDayBeforeHoliday = false;
        $isDayBeforeSpecialDay = false;
        $weekday = date("w", mktime(0, 0, 0, substr($businessDay['PosBusinessDay']['bday_date'], 5, 2), substr($businessDay['PosBusinessDay']['bday_date'], 8, 2), substr($businessDay['PosBusinessDay']['bday_date'], 0, 4)));
        $this->__checkCalendarHolidaySpecialDay($businessDay['PosBusinessDay']['bday_date'], $shop['OutShop']['shop_id'], $outlet['OutOutlet']['olet_id'], $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay);

        //from the check discount value base on item
        $checkDiscs = array();
        $checkDiscsValuePerItem = array();
        if ($this->__checkNumericExist($check['PosCheck'], "chks_pre_disc") != 0 || $this->__checkNumericExist($check['PosCheck'], "chks_mid_disc") != 0 || $this->__checkNumericExist($check['PosCheck'], "chks_post_disc") != 0)
            $checkDiscs = isset($checkInfo['checkDiscounts']) ? $checkInfo['checkDiscounts'] : array();

        //pre-handling for Malaysia tax analysis
        $this->__calculateMalaysiaTaxAnaylsis($check, $checkItems, $businessDay, $taxTypes);

        //construct the var array for render view
        $scTotal = 0;
        $taxTotal = 0;
        $vars = array();
        $DiscTotalByDiscType = array();
        $departmentTotals = array();
        $Departments = array();
        $cateogriesTotals = array();
        $Categories = array();

        $wohAwardSettingLists = array();
        $isWohModelExist = false;

        //Woh model existence checking
        if ($this->controller->importModel('Woh.WohAwardSetting')) {
            $isWohModelExist = true;
            // get Woh award list setup
            App::import('Component', 'Woh.WohApiGeneral');
            $wohApiGeneralComponent = new WohApiGeneralComponent(new ComponentCollection());
            $wohApiGeneralComponent->startup($this->controller);

            $shopId = $checkInfo['posCheck']['chks_shop_id'];
            $outletId = $checkInfo['posCheck']['chks_olet_id'];
            $info = array('shopId' => $shopId, 'outletId' => $outletId);
            $wohApiGeneralComponent->getAwardSettingListsByShopOutletId($info, $wohAwardSettingLists);
        }

        $departmentTotals[0] = 0;
        $depts = $MenuItemDeptModel->createTree();

        if (!empty($depts)) {
            foreach ($depts as $dept) {
                $Department = array(
                    'DepartmentId' => $dept['MenuItemDept']['idep_id'],
                    'DepartmentName' => $dept['MenuItemDept']['idep_name_l' . $prtFmtDefaultLang],
                    'DepartmentNameL1' => $dept['MenuItemDept']['idep_name_l1'],
                    'DepartmentNameL2' => $dept['MenuItemDept']['idep_name_l2'],
                    'DepartmentNameL3' => $dept['MenuItemDept']['idep_name_l3'],
                    'DepartmentNameL4' => $dept['MenuItemDept']['idep_name_l4'],
                    'DepartmentNameL5' => $dept['MenuItemDept']['idep_name_l5'],
                    'DepartmentTotal' => 0
                );
                $departmentTotals[$dept['MenuItemDept']['idep_id']] = 0;

                // Department Eligibile printing variable
                if ($isWohModelExist && !empty($wohAwardSettingLists)) {
                    // Hyatt Point Redemption handling
                    if (in_array($dept['MenuItemDept']['idep_id'], $wohAwardSettingLists['wohEligibleAwdItemDepartmentIds']))
                        $Department['DepartmentEligibleForPointRedemption'] = true;
                    else
                        $Department['DepartmentEligibleForPointRedemption'] = false;

                    // Hyatt Earn Point handling
                    if (in_array($dept['MenuItemDept']['idep_id'], $wohAwardSettingLists['wohEligibleEarningItemDepartmentIds']))
                        $Department['DepartmentEligibleForEarnPoint'] = true;
                    else
                        $Department['DepartmentEligibleForEarnPoint'] = false;
                }
                //push the array to $Departments
                $Departments[] = $Department;
            }
        }

        //initialize the Category loop
        $Categories[] = array(
            'CategoryId' => 0,
            'CategoryName' => '',
            'CategoryNameL1' => '',
            'CategoryNameL2' => '',
            'CategoryNameL3' => '',
            'CategoryNameL4' => '',
            'CategoryNameL5' => '',
            'CategoryTotal' => 0
        );
        $categoryTotals[0] = 0;
        $itemCategories = $MenuItemCategoryModel->createTree();
        if (!empty($itemCategories)) {
            foreach ($itemCategories as $category) {
                $Categories[] = array(
                    'CategoryId' => $category['MenuItemCategory']['icat_id'],
                    'CategoryName' => $category['MenuItemCategory']['icat_name_l' . $prtFmtDefaultLang],
                    'CategoryNameL1' => $category['MenuItemCategory']['icat_name_l1'],
                    'CategoryNameL2' => $category['MenuItemCategory']['icat_name_l2'],
                    'CategoryNameL3' => $category['MenuItemCategory']['icat_name_l3'],
                    'CategoryNameL4' => $category['MenuItemCategory']['icat_name_l4'],
                    'CategoryNameL5' => $category['MenuItemCategory']['icat_name_l5'],
                    'CategoryTotal' => 0
                );
                $categoryTotals[$category['MenuItemCategory']['icat_id']] = 0;
            }
        }

        //initialize the variable $vars
        $this->__initializeCheckVars($vars, $type, $supportTaiWanGUI);

        //assign general information to $var
        if (isset($checkInfo['tableInfo'])) {
            $vars['TableNumber'] = (($this->__checkNumericExist($checkInfo['tableInfo'], "tableNumber") == 0) ? "" : $this->__checkNumericExist($checkInfo['tableInfo'], "tableNumber")) . $this->__checkNumericExist($checkInfo['tableInfo'], "tableExtension");
        }
        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($station))
                    $vars['StationName'] = $this->__checkStringExist($station['PosStation'], "stat_name_l" . $langIndex);
                if (!empty($shop))
                    $vars['ShopName'] = $this->__checkStringExist($shop['OutShop'], "shop_name_l" . $langIndex);
                $vars['TableName'] = isset($checkInfo['tableInfo']["tableName" . $langIndex]) ? $checkInfo['tableInfo']["tableName" . $langIndex] : "";
            } else {
                if (!empty($station))
                    $vars['StationNameL' . $langIndex] = $this->__checkStringExist($station['PosStation'], "stat_name_l" . $langIndex);
                if (!empty($shop))
                    $vars['ShopNameL' . $langIndex] = $this->__checkStringExist($shop['OutShop'], "shop_name_l" . $langIndex);
                $vars['TableNameL' . $langIndex] = isset($checkInfo['tableInfo']["tableName" . $langIndex]) ? $checkInfo['tableInfo']["tableName" . $langIndex] : "";
            }
        }

        //assign outlet information to $var
        $vars['OutletLogo'] = $outletLogo;
        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($outlet)) {
                    $vars['OutletName'] = $this->__checkStringExist($outlet['OutOutlet'], "olet_name_l" . $langIndex);
                    $vars['Address'] = $this->__checkStringExist($outlet['OutOutlet'], "olet_addr_l" . $langIndex);
                }
                if (!empty($businessPeriod))
                    $vars['Greeting'] = $this->__checkStringExist($businessPeriod['PosBusinessPeriod'], "bper_greeting_l" . $langIndex);
            } else {
                if (!empty($outlet)) {
                    $vars['OutletNameL' . $langIndex] = $this->__checkStringExist($outlet['OutOutlet'], "olet_name_l" . $langIndex);
                    $vars['AddressL' . $langIndex] = $this->__checkStringExist($outlet['OutOutlet'], "olet_addr_l" . $langIndex);
                }
                if (!empty($businessPeriod))
                    $vars['GreetingL' . $langIndex] = $this->__checkStringExist($businessPeriod['PosBusinessPeriod'], "bper_greeting_l" . $langIndex);
            }
        }
        if (!empty($outlet)) {
            $vars['OutletCode'] = $this->__checkStringExist($outlet['OutOutlet'], "olet_code");
            $vars['OutletCurrencyCode'] = $this->__checkStringExist($outlet['OutOutlet'], "olet_currency_code");
            $vars['Phone'] = $this->__checkStringExist($outlet['OutOutlet'], "olet_phone");
            $vars['Fax'] = $this->__checkStringExist($outlet['OutOutlet'], "olet_fax");
            $vars['DollarSign'] = $this->__checkStringExist($outlet['OutOutlet'], "olet_currency_sign");
        }

        // assign check meal period name to $var
        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($businessPeriod)) {
                    $vars['CheckMealPeriodName'] = $businessPeriod['PosBusinessPeriod']['bper_name_l' . $langIndex];
                    $vars['CheckMealShortPeriodName'] = $this->__checkStringExist($businessPeriod['PosBusinessPeriod'], "bper_short_name_l" . $langIndex);
                }
            } else {
                if (!empty($businessPeriod)) {
                    $vars['CheckMealPeriodNameL' . $langIndex] = $businessPeriod['PosBusinessPeriod']['bper_name_l' . $langIndex];
                    $vars['CheckMealShortPeriodNameL'] = $this->__checkStringExist($businessPeriod['PosBusinessPeriod'], "bper_short_name_l" . $langIndex);
                }
            }
        }

        //assign tax and sc information to $var
        if (!empty($scTypes)) {
            foreach ($scTypes as $scType) {
                if ($scType['PosTaxScType']['txsc_number'] >= 1 || $scType['PosTaxScType']['txsc_number'] <= $scCount) {
                    if ($prtFmtDefaultLang > 0) {
                        $vars['SCName' . $scType['PosTaxScType']['txsc_number']] = $this->__checkStringExist($scType['PosTaxScType'], 'txsc_name_l' . $prtFmtDefaultLang);
                        $vars['SCShortName' . $scType['PosTaxScType']['txsc_number']] = $this->__checkStringExist($scType['PosTaxScType'], 'txsc_short_name_l' . $prtFmtDefaultLang);
                    }
                    for ($langIndex = 1; $langIndex <= 5; $langIndex++) {
                        $vars['SCName' . $scType['PosTaxScType']['txsc_number'] . 'L' . $langIndex] = $this->__checkStringExist($scType['PosTaxScType'], 'txsc_name_l' . $langIndex);
                        $vars['SCShortName' . $scType['PosTaxScType']['txsc_number'] . 'L' . $langIndex] = $this->__checkStringExist($scType['PosTaxScType'], 'txsc_short_name_l' . $langIndex);
                    }
                    // Service Charge Eligibile printing variable
                    if ($isWohModelExist && !empty($wohAwardSettingLists)) {
                        // Point redemption handling
                        if (in_array($scType['PosTaxScType']['txsc_id'], $wohAwardSettingLists['wohEligibleAwdServiceChargeIds']))
                            $vars['SCEligibleForPointRedemption' . $scType['PosTaxScType']['txsc_number']] = true;
                        else
                            $vars['SCEligibleForPointRedemption' . $scType['PosTaxScType']['txsc_number']] = false;
                        // Earn point handling
                        if (in_array($scType['PosTaxScType']['txsc_id'], $wohAwardSettingLists['wohEligibleEarningServiceChargeIds']))
                            $vars['SCEligibleForEarnPoint' . $scType['PosTaxScType']['txsc_number']] = true;
                        else
                            $vars['SCEligibleForEarnPoint' . $scType['PosTaxScType']['txsc_number']] = false;
                    }
                }
            }
        }

        if (!empty($taxTypes)) {
            foreach ($taxTypes as $taxType) {
                if ($taxType['PosTaxScType']['txsc_number'] >= 1 || $taxType['PosTaxScType']['txsc_number'] <= $taxCount) {
                    if ($prtFmtDefaultLang > 0) {
                        $vars['TaxName' . $taxType['PosTaxScType']['txsc_number']] = $this->__checkStringExist($taxType['PosTaxScType'], 'txsc_name_l' . $prtFmtDefaultLang);
                        $vars['TaxShortName' . $taxType['PosTaxScType']['txsc_number']] = $this->__checkStringExist($taxType['PosTaxScType'], 'txsc_short_name_l' . $prtFmtDefaultLang);
                    }
                    for ($langIndex = 1; $langIndex <= 5; $langIndex++) {
                        $vars['TaxName' . $taxType['PosTaxScType']['txsc_number'] . 'L' . $langIndex] = $this->__checkStringExist($taxType['PosTaxScType'], 'txsc_name_l' . $langIndex);
                        $vars['TaxShortName' . $taxType['PosTaxScType']['txsc_number'] . 'L' . $langIndex] = $this->__checkStringExist($taxType['PosTaxScType'], 'txsc_short_name_l' . $langIndex);
                    }
                    $vars['TaxRate' . $taxType['PosTaxScType']['txsc_number']] = $taxType['PosTaxScType']['txsc_rate'] * 100; // calculate back in %
                }
            }
        }

        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($checkOpenUser)) {
                    $vars['CheckOpenEmployeeFirstName'] = $this->__checkStringExist($checkOpenUser['UserUser'], "user_first_name_l" . $langIndex);
                    $vars['CheckOpenEmployeeLastName'] = $this->__checkStringExist($checkOpenUser['UserUser'], "user_last_name_l" . $langIndex);
                    $vars['CheckOpenEmployee'] = $vars['CheckOpenEmployeeLastName'] . ' ' . $vars['CheckOpenEmployeeFirstName'];
                }
                if (!empty($checkOwnerUser)) {
                    $vars['CheckOwnerEmployeeFirstName'] = $this->__checkStringExist($checkOwnerUser['UserUser'], "user_first_name_l" . $langIndex);
                    $vars['CheckOwnerEmployeeLastName'] = $this->__checkStringExist($checkOwnerUser['UserUser'], "user_last_name_l" . $langIndex);
                    $vars['CheckOwnerEmployee'] = $vars['CheckOwnerEmployeeLastName'] . ' ' . $vars['CheckOwnerEmployeeFirstName'];
                }
            } else {
                if (!empty($checkOpenUser)) {
                    $vars['CheckOpenEmployeeFirstNameL' . $langIndex] = $this->__checkStringExist($checkOpenUser['UserUser'], "user_first_name_l" . $langIndex);
                    $vars['CheckOpenEmployeeLastNameL' . $langIndex] = $this->__checkStringExist($checkOpenUser['UserUser'], "user_last_name_l" . $langIndex);
                    $vars['CheckOpenEmployeeL' . $langIndex] = $vars['CheckOpenEmployeeLastNameL' . $langIndex] . ' ' . $vars['CheckOpenEmployeeFirstNameL' . $langIndex];
                }
                if (!empty($checkOwnerUser)) {
                    $vars['CheckOwnerEmployeeFirstNameL' . $langIndex] = $this->__checkStringExist($checkOwnerUser['UserUser'], "user_first_name_l" . $langIndex);
                    $vars['CheckOwnerEmployeeLastNameL' . $langIndex] = $this->__checkStringExist($checkOwnerUser['UserUser'], "user_last_name_l" . $langIndex);
                    $vars['CheckOwnerEmployeeL' . $langIndex] = $vars['CheckOwnerEmployeeLastNameL' . $langIndex] . ' ' . $vars['CheckOwnerEmployeeFirstNameL' . $langIndex];
                }
            }
        }

        $vars['CheckNumber'] = $check['PosCheck']['chks_check_prefix_num'];
        $vars['CheckTitle'] = "";
        $vars['CheckGuests'] = $this->__checkNumericExist($check['PosCheck'], "chks_guests");
        if (isset($check['PosCheck']['chks_open_loctime']) && !empty($check['PosCheck']['chks_open_loctime']))
            $vars['CheckOpenTime'] = date('H:i:s', strtotime($check['PosCheck']['chks_open_loctime']));
        if (!empty($checkOpenUser))
            $vars['CheckOpenEmployeeNum'] = $this->__checkStringExist($checkOpenUser['UserUser'], "user_number");
        if ($check['PosCheck']['chks_memb_id'] > 0 && $MemMemberModel != null) {
            $checkMember = $MemMemberModel->findActiveById($check['PosCheck']['chks_memb_id'], 1);
            if (!empty($checkMember)) {
                $vars['CheckMemberNum'] = $checkMember['MemMember']['memb_number'];
                for ($index = 1; $index <= 2; $index++)
                    $vars['CheckMemberName' . $index] = $checkMember['MemMember']['memb_last_name_l' . $index] . ' ' . $checkMember['MemMember']['memb_first_name_l' . $index];
                $vars['CheckMemberDisplayName'] = $checkMember['MemMember']['memb_display_name'];

                if (isset($checkMember['MemMemberModuleInfo']) && !empty($checkMember['MemMemberModuleInfo'])) {
                    foreach ($checkMember['MemMemberModuleInfo'] as $checkMemberModuleInfo) {
                        if (strcmp($checkMemberModuleInfo['minf_module_alias'], "pos") == 0 && strcmp($checkMemberModuleInfo['minf_variable'], "life_time_spending") == 0) {
                            $vars['CheckMemberSpending'] = $checkMemberModuleInfo['minf_value'];
                            break;
                        }
                    }
                }
            }
        }
        if (strcmp($check['PosCheck']['chks_ordering_type'], "t") == 0)
            $vars['CheckTakeout'] = 1;
        if (strcmp($check['PosCheck']['chks_non_revenue'], "y") == 0)
            $vars['CheckNonRevenue'] = 1;
        if (strcmp($check['PosCheck']['chks_non_revenue'], "a") == 0)
            $vars['CheckAdvanceOrder'] = 1;
        $vars['CheckOrderMode'] = $check['PosCheck']['chks_ordering_mode'];

        // Construct payment inferface info
        if (!empty($paymentInterfaceArray)) {
            $vars['OgsPayUrl'] = $paymentInterfaceArray['payUrl'];
            $vars['OgsPayMatchNumber'] = $paymentInterfaceArray['matchNumber'];
            if ($type == 2) {
                if (!empty($paymentInterfaceArray['receipt_url'])) {
                    $vars['OgsEInvoiceQRCode'] = $paymentInterfaceArray['receipt_url'];
                    $vars['OgsPayReceiptUrl'] = $paymentInterfaceArray['receipt_url'];
                }
                $vars['OgsEInvoiceQRCodeError'] = (isset($paymentInterfaceArray['receipt_url_error'])) ? $paymentInterfaceArray['receipt_url_error'] : 0;
            }
        }

        // For voided check printing
        if ($checkVoided == true) {
            $vars['CheckVoided'] = 1;
            $voidReasonRecord = $PosVoidReasonModel->findActiveById($checkVoidedReasonId, -1);
            if (!empty($voidReasonRecord)) {
                for ($voidReasonIndex = 1; $voidReasonIndex <= 5; $voidReasonIndex++)
                    $vars['CheckVoidedReasonL' . $voidReasonIndex] = $this->__checkStringExist($voidReasonRecord['PosVoidReason'], 'vdrs_name_l' . $voidReasonIndex);

                foreach ($langIndexArray as $key => $langIndex) {
                    if ($key == 1) {
                        if (!empty($voidReasonRecord))
                            $vars['CheckVoidedReason'] = $this->__checkStringExist($voidReasonRecord['PosVoidReason'], 'vdrs_name_l' . $langIndex);
                    } else {
                        if (!empty($voidReasonRecord))
                            $vars['CheckVoidedReasonL' . $langIndex] = $this->__checkStringExist($voidReasonRecord['PosVoidReason'], 'vdrs_name_l' . $langIndex);
                    }
                }
            }
        }

        $DefaultPayments = array();
        $tempDefaultPayments = array();
        $tempDefaultPaymentSortKey = array();
        $defaultPaymentTotal = 0;
        $defaultPaymentCnt = 0;

        if (!empty($checkExtraInfos)) {
            foreach ($checkExtraInfos as $checkExtraInfo) {
                switch ($this->__checkStringExist($checkExtraInfo, 'ckei_variable')) {
                    case 'call_no':
                        $vars['CheckCallNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                        break;
                    case 'check_info':
                        $vars['CheckInfo' . $checkExtraInfo['ckei_index']] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                        break;
                    case 'paytype':
                        $vars['OgsPaytype'] = $checkExtraInfo['ckei_value'];
                        break;
                    default:
                        break;
                }

                // for section membership interface
                if ($this->__checkStringExist($checkExtraInfo, 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo, 'ckei_section') == "membership_interface") {
                    switch ($this->__checkStringExist($checkExtraInfo, 'ckei_variable')) {
                        case 'account_name':
                        case 'member_name':
                            $vars['CheckMembershipIntfAccountName'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'other_name':
                            $vars['CheckMembershipIntfMemberOtherName'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'account_number':
                            $vars['CheckMembershipIntfAccountNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'member_number':
                            $vars['CheckMembershipIntfMemberNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'nric_number':
                            $vars['CheckMembershipIntfNric'] = "****" . substr($this->__checkStringExist($checkExtraInfo, 'ckei_value'), 4, 5);
                            break;
                        case 'points_balance':
                            $vars['CheckMembershipIntfPointBalance'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'points_earn':
                            $vars['CheckMembershipIntfPointEarn'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'amount_for_earn_point':
                            $vars['CheckMembershipIntfAmountForEarnPoint'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'card_no':
                            $vars['CheckMembershipIntfCardNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'available_voucher_list':
                            if ($this->__checkStringExist($checkExtraInfo, 'ckei_value') != "") {
                                $voucherListJson = json_decode($this->__checkStringExist($checkExtraInfo, 'ckei_value'), true);
                                $voucherList = "";
                                if (isset($voucherListJson['voucherList'])) {
                                    $vars['MembershipIntfAvailableVouchers'] = array();
                                    foreach ($voucherListJson['voucherList'] as $voucher)
                                        $vars['MembershipIntfAvailableVouchers'][] = array("MembershipIntfAvailableVoucher" => $voucher['voucherNumber']);
                                }
                            }
                            break;
                        case 'event_order_number_for_add':
                            $vars['CheckMembershipIntfEventOrderNumberForAdd'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'event_order_deposit_for_add':
                            $vars['CheckMembershipIntfEventOrderDepositForAdd'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'event_order_deposit_balance':
                            $vars['CheckMembershipIntfEventOrderDepositBalance'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'event_order_number_for_use':
                            $vars['CheckMembershipIntfEventOrderNumberForUse'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'event_order_deposit_for_use':
                            $vars['CheckMembershipIntfEventOrderDepositForUse'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'card_store_value':
                            $vars['CheckMembershipIntfStoreValueBalance'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'exp_date':
                            $vars['CheckMembershipIntfExpiryDate'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'original_points':
                            $vars['CheckMembershipIntfOriginalPoints'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'posted_points_use':
                            $vars['CheckMembershipIntfPostedPointsUse'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'posted_amount_use':
                            $vars['CheckMembershipIntfPostedAmountUse'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'member_type':
                            $vars['CheckMembershipIntfMemberType'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'english_name':
                            $vars['CheckMembershipIntfMemberSurname'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'local_balance':
                            $vars['CheckMembershipIntfLocalBalance'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'points_available':
                            $vars['CheckMembershipIntfPointsAvailable'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'total_points_balance':
                            $vars['CheckMembershipIntfTotalPointsBalance'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'max_redempt_points':
                            $vars['CheckMembershipIntfMaxRedemptionPoints'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'points_use':
                            $vars['CheckMembershipIntfPointsToUse'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'max_redempt_amount':
                            $vars['CheckMembershipIntfMaxRedemptionAmount'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'amount_use':
                            $vars['CheckMembershipIntfAmountToUse'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'refund_amount':
                            $vars['CheckMembershipIntfRefundAmount'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'refund_points':
                            $vars['CheckMembershipIntfRefundPoints'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'voucher_value':
                            $vars['CheckMembershipIntfVoucherValue'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'no_redemption':
                            $vars['CheckMembershipIntfNoRedemption'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'member_info':
                            if ($checkExtraInfo['ckei_index'] == 1)
                                $vars['CheckMembershipIntfMemberInfo1'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 2)
                                $vars['CheckMembershipIntfMemberInfo2'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 3)
                                $vars['CheckMembershipIntfMemberInfo3'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 4)
                                $vars['CheckMembershipIntfMemberInfo4'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 5)
                                $vars['CheckMembershipIntfMemberInfo5'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 6)
                                $vars['CheckMembershipIntfMemberInfo6'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 7)
                                $vars['CheckMembershipIntfMemberInfo7'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 8)
                                $vars['CheckMembershipIntfMemberInfo8'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'bonus_code':
                            if ($checkExtraInfo['ckei_index'] == 1)
                                $vars['BonusCode1'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 2)
                                $vars['BonusCode2'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 3)
                                $vars['BonusCode3'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 4)
                                $vars['BonusCode4'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 5)
                                $vars['BonusCode5'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 6)
                                $vars['BonusCode6'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 7)
                                $vars['BonusCode7'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 8)
                                $vars['BonusCode8'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 9)
                                $vars['BonusCode9'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            else if ($checkExtraInfo['ckei_index'] == 10)
                                $vars['BonusCode10'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'reference':
                            $vars['CheckMembershipIntfMemberRef'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                //Loyalty interface
                if ($this->__checkStringExist($checkExtraInfo, 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo, 'ckei_section') == "loyalty") {
                    switch ($this->__checkStringExist($checkExtraInfo, 'ckei_variable')) {
                        case 'member_name':
                            $vars['CheckLoyaltyMemberName'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'member_number':
                            $vars['CheckLoyaltyMemberNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'card_no':
                            $vars['CheckLoyaltyCardNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'points_earn':
                            $vars['CheckLoyaltyPointEarn'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'points_balance':
                            $vars['CheckLoyaltyPointBalance'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'expiry_date':
                            $vars['CheckLoyaltyPointExpiryDate'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'point_redeem':
                            $vars['CheckLoyaltyPointRedeem'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                //Gaming interface
                if ($this->__checkStringExist($checkExtraInfo, 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo, 'ckei_section') == "gaming_interface") {
                    switch ($this->__checkStringExist($checkExtraInfo, 'ckei_variable')) {
                        case 'member_name':
                            $vars['CheckGamingIntfMemberName'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            $vars['CheckGamingIntfMemberFirstName'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'member_number':
                            $vars['CheckGamingIntfMemberNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'card_no':
                            $vars['CheckGamingIntfMemberCardNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'input_type':
                            $vars['CheckGamingIntfInputMethod'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'points_balance':
                            $vars['CheckGamingIntfPointBalance'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'points_department':
                            $vars['CheckGamingIntfPointDepartment'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'account_number':
                            $vars['CheckGamingIntfAccountNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'card_type_name':
                            $vars['CheckGamingIntfCardType'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'member_last_name':
                            $vars['CheckGamingIntfMemberLastName'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                // for section PMS interface
                if ($this->__checkStringExist($checkExtraInfo, 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo, 'ckei_section') == "pms") {
                    switch ($this->__checkStringExist($checkExtraInfo, 'ckei_variable')) {
                        case 'room':
                            $vars['CheckPmsIntfRoomNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'guest_name':
                            $vars['CheckPmsIntfRoomGuestName'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                // Add advance order information
                if ($this->__checkStringExist($checkExtraInfo, 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo, 'ckei_section') == "advance_order") {
                    switch ($this->__checkStringExist($checkExtraInfo, 'ckei_variable')) {
                        case 'reference':
                            $vars['CheckReferenceNumber'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'pickup_date':
                            $vars['CheckPickupDate'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'phone':
                            $vars['CheckGuestPhone'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'guest_name':
                            $vars['CheckGuestName'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'fax':
                            $vars['CheckGuestFax'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'note1':
                            $vars['CheckGuestNote' . '1'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'note2':
                            $vars['CheckGuestNote' . '2'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        case 'deposit_amount':
                            $vars['CheckDepositAmount'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                // for section payment interface
                if ($this->__checkStringExist($checkExtraInfo, 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo, 'ckei_section') == "payment_interface") {
                    switch ($this->__checkStringExist($checkExtraInfo, 'ckei_variable')) {
                        case 'qr_code':
                            $vars['QRCode'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                            break;
                        default:
                            break;
                    }
                }

                // Default payment list
                if ($type != 2) {
                    if ($this->__checkStringExist($checkExtraInfo, 'ckei_by') == "check" && $this->__checkStringExist($checkExtraInfo, 'ckei_section') == "default_payment"
                        && strcmp($this->__checkStringExist($checkExtraInfo, 'ckei_value'), "") != 0 && strcmp($this->__checkStringExist($checkExtraInfo, 'ckei_status'), "") == 0) {
                        $defaultPaymentInfo = json_decode($this->__checkStringExist($checkExtraInfo, 'ckei_value'), true);
                        if ($defaultPaymentInfo != NULL && isset($defaultPaymentInfo['paym_id']) && $defaultPaymentInfo['paym_id'] > 0) {
                            $paymentMethod = $PosPaymentMethodModel->findActiveById($defaultPaymentInfo['paym_id'], -1);
                            if (!empty($paymentMethod)) {
                                $defaultPayAmount = isset($defaultPaymentInfo['amount']) ? $defaultPaymentInfo['amount'] : 0;
                                $defaultPaymentCnt++;
                                $defaultPaymentTotal += $defaultPayAmount;
                                $tempDefaultPaymentSortKey[$defaultPaymentCnt] = $this->__checkStringExist($checkExtraInfo, 'ckei_index');
                                $tempDefaultPayments[] = array(
                                    'DefaultPaymentTempIdx' => $defaultPaymentCnt,
                                    'DefaultPaymentName' => $paymentMethod['PosPaymentMethod']['paym_name_l' . $prtFmtDefaultLang],
                                    'DefaultPaymentNameL1' => $paymentMethod['PosPaymentMethod']['paym_name_l1'],
                                    'DefaultPaymentNameL2' => $paymentMethod['PosPaymentMethod']['paym_name_l2'],
                                    'DefaultPaymentNameL3' => $paymentMethod['PosPaymentMethod']['paym_name_l3'],
                                    'DefaultPaymentNameL4' => $paymentMethod['PosPaymentMethod']['paym_name_l4'],
                                    'DefaultPaymentNameL5' => $paymentMethod['PosPaymentMethod']['paym_name_l5'],
                                    'DefaultPaymentAmount' => number_format($defaultPayAmount, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "")
                                );
                            }
                        }
                    }
                }
            }
        }

        //sorting default payment list by key
        if (count($tempDefaultPayments) > 0) {
            asort($tempDefaultPaymentSortKey);
            foreach ($tempDefaultPaymentSortKey as $key => $extraInfoIndex) {
                foreach ($tempDefaultPayments as $tempDefaultPayment) {
                    if ($tempDefaultPayment['DefaultPaymentTempIdx'] == $key) {
                        $DefaultPayments[] = $tempDefaultPayment;
                        break;
                    }
                }
            }
            $vars['CheckDefaultPaymentTotal'] = number_format($defaultPaymentTotal, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        }

        //assign printing information in $var
        foreach ($langIndexArray as $key => $langIndex) {
            if ($key == 1) {
                if (!empty($printUser)) {
                    $vars['PrintEmployeeFirstName'] = $this->__checkStringExist($printUser, "user_first_name_l" . $langIndex);
                    $vars['PrintEmployeeLastName'] = $this->__checkStringExist($printUser, "user_last_name_l" . $langIndex);
                    $vars['PrintEmployee'] = $vars['PrintEmployeeLastName'] . ' ' . $vars['PrintEmployeeFirstName'];
                }
            } else {
                if (!empty($printUser)) {
                    $vars['PrintEmployeeFirstNameL' . $langIndex] = $this->__checkStringExist($printUser, "user_first_name_l" . $langIndex);
                    $vars['PrintEmployeeLastNameL' . $langIndex] = $this->__checkStringExist($printUser, "user_last_name_l" . $langIndex);
                    $vars['PrintEmployeeL' . $langIndex] = $vars['PrintEmployeeLastNameL' . $langIndex] . ' ' . $vars['PrintEmployeeFirstNameL' . $langIndex];
                }
            }
        }

        if ($preview == 1 || empty($check['PosCheck']['chks_print_loctime']))
            $vars['PrintTime'] = (isset($shop['OutShop']['shop_timezone'])) ? date('H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time())) : date('H:i:s');
        else
            $vars['PrintTime'] = (isset($shop['OutShop']['shop_timezone'])) ? date('H:i:s', strtotime($check['PosCheck']['chks_print_loctime'])) : "";
        if (!empty($printUser))
            $vars['PrintEmployeeNum'] = $this->__checkStringExist($printUser, "user_number");
        $vars['PrintCount'] = $this->__checkNumericExist($check['PosCheck'], "chks_print_count");
        $vars['ReceiptPrintCount'] = $this->__checkNumericExist($check['PosCheck'], "chks_receipt_print_count");
        if ($type == 2 && $reprintReceipt == 1)
            $vars['ReprintReceipt'] = 1;

        //assign check Total information in $var
        $vars['CheckTotal'] = number_format($this->__checkNumericExist($check['PosCheck'], "chks_check_total"), $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_check_decimal"), ".", "");
        for ($index = 1; $index <= 5; $index++) {
            $vars['SC' . $index] = number_format($this->__checkNumericExist($check['PosCheck'], "chks_sc" . $index), $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_sc_decimal"), ".", "");
            $scTotal += $this->__checkNumericExist($check['PosCheck'], "chks_sc" . $index);
        }
        $vars['SCTotal'] = number_format($scTotal, $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_sc_decimal"), ".", "");
        $discountForTaxTotal = 0;
        for ($index = 1; $index <= 25; $index++) {
            $vars['Tax' . $index] = number_format($this->__checkNumericExist($check['PosCheck'], "chks_tax" . $index), $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_tax_decimal"), ".", "");
            $discountForTaxTotal += $this->__checkNumericExist($check['PosCheck']['discountOnTax'], $index);
            $vars['DiscountForTax' . $index] = number_format($this->__checkNumericExist($check['PosCheck']['discountOnTax'], $index), $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_tax_decimal"), ".", "");
            $taxTotal += $this->__checkNumericExist($check['PosCheck'], "chks_tax" . $index);
        }
        for ($index = 1; $index <= 4; $index++) {
            $vars['InclusiveTaxRef' . $index] = number_format($this->__checkNumericExist($check['PosCheck'], "chks_incl_tax_ref" . $index), $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_tax_decimal"), ".", "");
        }
        $vars['TaxTotal'] = number_format($taxTotal, $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_tax_decimal"), ".", "");
        $vars['DiscountForTaxTotal'] = number_format($discountForTaxTotal, $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_tax_decimal"), ".", "");

        //handle check tax sc reference records
        $this->__handleOnCheckTaxScRefs($vars, $businessDay['PosBusinessDay'], (isset($checkInfo['checkTaxScRefs'])) ? $checkInfo['checkTaxScRefs'] : array());

        //assign check surcharge total in $var
        $vars['CheckSurchargeTotal'] = number_format($this->__checkNumericExist($check['PosCheck'], "chks_surcharge_total"), $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_pay_decimal"), ".", "");

        //get reservation payment total
        $ret1 = $this->__checkStringExist($check['PosCheck'], "chks_resv_book_date");
        $ret2 = $this->__checkStringExist($check['PosCheck'], "chks_resv_refno_with_prefix");
        if ($ResvResvModel != null && $this->__checkStringExist($check['PosCheck'], "chks_resv_book_date") != null && !empty($ret1) && !empty($ret2)) {
            $reservation = $ResvResvModel->findActiveByOutletDateConfirmNo($outlet['OutOutlet']['olet_id'], $check['PosCheck']['chks_resv_book_date'], $check['PosCheck']['chks_resv_refno_with_prefix']);
            if (!empty($reservation['ResvResv']) && isset($reservation['ResvResv']['resv_payment_total']))
                $vars['ReservationPaymentTotal'] = $reservation['ResvResv']['resv_payment_total'];
        }
        $vars['ReservationPaymentTotal'] = number_format($vars['ReservationPaymentTotal'], $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");

        //generate the value of TaiWan GUI
        if ($supportTaiWanGUI && $type == 2) {
            $businessDayTaxRound = $this->__checkStringExist($businessDay, 'bday_tax_round');
            $businessDayTaxDecimal = $this->__checkNumericExist($businessDay, 'bday_tax_decimal');
            $businessDayItemDecimal = $this->__checkNumericExist($businessDay, 'bday_item_decimal');
            $tempBusinessDay['bday_tax_round'] = $businessDayTaxRound;
            $tempBusinessDay['bday_tax_decimal'] = $businessDayTaxDecimal;
            $tempBusinessDay['bday_item_decimal'] = $businessDayItemDecimal;
            $tempBusinessDay['bday_date'] = $businessDay['PosBusinessDay']['bday_date'];

            $this->__generateTaiWanGuiPrintVariable($vars, $station['PosStation']['stat_id'], $station['PosStation']['stat_params'], $shop['OutShop']['shop_timezone'] . " min", $prtFmtDefaultLang, $taxTypes, $tempBusinessDay, $check['PosCheck'], $checkItems, $checkInfo['taiwanGuiTrans']);
        }

        //Check item handling
        $Items = array();
        $totalItem = 0;
        $totalAmount = 0;
        $totalAmountUseItemOriPrice = 0;
        $checkItemDiscTotal = 0;
        foreach ($checkItems as $checkItem) {
            $checkItem = $checkItem;

            if (!empty($checkItem)) {
                //Check no-print item
                if ($this->__checkStringExist($checkItem, "citm_no_print") == 'y' && $this->__checkNumericExist($checkItem, "citm_round_total") == 0)
                    continue;

                //Get extra info
                $itemSvcCouponItem = "";
                $itemVoucherItem = "";
                $itemReference = "";
                $itemCallNo = "";
                $itemLoyaltyPointBalance = "";
                $itemLoyaltyPointAddValue = "";
                $itemLoyaltyCardNumber = "";
                $isPrinted = false;

                if (isset($checkItem["PosCheckExtraInfo"]) && count($checkItem["PosCheckExtraInfo"])) {
                    for ($extraInfoIndex = 0; $extraInfoIndex < count($checkItem["PosCheckExtraInfo"]); $extraInfoIndex++) {
                        $itemExtraInfo = $checkItem["PosCheckExtraInfo"][$extraInfoIndex];
                        if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") != "") {
                            if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") == "svc_coupon_number")
                                $itemSvcCouponItem = $this->__checkStringExist($itemExtraInfo, "ckei_value");
                            if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") == "item_reference")
                                $itemReference = $this->__checkStringExist($itemExtraInfo, 'ckei_value');
                            else if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") == "call_no")
                                $itemCallNo = $this->__checkStringExist($itemExtraInfo, 'ckei_value');
                            else if ($itemExtraInfo['ckei_by'] == "item" && ($itemExtraInfo['ckei_section'] == "loyalty")) {
                                switch ($itemExtraInfo['ckei_variable']) {
                                    case "points_balance":
                                        $itemLoyaltyPointBalance = $this->__checkStringExist($itemExtraInfo, 'ckei_value');
                                        break;
                                    case "points_earn":
                                        $itemLoyaltyPointAddValue = $this->__checkStringExist($itemExtraInfo, 'ckei_value');
                                        break;
                                    case "card_no":
                                        $itemLoyaltyCardNumber = $this->__checkStringExist($itemExtraInfo, 'ckei_value');
                                        break;
                                    default:
                                        break;
                                }
                            } else if ($itemExtraInfo['ckei_by'] == "item" && $itemExtraInfo['ckei_section'] == "membership_interface") {
                                switch ($itemExtraInfo['ckei_variable']) {
                                    case "voucher_number":
                                        $itemVoucherItem = $this->__checkStringExist($itemExtraInfo, "ckei_value");
                                        break;
                                }
                            } else if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") == "printed_status" && !empty($this->__checkStringExist($itemExtraInfo, "ckei_value"))) {
                                $printStatus = json_decode($this->__checkStringExist($itemExtraInfo, "ckei_value"), true);
                                $voidNeedToPrint = isset($printStatus['void']) && !$printStatus['void'] ? true : false;
                                $needToPrint = isset($printStatus['addUpdate']) && !$printStatus['addUpdate'] ? true : false;
                                if (!empty($this->__checkStringExist($checkItem, "citm_id")))
                                    if (empty($this->__checkStringExist($checkItem, "citm_status")) && !$needToPrint)
                                        $isPrinted = true;
                                    else if ($this->__checkStringExist($checkItem, "citm_status") == "d" && !$voidNeedToPrint)
                                        $isPrinted = true;
                            }
                        }
                    }
                }

                // Get loyalty info form extra info in item level
                $loyaltyItemInfo = array("svcCardNumber" => "", "memberNumber" => "", "svcCardExpiryDate" => "", "svcRemark" => "");
                if (isset($checkItem["PosCheckExtraInfo"]) && count($checkItem["PosCheckExtraInfo"])) {
                    for ($extraInfoIndex = 0; $extraInfoIndex < count($checkItem["PosCheckExtraInfo"]); $extraInfoIndex++) {
                        $itemExtraInfo = $checkItem["PosCheckExtraInfo"][$extraInfoIndex];
                        if ($this->__checkStringExist($itemExtraInfo, "ckei_section") != "") {
                            if ($this->__checkStringExist($itemExtraInfo, "ckei_section") == "loyalty") {
                                if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") != "") {
                                    if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") == "svc_card_number")
                                        $loyaltyItemInfo['svcCardNumber'] = $this->__checkStringExist($itemExtraInfo, "ckei_value");
                                    else if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") == "member_number")
                                        $loyaltyItemInfo['memberNumber'] = $this->__checkStringExist($itemExtraInfo, 'ckei_value');
                                    else if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") == "member_valid_through")
                                        $loyaltyItemInfo['svcCardExpiryDate'] = $this->__checkStringExist($itemExtraInfo, 'ckei_value');
                                    else if ($this->__checkStringExist($itemExtraInfo, "ckei_variable") == "remark")
                                        $loyaltyItemInfo['svcRemark'] = $this->__checkStringExist($itemExtraInfo, 'ckei_value');
                                }
                            }
                        }
                    }
                }
                //Checking for grouping items
                if (!empty($printFormat) && $printFormat['PosPrintFormat']['pfmt_group_item_by'] == 'c') {
                    if (count($Items) > 0) {
                        $itemGrouped = false;

                        if ($this->__checkStringExist($checkItem, "citm_ordering_type") == 't')
                            $itemTakeoutChecking = 1;
                        else
                            $itemTakeoutChecking = 0;
                        $itemGrossPriceChecking = $this->__checkNumericExist($checkItem, "citm_price");

                        $checkItemChecking['Modifiers'] = array(); // $itemPrtModiIdsChecking
                        $modifiersChecking = array();
                        if ($this->__checkNumericExist($checkItem, "citm_modifier_count") > 0) {
                            $modifiersChecking = $checkItem['ModifierList'];
                            if (!empty($modifiersChecking)) {
                                foreach ($modifiersChecking as $modifierChecking) {
                                    $itemGrossPriceChecking += $this->__checkNumericExist($modifierChecking, "citm_price");
                                    if (strcmp($this->__checkStringExist($modifierChecking, "citm_no_print"), '') == 0) {
                                        if (!array_key_exists($modifierChecking['citm_item_id'], $checkItemChecking['Modifiers']))
                                            $checkItemChecking['Modifiers'][$modifierChecking['citm_item_id']] = 1;
                                        else
                                            $checkItemChecking['Modifiers'][$modifierChecking['citm_item_id']] += 1;
                                    }
                                }
                            }
                        }

                        $childItemAppliedDiscTotal = 0;
                        $childItemTotal = 0;
                        $childItemsChecking = array();
                        $checkItemChecking['ChildItems'] = array();
                        $checkItemChecking['ChildDetail'] = array();
                        if ($this->__checkNumericExist($checkItem, "citm_child_count") > 0) {
                            $childItemsChecking = $checkItem['ChildItemList'];
                            if (!empty($childItemsChecking)) {
                                foreach ($childItemsChecking as $childItemChecking) {
                                    //Check no-print item
                                    //if($this->__checkStringExist($childItemChecking, "citm_no_print") == 'y' && $this->__checkNumericExist($childItemChecking, "citm_round_total") == 0)
                                    //	continue;
                                    $childItemTotal += $this->__checkNumericExist($childItemChecking, "citm_round_total");

                                    // count child items
                                    if (!array_key_exists($childItemChecking['citm_item_id'], $checkItemChecking['ChildItems'])) {
                                        $checkItemChecking['ChildItems'][$childItemChecking['citm_item_id']] = 1;

                                        $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Modifiers'] = array();
                                        if (isset($childItemChecking['ModifierList']) && count($childItemChecking['ModifierList']) > 0) {
                                            $childItemModifiersChecking = $childItemChecking['ModifierList'];
                                            foreach ($childItemModifiersChecking as $childItemModifierChecking) {
                                                // skip no print modifier
                                                if (strcmp($childItemModifierChecking['citm_no_print'], 'y') == 0)
                                                    continue;

                                                if (!array_key_exists($childItemModifierChecking['citm_item_id'], $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Modifiers']))
                                                    $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Modifiers'][$childItemModifierChecking['citm_item_id']] = 1;
                                                else
                                                    $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Modifiers'][$childItemModifierChecking['citm_item_id']] += 1;
                                            }
                                        }
                                        $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Discounts'] = array();
                                        if (isset($childItemChecking['PosCheckDiscount']) && count($childItemChecking['PosCheckDiscount']) > 0) {
                                            $childItemDiscountsChecking = $childItemChecking['PosCheckDiscount'];
                                            foreach ($childItemDiscountsChecking as $childItemDiscountChecking) {
                                                if (!array_key_exists($childItemDiscountChecking['cdis_dtyp_id'], $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Discounts']))
                                                    $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Discounts'][$childItemDiscountChecking['cdis_dtyp_id']] = 1;
                                                else
                                                    $checkItemChecking['ChildDetail'][$childItemChecking['citm_item_id']]['Discounts'][$childItemDiscountChecking['cdis_dtyp_id']] += 1;
                                            }
                                        }
                                    } else
                                        $checkItemChecking['ChildItems'][$childItemChecking['citm_item_id']] += 1;
                                }
                            }
                        }
                        $itemAppliedDiscTotal = 0;
                        $checkItemChecking['Discounts'] = array();
                        $itemDiscsChecking = array();
                        if ($this->__checkNumericExist($checkItem, "citm_pre_disc") != 0 || $this->__checkNumericExist($checkItem, "citm_mid_disc") != 0 || $this->__checkNumericExist($checkItem, "citm_post_disc") != 0) {
                            $itemDiscsChecking = $checkItem['PosCheckDiscount'];
                            if (!empty($itemDiscsChecking)) {
                                foreach ($itemDiscsChecking as $discChecking) {
                                    $itemAppliedDiscTotal += $this->__checkNumericExist($discChecking, "cdis_round_total");
                                    if (!array_key_exists($this->__checkNumericExist($discChecking, "cdis_dtyp_id"), $checkItemChecking['Discounts']))
                                        $checkItemChecking['Discounts'][$discChecking['cdis_dtyp_id']] = 1;
                                    else
                                        $checkItemChecking['Discounts'][$discChecking['cdis_dtyp_id']] += 1;
                                }
                            }
                        }

                        $itemsCheckingList = $this->__formItemsCheckingArray($Items);

                        for ($itemIndex = 0; $itemIndex < count($Items); $itemIndex++) {
                            //whether same menu item ID
                            if ($Items[$itemIndex]['ItemId'] != $checkItem['citm_item_id'])
                                continue;

                            //whether same item description
                            if ($Items[$itemIndex]['ItemName'] != $this->__checkStringExist($checkItem, "citm_name_l" . $prtFmtDefaultLang) || $Items[$itemIndex]['ItemNameL1'] != $this->__checkStringExist($checkItem, "citm_name_l1") || $Items[$itemIndex]['ItemNameL2'] != $this->__checkStringExist($checkItem, "citm_name_l2") || $Items[$itemIndex]['ItemNameL3'] != $this->__checkStringExist($checkItem, "citm_name_l3") || $Items[$itemIndex]['ItemNameL4'] != $this->__checkStringExist($checkItem, "citm_name_l4") || $Items[$itemIndex]['ItemNameL5'] != $this->__checkStringExist($checkItem, "citm_name_l5"))
                                continue;

                            //whether same takeout status
                            if ($Items[$itemIndex]['ItemTakeout'] != $itemTakeoutChecking)
                                continue;

                            //whether same gross price
                            if ($Items[$itemIndex]['ItemGrossPrice'] != $itemGrossPriceChecking)
                                continue;

                            //whether same printing modifiers
                            $diffItemPrtModi = array_diff_assoc($itemsCheckingList[$itemIndex]['Modifiers'], $checkItemChecking['Modifiers']);
                            if (!empty($diffItemPrtModi) || count($itemsCheckingList[$itemIndex]['Modifiers']) != count($checkItemChecking['Modifiers']))
                                continue;

                            //whether same printing childitems
                            $diffItemPrtChild = array_diff_assoc($itemsCheckingList[$itemIndex]['ChildItems'], $checkItemChecking['ChildItems']);
                            if (!empty($diffItemPrtChild) || count($itemsCheckingList[$itemIndex]['ChildItems']) != count($checkItemChecking['ChildItems']))
                                continue;

                            $sameChildItemDetail = true;
                            foreach ($checkItemChecking['ChildDetail'] as $id => $childItemDetailChecking) {
                                foreach ($childItemDetailChecking as $detail => $arrayIds) {
                                    if (!empty(array_diff_assoc($itemsCheckingList[$itemIndex]['ChildDetail'][$id][$detail], $arrayIds)) || count($itemsCheckingList[$itemIndex]['ChildDetail'][$id][$detail]) != count($arrayIds)) {
                                        $sameChildItemDetail = false;
                                        break;
                                    }
                                }
                                if (!$sameChildItemDetail)
                                    break;
                            }
                            if (!$sameChildItemDetail)
                                continue;

                            //whether same applied discount
                            $diffItemAppliedDisc = array_diff_assoc($itemsCheckingList[$itemIndex]['Discounts'], $checkItemChecking['Discounts']);
                            if (!empty($diffItemAppliedDisc) || count($itemsCheckingList[$itemIndex]['Discounts']) != count($checkItemChecking['Discounts']))
                                continue;

                            //whether same coupon number
                            if ($Items[$itemIndex]['ItemCouponNumber'] != $itemSvcCouponItem)
                                continue;

                            // whether same item status
                            if ($Items[$itemIndex]['ItemStatus'] != $checkItem['citm_status'])
                                continue;

                            $itemGrouped = true;

                            if ($this->__checkNumericExist($checkItem, "citm_child_count") > 0) {
                                $childItemsChecking = $checkItem['ChildItemList'];
                                if (!empty($childItemsChecking)) {
                                    foreach ($childItemsChecking as $childItemChecking) {
                                        //Check no-print item
                                        //if($this->__checkStringExist($childItemChecking, "citm_no_print") == 'y' && $this->__checkNumericExist($childItemChecking, "citm_round_total") == 0)
                                        //	continue;

                                        for ($index = 0; $index < count($Items); $index++) {
                                            if (count($childItemsChecking) != count($Items[$index]['ChildItems']))
                                                continue;

                                            $reply = array();
                                            $childItemGrouped = $this->groupChildItems($childItemChecking, $Items[$index]['ChildItems'], $checkItemChecking['ChildDetail'], $itemsCheckingList[$index]['ChildDetail'], $prtFmtDefaultLang, $businessDay, $DiscTotalByDiscType, $departmentTotals, $depts, $categoryTotals, $itemCategories, $reply);

                                            if ($childItemGrouped) {
                                                $childItemAppliedDiscTotal += $reply['itemAppliedDiscTotal'];
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            $itemAppliedDiscTotal += $childItemAppliedDiscTotal;

                            //update Items list
                            $Items[$itemIndex]['ItemQuantity'] += $this->__checkNumericExist($checkItem, "citm_qty");
                            $Items[$itemIndex]['ItemPrice'] += ($this->__checkNumericExist($checkItem, "citm_round_total") + $itemAppliedDiscTotal);
                            $Items[$itemIndex]['ItemTotal'] += $this->__checkNumericExist($checkItem, "citm_round_total") + $childItemTotal;
                            $Items[$itemIndex]['ItemDiscountTotal'] += $itemAppliedDiscTotal;
                            for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++) {
                                $itemTax[$taxIndex] = 0;
                                $itemTax[$taxIndex] = ($this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_round_total") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc1") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc2") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc3") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc4") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc5"));
                                $Items[$itemIndex]['ItemTaxTotal'] += $itemTax[$taxIndex];
                                $Items[$itemIndex]['ItemTax' . $taxIndex] += $itemTax[$taxIndex];

                                if ($itemTax[$taxIndex] > 0) {
                                    //discount on check discount
                                    $checkDiscountTotalForItem = 0;
                                    if (isset($checkItem['PosCheckDiscountItem'])) {
                                        foreach ($checkItem['PosCheckDiscountItem'] as $checkDisc)
                                            $checkDiscountTotalForItem += $this->__checkNumericExist($checkDisc, "cdit_round_total");
                                    }

                                    if ($this->__checkStringExist($checkItem, "citm_charge_tax" . $taxIndex) == "c" || $this->__checkStringExist($checkItem, "citm_charge_tax" . $taxIndex) == "i") {
                                        $vars['SCTotalWithTax' . $taxIndex] += ($checkItem['citm_sc1_round'] + $checkItem['citm_sc2_round'] + $checkItem['citm_sc3_round'] + $checkItem['citm_sc4_round'] + $checkItem['citm_sc5_round']);
                                    }
                                }
                            }
                            $Items[$itemIndex]['ItemOriginalPrice'] = number_format($Items[$itemIndex]['ItemOriginalPrice'], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
                            $Items[$itemIndex]['ItemPrice'] = number_format($Items[$itemIndex]['ItemPrice'], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
                            $Items[$itemIndex]['ItemTaxTotal'] = number_format($Items[$itemIndex]['ItemTaxTotal'], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", "");
                            for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++)
                                $Items[$itemIndex]['ItemTax' . $taxIndex] = number_format($Items[$itemIndex]['ItemTax' . $taxIndex], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", "");
                            $Items[$itemIndex]['ItemDiscountTotal'] = number_format($Items[$itemIndex]['ItemDiscountTotal'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");

                            //update discount value
                            foreach ($itemDiscsChecking as $discChecking) {
                                $iDiscTypeId = $this->__checkNumericExist($discChecking, "cdis_dtyp_id");
                                for ($iItmDiscIndex = 0; $iItmDiscIndex < count($Items[$itemIndex]['Discounts']); $iItmDiscIndex++) {
                                    if ($Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountId'] == $iDiscTypeId) {
                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountAmount'] += $this->__checkNumericExist($discChecking, "cdis_round_total");
                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountAmount'] = number_format($Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountAmount'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");

                                        //Update voucher numbers
                                        $membershipIntfVars = array('voucherNumber' => '', 'pointUsed' => '');
                                        if (isset($discChecking['checkExtraInfos'])) {
                                            foreach ($discChecking['checkExtraInfos'] as $itemDiscExtraInfo) {
                                                if ($itemDiscExtraInfo['ckei_by'] == "discount" && $itemDiscExtraInfo['ckei_section'] == "membership_interface" && $itemDiscExtraInfo['ckei_variable'] == "voucher_number") {
                                                    if (strlen($Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfVoucherNumbers']) > 0)
                                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfVoucherNumbers'] .= "," . $itemDiscExtraInfo['ckei_value'];
                                                    else
                                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfVoucherNumbers'] = $itemDiscExtraInfo['ckei_value'];
                                                } else if ($itemDiscExtraInfo['ckei_by'] == "discount" && $itemDiscExtraInfo['ckei_section'] == "membership_interface" && $itemDiscExtraInfo['ckei_variable'] == "points_use") {
                                                    if (strlen($Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfPointUsed']) > 0)
                                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfPointUsed'] .= "," . $itemDiscExtraInfo['ckei_value'];
                                                    else
                                                        $Items[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountMembershipIntfPointUsed'] = $itemDiscExtraInfo['ckei_value'];
                                                }
                                            }
                                        }
                                        break;
                                    }
                                }
                            }

                            //update discount total by discount type
                            foreach ($itemDiscsChecking as $discChecking) {
                                $iDiscTypeId = $this->__checkNumericExist($discChecking, "cdis_dtyp_id");
                                for ($iItmDiscIndex = 0; $iItmDiscIndex < count($DiscTotalByDiscType); $iItmDiscIndex++) {
                                    if ($DiscTotalByDiscType[$iItmDiscIndex]['DiscountId'] == $iDiscTypeId) {
                                        $DiscTotalByDiscType[$iItmDiscIndex]['DiscountAmount'] += $this->__checkNumericExist($discChecking, "cdis_round_total");
                                        $DiscTotalByDiscType[$iItmDiscIndex]['DiscountAmount'] = number_format($DiscTotalByDiscType[$iItmDiscIndex]['DiscountAmount'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                                        break;
                                    }
                                }
                            }

                            //update department totals
                            $departmentFirstLevelId = $this->__getHighestLevelDepartmentId($depts, $this->__checkNumericExist($checkItem, "citm_idep_id"));
                            if (isset($departmentTotals[$departmentFirstLevelId]))
                                $departmentTotals[$departmentFirstLevelId] += $this->__checkNumericExist($checkItem, "citm_round_total");
                            else
                                $departmentTotals[0] = $this->__checkNumericExist($checkItem, "citm_round_total");

                            //category total handling
                            $categoryFirstLevelId = $this->__getHighestLevelCategoryId($itemCategories, $this->__checkNumericExist($checkItem, "citm_icat_id"));
                            if (isset($categoryTotals[$categoryFirstLevelId]))
                                $categoryTotals[$categoryFirstLevelId] += $this->__checkNumericExist($checkItem, "citm_round_total");
                            else
                                $categoryTotals[0] += $this->__checkNumericExist($checkItem, "citm_round_total");

                            //update vars list
                            $totalItem += $this->__checkNumericExist($checkItem, "citm_qty");
                            $totalAmount += $this->__checkNumericExist($checkItem, "citm_round_total");
                            $totalAmountUseItemOriPrice += ($this->__checkNumericExist($checkItem, "citm_qty") * $Items[$itemIndex]['ItemOriginalPrice']);
                            $checkItemDiscTotal += $itemAppliedDiscTotal;
                            break;
                        }

                        if ($itemGrouped)
                            continue;
                    }
                }

                // continuous print skip to get order / void user if printed
                if (!$isPrinted) {
                    //order user
                    if (!array_key_exists($checkItem['citm_order_user_id'], $userList)) {
                        $user = $UserUserModel->findActiveById($checkItem['citm_order_user_id']);
                        if (!empty($user))
                            $userList[$checkItem['citm_order_user_id']] = $user['UserUser'];
                    }
                    $itemOrderUser = $userList[$checkItem['citm_order_user_id']];

                    //void order user
                    $itemVoidOrderUser = "";
                    if (!empty($checkItem['citm_void_user_id'])) {
                        if (!array_key_exists($checkItem['citm_void_user_id'], $userList)) {
                            $user = $UserUserModel->findActiveById($checkItem['citm_void_user_id']);
                            if (!empty($user))
                                $userList[$checkItem['citm_void_user_id']] = $user['UserUser'];
                        }
                        $itemVoidOrderUser = $userList[$checkItem['citm_void_user_id']];
                    }
                }

                //item total
                $itemTotal = $this->__checkNumericExist($checkItem, "citm_round_total");

                //item gross price
                $itemGrossPrice = $this->__checkNumericExist($checkItem, "citm_price");

                //item original price
                $itemOriginalPrice = $this->__checkNumericExist($checkItem, "citm_original_price");

                //get item category
                $itemCatName[0] = "";
                for ($index = 1; $index <= 5; $index++)
                    $itemCatName[$index] = "";

                // continuous print skip to get category name if printed
                if (!$isPrinted && $this->__checkNumericExist($checkItem, "citm_icat_id") > 0) {
                    if (!array_key_exists($checkItem['citm_icat_id'], $itemCategoryList)) {
                        $itemCategory = $MenuItemCategoryModel->findActiveById($checkItem['citm_icat_id']);
                        if (!empty($itemCategory))
                            $itemCategoryList[$checkItem['citm_icat_id']] = $itemCategory;
                    }

                    $itemCategory = $itemCategoryList[$checkItem['citm_icat_id']];
                    if (!empty($itemCategory)) {
                        for ($index = 1; $index <= 5; $index++)
                            $itemCatName[$index] = $itemCategory['MenuItemCategory']['icat_name_l' . $index];
                    }
                }

                //get item department
                $itemDeptName[0] = "";
                for ($index = 1; $index <= 5; $index++)
                    $itemDeptName[$index] = "";
                // continuous print skip to get department name if printed
                if (!$isPrinted && $this->__checkNumericExist($checkItem, "citm_idep_id") > 0) {
                    if (!array_key_exists($checkItem['citm_idep_id'], $itemDepartmentList)) {
                        $itemDept = $MenuItemDeptModel->findActiveById($checkItem['citm_idep_id']);
                        if (!empty($itemDept))
                            $itemDepartmentList[$checkItem['citm_idep_id']] = $itemDept;
                    }

                    $itemDept = $itemDepartmentList[$checkItem['citm_idep_id']];
                    if (!empty($itemDept)) {
                        for ($index = 1; $index <= 5; $index++)
                            $itemDeptName[$index] = $itemDept['MenuItemDept']['idep_name_l' . $index];
                    }
                }

                //get item course
                $itemCourseName[0] = "";
                for ($index = 1; $index <= 5; $index++)
                    $itemCourseName[$index] = "";

                // continuous print skip to get category name if printed
                if (!$isPrinted && $this->__checkNumericExist($checkItem, "citm_icou_id") > 0) {
                    if (!array_key_exists($checkItem['citm_icou_id'], $itemCourseList)) {
                        $itemCourse = $MenuItemCourseModel->findActiveById($checkItem['citm_icou_id']);
                        if (!empty($itemCourse))
                            $itemCourseList[$checkItem['citm_icou_id']] = $itemCourse;
                    }

                    $itemCourse = $itemCourseList[$checkItem['citm_icou_id']];
                    if (!empty($itemCourse)) {
                        for ($index = 1; $index <= 5; $index++)
                            $itemCourseName[$index] = $itemCourse['MenuItemCourse']['icou_name_l' . $index];
                    }
                }

                //get item discount
                $itemDiscountTotal = 0;
                $itemDiscounts = array();
                if ($this->__checkNumericExist($checkItem, "citm_pre_disc") != 0 || $this->__checkNumericExist($checkItem, "citm_mid_disc") != 0 || $this->__checkNumericExist($checkItem, "citm_post_disc") != 0) {
                    $itemDisc = (isset($checkItem['PosCheckDiscount'])) ? $checkItem['PosCheckDiscount'] : array();
                    if (!empty($itemDisc)) {
                        foreach ($itemDisc as $disc) {
                            if (empty($discountTypes) || !array_key_exists($this->__checkNumericExist($disc, "cdis_dtyp_id"), $discountTypes)) {
                                $discountType = $PosDiscountTypeModel->findActiveById($disc['cdis_dtyp_id']);
                                if (!empty($discountType))
                                    $discountTypes[$disc['cdis_dtyp_id']] = $discountType['PosDiscountType']['dtyp_code'];
                                else
                                    $discountTypes[$disc['cdis_dtyp_id']] = "";
                            }
                            $itemDiscountTotal += $this->__checkNumericExist($disc, "cdis_round_total");

                            //get item discount's extra info
                            $membershipIntfVars = array('voucherNumber' => '', 'pointUsed' => '');
                            $discountEmployee = array();
                            $discountMemberNo = $discountReference = $discountMemberExpiryDate = "";
                            if (isset($disc['checkExtraInfos'])) {
                                foreach ($disc['checkExtraInfos'] as $itemDiscExtraInfo) {
                                    if ($itemDiscExtraInfo['ckei_by'] == "discount" && $itemDiscExtraInfo['ckei_section'] == "membership_interface" && $itemDiscExtraInfo['ckei_variable'] == "voucher_number")
                                        $membershipIntfVars['voucherNumber'] = $itemDiscExtraInfo['ckei_value'];
                                    if ($itemDiscExtraInfo['ckei_by'] == "discount" && $itemDiscExtraInfo['ckei_section'] == "membership_interface" && $itemDiscExtraInfo['ckei_variable'] == "points_use")
                                        $membershipIntfVars['pointUsed'] = $itemDiscExtraInfo['ckei_value'];
                                    if ($itemDiscExtraInfo['ckei_by'] == "discount" && $itemDiscExtraInfo['ckei_section'] == "discount") {
                                        if ($itemDiscExtraInfo['ckei_variable'] == "user_id")
                                            $discountEmployee = $UserUserModel->findActiveById($itemDiscExtraInfo['ckei_value'], -1);
                                        else if ($itemDiscExtraInfo['ckei_variable'] == "member_number")
                                            $discountMemberNo = $itemDiscExtraInfo['ckei_value'];
                                        else if ($itemDiscExtraInfo['ckei_variable'] == "reference")
                                            $discountReference = $itemDiscExtraInfo['ckei_value'];
                                        else if ($itemDiscExtraInfo['ckei_variable'] == "exp_date")
                                            $discountMemberExpiryDate = $itemDiscExtraInfo['ckei_value'];
                                    }
                                }
                            }

                            // continuous print skip put into itemDiscounts[] if printed
                            if (!$isPrinted)
                                $itemDiscounts[] = array(
                                    'DiscountId' => $this->__checkNumericExist($disc, "cdis_dtyp_id"),
                                    'DiscountCode' => $discountTypes[$disc['cdis_dtyp_id']],
                                    'DiscountName' => $this->__checkStringExist($disc, "cdis_name_l" . $prtFmtDefaultLang),
                                    'DiscountNameL1' => $this->__checkStringExist($disc, "cdis_name_l1"),
                                    'DiscountNameL2' => $this->__checkStringExist($disc, "cdis_name_l2"),
                                    'DiscountNameL3' => $this->__checkStringExist($disc, "cdis_name_l3"),
                                    'DiscountNameL4' => $this->__checkStringExist($disc, "cdis_name_l4"),
                                    'DiscountNameL5' => $this->__checkStringExist($disc, "cdis_name_l5"),
                                    'DiscountAmount' => number_format($this->__checkNumericExist($disc, "cdis_round_total"), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                                    'DiscountMembershipIntfVoucherNumbers' => $membershipIntfVars['voucherNumber'],
                                    'DiscountMembershipIntfPointUsed' => $membershipIntfVars['pointUsed'],
                                    'DiscountAppliedEmployee' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] . ' ' . $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang]) : "",
                                    'DiscountAppliedEmployeeL1' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l1'] . ' ' . $discountEmployee['UserUser']['user_first_name_l1']) : "",
                                    'DiscountAppliedEmployeeL2' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l2'] . ' ' . $discountEmployee['UserUser']['user_first_name_l2']) : "",
                                    'DiscountAppliedEmployeeL3' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l3'] . ' ' . $discountEmployee['UserUser']['user_first_name_l3']) : "",
                                    'DiscountAppliedEmployeeL4' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l4'] . ' ' . $discountEmployee['UserUser']['user_first_name_l4']) : "",
                                    'DiscountAppliedEmployeeL5' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l5'] . ' ' . $discountEmployee['UserUser']['user_first_name_l5']) : "",
                                    'DiscountAppliedEmployeeFirstName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang] : "",
                                    'DiscountAppliedEmployeeFirstNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l1'] : "",
                                    'DiscountAppliedEmployeeFirstNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l2'] : "",
                                    'DiscountAppliedEmployeeFirstNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l3'] : "",
                                    'DiscountAppliedEmployeeFirstNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l4'] : "",
                                    'DiscountAppliedEmployeeFirstNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l5'] : "",
                                    'DiscountAppliedEmployeeLastName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] : "",
                                    'DiscountAppliedEmployeeLastNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l1'] : "",
                                    'DiscountAppliedEmployeeLastNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l2'] : "",
                                    'DiscountAppliedEmployeeLastNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l3'] : "",
                                    'DiscountAppliedEmployeeLastNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l4'] : "",
                                    'DiscountAppliedEmployeeLastNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l5'] : "",
                                    'DiscountMemberNumber' => $discountMemberNo,
                                    'DiscountReference' => $discountReference,
                                    'DiscountMemberExpiryDate' => $discountMemberExpiryDate
                                );

                            // continuous skip put into itemDiscounts[] if printed
                            if (empty($this->__checkStringExist($checkItem, "citm_status"))) {
                                $isExist = false;
                                for ($index = 0; $index < count($DiscTotalByDiscType); $index++) {
                                    if ($DiscTotalByDiscType[$index]['DiscountId'] == $disc['cdis_dtyp_id']) {
                                        $isExist = true;
                                        $newDiscTotal = $DiscTotalByDiscType[$index]['DiscountAmount'] + $this->__checkNumericExist($disc, "cdis_round_total");
                                        $DiscTotalByDiscType[$index]['DiscountAmount'] = number_format($newDiscTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                                        break;
                                    }
                                }
                                if (!$isExist)
                                    $DiscTotalByDiscType[] = array(
                                        'DiscountId' => $disc['cdis_dtyp_id'],
                                        'DiscountCode' => $discountTypes[$disc['cdis_dtyp_id']],
                                        'DiscountName' => $this->__checkStringExist($disc, "cdis_name_l" . $prtFmtDefaultLang),
                                        'DiscountNameL1' => $this->__checkStringExist($disc, "cdis_name_l1"),
                                        'DiscountNameL2' => $this->__checkStringExist($disc, "cdis_name_l2"),
                                        'DiscountNameL3' => $this->__checkStringExist($disc, "cdis_name_l3"),
                                        'DiscountNameL4' => $this->__checkStringExist($disc, "cdis_name_l4"),
                                        'DiscountNameL5' => $this->__checkStringExist($disc, "cdis_name_l5"),
                                        'DiscountAmount' => number_format($this->__checkNumericExist($disc, "cdis_round_total"), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                                        'DiscountMemberNumber' => $discountMemberNo,
                                        'DiscountReference' => $discountReference,
                                        'DiscountMemberExpiryDate' => $discountMemberExpiryDate
                                    );
                            }
                        }
                    }
                }

                //Item Tax
                $itemTaxTotal = 0;
                for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++) {
                    $itemTax[$taxIndex] = 0;
                    $itemTax[$taxIndex] = ($this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_round_total") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc1") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc2") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc3") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc4") + $this->__checkNumericExist($checkItem, "tax" . $taxIndex . "_on_citm_sc5"));
                    $itemTaxTotal += $itemTax[$taxIndex];

                    if ($itemTax[$taxIndex] > 0) {
                        $checkDiscountTotalForItem = 0;
                        if (isset($checkItem['PosCheckDiscountItem']) && count($checkItem['PosCheckDiscountItem']) > 0) {
                            foreach ($checkItem['PosCheckDiscountItem'] as $itemCheckDiscountItem)
                                $checkDiscountTotalForItem += $this->__checkNumericExist($itemCheckDiscountItem, "cdit_round_total");
                        }

                        if ($this->__checkStringExist($checkItem, "citm_charge_tax" . $taxIndex) == "c" || $this->__checkStringExist($checkItem, "citm_charge_tax" . $taxIndex) == "i") {
                            $vars['SCTotalWithTax' . $taxIndex] += ($this->__checkNumericExist($checkItem, "citm_sc1_round") + $this->__checkNumericExist($checkItem, "citm_sc2_round") + $this->__checkNumericExist($checkItem, "citm_sc3_round") + $this->__checkNumericExist($checkItem, "citm_sc4_round") + $this->__checkNumericExist($checkItem, "citm_sc5_round"));
                        }
                    }
                }

                //continuous print skip calculate if it is deleted
                if (empty($this->__checkStringExist($checkItem, "citm_status"))) {
                    //department total handling
                    $departmentFirstLevelId = $this->__getHighestLevelDepartmentId($depts, $this->__checkNumericExist($checkItem, "citm_idep_id"));
                    if (isset($departmentTotals[$departmentFirstLevelId]))
                        $departmentTotals[$departmentFirstLevelId] += $this->__checkNumericExist($checkItem, "citm_round_total");
                    else
                        $departmentTotals[0] += $this->__checkNumericExist($checkItem, "citm_round_total");

                    //category total handling
                    $categoryFirstLevelId = $this->__getHighestLevelCategoryId($itemCategories, $this->__checkNumericExist($checkItem, "citm_icat_id"));
                    if (isset($categoryTotals[$categoryFirstLevelId]))
                        $categoryTotals[$categoryFirstLevelId] += $this->__checkNumericExist($checkItem, "citm_round_total");
                    else
                        $categoryTotals[0] += $this->__checkNumericExist($checkItem, "citm_round_total");
                }

                //handle modifier's if necessary
                $modifierList = array();
                if ($this->__checkNumericExist($checkItem, "citm_modifier_count") > 0) {
                    $modifiers = $checkItem['ModifierList'];

                    if (!empty($modifiers)) {
                        foreach ($modifiers as $modifier) {
                            $itemGrossPrice += $this->__checkNumericExist($modifier, "citm_price");
                            $itemOriginalPrice += $this->__checkNumericExist($modifier, "citm_original_price");

                            // continuous print skip put into modifierList[] if printed
                            if (!$isPrinted && $this->__checkStringExist($modifier, "citm_no_print") == '')
                                $modifierList[] = array(
                                    'ModifierId' => $modifier['citm_item_id'],
                                    'ModifierCode' => $this->__checkStringExist($modifier, "citm_code"),
                                    'ModifierName' => $this->__checkStringExist($modifier, "citm_name_l" . $prtFmtDefaultLang),
                                    'ModifierNameL1' => $this->__checkStringExist($modifier, "citm_name_l1"),
                                    'ModifierNameL2' => $this->__checkStringExist($modifier, "citm_name_l2"),
                                    'ModifierNameL3' => $this->__checkStringExist($modifier, "citm_name_l3"),
                                    'ModifierNameL4' => $this->__checkStringExist($modifier, "citm_name_l4"),
                                    'ModifierNameL5' => $this->__checkStringExist($modifier, "citm_name_l5"),
                                    'ModifierShortName' => $this->__checkStringExist($modifier, "citm_short_name_l" . $prtFmtDefaultLang),
                                    'ModifierShortNameL1' => $this->__checkStringExist($modifier, "citm_short_name_l1"),
                                    'ModifierShortNameL2' => $this->__checkStringExist($modifier, "citm_short_name_l2"),
                                    'ModifierShortNameL3' => $this->__checkStringExist($modifier, "citm_short_name_l3"),
                                    'ModifierShortNameL4' => $this->__checkStringExist($modifier, "citm_short_name_l4"),
                                    'ModifierShortNameL5' => $this->__checkStringExist($modifier, "citm_short_name_l5"),
                                    'ModifierQuantity' => $this->__checkNumericExist($modifier, "citm_qty"),
                                    'ModifierPrice' => number_format(($this->__checkNumericExist($modifier, "citm_original_price") * $this->__checkNumericExist($modifier, "citm_qty")), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "")
                                );
                        }
                    }
                }

                //handle child item's if necessary
                $childItemList = array();
                if ($bPutItemInMainList == false && $this->__checkNumericExist($checkItem, "citm_child_count") > 0) {
                    $childItems = $checkItem['ChildItemList'];
                    if (!empty($childItems)) {
                        foreach ($childItems as $childItem) {
                            $itemOriginalPrice += $childItem['citm_original_price'];

                            //get child item category
                            $childCatName[0] = "";
                            for ($index = 1; $index <= 5; $index++)
                                $childCatName[$index] = "";

                            // continuous print (skip get category if printed
                            if (!$isPrinted && $this->__checkNumericExist($childItem, "citm_icat_id") > 0) {
                                if (!array_key_exists($childItem['citm_icat_id'], $itemCategoryList)) {
                                    $childItemCat = $MenuItemCategoryModel->findActiveById($childItem['citm_icat_id']);
                                    if (!empty($childItemCat))
                                        $itemCategoryList[$childItem['citm_icat_id']] = $childItemCat;
                                }
                                $childItemCat = $itemCategoryList[$childItem['citm_icat_id']];
                                if (!empty($childItemCat))
                                    for ($index = 1; $index <= 5; $index++)
                                        $childCatName[$index] = $childItemCat['MenuItemCategory']['icat_name_l' . $index];
                            }

                            //get child item department
                            $childDeptName[0] = "";
                            for ($index = 1; $index <= 5; $index++)
                                $childDeptName[$index] = "";

                            // continuous print (skip get department if printed)
                            if (!$isPrinted && $this->__checkNumericExist($childItem, "citm_idep_id") > 0) {
                                if (!array_key_exists($childItem['citm_idep_id'], $itemDepartmentList)) {
                                    $childItemDept = $MenuItemDeptModel->findActiveById($childItem['citm_idep_id']);
                                    if (!empty($childItemDept))
                                        $itemDepartmentList[$childItem['citm_idep_id']] = $childItemDept;
                                }
                                $childItemDept = $itemDepartmentList[$childItem['citm_idep_id']];
                                if (!empty($childItemDept))
                                    for ($index = 1; $index <= 5; $index++)
                                        $childDeptName[$index] = $childItemDept['MenuItemDept']['idep_name_l' . $index];
                            }

                            $childModifierList = array();
                            if ($this->__checkNumericExist($childItem, "citm_modifier_count") > 0) {
                                $childModifiers = $childItem['ModifierList'];
                                if (!empty($childModifiers)) {
                                    foreach ($childModifiers as $childModi) {
                                        if ($this->__checkStringExist($childModi, "citm_no_print") == '') {
                                            $itemOriginalPrice += $this->__checkNumericExist($childModi, "citm_original_price");

                                            // continuous print (skip put into childModifierList[] if printed)
                                            if (!$isPrinted)
                                                $childModifierList[] = array(
                                                    'ModifierId' => $childModi['citm_item_id'],
                                                    'ModifierCode' => $this->__checkStringExist($childModi, "citm_code"),
                                                    'ModifierName' => $this->__checkStringExist($childModi, "citm_name_l" . $prtFmtDefaultLang),
                                                    'ModifierNameL1' => $this->__checkStringExist($childModi, "citm_name_l1"),
                                                    'ModifierNameL2' => $this->__checkStringExist($childModi, "citm_name_l2"),
                                                    'ModifierNameL3' => $this->__checkStringExist($childModi, "citm_name_l3"),
                                                    'ModifierNameL4' => $this->__checkStringExist($childModi, "citm_name_l4"),
                                                    'ModifierNameL5' => $this->__checkStringExist($childModi, "citm_name_l5"),
                                                    'ModifierShortName' => $this->__checkStringExist($childModi, "citm_short_name_l" . $prtFmtDefaultLang),
                                                    'ModifierShortNameL1' => $this->__checkStringExist($childModi, "citm_short_name_l1"),
                                                    'ModifierShortNameL2' => $this->__checkStringExist($childModi, "citm_short_name_l2"),
                                                    'ModifierShortNameL3' => $this->__checkStringExist($childModi, "citm_short_name_l3"),
                                                    'ModifierShortNameL4' => $this->__checkStringExist($childModi, "citm_short_name_l4"),
                                                    'ModifierShortNameL5' => $this->__checkStringExist($childModi, "citm_short_name_l5"),
                                                    'ModifierQuantity' => $this->__checkNumericExist($childModi, "citm_qty"),
                                                    'ModifierPrice' => number_format(($this->__checkNumericExist($childModi, "citm_original_price") * $this->__checkNumericExist($childModi, "citm_qty")), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "")
                                                );
                                        }
                                    }
                                }
                            }

                            $childDiscountList = array();
                            if ($this->__checkNumericExist($childItem, "citm_pre_disc") != 0 || $this->__checkNumericExist($childItem, "citm_mid_disc") != 0 || $this->__checkNumericExist($childItem, "citm_post_disc") != 0) {
                                $itemDisc = (isset($childItem['PosCheckDiscount'])) ? $childItem['PosCheckDiscount'] : array();
                                if (!empty($itemDisc)) {
                                    foreach ($itemDisc as $disc) {
                                        if (empty($discountTypes) || !array_key_exists($this->__checkNumericExist($disc, "cdis_dtyp_id"), $discountTypes)) {
                                            $discountType = $PosDiscountTypeModel->findActiveById($disc['cdis_dtyp_id']);
                                            if (!empty($discountType))
                                                $discountTypes[$disc['cdis_dtyp_id']] = $discountType['PosDiscountType']['dtyp_code'];
                                            else
                                                $discountTypes[$disc['cdis_dtyp_id']] = "";
                                        }

                                        $itemDiscountTotal += $this->__checkNumericExist($disc, "cdis_round_total");
                                        // continuous print (skip put into childDiscountList[] if printed)
                                        if (!$isPrinted)
                                            $childDiscountList[] = array(
                                                'DiscountId' => $this->__checkNumericExist($disc, "cdis_dtyp_id"),
                                                'DiscountCode' => $discountTypes[$disc['cdis_dtyp_id']],
                                                'DiscountName' => $this->__checkStringExist($disc, "cdis_name_l" . $prtFmtDefaultLang),
                                                'DiscountNameL1' => $this->__checkStringExist($disc, "cdis_name_l1"),
                                                'DiscountNameL2' => $this->__checkStringExist($disc, "cdis_name_l2"),
                                                'DiscountNameL3' => $this->__checkStringExist($disc, "cdis_name_l3"),
                                                'DiscountNameL4' => $this->__checkStringExist($disc, "cdis_name_l4"),
                                                'DiscountNameL5' => $this->__checkStringExist($disc, "cdis_name_l5"),
                                                'DiscountAmount' => number_format($this->__checkNumericExist($disc, "cdis_round_total"), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "")
                                            );

                                        $isExist = false;
                                        for ($index = 0; $index < count($DiscTotalByDiscType); $index++) {
                                            if ($DiscTotalByDiscType[$index]['DiscountId'] == $disc['cdis_dtyp_id']) {
                                                $isExist = true;
                                                $newDiscTotal = $DiscTotalByDiscType[$index]['DiscountAmount'] + $this->__checkNumericExist($disc, "cdis_round_total");
                                                $DiscTotalByDiscType[$index]['DiscountAmount'] = number_format($newDiscTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                                                break;
                                            }
                                        }
                                        if (!$isExist)
                                            $DiscTotalByDiscType[] = array(
                                                'DiscountId' => $disc['cdis_dtyp_id'],
                                                'DiscountCode' => $discountTypes[$disc['cdis_dtyp_id']],
                                                'DiscountName' => $this->__checkStringExist($disc, "cdis_name_l" . $prtFmtDefaultLang),
                                                'DiscountNameL1' => $this->__checkStringExist($disc, "cdis_name_l1"),
                                                'DiscountNameL2' => $this->__checkStringExist($disc, "cdis_name_l2"),
                                                'DiscountNameL3' => $this->__checkStringExist($disc, "cdis_name_l3"),
                                                'DiscountNameL4' => $this->__checkStringExist($disc, "cdis_name_l4"),
                                                'DiscountNameL5' => $this->__checkStringExist($disc, "cdis_name_l5"),
                                                'DiscountAmount' => number_format($this->__checkNumericExist($disc, "cdis_round_total"), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "")
                                            );
                                    }
                                }
                            }

                            //Get extra info
                            $childItemCallNo = "";
                            if (isset($childItem["PosCheckExtraInfo"]) && count($childItem["PosCheckExtraInfo"])) {
                                for ($extraInfoIndex = 0; $extraInfoIndex < count($childItem["PosCheckExtraInfo"]); $extraInfoIndex++) {
                                    $childItemExtraInfo = $childItem["PosCheckExtraInfo"][$extraInfoIndex];
                                    if ($this->__checkStringExist($childItemExtraInfo, "ckei_variable") != "") {
                                        if ($this->__checkStringExist($childItemExtraInfo, "ckei_variable") == "call_no")
                                            $childItemCallNo = $this->__checkStringExist($childItemExtraInfo, 'ckei_value');
                                    }
                                }
                            }

                            // continuous print skip add to total if it is deleted
                            if (empty($this->__checkStringExist($childItem, "chks_status"))) {
                                //department total handling
                                $departmentFirstLevelId = $this->__getHighestLevelDepartmentId($depts, $this->__checkNumericExist($childItem, "citm_idep_id"));
                                if (isset($departmentTotals[$departmentFirstLevelId]))
                                    $departmentTotals[$departmentFirstLevelId] += $this->__checkNumericExist($childItem, "citm_round_total");
                                else
                                    $departmentTotals[0] += $this->__checkNumericExist($childItem, "citm_round_total");

                                //category total handling
                                $categoryFirstLevelId = $this->__getHighestLevelCategoryId($itemCategories, $childItem['citm_icat_id']);
                                if (isset($categoryTotals[$categoryFirstLevelId]))
                                    $categoryTotals[$categoryFirstLevelId] += $childItem['citm_round_total'];
                                else
                                    $categoryTotals[0] += $childItem['citm_round_total'];
                            }

                            //add the child total to item total if parent's basic calculate method is 'c'
                            if (strcmp($this->__checkStringExist($checkItem, "citm_basic_calculate_method"), "c") == 0)
                                $itemTotal += $this->__checkNumericExist($childItem, "citm_round_total");

                            // continuous print (skip put into childItemList[] if printed
                            if (!$isPrinted && $this->__checkStringExist($childItem, "citm_no_print") == '')
                                $childItemList[] = array(
                                    'ChildItemId' => $this->__checkStringExist($childItem, 'citm_item_id'),
                                    'ChildItemCode' => $this->__checkStringExist($childItem, "citm_code"),
                                    'ChildItemName' => $this->__checkStringExist($childItem, "citm_name_l" . $prtFmtDefaultLang),
                                    'ChildItemNameL1' => $this->__checkStringExist($childItem, "citm_name_l1"),
                                    'ChildItemNameL2' => $this->__checkStringExist($childItem, "citm_name_l2"),
                                    'ChildItemNameL3' => $this->__checkStringExist($childItem, "citm_name_l3"),
                                    'ChildItemNameL4' => $this->__checkStringExist($childItem, "citm_name_l4"),
                                    'ChildItemNameL5' => $this->__checkStringExist($childItem, "citm_name_l5"),
                                    'ChildItemShortName' => $this->__checkStringExist($childItem, "citm_short_name_l" . $prtFmtDefaultLang),
                                    'ChildItemShortNameL1' => $this->__checkStringExist($childItem, "citm_short_name_l1"),
                                    'ChildItemShortNameL2' => $this->__checkStringExist($childItem, "citm_short_name_l2"),
                                    'ChildItemShortNameL3' => $this->__checkStringExist($childItem, "citm_short_name_l3"),
                                    'ChildItemShortNameL4' => $this->__checkStringExist($childItem, "citm_short_name_l4"),
                                    'ChildItemShortNameL5' => $this->__checkStringExist($childItem, "citm_short_name_l5"),
                                    'ChildItemQuantity' => $this->__checkNumericExist($childItem, "citm_qty"),
                                    'ChildItemPrice' => number_format(($this->__checkNumericExist($childItem, "citm_round_total")), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                                    'ChildItemTakeout' => ($this->__checkStringExist($childItem, "citm_ordering_type") == 't') ? 1 : 0,
                                    'ChildItemCallNumber' => $childItemCallNo,
                                    'Modifiers' => $childModifierList,
                                    'Discounts' => $childDiscountList
                                );
                        }
                    }
                }

                $itemName = $this->__checkStringExist($checkItem, "citm_name_l" . $prtFmtDefaultLang);
                $itemShortName = $this->__checkStringExist($checkItem, "citm_short_name_l" . $prtFmtDefaultLang);
                $itemInfo = "";
                if ((isset($checkInfo['menuItems'][$checkItem['citm_item_id']])) && !empty($checkInfo['menuItems'][$checkItem['citm_item_id']]))
                    $itemInfo = $this->__checkStringExist($checkInfo['menuItems'][$checkItem['citm_item_id']], "item_info_l" . $prtFmtDefaultLang);

                if (!empty($itemOrderUser)) {
                    $itemOrderEmployeeFirstName = $this->__checkStringExist($itemOrderUser, "user_first_name_l" . $prtFmtDefaultLang);
                    $itemOrderEmployeeLastName = $this->__checkStringExist($itemOrderUser, "user_last_name_l" . $prtFmtDefaultLang);
                    $itemOrderEmployee = $itemOrderEmployeeLastName . ' ' . $itemOrderEmployeeFirstName;
                } else {
                    $itemOrderEmployeeFirstName = "";
                    $itemOrderEmployeeLastName = "";
                    $itemOrderEmployee = "";
                }
                if (!empty($itemVoidOrderUser)) {
                    $itemVoidOrderEmployeeFirstName = $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l" . $prtFmtDefaultLang);
                    $itemVoidOrderEmployeeLastName = $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l" . $prtFmtDefaultLang);
                    $itemVoidOrderEmployee = $itemVoidOrderEmployeeLastName . ' ' . $itemVoidOrderEmployeeFirstName;
                } else {
                    $itemVoidOrderEmployeeFirstName = "";
                    $itemVoidOrderEmployeeLastName = "";
                    $itemVoidOrderEmployee = "";
                }
                if ($this->__checkNumericExist($checkItem, "citm_icou_id") > 0 && !empty($itemCourse))
                    $itemCourseName[0] = $itemCourse['MenuItemCourse']['icou_name_l' . $prtFmtDefaultLang];
                else
                    $itemCourseName[0] = "";
                if ($this->__checkNumericExist($checkItem, "citm_icat_id") > 0 && !empty($itemCategory))
                    $itemCatName[0] = $itemCategory['MenuItemCategory']['icat_name_l' . $prtFmtDefaultLang];
                else
                    $itemCatName[0] = "";
                if ($this->__checkNumericExist($checkItem, "citm_idep_id") > 0 && !empty($itemDept))
                    $itemDeptName[0] = $itemDept['MenuItemDept']['idep_name_l' . $prtFmtDefaultLang];
                else
                    $itemDeptName[0] = "";

                // continuous print skip put into Items[] if printed
                if (!$isPrinted)
                    $Items[] = array(
                        'ItemId' => $checkItem['citm_item_id'],
                        'ItemCode' => $this->__checkStringExist($checkItem, "citm_code"),
                        'ItemMenuId' => $checkItem['citm_item_id'],
                        'ItemName' => $itemName,
                        'ItemNameL1' => $this->__checkStringExist($checkItem, "citm_name_l1"),
                        'ItemNameL2' => $this->__checkStringExist($checkItem, "citm_name_l2"),
                        'ItemNameL3' => $this->__checkStringExist($checkItem, "citm_name_l3"),
                        'ItemNameL4' => $this->__checkStringExist($checkItem, "citm_name_l4"),
                        'ItemNameL5' => $this->__checkStringExist($checkItem, "citm_name_l5"),
                        'ItemShortName' => $itemShortName,
                        'ItemShortNameL1' => $this->__checkStringExist($checkItem, "citm_short_name_l1"),
                        'ItemShortNameL2' => $this->__checkStringExist($checkItem, "citm_short_name_l2"),
                        'ItemShortNameL3' => $this->__checkStringExist($checkItem, "citm_short_name_l3"),
                        'ItemShortNameL4' => $this->__checkStringExist($checkItem, "citm_short_name_l4"),
                        'ItemShortNameL5' => $this->__checkStringExist($checkItem, "citm_short_name_l5"),
                        'ItemQuantity' => $this->__checkNumericExist($checkItem, "citm_qty"),
                        'ItemInfo' => $itemInfo,
                        'ItemInfoL1' => $this->__checkStringExist($checkItem, "citm_short_name_l1"),
                        'ItemInfoL2' => $this->__checkStringExist($checkItem, "citm_short_name_l2"),
                        'ItemInfoL3' => $this->__checkStringExist($checkItem, "citm_short_name_l3"),
                        'ItemInfoL4' => $this->__checkStringExist($checkItem, "citm_short_name_l4"),
                        'ItemInfoL5' => $this->__checkStringExist($checkItem, "citm_short_name_l5"),
                        'ItemOriginalPrice' => number_format($itemOriginalPrice, $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                        'ItemPrice' => number_format(($this->__checkNumericExist($checkItem, "citm_round_total") + $itemDiscountTotal), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                        'ItemCost' => number_format($this->__checkNumericExist($checkItem, "citm_unit_cost"), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                        'TotalItemCost' => number_format($this->__checkNumericExist($checkItem, "citm_unit_cost") * $this->__checkNumericExist($checkItem, "citm_qty"), $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                        'ItemGrossPrice' => $itemGrossPrice,
                        'ItemTotal' => number_format($itemTotal, $businessDay['PosBusinessDay']['bday_item_decimal'], ".", ""),
                        'ItemTaxTotal' => number_format($itemTaxTotal, $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax1' => number_format($itemTax[1], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax2' => number_format($itemTax[2], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax3' => number_format($itemTax[3], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax4' => number_format($itemTax[4], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax5' => number_format($itemTax[5], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax6' => number_format($itemTax[6], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax7' => number_format($itemTax[7], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax8' => number_format($itemTax[8], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax9' => number_format($itemTax[9], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax10' => number_format($itemTax[10], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax11' => number_format($itemTax[11], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax12' => number_format($itemTax[12], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax13' => number_format($itemTax[13], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax14' => number_format($itemTax[14], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax15' => number_format($itemTax[15], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax16' => number_format($itemTax[16], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax17' => number_format($itemTax[17], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax18' => number_format($itemTax[18], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax19' => number_format($itemTax[19], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax20' => number_format($itemTax[20], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax21' => number_format($itemTax[21], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax22' => number_format($itemTax[22], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax23' => number_format($itemTax[23], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax24' => number_format($itemTax[24], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemTax25' => number_format($itemTax[25], $businessDay['PosBusinessDay']['bday_tax_decimal'], ".", ""),
                        'ItemCourseName' => $itemCourseName[0],
                        'ItemCourseNameL1' => $itemCourseName[1],
                        'ItemCourseNameL2' => $itemCourseName[2],
                        'ItemCourseNameL3' => $itemCourseName[3],
                        'ItemCourseNameL4' => $itemCourseName[4],
                        'ItemCourseNameL5' => $itemCourseName[5],
                        'ItemCatName' => $itemCatName[0],
                        'ItemCatNameL1' => $itemCatName[1],
                        'ItemCatNameL2' => $itemCatName[2],
                        'ItemCatNameL3' => $itemCatName[3],
                        'ItemCatNameL4' => $itemCatName[4],
                        'ItemCatNameL5' => $itemCatName[5],
                        'ItemDeptId' => $this->__checkNumericExist($checkItem, "citm_idep_id"),
                        'ItemDept' => $itemDeptName[0],
                        'ItemDeptL1' => $itemDeptName[1],
                        'ItemDeptL2' => $itemDeptName[2],
                        'ItemDeptL3' => $itemDeptName[3],
                        'ItemDeptL4' => $itemDeptName[4],
                        'ItemDeptL5' => $itemDeptName[5],
                        'ItemOrderTime' => substr($checkItem['citm_order_loctime'], 11, 8),
                        'ItemOrderEmployee' => $itemOrderEmployee,
                        'ItemOrderEmployeeL1' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l1") . ' ' . $this->__checkStringExist($itemOrderUser, "user_first_name_l1") : "",
                        'ItemOrderEmployeeL2' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l2") . ' ' . $this->__checkStringExist($itemOrderUser, "user_first_name_l2") : "",
                        'ItemOrderEmployeeL3' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l3") . ' ' . $this->__checkStringExist($itemOrderUser, "user_first_name_l3") : "",
                        'ItemOrderEmployeeL4' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l4") . ' ' . $this->__checkStringExist($itemOrderUser, "user_first_name_l4") : "",
                        'ItemOrderEmployeeL5' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l5") . ' ' . $this->__checkStringExist($itemOrderUser, "user_first_name_l5") : "",
                        'ItemOrderEmployeeFirstName' => $itemOrderEmployeeFirstName,
                        'ItemOrderEmployeeFirstNameL1' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_first_name_l1") : "",
                        'ItemOrderEmployeeFirstNameL2' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_first_name_l2") : "",
                        'ItemOrderEmployeeFirstNameL3' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_first_name_l3") : "",
                        'ItemOrderEmployeeFirstNameL4' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_first_name_l4") : "",
                        'ItemOrderEmployeeFirstNameL5' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_first_name_l5") : "",
                        'ItemOrderEmployeeLastName' => $itemOrderEmployeeLastName,
                        'ItemOrderEmployeeLastNameL1' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l1") : "",
                        'ItemOrderEmployeeLastNameL2' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l2") : "",
                        'ItemOrderEmployeeLastNameL3' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l3") : "",
                        'ItemOrderEmployeeLastNameL4' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l4") : "",
                        'ItemOrderEmployeeLastNameL5' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_last_name_l5") : "",
                        'ItemOrderEmployeeNum' => (!empty($itemOrderUser)) ? $this->__checkStringExist($itemOrderUser, "user_number") : "",
                        'ItemDiscountTotal' => number_format($itemDiscountTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                        'ItemSeatNum' => $this->__checkNumericExist($checkItem, "citm_seat"),
                        'ItemCourseNum' => $this->__checkNumericExist($checkItem, "citm_icou_id"),
                        'ItemGroupStart' => 0,
                        'ItemGroupEnd' => 0,
                        'ItemTakeout' => ($this->__checkStringExist($checkItem, "citm_ordering_type") == 't') ? 1 : 0,
                        'ItemCouponNumber' => $itemSvcCouponItem,
                        'ItemReference' => $itemReference,
                        'ItemMembershipIntfVoucherNumber' => $itemVoucherItem,
                        'ItemCallNumber' => $itemCallNo,
                        'ItemPending' => ($this->__checkStringExist($checkItem, "citm_pending") == 'y' && $this->__checkStringExist($checkItem, "citm_status") == "") ? 1 : 0, //"1 : Pending, 0 : not Pending",
                        'ItemVoid' => ($this->__checkStringExist($checkItem, "citm_status") == 'd') ? 1 : 0, //"1 : Voided, 0 : Active",
                        'ItemVoidOrderTime' => (!empty($itemVoidOrderUser)) ? substr($checkItem['citm_void_loctime'], 11, 8) : "",
                        'ItemVoidOrderEmployee' => $itemVoidOrderEmployee,
                        'ItemVoidOrderEmployeeL1' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l1") . ' ' . $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l1") : "",
                        'ItemVoidOrderEmployeeL2' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l2") . ' ' . $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l2") : "",
                        'ItemVoidOrderEmployeeL3' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l3") . ' ' . $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l3") : "",
                        'ItemVoidOrderEmployeeL4' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l4") . ' ' . $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l4") : "",
                        'ItemVoidOrderEmployeeL5' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l5") . ' ' . $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l5") : "",
                        'ItemVoidOrderEmployeeFirstName' => $itemVoidOrderEmployeeFirstName,
                        'ItemVoidOrderEmployeeFirstNameL1' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l1") : "",
                        'ItemVoidOrderEmployeeFirstNameL2' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l2") : "",
                        'ItemVoidOrderEmployeeFirstNameL3' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l3") : "",
                        'ItemVoidOrderEmployeeFirstNameL4' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l4") : "",
                        'ItemVoidOrderEmployeeFirstNameL5' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_first_name_l5") : "",
                        'ItemVoidOrderEmployeeLastName' => $itemVoidOrderEmployeeLastName,
                        'ItemVoidOrderEmployeeLastNameL1' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l1") : "",
                        'ItemVoidOrderEmployeeLastNameL2' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l2") : "",
                        'ItemVoidOrderEmployeeLastNameL3' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l3") : "",
                        'ItemVoidOrderEmployeeLastNameL4' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l4") : "",
                        'ItemVoidOrderEmployeeLastNameL5' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_last_name_l5") : "",
                        'ItemVoidOrderEmployeeNum' => (!empty($itemVoidOrderUser)) ? $this->__checkStringExist($itemVoidOrderUser, "user_number") : "",
                        'ItemLoyaltySVCCardNumber' => $loyaltyItemInfo['svcCardNumber'],
                        'ItemLoyaltyMemberNumber' => $loyaltyItemInfo['memberNumber'],
                        'ItemLoyaltySVCCardExpiryDate' => $loyaltyItemInfo['svcCardExpiryDate'],
                        'ItemLoyaltySVCRemark' => $loyaltyItemInfo['svcRemark'],
                        'ItemLoyaltyPointBalance' => $itemLoyaltyPointBalance,
                        'ItemLoyaltyPointAddValue' => $itemLoyaltyPointAddValue,
                        'ItemLoyaltyCardNumber' => $itemLoyaltyCardNumber,
                        'ChildItems' => $childItemList,
                        'Modifiers' => $modifierList,
                        'Discounts' => $itemDiscounts,
                        'ItemStatus' => $checkItem['citm_status']
                    );
                if ($this->__checkStringExist($checkItem, "citm_status") == "d" || $this->__checkStringExist($checkItem, "citm_pending") == "y")
                    continue;

                $totalItem += $this->__checkNumericExist($checkItem, "citm_qty");
                $totalAmount += $this->__checkNumericExist($checkItem, "citm_round_total");
                $totalAmountUseItemOriPrice += ($this->__checkNumericExist($checkItem, "citm_qty") * $itemOriginalPrice);
                $checkItemDiscTotal += $itemDiscountTotal;
            }
        }

        $vars['TotalItem'] = $totalItem;
        $vars['CheckItemGrossTotal'] = number_format($totalAmount, $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_check_decimal"), ".", "");
        $vars['CheckItemDiscountTotal'] = number_format($checkItemDiscTotal, $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_disc_decimal"), ".", "");
        $tmpCheckDiscountTotal = $this->__checkNumericExist($check['PosCheck'], "chks_pre_disc") + $this->__checkNumericExist($check['PosCheck'], "chks_mid_disc") + $this->__checkNumericExist($check['PosCheck'], "chks_post_disc");
        //$vars['DiscountTotal'] = number_format(($check['PosCheck']['chks_pre_disc'] + $check['PosCheck']['chks_mid_disc'] + $check['PosCheck']['chks_post_disc']), $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_disc_decimal"), ".", "");
        $vars['DiscountTotal'] = number_format($tmpCheckDiscountTotal, $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_disc_decimal"), ".", "");
        $vars['CheckItemTotal'] = number_format(($this->__checkNumericExist($check['PosCheck'], "chks_item_total") + $checkItemDiscTotal), $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_item_decimal"), ".", "");
        $vars['CheckItemTotalUseItemOriPrice'] = number_format($totalAmountUseItemOriPrice, $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_item_decimal"), ".", "");
        $vars['CheckRoundTotal'] = $this->__checkNumericExist($check['PosCheck'], "chks_round_amount");
        for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++)
            $vars['CheckItemTotalWithTax' . $taxIndex] = number_format($this->__checkNumericExist($check['PosCheck']['checkItemNetTotalWithTax'], $taxIndex), $this->__checkNumericExist($businessDay['PosBusinessDay'], "bday_item_decimal"), ".", "");

        //Update departments loop
        for ($index = 0; $index < count($Departments); $index++) {
            $deptId = $Departments[$index]['DepartmentId'];
            if (isset($departmentTotals[$deptId]))
                $Departments[$index]['DepartmentTotal'] = number_format($departmentTotals[$deptId], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
        }

        //Update categories loop
        for ($index = 0; $index < count($Categories); $index++) {
            $catId = $Categories[$index]['CategoryId'];
            if (isset($categoryTotals[$catId]))
                $Categories[$index]['CategoryTotal'] = number_format($categoryTotals[$catId], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");
        }

        //Retrieve check discounts
        $CheckDiscounts = array();
        $checkDiscountTotal = 0;
        $CheckExtraCharges = array();
        $checkExtraChargeTotal = 0;
        if ($this->__checkNumericExist($check['PosCheck'], "chks_pre_disc") != 0 || $this->__checkNumericExist($check['PosCheck'], "chks_mid_disc") != 0 || $this->__checkNumericExist($check['PosCheck'], "chks_post_disc") != 0) {
            //$checkDisc = $PosCheckModel->PosCheckDiscount->findAllByChksIdCitmId($check['PosCheck']['chks_id'], 0);
            if (!empty($checkDiscs)) {
                //get discount applied employee
                $discAppliedEmpIds = array();
                $discAppliedEmployees = array();
                foreach ($checkDiscs as $disc) {
                    if ($disc['cdis_apply_user_id'] > 0 && !in_array($disc['cdis_apply_user_id'], $discAppliedEmpIds))
                        $discAppliedEmpIds[] = $disc['cdis_apply_user_id'];
                }
                if (!empty($discAppliedEmpIds)) {
                    $tempArray = $UserUserModel->findMultipleByIds($discAppliedEmpIds);
                    foreach ($tempArray as $temp)
                        $discAppliedEmployees[$temp['UserUser']['user_id']] = $temp;
                }

                foreach ($checkDiscs as $disc) {
                    if (empty($discountTypes) || !isset($discountTypes[$disc['cdis_dtyp_id']])) {
                        $discountType = $PosDiscountTypeModel->findActiveById($disc['cdis_dtyp_id']);
                        if (!empty($discountType))
                            $discountTypes[$disc['cdis_dtyp_id']] = $discountType['PosDiscountType']['dtyp_code'];
                        else
                            $discountTypes[$disc['cdis_dtyp_id']] = "";
                    }

                    //get extra info of check discount
                    $membershipIntfVars = array('voucherNumber' => '', 'pointUsed' => '');
                    $discountEmployee = array();
                    $discountMemberNo = $discountReference = $discountMemberExpiryDate = "";
                    if (isset($disc['checkExtraInfos'])) {
                        foreach ($disc['checkExtraInfos'] as $checkDiscExtraInfo) {
                            // Section: membership_interface
                            if ($checkDiscExtraInfo['ckei_by'] == "discount" && $checkDiscExtraInfo['ckei_section'] == "membership_interface") {
                                if ($checkDiscExtraInfo['ckei_variable'] == "voucher_number")
                                    $membershipIntfVars['voucherNumber'] = $checkDiscExtraInfo['ckei_value'];
                                if ($checkDiscExtraInfo['ckei_variable'] == "points_use")
                                    $membershipIntfVars['pointUsed'] = $checkDiscExtraInfo['ckei_value'];
                                else if ($checkDiscExtraInfo['ckei_variable'] == "reference")
                                    $discountReference = $checkDiscExtraInfo['ckei_value'];
                                else if ($checkDiscExtraInfo['ckei_variable'] == "member_number")
                                    $discountMemberNo = $checkDiscExtraInfo['ckei_value'];
                            }
                            // Section: discount
                            if ($checkDiscExtraInfo['ckei_by'] == "discount" && $checkDiscExtraInfo['ckei_section'] == "discount") {
                                if ($checkDiscExtraInfo['ckei_variable'] == "user_id")
                                    $discountEmployee = $UserUserModel->findActiveById($checkDiscExtraInfo['ckei_value'], -1);
                                else if ($checkDiscExtraInfo['ckei_variable'] == "member_number")
                                    $discountMemberNo = $checkDiscExtraInfo['ckei_value'];
                                else if ($checkDiscExtraInfo['ckei_variable'] == "reference")
                                    $discountReference = $checkDiscExtraInfo['ckei_value'];
                                else if ($checkDiscExtraInfo['ckei_variable'] == "exp_date")
                                    $discountMemberExpiryDate = $checkDiscExtraInfo['ckei_value'];
                            }
                            if ($checkDiscExtraInfo['ckei_by'] == "discount" && $checkDiscExtraInfo['ckei_section'] == "gaming_interface" && $checkDiscExtraInfo['ckei_variable'] == "discount_rate")
                                $vars['CheckGamingIntfDiscountRate'] = $checkDiscExtraInfo['ckei_value'];
                        }
                    }

                    if (empty($discountEmployee) && array_key_exists($disc['cdis_apply_user_id'], $discAppliedEmployees))
                        $discountEmployee = $discAppliedEmployees[$disc['cdis_apply_user_id']];

                    if (!empty($this->__checkStringExist($disc, "cdis_used_for")) && strcmp($disc['cdis_used_for'], "c") == 0) {
                        $CheckExtraCharges[] = array(
                            'ExtraChargeId' => $disc['cdis_dtyp_id'],
                            'ExtraChargeCode' => $discountTypes[$disc['cdis_dtyp_id']],
                            'ExtraChargeName' => $this->__checkStringExist($disc, "cdis_name_l" . $prtFmtDefaultLang),
                            'ExtraChargeNameL1' => $this->__checkStringExist($disc, "cdis_name_l1"),
                            'ExtraChargeNameL2' => $this->__checkStringExist($disc, "cdis_name_l2"),
                            'ExtraChargeNameL3' => $this->__checkStringExist($disc, "cdis_name_l3"),
                            'ExtraChargeNameL4' => $this->__checkStringExist($disc, "cdis_name_l4"),
                            'ExtraChargeNameL5' => $this->__checkStringExist($disc, "cdis_name_l5"),
                            'ExtraChargeAmount' => number_format($this->__checkNumericExist($disc, "cdis_round_total"), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                            'ExtraChargeMembershipIntfVoucherNumbers' => $membershipIntfVars['voucherNumber'],
                            'ExtraChargeAppliedEmployee' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] . ' ' . $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang]) : "",
                            'ExtraChargeAppliedEmployeeL1' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l1'] . ' ' . $discountEmployee['UserUser']['user_first_name_l1']) : "",
                            'ExtraChargeAppliedEmployeeL2' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l2'] . ' ' . $discountEmployee['UserUser']['user_first_name_l2']) : "",
                            'ExtraChargeAppliedEmployeeL3' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l3'] . ' ' . $discountEmployee['UserUser']['user_first_name_l3']) : "",
                            'ExtraChargeAppliedEmployeeL4' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l4'] . ' ' . $discountEmployee['UserUser']['user_first_name_l4']) : "",
                            'ExtraChargeAppliedEmployeeL5' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l5'] . ' ' . $discountEmployee['UserUser']['user_first_name_l5']) : "",
                            'ExtraChargeAppliedEmployeeFirstName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l1'] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l2'] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l3'] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l4'] : "",
                            'ExtraChargeAppliedEmployeeFirstNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l5'] : "",
                            'ExtraChargeAppliedEmployeeLastName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] : "",
                            'ExtraChargeAppliedEmployeeLastNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l1'] : "",
                            'ExtraChargeAppliedEmployeeLastNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l2'] : "",
                            'ExtraChargeAppliedEmployeeLastNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l3'] : "",
                            'ExtraChargeAppliedEmployeeLastNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l4'] : "",
                            'ExtraChargeAppliedEmployeeLastNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l5'] : "",
                            'ExtraChargeMemberNumber' => $discountMemberNo,
                            'ExtraChargeReference' => $discountReference
                        );
                        $checkExtraChargeTotal += $this->__checkNumericExist($disc, "cdis_round_total");
                    } else {
                        $CheckDiscounts[] = array(
                            'DiscountId' => $disc['cdis_dtyp_id'],
                            'DiscountCode' => $discountTypes[$disc['cdis_dtyp_id']],
                            'DiscountName' => $this->__checkStringExist($disc, "cdis_name_l" . $prtFmtDefaultLang),
                            'DiscountNameL1' => $this->__checkStringExist($disc, "cdis_name_l1"),
                            'DiscountNameL2' => $this->__checkStringExist($disc, "cdis_name_l2"),
                            'DiscountNameL3' => $this->__checkStringExist($disc, "cdis_name_l3"),
                            'DiscountNameL4' => $this->__checkStringExist($disc, "cdis_name_l4"),
                            'DiscountNameL5' => $this->__checkStringExist($disc, "cdis_name_l5"),
                            'DiscountAmount' => number_format($this->__checkNumericExist($disc, "cdis_round_total"), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                            'DiscountMembershipIntfVoucherNumbers' => $membershipIntfVars['voucherNumber'],
                            'DiscountMembershipIntfPointUsed' => $membershipIntfVars['pointUsed'],
                            'DiscountAppliedEmployee' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] . ' ' . $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang]) : "",
                            'DiscountAppliedEmployeeL1' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l1'] . ' ' . $discountEmployee['UserUser']['user_first_name_l1']) : "",
                            'DiscountAppliedEmployeeL2' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l2'] . ' ' . $discountEmployee['UserUser']['user_first_name_l2']) : "",
                            'DiscountAppliedEmployeeL3' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l3'] . ' ' . $discountEmployee['UserUser']['user_first_name_l3']) : "",
                            'DiscountAppliedEmployeeL4' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l4'] . ' ' . $discountEmployee['UserUser']['user_first_name_l4']) : "",
                            'DiscountAppliedEmployeeL5' => (!empty($discountEmployee)) ? ($discountEmployee['UserUser']['user_last_name_l5'] . ' ' . $discountEmployee['UserUser']['user_first_name_l5']) : "",
                            'DiscountAppliedEmployeeFirstName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang] : "",
                            'DiscountAppliedEmployeeFirstNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l1'] : "",
                            'DiscountAppliedEmployeeFirstNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l2'] : "",
                            'DiscountAppliedEmployeeFirstNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l3'] : "",
                            'DiscountAppliedEmployeeFirstNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l4'] : "",
                            'DiscountAppliedEmployeeFirstNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_first_name_l5'] : "",
                            'DiscountAppliedEmployeeLastName' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] : "",
                            'DiscountAppliedEmployeeLastNameL1' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l1'] : "",
                            'DiscountAppliedEmployeeLastNameL2' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l2'] : "",
                            'DiscountAppliedEmployeeLastNameL3' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l3'] : "",
                            'DiscountAppliedEmployeeLastNameL4' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l4'] : "",
                            'DiscountAppliedEmployeeLastNameL5' => (!empty($discountEmployee)) ? $discountEmployee['UserUser']['user_last_name_l5'] : "",
                            'DiscountMemberNumber' => $discountMemberNo,
                            'DiscountReference' => $discountReference,
                            'DiscountMemberExpiryDate' => $discountMemberExpiryDate
                        );

                        $checkDiscountTotal += $this->__checkNumericExist($disc, "cdis_round_total");
                    }

                    $isExist = false;
                    for ($index = 0; $index < count($DiscTotalByDiscType); $index++) {
                        if ($DiscTotalByDiscType[$index]['DiscountId'] == $disc['cdis_dtyp_id']) {
                            $isExist = true;
                            $newDiscTotal = $DiscTotalByDiscType[$index]['DiscountAmount'] + $this->__checkNumericExist($disc, "cdis_round_total");
                            $DiscTotalByDiscType[$index]['DiscountAmount'] = number_format($newDiscTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                            break;
                        }
                    }
                    if (!$isExist) {
                        $DiscTotalByDiscType[] = array(
                            'DiscountId' => $disc['cdis_dtyp_id'],
                            'DiscountCode' => $discountTypes[$disc['cdis_dtyp_id']],
                            'DiscountName' => $this->__checkStringExist($disc, "cdis_name_l" . $prtFmtDefaultLang),
                            'DiscountNameL1' => $this->__checkStringExist($disc, "cdis_name_l1"),
                            'DiscountNameL2' => $this->__checkStringExist($disc, "cdis_name_l2"),
                            'DiscountNameL3' => $this->__checkStringExist($disc, "cdis_name_l3"),
                            'DiscountNameL4' => $this->__checkStringExist($disc, "cdis_name_l4"),
                            'DiscountNameL5' => $this->__checkStringExist($disc, "cdis_name_l5"),
                            'DiscountAmount' => number_format($this->__checkNumericExist($disc, "cdis_round_total"), $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", ""),
                            'DiscountMemberNumber' => $discountMemberNo,
                            'DiscountReference' => $discountReference,
                            'DiscountMemberExpiryDate' => $discountMemberExpiryDate
                        );
                    }
                }
            }
        }
        $vars['CheckDiscountTotal'] = number_format($checkDiscountTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
        $vars['CheckExtraChargeTotal'] = number_format($checkExtraChargeTotal, $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");

        // Retrieve the payment
        $Payments = array();
        $AllReleasePaymentRunningNumber = array();
        $LastReleasePaymentRunningNumber = array();
        $paymentAmountTotal = 0;
        $previousPaidPaymentTotal = 0;
        $currentPaidPaymentTotal = 0;
        $unpaidPaymentTotal = 0;
        $totalTips = 0;
        $totalChange = 0;

        if ($type == 2) {
            //get duty meal reset period
            $dutyMealLimitReset = 'm';
            if ($this->__getConfigByLocationValue($check['PosCheck']['chks_close_stat_id'], $check['PosCheck']['chks_olet_id'], $check['PosCheck']['chks_shop_id'], 'system', 'dutymeal_limit_reset_period') == 'd')
                $dutyMealLimitReset = 'd';

            //get on credit reset period
            $onCreditLimitReset = 'm';
            if ($this->__getConfigByLocationValue($check['PosCheck']['chks_close_stat_id'], $check['PosCheck']['chks_olet_id'], $check['PosCheck']['chks_shop_id'], 'system', 'on_credit_limit_reset_period') == 'd')
                $onCreditLimitReset = 'd';


            foreach ($checkInfo['checkPayments'] as $checkPayment) {
                if (!empty($checkPayment)) {
                    $paymentAmountTotal += $checkPayment['cpay_pay_total'];
                    if (!empty($this->__checkStringExist($checkPayment, "cpay_id")))
                        $previousPaidPaymentTotal += $checkPayment['cpay_pay_total'];
                    else
                        $currentPaidPaymentTotal += $checkPayment['cpay_pay_total'];
                    $totalTips += $checkPayment['cpay_pay_tips'];
                    $totalChange += $checkPayment['cpay_pay_change'];

                    //get payment method information
                    $paymentMethod = null;
                    if ($this->__checkNumericExist($checkPayment, "cpay_paym_id") > 0)
                        $paymentMethod = $PosPaymentMethodModel->findActiveById($checkPayment['cpay_paym_id'], -1);

                    //get payment tips / residue
                    if ($paymentMethod != null && !empty($paymentMethod) && isset($paymentMethod['PosPaymentMethod']['paym_tips']) && $paymentMethod['PosPaymentMethod']['paym_tips'] == "r") {
                        $paymentTips = 0.0;
                        $paymentResidue = $this->__checkNumericExist($checkPayment, "cpay_pay_tips");
                        $paymentTipsInForeignCurrency = number_format(0.0, $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "");
                        $paymentResidueInForeignCurrency = number_format($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_tips"), $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "");
                    } else {
                        $paymentTips = $this->__checkNumericExist($checkPayment, "cpay_pay_tips");
                        $paymentResidue = 0.0;
                        $paymentTipsInForeignCurrency = ($paymentMethod != null) ? number_format($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_tips"), $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_tips"), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
                        $paymentResidueInForeignCurrency = ($paymentMethod != null) ? number_format(0.0, $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format(0.0, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
                    }

                    if ($paymentMethod != null && !empty($paymentMethod) && isset($paymentMethod['PosPaymentMethod']))
                        $paymentCode = $this->__checkStringExist($paymentMethod['PosPaymentMethod'], "paym_code");

                    //get payment member
                    $paymentMember = array();
                    $paymentMemberSpending = 0;
                    if ($this->__checkNumericExist($checkPayment, "cpay_memb_id") > 0 && $MemMemberModel != null) {
                        $paymentMember = $MemMemberModel->findActiveById($checkPayment['cpay_memb_id'], 1);
                        if (!empty($paymentMember) && isset($paymentMember['MemMemberModuleInfo']) && !empty($paymentMember['MemMemberModuleInfo'])) {
                            foreach ($paymentMember['MemMemberModuleInfo'] as $checkPaymentMemberModuleInfo) {
                                if (strcmp($checkPaymentMemberModuleInfo['minf_module_alias'], "pos") == 0 && strcmp($checkPaymentMemberModuleInfo['minf_variable'], "life_time_spending") == 0) {
                                    $paymentMemberSpending = $checkPaymentMemberModuleInfo['minf_value'];
                                    break;
                                }
                            }
                        }
                    }

                    $paymentEmployee = array();
                    $dutyMealLimit = 0;
                    $onCreditLimit = 0;
                    $remainingCreditLimit = 0;
                    $employeeMaximumCreditLimit = 0;
                    if ($this->__checkNumericExist($checkPayment, "cpay_meal_user_id") > 0) {
                        $paymentEmployee = $UserUserModel->findActiveById($checkPayment['cpay_meal_user_id'], 1);
                        if (!empty($paymentEmployee) && isset($paymentEmployee['UserUserModuleInfo']) && count($paymentEmployee['UserUserModuleInfo']) > 0) {
                            foreach ($paymentEmployee['UserUserModuleInfo'] as $userModuleInfo) {
                                if (strcmp($userModuleInfo['uinf_module_alias'], "pos") == 0 && strcmp($userModuleInfo['uinf_variable'], "duty_meal_limit") == 0) {
                                    $dutyMealLimit = $userModuleInfo['uinf_value'];

                                } else if (strcmp($userModuleInfo['uinf_module_alias'], "pos") == 0 && strcmp($userModuleInfo['uinf_variable'], "on_credit_limit") == 0)
                                    $onCreditLimit = $userModuleInfo['uinf_value'];
                                $remainingCreditLimit = $userModuleInfo['uinf_value'];
                            }
                        }

                        if ((strcmp($this->__checkStringExist($checkPayment, "cpay_payment_type"), "duty_meal") == 0 && $dutyMealLimit > 0) || (strcmp($this->__checkStringExist($checkPayment, "cpay_payment_type"), "on_credit") == 0 && $onCreditLimit > 0)) {
                            $businessMonth = substr($businessDay['PosBusinessDay']['bday_date'], 0, 8) . "%";
                            if ((strcmp($checkPayment['cpay_payment_type'], "duty_meal") == 0 && $dutyMealLimitReset == 'd') || (strcmp($checkPayment['cpay_payment_type'], "on_credit") == 0 && $onCreditLimitReset == 'd'))
                                $businessMonth = $businessDay['PosBusinessDay']['bday_date'];
                            $businessDayList = $PosBusinessDayModel->find('all', array(
                                    'conditions' => array(
                                        'PosBusinessDay.bday_olet_id' => $check['PosCheck']['chks_olet_id'],
                                        'PosBusinessDay.bday_shop_id' => $check['PosCheck']['chks_shop_id'],
                                        'PosBusinessDay.bday_date LIKE' => $businessMonth
                                    ),
                                    'recursive' => -1
                                )
                            );

                            if (!empty($businessDayList)) {
                                $bdayIds = array();
                                foreach ($businessDayList as $singleBusinessDay)
                                    $bdayIds[] = $singleBusinessDay['PosBusinessDay']['bday_id'];

                                $employeeCheckPayments = $PosCheckModel->PosCheckPayment->find('all', array(
                                        'conditions' => array(
                                            'PosCheckPayment.cpay_olet_id' => $check['PosCheck']['chks_olet_id'],
                                            'PosCheckPayment.cpay_shop_id' => $check['PosCheck']['chks_shop_id'],
                                            'PosCheckPayment.cpay_payment_type' => $checkPayment['cpay_payment_type'],
                                            'PosCheckPayment.cpay_meal_user_id' => $checkPayment['cpay_meal_user_id'],
                                            'PosCheckPayment.cpay_bday_id' => $bdayIds,
                                            'PosCheckPayment.cpay_pay_time <' => $checkPayment['cpay_pay_time'],
                                            'PosCheckPayment.cpay_status' => '',
                                        ),
                                        'recursive' => -1
                                    )
                                );

                                $employeePayTypeTotal = 0;
                                if (!empty($employeeCheckPayments)) {
                                    foreach ($employeeCheckPayments as $employeeCheckPayment)
                                        $employeePayTypeTotal += $employeeCheckPayment['PosCheckPayment']['cpay_pay_total'];

                                    if (strcmp($checkPayment['cpay_payment_type'], "duty_meal") == 0) {
                                        $employeeMaximumCreditLimit = $dutyMealLimit;
                                        $remainingCreditLimit = $dutyMealLimit - $employeePayTypeTotal;
                                    } else if (strcmp($checkPayment['cpay_payment_type'], "on_credit") == 0) {
                                        $employeeMaximumCreditLimit = $onCreditLimit;
                                        $remainingCreditLimit = $onCreditLimit - $employeePayTypeTotal;
                                    }

                                    if ($remainingCreditLimit < 0)
                                        $remainingCreditLimit = 0;
                                } else {
                                    if (strcmp($checkPayment['cpay_payment_type'], "duty_meal") == 0)
                                        $employeeMaximumCreditLimit = $dutyMealLimit;
                                    else if (strcmp($checkPayment['cpay_payment_type'], "on_credit") == 0)
                                        $employeeMaximumCreditLimit = $onCreditLimit;
                                }
                            }
                        }
                    }

                    $paymentVoucherNum = "";
                    $paymentOctopusCardType = "";
                    $paymentOctopusTransactionAmount = "";
                    $paymentOctopusUdsn = "";
                    $paymentOctopusOriginalAmount = "";
                    $paymentOctopusDeviceId = "";
                    $paymentOctopusCurrentAmount = "";
                    $paymentOctopusCardId = "";
                    $paymentOctopusLastAddValueType = "";
                    $paymentOctopusLastAddValueDate = "";
                    $paymentOctopusTransactionTime = "";
                    $paymentRewriteCardOriginalAmount = "";
                    $paymentRewriteCardCurrentAmount = "";
                    $paymentRewriteCardCardNumber = "";
                    $paymentReferenceNum = array("1" => "", "2" => "", "3" => "");
                    if (strcmp($this->__checkStringExist($checkPayment, "cpay_ref_data1"), "") != 0 && json_decode($this->__checkStringExist($checkPayment, "cpay_ref_data1")) != NULL) {
                        $refData1 = json_decode($checkPayment['cpay_ref_data1'], true);
                        if (strcmp($checkPayment['cpay_payment_type'], "voucher") == 0) {
                            //get voucher number if payment type is "voucher"
                            if (isset($refData1['voucher_number']))
                                $paymentVoucherNum = $refData1['voucher_number'];
                        } else if (strcmp($checkPayment['cpay_payment_type'], "octopus") == 0) {
                            //get Octopus payment information if payment type is "octopus"
                            if (isset($refData1['card_type']))
                                $paymentOctopusCardType = $refData1['card_type'];
                            if (isset($refData1['transaction_amount']))
                                $paymentOctopusTransactionAmount = $refData1['transaction_amount'];
                            if (isset($refData1['udsn']))
                                $paymentOctopusUdsn = $refData1['udsn'];
                            if (isset($refData1['original_remain_amount']))
                                $paymentOctopusOriginalAmount = $refData1['original_remain_amount'];
                            if (isset($refData1['device_id']))
                                $paymentOctopusDeviceId = $refData1['device_id'];
                            if (isset($refData1['current_remain_amount']))
                                $paymentOctopusCurrentAmount = $refData1['current_remain_amount'];
                            if (isset($refData1['card_id']))
                                $paymentOctopusCardId = $refData1['card_id'];
                            if (isset($refData1['last_add_value_type']))
                                $paymentOctopusLastAddValueType = $refData1['last_add_value_type'];
                            if (isset($refData1['last_add_value_date']))
                                $paymentOctopusLastAddValueDate = $refData1['last_add_value_date'];
                            if (isset($refData1['transaction_time']))
                                $paymentOctopusTransactionTime = $refData1['transaction_time'];
                        } else if (strcmp($checkPayment['cpay_payment_type'], "rewrite_card") == 0) {
                            if (isset($refData1['card_number']))
                                $paymentRewriteCardCardNumber = $refData1['card_number'];
                            if (isset($refData1['original_remain_amount']))
                                $paymentRewriteCardOriginalAmount = $refData1['original_remain_amount'];
                            if (isset($refData1['current_remain_amount']))
                                $paymentRewriteCardCurrentAmount = $refData1['current_remain_amount'];
                        }
                    } else if (strcmp($this->__checkStringExist($checkPayment, "cpay_ref_data1"), "") != 0)
                        $paymentReferenceNum['1'] = $checkPayment['cpay_ref_data1'];
                    if ($this->__checkNumericExist($checkPayment, "cpay_ref_data1") != 0)
                        $paymentReferenceNum['1'] = $checkPayment['cpay_ref_data1'];

                    if (strcmp($this->__checkStringExist($checkPayment, "cpay_ref_data2"), "") != 0 || $this->__checkNumericExist($checkPayment, "cpay_ref_data2") != 0)
                        $paymentReferenceNum['2'] = $checkPayment['cpay_ref_data2'];

                    if (strcmp($this->__checkStringExist($checkPayment, "cpay_ref_data3"), "") != 0 || $this->__checkNumericExist($checkPayment, "cpay_ref_data3") != 0)
                        $paymentReferenceNum['3'] = $checkPayment['cpay_ref_data3'];

                    $paymentRunningNum = "";
                    $paymentEncryptedEsignature = "";
                    $paymentPms = array('roomNumber' => "", 'guestNumber' => "", 'guestName' => "");
                    $paymentCreditCard = array('cardNo' => "", 'expDate' => "", 'holderName' => "", 'cardTypeName' => "", 'merchantNumber' => "", 'batchNumber' => '', 'terminalNumber' => '', 'approvalCode' => '', 'referenceNumber' => '');
                    $paymentMembershipIntf = array('accountNumber' => "", 'traceId' => "", 'authCode' => "", 'localBalance' => "", 'accountName' => "", 'englishName' => "", 'cardTypeName' => "", 'printLine1' => "", 'printLine2' => "", 'pointsBalance' => "", 'pointsEarn' => "", 'cardSn' => "", 'expiryDate' => "", 'cardLevelName' => "", "memberNumber" => "", "memberName" => "", "memberType" => "", "couponNumber" => "", "couponFaceAmount" => "",
                        'cardStoreValueUsed' => "", 'pointsUsed' => "", 'cardNo' => "", 'awardCode' => "", 'pointRedeem' => "", 'pointsReturned' => "", 'cancelAwardNumber' => "");
                    $paymentGamingIntf = array("memberNumber" => "", "memberName" => "", 'cardNo' => "", 'inputMethod' => "", 'staffId' => "", 'remark' => "", 'userNumber' => "", 'pointsBalance' => "", "pointsDepartment" => "", "giftCertId" => "", "couponId" => "", 'accountNumber' => "", 'cardType' => "", 'firstName' => "", 'lastName' => "", 'referenceNo' => "", 'compNumber' => "", 'transactionKey' => "");
                    $paymentLoyaltyIntf = array('cardNo' => "", 'originalAmount' => "", 'currentAmount' => "");
                    $paymentPaymentIntf = array('merchantName' => "", 'merchantNumber' => "", 'transactionTime' => "", 'transactionNum' => "", 'payAmount' => 0, 'invoiceAmount' => 0, 'channelTransactionNum' => "", 'accountNumber' => "", 'pointsBalance' => "", 'platformTransactionNum' => "", 'cardNo' => "");
                    $paymentVoucherIntf = array('voucherNumber' => "");
                    $paymentLoyaltySVCInfo = array('svcCardNumber' => "", 'svcRemainingBalance' => "");
                    $oddEvenAmount = "";
                    if (isset($checkPayment['checkExtraInfos'])) {
                        $paymentExtraInfos = $checkPayment['checkExtraInfos'];
                        if (!empty($paymentExtraInfos)) {
                            foreach ($paymentExtraInfos as $paymentExtraInfo) {
                                if ($paymentExtraInfo['ckei_by'] == "payment" && (isset($paymentExtraInfo['ckei_section']) && ($paymentExtraInfo['ckei_section'] == "pms" || $paymentExtraInfo['ckei_section'] == "credit_card" || $paymentExtraInfo['ckei_section'] == "membership_interface" || $paymentExtraInfo['ckei_section'] == "payment_interface" || $paymentExtraInfo['ckei_section'] == "loyalty_svc" || $paymentExtraInfo['ckei_section'] == "loyalty" || $paymentExtraInfo['ckei_section'] == "gaming_interface" || $paymentExtraInfo['ckei_section'] == "voucher_interface"))) {
                                    switch ($paymentExtraInfo['ckei_variable']) {
                                        case "room":
                                            $paymentPms['roomNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "guest_no":
                                            $paymentPms['guestNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "guest_name":
                                            $paymentPms['guestName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "card_no":
                                            if ($paymentExtraInfo['ckei_section'] == "loyalty")
                                                $paymentLoyaltyIntf['cardNo'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else if ($paymentExtraInfo['ckei_section'] == "membership_interface") {
                                                $paymentMembershipIntf["cardNo"] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                                if (!empty($paymentMembershipIntf["cardNo"]))
                                                    $vars['CheckMembershipIntfAttachedAtPayment'] = 1;
                                            } else if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                                $paymentGamingIntf['cardNo'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else
                                                $paymentCreditCard["cardNo"] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "exp_date":
                                            if ($paymentExtraInfo['ckei_section'] == "membership_interface")
                                                $paymentMembershipIntf['expiryDate'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else
                                                $paymentCreditCard["expDate"] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "holder_name":
                                            $paymentCreditCard["holderName"] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "account_number":
                                            if ($paymentExtraInfo['ckei_section'] == "payment_interface")
                                                $paymentPaymentIntf['accountNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                                $paymentGamingIntf['accountNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else
                                                $paymentMembershipIntf['accountNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "trace_id":
                                            if ($paymentExtraInfo['ckei_section'] == "payment_interface")
                                                $paymentPaymentIntf['transactionNum'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else
                                                $paymentMembershipIntf['traceId'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "auth_code":
                                            $paymentMembershipIntf['authCode'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "spa_standard_masked_pan":
                                            $paymentPaymentIntf['cardNo'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "local_balance":
                                            $paymentMembershipIntf['localBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "account_name":
                                            $paymentMembershipIntf['accountName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "english_name":
                                            $paymentMembershipIntf['englishName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "card_type_name":
                                            if ($paymentExtraInfo['ckei_section'] == "credit_card")
                                                $paymentCreditCard['cardTypeName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else
                                                $paymentMembershipIntf['cardTypeName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "print_line":
                                            if ($paymentExtraInfo['ckei_index'] == 1)
                                                $paymentMembershipIntf['printLine1'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else if ($paymentExtraInfo['ckei_index'] == 2)
                                                $paymentMembershipIntf['printLine2'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "points_balance":
                                            if ($paymentExtraInfo['ckei_section'] == "payment_interface")
                                                $paymentPaymentIntf['pointsBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else if ($paymentExtraInfo['ckei_section'] == "loyalty")
                                                $paymentLoyaltyIntf['originalAmount'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                                $paymentGamingIntf['pointsBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else
                                                $paymentMembershipIntf['pointsBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "points_earn":
                                            $paymentMembershipIntf['pointsEarn'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "card_sn":
                                            $paymentMembershipIntf['cardSn'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "expiry_date":
                                            $paymentMembershipIntf['expiryDate'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "card_level_name":
                                            $paymentMembershipIntf['cardLevelName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "member_number":
                                            if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                                $paymentGamingIntf['memberNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else {
                                                $paymentMembershipIntf['memberNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                                if (!empty($paymentMembershipIntf['memberNumber']))
                                                    $vars['CheckMembershipIntfAttachedAtPayment'] = 1;
                                            }
                                            break;
                                        case "member_name":
                                            if ($paymentExtraInfo['ckei_section'] == "gaming_interface") {
                                                $paymentGamingIntf['memberName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                                $paymentGamingIntf['firstName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            } else
                                                $paymentMembershipIntf['memberName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "svc_coupon_number":
                                        case "voucher_number":
                                            if ($paymentExtraInfo['ckei_section'] == "voucher_interface")
                                                $paymentVoucherIntf['voucherNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else
                                                $paymentMembershipIntf['couponNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "svc_coupon_amount":
                                        case "voucher_value":
                                            $paymentMembershipIntf['couponFaceAmount'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "terminal_number":
                                            $paymentCreditCard['terminalNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "merchant_number":
                                            $paymentCreditCard['merchantNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "batch_number":
                                            $paymentCreditCard['batchNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "approval_code":
                                            $paymentCreditCard['approvalCode'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "reference":
                                            if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                                $paymentGamingIntf['referenceNo'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            else
                                                $paymentCreditCard['referenceNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "internal_use":
                                            $internalUse = (isset($paymentExtraInfo['ckei_value'])) ? json_decode($paymentExtraInfo['ckei_value'], true) : "";
                                            if (empty($internalUse))
                                                break;

                                            if ($paymentExtraInfo['ckei_section'] == "payment_interface") {
                                                if (isset($internalUse['merchantName']))
                                                    $paymentPaymentIntf['merchantName'] = $internalUse['merchantName'];
                                                if (isset($internalUse['merchantId']))
                                                    $paymentPaymentIntf['merchantNumber'] = $internalUse['merchantId'];
                                                if (isset($internalUse['transactionTime']))
                                                    $paymentPaymentIntf['transactionTime'] = $internalUse['transactionTime'];
                                                if (isset($internalUse['transactionPayTotal']))
                                                    $paymentPaymentIntf['payAmount'] = $internalUse['transactionPayTotal'];
                                                if (isset($internalUse['invoiceTotal']))
                                                    $paymentPaymentIntf['invoiceAmount'] = $internalUse['invoiceTotal'];
                                                if (isset($internalUse['channelTransactionNum']))
                                                    $paymentPaymentIntf['channelTransactionNum'] = $internalUse['channelTransactionNum'];
                                                if (isset($internalUse['platformTransactionNum']))
                                                    $paymentPaymentIntf['platformTransactionNum'] = $internalUse['platformTransactionNum'];
                                            }
                                            break;
                                        case "e_signature":
                                            $paymentEncryptedEsignature = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "points_use":
                                            $paymentMembershipIntf['pointsUsed'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "card_store_value_used":
                                            $paymentMembershipIntf['cardStoreValueUsed'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "svc_card_number":
                                            $paymentLoyaltySVCInfo['svcCardNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "svc_remaining_balance":
                                            $paymentLoyaltySVCInfo['svcRemainingBalance'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "total_points_balance":
                                            if ($paymentExtraInfo['ckei_section'] == "loyalty")
                                                $paymentLoyaltyIntf['currentAmount'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "input_type":
                                            $paymentGamingIntf['inputMethod'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "user_id":
                                            $paymentGamingIntf['userNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "staff_id":
                                            $paymentGamingIntf['staffId'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "remark":
                                            $paymentGamingIntf['remark'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "points_department":
                                            $paymentGamingIntf['pointsDepartment'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "gift_cert_id":
                                            $paymentGamingIntf['giftCertId'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "coupon":
                                            $paymentGamingIntf['couponId'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "member_last_name":
                                            if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                                $paymentGamingIntf['lastName'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "payment_info":
                                            if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                                $paymentGamingIntf['compNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "posting_key":
                                            if ($paymentExtraInfo['ckei_section'] == "gaming_interface")
                                                $paymentGamingIntf['transactionKey'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "award_code":
                                            $paymentMembershipIntf['awardCode'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "point_redeem":
                                            $paymentMembershipIntf['pointRedeem'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "points_returned":
                                            $paymentMembershipIntf['pointsReturned'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "cancel_award_number":
                                            $paymentMembershipIntf['cancelAwardNumber'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        case "member_type":
                                            $paymentMembershipIntf['memberType'] = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                            break;
                                        default:
                                            break;
                                    }
                                } else if ($paymentExtraInfo['ckei_by'] == "payment" && $paymentExtraInfo['ckei_variable'] == "running_number")
                                    $paymentRunningNum = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                                else if ($paymentExtraInfo['ckei_by'] == "payment" && $paymentExtraInfo['ckei_variable'] == "payment_info")
                                    $oddEvenAmount = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                            }
                        }
                    }

                    $Payments[] = array(
                        'PaymentName' => $this->__checkStringExist($checkPayment, "cpay_name_l" . $prtFmtDefaultLang),
                        'PaymentNameL1' => $this->__checkStringExist($checkPayment, "cpay_name_l1"),
                        'PaymentNameL2' => $this->__checkStringExist($checkPayment, "cpay_name_l2"),
                        'PaymentNameL3' => $this->__checkStringExist($checkPayment, "cpay_name_l3"),
                        'PaymentNameL4' => $this->__checkStringExist($checkPayment, "cpay_name_l4"),
                        'PaymentNameL5' => $this->__checkStringExist($checkPayment, "cpay_name_l5"),
                        'PaymentCode' => (!empty($paymentCode)) ? $paymentCode : "",
                        'PaymentAmount' => number_format($this->__checkNumericExist($checkPayment, "cpay_pay_total"), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentTotal' => number_format(($this->__checkNumericExist($checkPayment, "cpay_pay_total") + $this->__checkNumericExist($checkPayment,
                                "cpay_pay_tips")), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentTips' => number_format($paymentTips, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentResidue' => number_format($paymentResidue, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentChanges' => number_format($this->__checkNumericExist($checkPayment, "cpay_pay_change"), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentSurcharge' => number_format($this->__checkNumericExist($checkPayment, "cpay_pay_surcharge"), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentType' => $this->__checkStringExist($checkPayment, "cpay_payment_type"),
                        'PaymentMemberNum' => (!empty($paymentMember)) ? $paymentMember['MemMember']['memb_number'] : "",
                        'PaymentMemberName1' => (!empty($paymentMember)) ? ($paymentMember['MemMember']['memb_last_name_l1'] . ' ' . $paymentMember['MemMember']['memb_first_name_l1']) : "",
                        'PaymentMemberName2' => (!empty($paymentMember)) ? ($paymentMember['MemMember']['memb_last_name_l2'] . ' ' . $paymentMember['MemMember']['memb_first_name_l2']) : "",
                        'PaymentMemberDisplayName' => (!empty($paymentMember)) ? $paymentMember['MemMember']['memb_display_name'] : "",
                        'PaymentMemberSpending' => $paymentMemberSpending,
                        'PaymentEmployeeNum' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_number'] : "",
                        'PaymentEmployeeName' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] . ' ' . $paymentEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang]) : "",
                        'PaymentEmployeeNameL1' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l1'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l1']) : "",
                        'PaymentEmployeeNameL2' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l2'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l2']) : "",
                        'PaymentEmployeeNameL3' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l3'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l3']) : "",
                        'PaymentEmployeeNameL4' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l4'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l4']) : "",
                        'PaymentEmployeeNameL5' => (!empty($paymentEmployee)) ? ($paymentEmployee['UserUser']['user_last_name_l5'] . ' ' . $paymentEmployee['UserUser']['user_first_name_l5']) : "",
                        'PaymentEmployeeFirstName' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang] : "",
                        'PaymentEmployeeFirstNameL1' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l1'] : "",
                        'PaymentEmployeeFirstNameL2' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l2'] : "",
                        'PaymentEmployeeFirstNameL3' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l3'] : "",
                        'PaymentEmployeeFirstNameL4' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l4'] : "",
                        'PaymentEmployeeFirstNameL5' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_first_name_l5'] : "",
                        'PaymentEmployeeLastName' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang] : "",
                        'PaymentEmployeeLastNameL1' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l1'] : "",
                        'PaymentEmployeeLastNameL2' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l2'] : "",
                        'PaymentEmployeeLastNameL3' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l3'] : "",
                        'PaymentEmployeeLastNameL4' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l4'] : "",
                        'PaymentEmployeeLastNameL5' => (!empty($paymentEmployee)) ? $paymentEmployee['UserUser']['user_last_name_l5'] : "",
                        'PaymentEmployeeRemainingLimit' => $remainingCreditLimit,
                        'PaymentEmployeeMaxLimit' => $employeeMaximumCreditLimit,
                        'PaymentVoucherNum' => $paymentVoucherNum,
                        'PaymentOctopusCardType' => $paymentOctopusCardType,
                        'PaymentOctopusTransactionAmount' => $paymentOctopusTransactionAmount,
                        'PaymentOctopusUdsn' => $paymentOctopusUdsn,
                        'PaymentOctopusOriginalAmount' => $paymentOctopusOriginalAmount,
                        'PaymentOctopusDeviceId' => $paymentOctopusDeviceId,
                        'PaymentOctopusCurrentAmount' => $paymentOctopusCurrentAmount,
                        'PaymentOctopusCardId' => $paymentOctopusCardId,
                        'PaymentOctopusLastAddValueType' => $paymentOctopusLastAddValueType,
                        'PaymentOctopusLastAddValueDate' => $paymentOctopusLastAddValueDate,
                        'PaymentOctopusTransactionTime' => $paymentOctopusTransactionTime,
                        'PaymentPmsRoomNumber' => $paymentPms['roomNumber'],
                        'PaymentPmsGuestNumber' => $paymentPms['guestNumber'],
                        'PaymentPmsGuestName' => $paymentPms['guestName'],
                        'PaymentNonGUI' => (isset($checkPayment['cpay_special']) && $checkPayment['cpay_special'] == 'g') ? 1 : 0,
                        'PaymentCreditCardNum' => $paymentCreditCard['cardNo'],
                        'PaymentCreditCardExpDate' => $paymentCreditCard['expDate'],
                        'PaymentCreditCardHolderName' => $paymentCreditCard['holderName'],
                        'PaymentCreditCardTerminalNumber' => $paymentCreditCard['terminalNumber'],
                        'PaymentCreditCardBatchNumber' => $paymentCreditCard['batchNumber'],
                        'PaymentCreditCardApprovalCode' => $paymentCreditCard['approvalCode'],
                        'PaymentCreditCardMerchantNumber' => $paymentCreditCard['merchantNumber'],
                        'PaymentCreditCardReferenceNumber' => $paymentCreditCard['referenceNumber'],
                        'PaymentCreditCardTypeName' => $paymentCreditCard['cardTypeName'],
                        'PaymentRewriteCardOriginalAmount' => $paymentRewriteCardOriginalAmount,
                        'PaymentRewriteCardCurrentAmount' => $paymentRewriteCardCurrentAmount,
                        'PaymentRewriteCardCardNumber' => $paymentRewriteCardCardNumber,
                        'PaymentMembershipIntfAccountNumber' => $paymentMembershipIntf['accountNumber'],
                        'PaymentMembershipIntfTraceID' => $paymentMembershipIntf['traceId'],
                        'PaymentMembershipIntfAuthorityCode' => $paymentMembershipIntf['authCode'],
                        'PaymentMembershipIntfAwardCode' => $paymentMembershipIntf['awardCode'],
                        'PaymentMembershipIntfCancelAwardNumber' => $paymentMembershipIntf['cancelAwardNumber'],
                        'PaymentMembershipIntfLocalBalance' => $paymentMembershipIntf['localBalance'],
                        'PaymentMembershipIntfAccountName' => $paymentMembershipIntf['accountName'],
                        'PaymentMembershipIntfEnglishName' => $paymentMembershipIntf['englishName'],
                        'PaymentMembershipIntfCardTypeName' => $paymentMembershipIntf['cardTypeName'],
                        'PaymentMembershipIntfPrintLine1' => $paymentMembershipIntf['printLine1'],
                        'PaymentMembershipIntfPrintLine2' => $paymentMembershipIntf['printLine2'],
                        'PaymentMembershipIntfPointBalance' => $paymentMembershipIntf['pointsBalance'],
                        'PaymentMembershipIntfPointEarn' => $paymentMembershipIntf['pointsEarn'],
                        'PaymentMembershipIntfPointsReturned' => $paymentMembershipIntf['pointsReturned'],
                        'PaymentMembershipIntfCardSN' => $paymentMembershipIntf['cardSn'],
                        'PaymentMembershipIntfExpiryDate' => $paymentMembershipIntf['expiryDate'],
                        'PaymentMembershipIntfCardLevelName' => $paymentMembershipIntf['cardLevelName'],
                        'PaymentMembershipIntfCardNumber' => $paymentMembershipIntf['cardNo'],
                        'PaymentMembershipIntfMemberNumber' => $paymentMembershipIntf['memberNumber'],
                        'PaymentMembershipIntfMemberName' => $paymentMembershipIntf['memberName'],
                        'PaymentMembershipIntfMemberType' => $paymentMembershipIntf['memberType'],
                        'PaymentMembershipIntfCouponNumber' => $paymentMembershipIntf['couponNumber'],
                        'PaymentMembershipIntfCouponFaceAmt' => $paymentMembershipIntf['couponFaceAmount'],
                        'PaymentMembershipIntfPointRedeem' => $paymentMembershipIntf['pointRedeem'],
                        'PaymentMembershipIntfPointUsed' => $paymentMembershipIntf['pointsUsed'],
                        'PaymentMembershipInfCardStoreValueUsed' => $paymentMembershipIntf['cardStoreValueUsed'],
                        'PaymentByForeignCurrency' => ($this->__checkStringExist($checkPayment, "cpay_pay_foreign_currency") == 'y') ? 1 : 0,
                        'PaymentChangesBackForeignCurrency' => ($this->__checkStringExist($checkPayment, "cpay_change_back_currency") == 'f') ? 1 : 0,
                        'PaymentAmountInForeignCurrency' => ($paymentMethod != null) ? number_format($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_total"), $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_total"), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentTotalInForeignCurrency' => ($paymentMethod != null) ? number_format(($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_total") + $this->__checkNumericExist($checkPayment, "cpay_pay_foreign_tips")), $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format(($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_total") + $this->__checkNumericExist($checkPayment, "cpay_pay_foreign_tips")), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentTipsInForeignCurrency' => $paymentTipsInForeignCurrency,
                        'PaymentResidueInForeignCurrency' => $paymentResidueInForeignCurrency,
                        'PaymentChangesInForeignCurrency' => ($paymentMethod != null) ? number_format($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_change"), $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_change"), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentSurchargeInForeignCurrency' => ($paymentMethod != null) ? number_format($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_surcharge"), $paymentMethod['PosPaymentMethod']['paym_currency_decimal'], ".", "") : number_format($this->__checkNumericExist($checkPayment, "cpay_pay_foreign_surcharge"), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                        'PaymentPayInfMerchantName' => $paymentPaymentIntf['merchantName'],
                        'PaymentPayInfMerchantNumber' => $paymentPaymentIntf['merchantNumber'],
                        'PaymentPayInfTransactionTime' => $paymentPaymentIntf['transactionTime'],
                        'PaymentPayInfTransactionNum' => $paymentPaymentIntf['transactionNum'],
                        'PaymentPayInfPayAmount' => $paymentPaymentIntf['payAmount'],
                        'PaymentPayInfInvoiceAmount' => $paymentPaymentIntf['invoiceAmount'],
                        'PaymentPayInfChannelTransactionNum' => $paymentPaymentIntf['channelTransactionNum'],
                        'PaymentPayInfAccountNum' => $paymentPaymentIntf['accountNumber'],
                        'PaymentPayInfPointsBalance' => $paymentPaymentIntf['pointsBalance'],
                        'PaymentPayInfPlatformTransactionNum' => $paymentPaymentIntf['platformTransactionNum'],
                        'PaymentPayInfCardNumber' => $paymentPaymentIntf['cardNo'],
                        'PaymentVoucherIntfVoucherNumber' => $paymentVoucherIntf['voucherNumber'],
                        'PaymentRunningNumber' => $paymentRunningNum,
                        'PaymentReferenceNum1' => $paymentReferenceNum['1'],
                        'PaymentReferenceNum2' => $paymentReferenceNum['2'],
                        'PaymentReferenceNum3' => $paymentReferenceNum['3'],
                        'PaymentESignature' => $paymentEncryptedEsignature,
                        'PaymentLoyaltySVCCardNumber' => $paymentLoyaltySVCInfo['svcCardNumber'],
                        'PaymentLoyaltySVCRemainingBalance' => $paymentLoyaltySVCInfo['svcRemainingBalance'],
                        'PaymentLoyaltyCardNumber' => $paymentLoyaltyIntf['cardNo'],
                        'PaymentLoyaltyOriginalAmount' => $paymentLoyaltyIntf['originalAmount'],
                        'PaymentLoyaltyCurrentAmount' => $paymentLoyaltyIntf['currentAmount'],
                        'PaymentGamingIntfMemberName' => $paymentGamingIntf['memberName'],
                        'PaymentGamingIntfMemberNumber' => $paymentGamingIntf['memberNumber'],
                        'PaymentGamingIntfMemberCardNumber' => $paymentGamingIntf['cardNo'],
                        'PaymentGamingIntfInputMethod' => $paymentGamingIntf['inputMethod'],
                        'PaymentGamingIntfStaffId' => $paymentGamingIntf['staffId'],
                        'PaymentGamingIntfRemark' => $paymentGamingIntf['remark'],
                        'PaymentGamingIntfUserNumber' => $paymentGamingIntf['userNumber'],
                        'PaymentGamingIntfPointBalance' => $paymentGamingIntf['pointsBalance'],
                        'PaymentGamingIntfPointDepartment' => $paymentGamingIntf['pointsDepartment'],
                        'PaymentGamingIntfGiftCertId' => $paymentGamingIntf['giftCertId'],
                        'PaymentGamingIntfCouponId' => $paymentGamingIntf['couponId'],
                        'PaymentGamingIntfAccountNumber' => $paymentGamingIntf['accountNumber'],
                        'PaymentGamingIntfCardType' => $paymentGamingIntf['cardType'],
                        'PaymentGamingIntfMemberFirstName' => $paymentGamingIntf['firstName'],
                        'PaymentGamingIntfMemberLastName' => $paymentGamingIntf['lastName'],
                        'PaymentGamingIntfReferenceNumber' => $paymentGamingIntf['referenceNo'],
                        'PaymentGamingIntfCompNumber' => $paymentGamingIntf['compNumber'],
                        'PaymentGamingIntfTransactionKey' => $paymentGamingIntf['transactionKey'],
                        'OddEvenAmount' => $oddEvenAmount
                    );
                }
            }

            //get release payment information
            if ($this->__checkNumericExist($check['PosCheck'], "chks_id") > 0) {
                $voidedPayments = $PosCheckModel->PosCheckPayment->findAllVoidByCheckId($check['PosCheck']['chks_id']);
                if (!empty($voidedPayments)) {
                    $currentVoidTime = "";
                    $previousVoidTime = "";
                    $releasePaymentCount = 0;
                    $releasePaymentTotal = array();
                    $posCheckExtraInfoModel = new PosCheckExtraInfo();
                    $voidedPaymentExtraInfos = $posCheckExtraInfoModel->findAllByConfigAndCheckId('payment', $check['PosCheck']['chks_id'], "d", -1);
                    $latestVoidTime = "";
                    $latestIndex = array();
                    for ($index = 0; $index < count($voidedPayments); $index++) {
                        $currentVoidTime = $voidedPayments[$index]['PosCheckPayment']['cpay_void_loctime'];
                        if (strcmp($previousVoidTime, $currentVoidTime) != 0) {
                            $releasePaymentCount++;
                            $releasePaymentTotal[$releasePaymentCount] = 0;
                        }
                        $releasePaymentTotal[$releasePaymentCount] += $voidedPayments[$index]['PosCheckPayment']['cpay_pay_total'];
                        $previousVoidTime = $currentVoidTime;
                        if ($index == 0) {
                            $latestVoidTime = $currentVoidTime;
                            $latestIndex[] = $index;
                        } else {
                            if (strcmp($latestVoidTime, $currentVoidTime) < 0) {
                                $latestVoidTime = $currentVoidTime;
                                unset($latestIndex);
                                $latestIndex[] = $index;
                            } else if (strcmp($latestVoidTime, $currentVoidTime) == 0)
                                $latestIndex[] = $index;
                        }
                        $paymentExtraInfos = $this->__getExtraInfo($voidedPaymentExtraInfos, $voidedPayments[$index]['PosCheckPayment']['cpay_id'], 0);
                        $paymentRunningNum = "";
                        if (!empty($paymentExtraInfos)) {
                            foreach ($paymentExtraInfos as $paymentExtraInfo) {
                                if ($paymentExtraInfo['ckei_by'] == "payment" && $paymentExtraInfo['ckei_variable'] == "running_number")
                                    $paymentRunningNum = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                            }
                        }
                        if (empty($paymentRunningNum))
                            continue;
                        $existSameRunningNumber = 0;
                        foreach ($AllReleasePaymentRunningNumber as $eachRunningNumber) {
                            if (in_array($paymentRunningNum, $eachRunningNumber)) {
                                $existSameRunningNumber = 1;
                                break;
                            }
                        }
                        if ($existSameRunningNumber == 0)
                            $AllReleasePaymentRunningNumber[] = array("AllReleasePaymentRunningNumber" => $paymentRunningNum);
                    }
                    foreach ($latestIndex as $latestEachIndex) {
                        $paymentExtraInfos = $this->__getExtraInfo($voidedPaymentExtraInfos, $voidedPayments[$latestEachIndex]['PosCheckPayment']['cpay_id'], 0);
                        $paymentRunningNum = "";
                        if (!empty($paymentExtraInfos)) {
                            foreach ($paymentExtraInfos as $paymentExtraInfo) {
                                if ($paymentExtraInfo['ckei_by'] == "payment" && $paymentExtraInfo['ckei_variable'] == "running_number")
                                    $paymentRunningNum = (isset($paymentExtraInfo['ckei_value'])) ? $paymentExtraInfo['ckei_value'] : "";
                            }
                        }
                        if (empty($paymentRunningNum))
                            continue;
                        $existSameRunningNumber = 0;
                        foreach ($LastReleasePaymentRunningNumber as $eachRunningNumber) {
                            if (in_array($paymentRunningNum, $eachRunningNumber)) {
                                $existSameRunningNumber = 1;
                                break;
                            }
                        }
                        if ($existSameRunningNumber == 0)
                            $LastReleasePaymentRunningNumber[] = array("LastReleasePaymentRunningNumber" => $paymentRunningNum);
                    }

                    $vars['CheckReleasePaymentCount'] = $releasePaymentCount;
                    $vars['CheckLastReleasePaymentTotal'] = number_format($releasePaymentTotal[$releasePaymentCount], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
                }
            }
        } else {
            if (isset($checkInfo['checkPayments'])) {
                foreach ($checkInfo['checkPayments'] as $checkPayment)
                    $previousPaidPaymentTotal += $checkPayment['cpay_pay_total'];
            }
        }
        $unpaidPaymentTotal = $vars['CheckTotal'] - $previousPaidPaymentTotal - $currentPaidPaymentTotal;

        $vars['PayAmountTotal'] = number_format($paymentAmountTotal, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        $vars['CheckPreviousPaidPaymentTotal'] = number_format($previousPaidPaymentTotal, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        $vars['CheckCurrentPaidPaymentTotal'] = number_format($currentPaidPaymentTotal, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        $vars['CheckUnpaidPaymentTotal'] = number_format($unpaidPaymentTotal, $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");
        $vars['TipsTotal'] = number_format($totalTips, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        $vars['Changes'] = number_format($totalChange, $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "");
        $vars['CheckIsPaid'] = ($checkInfo['posCheck']['chks_paid'] != 'f') ? '0' : '1';
        if ($prtFmtDefaultLang > 0) {
            if ($checkCloseUser != null) {
                $vars['CheckCloseEmployeeFirstName'] = $this->__checkStringExist($checkCloseUser, "user_first_name_l" . $prtFmtDefaultLang);
                $vars['CheckCloseEmployeeLastName'] = $this->__checkStringExist($checkCloseUser, "user_last_name_l" . $prtFmtDefaultLang);
                $vars['CheckCloseEmployee'] = $vars['CheckCloseEmployeeLastName'] . ' ' . $vars['CheckCloseEmployeeFirstName'];
            }
        }
        for ($index = 1; $index <= 5; $index++) {
            if ($checkCloseUser != null) {
                $vars['CheckCloseEmployeeFirstNameL' . $index] = $this->__checkStringExist($checkCloseUser, "user_first_name_l" . $index);
                $vars['CheckCloseEmployeeLastNameL' . $index] = $this->__checkStringExist($checkCloseUser, "user_last_name_l" . $index);
                $vars['CheckCloseEmployeeL' . $index] = $vars['CheckCloseEmployeeLastNameL' . $index] . ' ' . $vars['CheckCloseEmployeeFirstNameL' . $index];
            }
        }
        $vars['CheckCloseTime'] = date('H:i:s', strtotime($check['PosCheck']['chks_close_loctime']));

        $checkPrintDate = (isset($check['PosCheck']['chks_print_loctime'])) ? date('Y-m-d', strtotime($check['PosCheck']['chks_print_loctime'])) : "";

        $outletDateFormat = 0;
        $outletDateFormat = $this->__checkNumericExist($outlet['OutOutlet'], "olet_date_format");

        $this->__updateDateFormat($printFormat, $outletDateFormat, $shop['OutShop']['shop_timezone'], $shop['OutShop']['shop_timezone_name'], $businessDay['PosBusinessDay']['bday_date'], $check['PosCheck']['chks_open_loctime'], $checkPrintDate, $check['PosCheck']['chks_close_loctime'], $vars, "generateCheckSlip");

        $gratuityTotal = 0;
        $vars['Gratuity'] = array();
        $posCheckGratuities = $PosCheckGratuityModel->findAllByOutletAndCheckId($check['PosCheck']['chks_olet_id'], $check['PosCheck']['chks_id']);
        foreach ($posCheckGratuities as $posCheckGratuity) {
            $gratuityName = $posCheckGratuity['PosCheckGratuity']['cgra_name_l' . $prtFmtDefaultLang];
            $gratuityNameL1 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l1'];
            $gratuityNameL2 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l2'];
            $gratuityNameL3 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l3'];
            $gratuityNameL4 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l4'];
            $gratuityNameL5 = $posCheckGratuity['PosCheckGratuity']['cgra_name_l5'];
            $gratuityAmount = $posCheckGratuity['PosCheckGratuity']['cgra_round_total'];
            $gratuityTotal += $posCheckGratuity['PosCheckGratuity']['cgra_round_total'];
            $gratuity = array('GratuityName' => $gratuityName, 'GratuityAmount' => $gratuityAmount);

            // Gratuity Eligibile printing variable
            if ($isWohModelExist && !empty($wohAwardSettingLists)) {
                // Hyatt Point Redemption handling
                if (in_array($posCheckGratuity['PosCheckGratuity']['cgra_grat_id'], $wohAwardSettingLists['wohEligibleAwdGratuityIds']))
                    $gratuity['GratuityEligibleForPointRedemption'] = true;
                else
                    $gratuity['GratuityEligibleForPointRedemption'] = false;

                // Hyatt Earn Point handling
                if (in_array($posCheckGratuity['PosCheckGratuity']['cgra_grat_id'], $wohAwardSettingLists['wohEligibleEarningGratuityIds']))
                    $gratuity['GratuityEligibleForEarnPoint'] = true;
                else
                    $gratuity['GratuityEligibleForEarnPoint'] = false;
            }

            $vars['Gratuity'][] = $gratuity;
        }
        $vars['GratuityTotal'] = $gratuityTotal;
        $vars['AuthAcquirerData'] = "";
        $vars['AuthAcquirerDatetime'] = "";
        $vars['AuthAcquirerMerchant'] = "";
        $vars['AuthAcquirerName'] = "";
        $vars['AuthAcquirerTerminal'] = "";
        $vars['AuthAmount'] = "";
        $vars['AuthAmountTotal'] = "";
        $vars['AuthCardNumber'] = "";
        $vars['AuthCode'] = "";
        $vars['AuthCurrencyCode'] = "";
        $vars['AuthCustomerData'] = "";
        $vars['AuthECashBalance'] = "";
        $vars['AuthEmployeeCode'] = "";
        $vars['AuthEmployeeName'] = "";
        $vars['AuthEmployeeNameL1'] = "";
        $vars['AuthEmployeeNameL2'] = "";
        $vars['AuthEmployeeNameL3'] = "";
        $vars['AuthEmployeeNameL4'] = "";
        $vars['AuthEmployeeNameL5'] = "";
        $vars['AuthEmployeeFirstName'] = "";
        $vars['AuthEmployeeFirstNameL1'] = "";
        $vars['AuthEmployeeFirstNameL2'] = "";
        $vars['AuthEmployeeFirstNameL3'] = "";
        $vars['AuthEmployeeFirstNameL4'] = "";
        $vars['AuthEmployeeFirstNameL5'] = "";
        $vars['AuthEmployeeLastName'] = "";
        $vars['AuthEmployeeLastNameL1'] = "";
        $vars['AuthEmployeeLastNameL2'] = "";
        $vars['AuthEmployeeLastNameL3'] = "";
        $vars['AuthEmployeeLastNameL4'] = "";
        $vars['AuthEmployeeLastNameL5'] = "";
        $vars['AuthEmv'] = "";
        $vars['AuthEmvData'] = "";
        $vars['AuthEntryMode'] = "";
        $vars['AuthIcCardSequence'] = "";
        $vars['AuthInvoiceNumber'] = "";
        $vars['AuthIssuer'] = "";
        $vars['AuthIntlCardTraceNum'] = "";
        $vars['AuthReferenceNumber'] = "";
        $vars['AuthSignFree'] = "";
        $vars['AuthSignFreeData'] = "";
        $vars['AuthSlipType'] = "";
        $vars['AuthTerminalSequence'] = "";
        $vars['AuthTips'] = "";
        $vars['AuthTransactionDateTime'] = "";
        $vars['AuthTraceNumber'] = "";
        $cardAuthorizations = array();
        if (isset($check['PosPaymentGatewayTransaction'])) {
            $TempCardAuthorizations = $check['PosPaymentGatewayTransaction'];
            foreach ($check['PosPaymentGatewayTransaction'] as $cardAuthorization) {
                if (isset($cardAuthorization['PosPaymentGatewayTransaction']))
                    $cardAuthorization = $cardAuthorization['PosPaymentGatewayTransaction'];
                $acquirerInfo = $cardAuthorization['pgtx_acquirer_info'];
                $otherInfo = $cardAuthorization['pgtx_other_info'];
                // cal the amount total
                $AuthAmountTotal = 0;
                if ($cardAuthorization['pgtx_type_key'] != "credit_card_complete_auth") {
                    foreach ($TempCardAuthorizations as $TempCardAuthorization) {
                        if ($TempCardAuthorization['pgtx_type_key'] == "credit_card_complete_auth")
                            continue;
                        if (isset($TempCardAuthorization['PosPaymentGatewayTransaction']))
                            $TempCardAuthorization = $TempCardAuthorization['PosPaymentGatewayTransaction'];
                        if ($TempCardAuthorization['pgtx_auth_code'] == $cardAuthorization['pgtx_auth_code']
                            || $TempCardAuthorization['pgtx_parent_auth_code'] == $cardAuthorization['pgtx_auth_code']
                            || ($cardAuthorization['pgtx_parent_auth_code'] != ""
                                && ($TempCardAuthorization['pgtx_auth_code'] == $cardAuthorization['pgtx_parent_auth_code']
                                    || $TempCardAuthorization['pgtx_parent_auth_code'] == $cardAuthorization['pgtx_parent_auth_code'])))
                            $AuthAmountTotal += $TempCardAuthorization['pgtx_amount'];
                    }
                }

                // Get the employer name
                $paymentEmployee = array();
                $authEmployeeName = $authEmployeeNameL1 = $authEmployeeNameL2 = $authEmployeeNameL3 = $authEmployeeNameL4 = $authEmployeeNameL5 = "";
                $authEmployeeFirstName = $authEmployeeFirstNameL1 = $authEmployeeFirstNameL2 = $authEmployeeFirstNameL3 = $authEmployeeFirstNameL4 = $authEmployeeFirstNameL5 = "";
                $authEmployeeLastName = $authEmployeeLastNameL1 = $authEmployeeLastNameL2 = $authEmployeeLastNameL3 = $authEmployeeLastNameL4 = $authEmployeeLastNameL5 = "";
                $paymentEmployee = $UserUserModel->findActiveById($cardAuthorization['pgtx_action_user_id'], -1);
                if ($paymentEmployee != null) {
                    $authEmployeeFirstName = $paymentEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang];
                    $authEmployeeFirstNameL1 = $paymentEmployee['UserUser']['user_first_name_l1'];
                    $authEmployeeFirstNameL2 = $paymentEmployee['UserUser']['user_first_name_l2'];
                    $authEmployeeFirstNameL3 = $paymentEmployee['UserUser']['user_first_name_l3'];
                    $authEmployeeFirstNameL4 = $paymentEmployee['UserUser']['user_first_name_l4'];
                    $authEmployeeFirstNameL5 = $paymentEmployee['UserUser']['user_first_name_l5'];
                    $authEmployeeLastName = $paymentEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang];
                    $authEmployeeLastNameL1 = $paymentEmployee['UserUser']['user_last_name_l1'];
                    $authEmployeeLastNameL2 = $paymentEmployee['UserUser']['user_last_name_l2'];
                    $authEmployeeLastNameL3 = $paymentEmployee['UserUser']['user_last_name_l3'];
                    $authEmployeeLastNameL4 = $paymentEmployee['UserUser']['user_last_name_l4'];
                    $authEmployeeLastNameL5 = $paymentEmployee['UserUser']['user_last_name_l5'];
                    $authEmployeeName = $authEmployeeLastName . ' ' . $authEmployeeFirstName;
                    $authEmployeeNameL1 = $authEmployeeLastNameL1 . ' ' . $authEmployeeFirstNameL1;
                    $authEmployeeNameL2 = $authEmployeeLastNameL2 . ' ' . $authEmployeeFirstNameL2;
                    $authEmployeeNameL3 = $authEmployeeLastNameL3 . ' ' . $authEmployeeFirstNameL3;
                    $authEmployeeNameL4 = $authEmployeeLastNameL4 . ' ' . $authEmployeeFirstNameL4;
                    $authEmployeeNameL5 = $authEmployeeLastNameL5 . ' ' . $authEmployeeFirstNameL5;
                }

                $cardAuthorizations[] = array(
                    "AuthAcquirerData" => $acquirerInfo['data'],
                    "AuthAcquirerDatetime" => $acquirerInfo['datetime'],
                    "AuthAcquirerMerchant" => $acquirerInfo['merchant_id'],
                    "AuthAcquirerName" => $acquirerInfo['name'],
                    "AuthAcquirerTerminal" => $acquirerInfo['terminal'],
                    "AuthAmount" => $cardAuthorization['pgtx_amount'],
                    "AuthAmountTotal" => $AuthAmountTotal,
                    "AuthCardNumber" => $cardAuthorization['pgtx_masked_pan'],
                    "AuthCode" => $cardAuthorization['pgtx_auth_code'],
                    "AuthCurrencyCode" => $otherInfo['currency_code'],
                    "AuthCustomerData" => base64_decode($cardAuthorization['pgtx_customer_copy']),
                    "AuthECashBalance" => $otherInfo['ecash_balance'],
                    "AuthEmployeeCode" => $cardAuthorization['pgtx_action_user_id'],
                    "AuthEmployeeName" => $authEmployeeName,
                    "AuthEmployeeNameL1" => $authEmployeeNameL1,
                    "AuthEmployeeNameL2" => $authEmployeeNameL2,
                    "AuthEmployeeNameL3" => $authEmployeeNameL3,
                    "AuthEmployeeNameL4" => $authEmployeeNameL4,
                    "AuthEmployeeNameL5" => $authEmployeeNameL5,
                    "AuthEmployeeFirstName" => $authEmployeeFirstName,
                    "AuthEmployeeFirstNameL1" => $authEmployeeFirstNameL1,
                    "AuthEmployeeFirstNameL2" => $authEmployeeFirstNameL2,
                    "AuthEmployeeFirstNameL3" => $authEmployeeFirstNameL3,
                    "AuthEmployeeFirstNameL4" => $authEmployeeFirstNameL4,
                    "AuthEmployeeFirstNameL5" => $authEmployeeFirstNameL5,
                    "AuthEmployeeLastName" => $authEmployeeLastName,
                    "AuthEmployeeLastNameL1" => $authEmployeeLastNameL1,
                    "AuthEmployeeLastNameL2" => $authEmployeeLastNameL2,
                    "AuthEmployeeLastNameL3" => $authEmployeeLastNameL3,
                    "AuthEmployeeLastNameL4" => $authEmployeeLastNameL4,
                    "AuthEmployeeLastNameL5" => $authEmployeeLastNameL5,
                    "AuthEmv" => $otherInfo['emv'],
                    "AuthEmvData" => $otherInfo['emv_data'],
                    "AuthEntryMode" => $cardAuthorization['pgtx_entry_mode'],
                    "AuthIcCardSequence" => $otherInfo['ic_card_seq'],
                    "AuthInvoiceNumber" => $cardAuthorization['pgtx_invoice_num'],
                    "AuthIssuer" => $cardAuthorization['pgtx_issuer'],
                    "AuthIntlCardTraceNum" => $otherInfo['intl_card_trace_num'],
                    "AuthMerchantData" => base64_decode($cardAuthorization['pgtx_merchant_copy']),
                    "AuthReferenceNumber" => $cardAuthorization['pgtx_ref_num'],
                    "AuthSignFree" => $otherInfo['sign_free'],
                    "AuthSignFreeData" => $otherInfo['sign_free_data'],
                    "AuthTerminalSequence" => $otherInfo['terminal_seq'],
                    "AuthTips" => $cardAuthorization['pgtx_tips'],
                    "AuthTransactionDateTime" => $cardAuthorization['pgtx_action_time'],
                    "AuthTraceNumber" => $cardAuthorization['pgtx_trace_num']
                );
            }
        }
        $vars['AuthSlipType '] = '';
        $vars['CardAuthorizations'] = $cardAuthorizations;

        //need to enhance later
        $bPrint = false;    // Not print @ browser but at print service instead
        $license = "";
        $language = "eng";
        $stationGroupId = 0;
        if (!empty($station))
            $stationGroupId = $this->__checkNumericExist($station['PosStation'], "stat_stgp_id");
        $printQueue = $this->__checkPrintQueueOverride($printQueueOverrideConditions, $prtqId, $businessDay['PosBusinessDay']['bday_date'], substr($check['PosCheck']['chks_open_loctime'], 11), "", $this->__checkStringExist($checkInfo['tableInfo'], "tableNumber"), $this->__checkNumericExist($checkInfo['tableInfo'], "tableExtension"), "", $stationGroupId, $this->__checkNumericExist($businessPeriod['PosBusinessPeriod'], "bper_perd_id"), $check['PosCheck']['chks_ctyp_id'], $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay, $weekday);

        //Add Item Group Start and Group End
        if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "1")
            $groupTypeName = "ItemNameL1";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "2")
            $groupTypeName = "ItemNameL2";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "3")
            $groupTypeName = "ItemNameL3";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "4")
            $groupTypeName = "ItemNameL4";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "5")
            $groupTypeName = "ItemNameL5";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "c")
            $groupTypeName = "ItemCatName";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "d")
            $groupTypeName = "ItemDept";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "u")
            $groupTypeName = "ItemCourseName";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "s")
            $groupTypeName = "ItemSeatNum";
        else if ($printFormat['PosPrintFormat']['pfmt_sort_item_by1'] == "t")
            $groupTypeName = "ItemId";
        else
            $groupTypeName = "ItemMenuId";
        $currentGroup = "";
        $pastGroup = "";
        for ($index = 0; $index < count($Items); $index++) {
            $currentGroup = $Items[$index][$groupTypeName];
            if (strcmp($currentGroup, $pastGroup) != 0) {
                $Items[$index]['ItemGroupStart'] = 1;
                if (($index - 1) >= 0)
                    $Items[($index - 1)]['ItemGroupEnd'] = 1;
            }
            if ($index == (count($Items) - 1))
                $Items[$index]['ItemGroupEnd'] = 1;
            $pastGroup = $currentGroup;
        }

        // Construct the var array for view vendor
        $vars['Items'] = $Items;
        $vars['CheckDiscounts'] = $CheckDiscounts;
        $vars['DiscTotalByDiscType'] = $DiscTotalByDiscType;
        $vars['CheckExtraCharges'] = $CheckExtraCharges;
        $vars['Departments'] = $Departments;
        $vars['Categories'] = $Categories;
        $vars['Payments'] = $Payments;
        $vars['DefaultPayments'] = $DefaultPayments;
        $vars['AllReleasePaymentRunningNumber'] = $AllReleasePaymentRunningNumber;
        $vars['LastReleasePaymentRunningNumber'] = $LastReleasePaymentRunningNumber;

        // Get the print format template
        $tpls = json_decode($printFormat['PosPrintFormat']['pfmt_format'], true);
        $pageOffset = 0;
        $lineOffset = 0;
        $continuousPrintRecord = '';
        $bContinuousPrint = $this->__checkForContinuousPrint($station['PosStation']['stat_id'], $outlet['OutOutlet']['olet_id']);

        if (($bContinuousPrint) && isset($checkInfo['printInfo']['continuousPrint'])) {
            if (!empty($check['PosCheck']['chks_id'])) {
                $printPositionExtraInfo = $PosCheckExtraInfoModel->findActiveByCheckIdAndVariable($check['PosCheck']['chks_id'], 'check', 'print_position');
                if (!empty($printPositionExtraInfo)) {
                    $continuousPrintRecord = json_decode($printPositionExtraInfo['PosCheckExtraInfo']['ckei_value'], true);
                    if (isset($continuousPrintRecord['page_number']))
                        $pageOffset = $continuousPrintRecord['page_number'];
                    if (isset($continuousPrintRecord['line_number']))
                        $lineOffset = $continuousPrintRecord['line_number'];
                    $vars['ContinuousPrintPageNumber'] = $pageOffset;
                    $vars['ContinuousPrintLineNumber'] = $lineOffset;
                }
            } else if (isset($checkInfo['printInfo']['continuousPrint'])) {
                if (!empty($checkExtraInfos)) {
                    foreach ($checkExtraInfos as $checkExtraInfo) {
                        switch ($this->__checkStringExist($checkExtraInfo, 'ckei_variable')) {
                            case 'print_position':
                                $continuousPrintRecord = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                                break;
                        }
                    }
                }
                if (empty($continuousPrintRecord)) {
                    App::import('Model', 'Pos.PosCheckExtraInfo');
                    $posCheckExtraInfoModel = new PosCheckExtraInfo();
                    $posCheckExtraInfo = $PosCheckExtraInfoModel->findActiveByCheckIdAndVariable($check['PosCheck']['chks_id'], 'check', 'print_position');
                    if (!empty($posCheckExtraInfo))
                        $continuousPrintRecord = $posCheckExtraInfo['PosCheckExtraInfo']['ckei_value'];
                }
                if (isset($checkInfo['printInfo']['continuousPrint'])) {
                    if ($continuousPrintRecord != '') {
                        $continuousPrintRecord = json_decode($continuousPrintRecord, true);
                        if (isset($continuousPrintRecord['page_number']))
                            $pageOffset = $continuousPrintRecord['page_number'];
                        if (isset($continuousPrintRecord['line_number']))
                            $lineOffset = $continuousPrintRecord['line_number'];
                        $vars['ContinuousPrintPageNumber'] = $pageOffset;
                        $vars['ContinuousPrintLineNumber'] = $lineOffset;
                    }
                }
            }
        }

        $printCtrls = array(
            'pageOffset' => $pageOffset,
            'lineOffset' => $lineOffset,
            'mediaUrl' => $this->controller->Common->getDataUrl('media_files/')
        );

        // Output the rendered page into HTML
        if ($type == 3)
            $outputFileName = "serving_list-" . date('YmdHis', time()) . "-" . $check['PosCheck']['chks_check_num'];
        else
            $outputFileName = "bill-" . date('YmdHis', time()) . "-" . $check['PosCheck']['chks_check_num'] . "-" . $pfmtId;

        if ($preview == 0) {
            $isAddToPrintJob = true;
            $isGenerateEmailPdf = false;

            if (empty($renderFormatType))
                $renderFormatType = $printFormat['PosPrintFormat']['pfmt_render_type'];
            // If is send receipt by email, email address should exist
            if (isset($checkInfo['printInfo']['email']['action']) && $checkInfo['printInfo']['email']['action'] == "email_receipt") {
                if (isset($checkInfo['printInfo']['email']['isPrintHardCopy']) && $checkInfo['printInfo']['email']['isPrintHardCopy'] == 1) {
                    // Need to print hardcopy as well, Generate the pdf format
                    $isGenerateEmailPdf = true;
                } else {
                    // No need to print hardcopy, Override the renderFormatType & Redirect configPath
                    $isAddToPrintJob = false;

                    $renderFormatType = 'p';
                    $sExportPath = $this->controller->Common->getDataPath(array('pos_emails'));
                }
            }
            /////////////////////////////////////////////////////////////////////////
            //	Render the output file
            if ($renderFormatType == 't') {
                $viewFile = 'print_format_txt_' . $pfmtId;
                $printFileFmt = 'TXT';
                $printFileExt = '.txt';

                if (!empty($sExportPath)) {
                    $outputFileName = "e_journal_" . (isset($vars['BusinessDate']) && !empty($vars['BusinessDate'])) ? $vars['BusinessDate'] : date('Ymd', time()) . "_" . $station['PosStation']['stat_id'];
                    $configPath = $sExportPath;
                }
            } else if ($renderFormatType == 'h') {
                $viewFile = 'print_format_html_' . $pfmtId;
                $printFileFmt = 'WEBPAGE';
                $printFileExt = '.html';
            } else if ($renderFormatType == 'p') {
                $viewFile = 'print_format_pdf_' . $pfmtId;
                $printFileFmt = 'PDF';
                $printFileExt = '.pdf';
                if (!empty($sExportPath))
                    $configPath = $sExportPath;
            } else {
                $viewFile = 'print_format_pfi_' . $pfmtId;
                $printFileFmt = 'PFILE';
                $printFileExt = '.pfi';
            }

            $outputFile = $configPath . $outputFileName . $printFileExt;
            $outputDest = 'F';
            $outputView = new View($this->controller, false);

            App::build(array('View' => array($shareDataPath)));
            $outputView->viewPath = '';
            $outputView->set(compact('tpls', 'vars', 'outputFile', 'outputDest', 'printCtrls'));

            $totalPages = 1;
            if ($renderFormatType == 't') {
                $PosPrintFormatModel->checkPrintFormatPlainTextViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewFile, $this->controller->languages);
                $viewContent = @$outputView->render($viewFile, '');
                if (!empty($sExportPath)) {
                    file_put_contents($outputFile, $viewContent, FILE_APPEND);
                } else
                    file_put_contents($outputFile, $viewContent);
            } else if ($renderFormatType == 'h') {
                $PosPrintFormatModel->checkPrintFormatHtmlViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewFile, $this->controller->languages);
                $viewContent = @$outputView->render($viewFile, '');
                file_put_contents($outputFile, $viewContent);
            } else if ($renderFormatType == 'p') {
                $PosPrintFormatModel->checkPrintFormatTcpdfViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewFile, $this->controller->languages);
                $viewContent = @$outputView->render($viewFile, '');
            } else {
                $PosPrintFormatModel->checkPrintFormatPfileViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewFile, $this->controller->languages);
                $viewContent = @$outputView->render($viewFile, '');
                file_put_contents($outputFile, $viewContent);

                $viewContentJson = json_decode($viewContent, true);
                if ($viewContentJson != null && isset($viewContentJson['paper']['total_pages']))
                    $totalPages = $viewContentJson['paper']['total_pages'];
            }

            if ($bContinuousPrint && $renderFormatType != 't' && $renderFormatType != 'h' && $renderFormatType != 'p') {
                $continuousPrint = array();
                $continuousPrint['page_number'] = (isset($viewContentJson['paper']['page_offset'])) ? intval($viewContentJson['paper']['page_offset']) : intval('0');
                $continuousPrint['line_number'] = (isset($viewContentJson['paper']['line_offset'])) ? intval($viewContentJson['paper']['line_offset']) : intval('0');
                App::import('Model', 'Pos.PosCheckExtraInfo');
                $posCheckExtraInfoModel = new PosCheckExtraInfo();

                $saveField = $PosCheckExtraInfoModel->findActiveByCheckIdAndVariable($check['PosCheck']['chks_id'], 'check', 'print_position');
                if (!empty($saveField)) {
                    $saveField['PosCheckExtraInfo']['ckei_value'] = json_encode($continuousPrint, true);
                    $saveField['PosCheckExtraInfo']['modified'] = null;
                } else {
                    $saveField['PosCheckExtraInfo'] = array();
                    $saveField['PosCheckExtraInfo']['ckei_by'] = 'check';
                    $saveField['PosCheckExtraInfo']['ckei_olet_id'] = $check['PosCheck']['chks_olet_id'];
                    $saveField['PosCheckExtraInfo']['ckei_chks_id'] = $check['PosCheck']['chks_id'];
                    $saveField['PosCheckExtraInfo']['ckei_section'] = 'continuous_print';
                    $saveField['PosCheckExtraInfo']['ckei_variable'] = 'print_position';
                    $saveField['PosCheckExtraInfo']['ckei_value'] = json_encode($continuousPrint, true);
                    $posCheckExtraInfoModel->create();    // create a new row
                }

                $posCheckExtraInfoModel->save($saveField);
            }
            if ($isAddToPrintJob) {
                App::import('Component', 'Printing.PrintingApiGeneral');
                $this->PrintingApiGeneral = new PrintingApiGeneralComponent(new ComponentCollection());
                $this->PrintingApiGeneral->startup($this->controller);

                $reply = array();
                $param = array();
                $param['printJobFile'] = Router::url($configUrl . $outputFileName . $printFileExt, array('full' => true, 'escape' => true));
                $param['printQ'] = $printQueue;
                $param['printJobFileMediaType'] = $printFileFmt;
                if ($type == 2)
                    $param['printJobFileType'] = 'RECEIPT';
                else if ($type == 3)
                    $param['printJobFileType'] = 'OTHERS';
                else
                    $param['printJobFileType'] = 'BILL';

                $this->PrintingApiGeneral->addPrintJob($param, $reply);
            }

            $resultFile['url'] = Router::url($configUrl . $outputFileName . $printFileExt, array('full' => true, 'escape' => true));
            $resultFile['viewContent'] = $viewContent;
            if ($type == 2 && $supportTaiWanGUI && $taiWanGuiGenerateBy == 's' && $taiWanGuiMode == 'r' && $taiwanGuiPfmtId == $pfmtId) {
                $resultFile['page'] = $totalPages;
                $resultFile['taiwanGuiNeedExtraTrans'] = true;
            }

            // If is send receipt by email, record the params
            if (isset($checkInfo['printInfo']['email']['action'])) {
                $emailPdfReceiptFileName = $outputFileName . $printFileExt;
                if ($isGenerateEmailPdf)
                    $emailPdfReceiptFileName = $outputFileName . '.pdf';
                $this->__constructEmailPrintingParams($checkInfo['printInfo']['email'], $reprintReceipt, $emailPdfReceiptFileName, $shop, $outlet, $station, $check, $businessDay, isset($checkMember) ? $checkMember : array(), $checkExtraInfos, $resultFile);
            }
            // save audit log only when the receipt is added to print job
            if ($isAddToPrintJob) {
                App::import('Component', 'Pos.PosApiGeneral');
                $posApiGeneralComponent = new PosApiGeneralComponent(new ComponentCollection());
                $posApiGeneralComponent->startup($this->controller);
                $params2 = array(
                    'outletId' => $check['PosCheck']['chks_olet_id'],
                    'stationId' => (isset($check['PosCheck']['chks_print_stat_id']) && intval($check['PosCheck']['chks_print_stat_id']) > 0) ? intval($check['PosCheck']['chks_print_stat_id']) : intval($check['PosCheck']['chks_open_stat_id']),
                    'section' => 'system',
                    'variable' => 'audit_log_level'
                );
                $posConfig = $posApiGeneralComponent->getConfigBySectionVariable($params2);
                if ($posConfig != null && $posConfig['PosConfig']['scfg_value'] == 1 && array_key_exists("audit_log", $this->controller->plugins)) {
                    App::import('Component', 'AuditLog.AuditLogApiGeneral');
                    if (class_exists('AuditLogApiGeneralComponent')) {
                        $auditLogApiGeneralComponent = new AuditLogApiGeneralComponent(new ComponentCollection());
                        $auditLogApiGeneralComponent->startup($this->controller);

                        if (method_exists($auditLogApiGeneralComponent, 'saveAuditLogs')) {
                            $auditLogArray = array();

                            App::import('Model', 'AuditLog.AlogLog');
                            $alogLogModel = new AlogLog();

                            App::import('Model', 'AuditLog.AlogLogInfo');
                            $alogLogInfoModel = new AlogLogInfo();

                            $typeKey = '';
                            if ($type == 1) {
                                $typeKey = 'print_guest_chk';
                                $desc = 'Print';
                            } else if ($reprintReceipt == 1) {
                                $typeKey = 'reprint_guest_chk';
                                $desc = 'Reprint';
                            }

                            if (!empty($typeKey)) {
                                $checkId = $check['PosCheck']['chks_id'];
                                $auditLogInfoArray = array(
                                    'curr_chk' => array(
                                        'value' => $check['PosCheck']['chks_check_num'],
                                        'record_id' => $checkId
                                    ),
                                    'prt_job' => array(
                                        'value' => $resultFile['url'],
                                        'record_id' => $reply[0] // pjob_id
                                    )
                                );
                                $auditLogInfos = $alogLogInfoModel->constructAlogInfoArray($auditLogInfoArray);
                                $desc .= ' check at station: (' . $station['PosStation']['stat_name_l1'] . ') Print total: ($' . $check['PosCheck']['chks_check_total'] . ') [' . $check['PosCheck']['chks_print_count'] . ']';
                                $auditLog = $alogLogModel->constructAlogLog("pos", $typeKey, $check['PosCheck']['chks_shop_id'], $check['PosCheck']['chks_olet_id'], $check['PosCheck']['chks_print_user_id'], $check['PosCheck']['chks_bday_id'], $desc, $auditLogInfos, $shop['OutShop']['shop_timezone'], $shop['OutShop']['shop_timezone_name']);

                                $params2 = array(
                                    'AuditLogs' => array(0 => $auditLog)
                                );
                                $reply2 = array();
                                $auditLogApiGeneralComponent->saveAuditLogs($params2, $reply2);
                            }
                        }
                    }
                }
            }
        } else {
            /////////////////////////////////////////////////////////////////////////
            //	Render the HTML file
            $outputFile = $configPath . $outputFileName . ".html";
            $outputView = new View($this->controller, false);
            $outputView->set(compact('tpls', 'vars', 'printCtrls'));
            $outputView->viewPath = '';

            $viewFile = $shareDataPath . 'print_format_html_' . $pfmtId . '.ctp';
            $PosPrintFormatModel->checkPrintFormatHtmlViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $viewFile, $this->controller->languages);
            $viewContent = @$outputView->render('print_format_html_' . $pfmtId);
            file_put_contents($outputFile, $viewContent);

            //	Render the TXT file
            $outputPlainTextView = new View($this->controller, false);
            $outputPlainTextView->set(compact('tpls', 'vars', 'printCtrls'));
            $outputPlainTextView->viewPath = '';

            $viewPlainTextFile = 'print_format_txt_' . $pfmtId;
            $PosPrintFormatModel->checkPrintFormatPlainTextViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $viewPlainTextFile, $this->controller->languages);
            $viewPlainTextContent = @$outputPlainTextView->render($viewPlainTextFile, '');

            $resultFile['url'] = Router::url($configUrl . $outputFileName . '.html', array('full' => true, 'escape' => true));
            $resultFile['viewContent'] = $viewPlainTextContent;
            $resultFile['path'] = $outputFile;
        }

        return '';
    }

    public function __constructEmailPrintingParams($emailParams = array(), $reprintReceipt = 0, $fileName = '', $shop, $outlet, $station, $check, $businessDay, $checkMember, $checkExtraInfos, &$resultFile)
    {
        if (empty($emailParams) || !isset($emailParams['action']))
            return;

        $outShop = $shop['OutShop'];
        $outOutlet = $outlet['OutOutlet'];
        $posStation = $station['PosStation'];
        $posCheck = $check['PosCheck'];

        // If is send receipt by email, record the params
        if ($emailParams['action'] == "email_receipt") {
            $resultFile['FileName'] = $fileName;

            $printingVaribles = array();
            for ($iLangIndex = 1; $iLangIndex <= 5; $iLangIndex++) {
                $printingVaribles['ShopNameL' . $iLangIndex] = $this->__checkStringExist($outShop, "shop_name_l" . $iLangIndex);
                $printingVaribles['OutletNameL' . $iLangIndex] = $this->__checkStringExist($outOutlet, "olet_name_l" . $iLangIndex);
            }
            $printingVaribles['CheckNo'] = $posCheck['chks_check_prefix_num'];
            $printingVaribles['BusinessDay'] = $businessDay['PosBusinessDay']['bday_date'];

            $printingVaribles['MemberNo'] = '';
            $printingVaribles['MemberLastNameL1'] = '';
            $printingVaribles['MemberLastNameL2'] = '';
            $printingVaribles['MemberFirstNameL1'] = '';
            $printingVaribles['MemberFirstNameL2'] = '';
            $printingVaribles['MemberChineseName'] = '';

            // Member Info Handling
            if (!empty($checkMember)) {
                $memMember = $checkMember['MemMember'];

                // Hero Member
                $printingVaribles['MemberNo'] = $memMember['memb_number'];
                $printingVaribles['MemberLastNameL1'] = $memMember['memb_last_name_l1'];
                $printingVaribles['MemberLastNameL2'] = $memMember['memb_last_name_l2'];
                $printingVaribles['MemberFirstNameL1'] = $memMember['memb_first_name_l1'];
                $printingVaribles['MemberFirstNameL2'] = $memMember['memb_first_name_l2'];
            } else if (!empty($checkExtraInfos)) {
                // Hero Interface Member
                foreach ($checkExtraInfos as $checkExtraInfo) {
                    if ($this->__checkStringExist($checkExtraInfo, 'ckei_by') == "check") {
                        switch ($this->__checkStringExist($checkExtraInfo, 'ckei_variable')) {
                            case 'member_number':
                                $printingVaribles['MemberNo'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                                break;
                            case 'account_name':
                            case 'member_name':
                                $printingVaribles['MemberLastNameL1'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                                $printingVaribles['MemberLastNameL2'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                                $printingVaribles['MemberFirstNameL1'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                                $printingVaribles['MemberFirstNameL2'] = $this->__checkStringExist($checkExtraInfo, 'ckei_value');
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
            $resultFile['PrintingVaribles'] = $printingVaribles;

            // Construct audit log params only if is reprint receipt
            $auditLogPassingInfo = array();
            if ($reprintReceipt) {
                $auditLogPassingInfo['stat_name_l1'] = isset($posStation['stat_name_l1']) ? $posStation['stat_name_l1'] : '';
                $auditLogPassingInfo['chks_open_stat_id'] = isset($posCheck['chks_open_stat_id']) ? $posCheck['chks_open_stat_id'] : 0;
                $auditLogPassingInfo['chks_print_stat_id'] = isset($posCheck['chks_print_stat_id']) ? $posCheck['chks_print_stat_id'] : 0;
                $auditLogPassingInfo['chks_id'] = isset($posCheck['chks_id']) ? $posCheck['chks_id'] : 0;
                $auditLogPassingInfo['chks_check_prefix_num'] = isset($posCheck['chks_check_prefix_num']) ? $posCheck['chks_check_prefix_num'] : '';
                $auditLogPassingInfo['chks_check_total'] = isset($posCheck['chks_check_total']) ? $posCheck['chks_check_total'] : 0;
                $auditLogPassingInfo['chks_print_count'] = isset($posCheck['chks_print_count']) ? $posCheck['chks_print_count'] : 0;
                $auditLogPassingInfo['chks_shop_id'] = isset($posCheck['chks_shop_id']) ? $posCheck['chks_shop_id'] : 0;
                $auditLogPassingInfo['chks_olet_id'] = isset($posCheck['chks_olet_id']) ? $posCheck['chks_olet_id'] : 0;
                $auditLogPassingInfo['chks_bday_id'] = isset($posCheck['chks_bday_id']) ? $posCheck['chks_bday_id'] : '';
                $auditLogPassingInfo['chks_print_user_id'] = isset($posCheck['chks_print_user_id']) ? $posCheck['chks_print_user_id'] : 0;
                $auditLogPassingInfo['shop_timezone'] = isset($outShop['shop_timezone']) ? $outShop['shop_timezone'] : '';
                $auditLogPassingInfo['shop_timezone_name'] = isset($outShop['shop_timezone_name']) ? $outShop['shop_timezone_name'] : '';
            }
            $resultFile['auditLogPassingInfo'] = $auditLogPassingInfo;
        }
    }

    public function groupChildItems($checkChildItem, &$ChildItems, $checkItemCheckingDetail, $itemsCheckingListDetail, $prtFmtDefaultLang, $businessDay, &$DiscTotalByDiscType, &$departmentTotals, $depts, &$categoryTotals, $itemCategories, &$reply)
    {
        $itemGrouped = false;

        if ($this->__checkStringExist($checkChildItem, "citm_ordering_type") == 't')
            $itemTakeoutChecking = 1;
        else
            $itemTakeoutChecking = 0;
        $itemGrossPriceChecking = $this->__checkNumericExist($checkChildItem, "citm_price");

        $itemPrtModiCountChecking = 0;
        $itemPrtModiIdsChecking = array();
        $modifiersChecking = array();
        if ($this->__checkNumericExist($checkChildItem, "citm_modifier_count") > 0) {
            $modifiersChecking = $checkChildItem['ModifierList'];
            if (!empty($modifiersChecking)) {
                foreach ($modifiersChecking as $modifierChecking) {
                    $itemGrossPriceChecking += $this->__checkNumericExist($modifierChecking, "citm_price");
                    if ($this->__checkStringExist($modifierChecking, "citm_no_print") == '') {
                        if (!array_key_exists($modifierChecking['citm_item_id'], $itemPrtModiIdsChecking))
                            $itemPrtModiIdsChecking[$modifierChecking['citm_item_id']] = 1;
                        else
                            $itemPrtModiIdsChecking[$modifierChecking['citm_item_id']] += 1;
                    }
                }
            }
        }

        $itemAppliedDiscTotal = 0;
        $itemAppliedDiscIds = array();
        $itemDiscsChecking = array();
        if ($this->__checkNumericExist($checkChildItem, "citm_pre_disc") != 0 || $this->__checkNumericExist($checkChildItem, "citm_mid_disc") != 0 || $this->__checkNumericExist($checkChildItem, "citm_post_disc") != 0) {
            $itemDiscsChecking = $checkChildItem['PosCheckDiscount'];
            if (!empty($itemDiscsChecking)) {
                foreach ($itemDiscsChecking as $discChecking) {
                    $itemAppliedDiscCount++;
                    $itemAppliedDiscTotal += $this->__checkNumericExist($discChecking, "cdis_round_total");

                    if (!array_key_exists($this->__checkNumericExist($discChecking, "cdis_dtyp_id"), $itemAppliedDiscIds))
                        $itemAppliedDiscIds[$discChecking['cdis_dtyp_id']] = 1;
                    else
                        $itemAppliedDiscIds[$discChecking['cdis_dtyp_id']] += 1;
                }
            }
        }

        for ($itemIndex = 0; $itemIndex < count($ChildItems); $itemIndex++) {
            //whether same menu item ID
            if ($ChildItems[$itemIndex]['ChildItemId'] != $checkChildItem['citm_item_id'])
                continue;

            //whether same item description
            if ($ChildItems[$itemIndex]['ChildItemName'] != $this->__checkStringExist($checkChildItem, "citm_name_l" . $prtFmtDefaultLang) || $ChildItems[$itemIndex]['ChildItemNameL1'] != $this->__checkStringExist($checkChildItem, "citm_name_l1") || $ChildItems[$itemIndex]['ChildItemNameL2'] != $this->__checkStringExist($checkChildItem, "citm_name_l2") || $ChildItems[$itemIndex]['ChildItemNameL3'] != $this->__checkStringExist($checkChildItem, "citm_name_l3") || $ChildItems[$itemIndex]['ChildItemNameL4'] != $this->__checkStringExist($checkChildItem, "citm_name_l4") || $ChildItems[$itemIndex]['ChildItemNameL5'] != $this->__checkStringExist($checkChildItem, "citm_name_l5"))
                continue;

            //whether same takeout status
            if ($ChildItems[$itemIndex]['ChildItemTakeout'] != $itemTakeoutChecking)
                continue;

            //whether same gross price
            //		if($ChildItems[$itemIndex]['ChildItemGrossPrice'] != $itemGrossPriceChecking)
            //			continue;

            //whether same printing modifiers & applied discount
            $sameChildItemDetail = true;
            foreach ($checkItemCheckingDetail as $childItemId => $childItemArray) {
                $diffItemPrtModi = array_diff_assoc($itemsCheckingListDetail[$childItemId]['Modifiers'], $childItemArray['Modifiers']);
                if (!empty($diffItemPrtModi) || count($itemsCheckingListDetail[$childItemId]['Modifiers']) != count($childItemArray['Modifiers'])) {
                    $sameChildItemDetail = false;
                    break;
                }

                $diffItemAppliedDisc = array_diff_assoc($itemsCheckingListDetail[$childItemId]['Discounts'], $childItemArray['Discounts']);
                if (!empty($diffItemAppliedDisc) || count($itemsCheckingListDetail[$childItemId]['Discounts']) != count($childItemArray['Discounts'])) {
                    $sameChildItemDetail = false;
                    break;
                }
            }
            if (!$sameChildItemDetail)
                continue;

            //whether same coupon number
            //		if($ChildItems[$itemIndex]['ItemCouponNumber'] != $itemSvcCouponItem)
            //			continue;

            $itemGrouped = true;
            //update Items list
            $ChildItems[$itemIndex]['ChildItemQuantity'] += $this->__checkNumericExist($checkChildItem, "citm_qty");
            $ChildItems[$itemIndex]['ChildItemPrice'] += $this->__checkNumericExist($checkChildItem, "citm_round_total");
            $ChildItems[$itemIndex]['ChildItemPrice'] = number_format($ChildItems[$itemIndex]['ChildItemPrice'], $businessDay['PosBusinessDay']['bday_item_decimal'], ".", "");

            //update discount value
            foreach ($itemDiscsChecking as $discChecking) {
                $iDiscTypeId = $this->__checkNumericExist($discChecking, "cdis_dtyp_id");
                for ($iItmDiscIndex = 0; $iItmDiscIndex < count($ChildItems[$itemIndex]['Discounts']); $iItmDiscIndex++) {
                    if ($ChildItems[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountId'] == $iDiscTypeId) {
                        $ChildItems[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountAmount'] += $this->__checkNumericExist($discChecking, "cdis_round_total");
                        $ChildItems[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountAmount'] = number_format($ChildItems[$itemIndex]['Discounts'][$iItmDiscIndex]['DiscountAmount'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                        break;
                    }
                }
            }

            //update discount total by discount type
            foreach ($itemDiscsChecking as $discChecking) {
                $iDiscTypeId = $this->__checkNumericExist($discChecking, "cdis_dtyp_id");
                for ($iItmDiscIndex = 0; $iItmDiscIndex < count($DiscTotalByDiscType); $iItmDiscIndex++) {
                    if ($DiscTotalByDiscType[$iItmDiscIndex]['DiscountId'] == $iDiscTypeId) {
                        $DiscTotalByDiscType[$iItmDiscIndex]['DiscountAmount'] += $this->__checkNumericExist($discChecking, "cdis_round_total");
                        $DiscTotalByDiscType[$iItmDiscIndex]['DiscountAmount'] = number_format($DiscTotalByDiscType[$iItmDiscIndex]['DiscountAmount'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "");
                        break;
                    }
                }
            }

            //update department totals
            $departmentFirstLevelId = $this->__getHighestLevelDepartmentId($depts, $this->__checkNumericExist($checkChildItem, "citm_idep_id"));
            if (isset($departmentTotals[$departmentFirstLevelId]))
                $departmentTotals[$departmentFirstLevelId] += $this->__checkNumericExist($checkChildItem, "citm_round_total");
            else
                $departmentTotals[0] = $this->__checkNumericExist($checkChildItem, "citm_round_total");

            //category total handling
            $categoryFirstLevelId = $this->__getHighestLevelCategoryId($itemCategories, $this->__checkNumericExist($checkChildItem, "citm_icat_id"));
            if (isset($categoryTotals[$categoryFirstLevelId]))
                $categoryTotals[$categoryFirstLevelId] += $this->__checkNumericExist($checkChildItem, "citm_round_total");
            else
                $categoryTotals[0] += $this->__checkNumericExist($checkChildItem, "citm_round_total");

            break;
        }

        $reply = array('itemAppliedDiscTotal' => $itemAppliedDiscTotal);
        return $itemGrouped;
    }

    /**
     * The generate output of the special slip
     * @param string $type ()Special slip type
     *                                        # change_quantity - for changing item's quantity
     *                                        # delete_item - for deleting item
     *                                        # change_table - for changing table
     *                                        # change_table_item - for changing table (with items)
     *                                        # split_table - for spliting table
     *                                        # merge_table - for merging table
     *                                        # merge_table_item - for merging table (with items)
     *                                        # void_check - for void check
     *                                        # rush_order - for rush order
     *                                        # pantry_message - for pantry message
     *                                        # credit_card_auth_topup - for credit card authority
     *                                        # member_balance = for GC member balance or General V2 action slip
     * # member_enrollment for General V2 action Slip
     *                                        # loyalty_balance = for loyalty check balance
     * @      integer    $checkId            Check ID
     * @      integer    $checkItemId        Check Item ID
     */
    public function generateMultipleSpecialSlip($type, $checkId, $posLangIndex, $info)
    {
        Configure::write('debug', 0);
        $this->controller->layout = 'print_slip';
        $haveItem = false;
        set_time_limit(0);
        // Leave controller without any result view rendered

        //	Check if printing plugin exists
        if (!array_key_exists("printing", $this->controller->plugins))
            return 'load_model_error';

        // Check if display control plugin exist
        if (array_key_exists("display_control", $this->controller->plugins))
            App::import('Component', 'Pos.PosDisplayControl');

        //	Load required model
        $modelArray = array('Pos.PosStation', 'Pos.PosConfig', 'Pos.PosCheck', 'Pos.PosBusinessDay', 'Pos.PosBusinessPeriod', 'Pos.PosActionPrintQueue', 'Pos.PosPrintFormat',
            'Pos.PosVoidReason', 'Pos.PosPantryMessage', 'Pos.PosOverrideCondition', 'Pos.PosItemPrintQueue', 'Outlet.OutShop', 'Outlet.OutMediaObject',
            'Outlet.OutFloorPlan', 'Outlet.OutFloorPlanTable', 'Outlet.OutCalendar', 'User.UserUser', 'Menu.MenuItemCategory', 'Menu.MenuItemDept',
            'Menu.MenuItemCourse', 'Menu.MenuItemPrintQueue', 'Media.MedMedia', 'Printing.PrtPrintQueue', 'Menu.MenuItem');
        if ($this->controller->importModels($modelArray) !== true)
            return 'load_model_error';

        $PosStationModel = $this->controller->PosStation;
        $PosConfigModel = $this->controller->PosConfig;
        $PosCheckModel = $this->controller->PosCheck;
        $PosBusinessDayModel = $this->controller->PosBusinessDay;
        $PosBusinessPeriodModel = $this->controller->PosBusinessPeriod;
        $PosActionPrintQueueModel = $this->controller->PosActionPrintQueue;
        $PosPrintFormatModel = $this->controller->PosPrintFormat;
        $PosVoidReasonModel = $this->controller->PosVoidReason;
        $PosPantryMessageModel = $this->controller->PosPantryMessage;
        $PosOverrideConditionModel = $this->controller->PosOverrideCondition;
        $OutShopModel = $this->controller->OutShop;
        $OutMediaObjectModel = $this->controller->OutMediaObject;
        $OutFloorPlanModel = $this->controller->OutFloorPlan;
        $OutFloorPlanTableModel = $this->controller->OutFloorPlanTable;
        $UserUserModel = $this->controller->UserUser;
        $MenuItemPrintQueueModel = $this->controller->MenuItemPrintQueue;
        $MedMediaModel = $this->controller->MedMedia;
        $PrtPrintQueueModel = $this->controller->PrtPrintQueue;
        $MenuItemModel = $this->controller->MenuItem;

        //initialize the number of slip
        $numOfSlip = 1;

        //	Add this data path in View folder and set The viewPath be empty (default is the controller name)
        $shareDataPath = $this->controller->Common->getDataPath(array('pos_print_formats'));
        App::build(array('View' => array($shareDataPath)));

        $configPath = $this->controller->Common->getDataPath(array('pos_print_jobs'), true);
        $configUrl = $this->controller->Common->getDataUrl('pos_print_jobs/');
        if (empty($configPath) || empty($configUrl))
            return 'missing_config_path';


        $newCheck = false;
        //loyalty check balance & new check
        if (empty($checkId) && ($type == 'loyalty_balance' || $type == 'member_balance' || $type == 'award_list' || $type == 'member_enrollment'
                || $type == 'tip_tracking'))
            $newCheck = true;

        if ($newCheck) {
            if (isset($info['info']['balanceDetail']['outletId']))
                $outlet = $OutShopModel->OutOutlet->findActiveById($info['info']['balanceDetail']['outletId']);
            if (isset($info['info']['outletId']))
                $outlet = $OutShopModel->OutOutlet->findActiveById($info['info']['outletId']);
        } else {
            //get check information
            $check = $PosCheckModel->findById($checkId, -1);

            if (empty($check))
                return 'missing_check (id: ' . $checkId . ')';

            $checkTable = $PosCheckModel->PosCheckTable->findByCheckId($check['PosCheck']['chks_id']);
            $outlet = $OutShopModel->OutOutlet->findActiveById($check['PosCheck']['chks_olet_id']);
        }

        $outletLogo = "";
        $outletLogoMedia = $OutMediaObjectModel->findMediasByObject($outlet['OutOutlet']['olet_id'], 'outlet', 'l');
        if (!empty($outletLogoMedia) && count($outletLogoMedia) > 0) {
            $medConfigs = array(
                'med_path' => $this->controller->Common->getDataPath(array('media_files'), true),
                'med_url' => $this->controller->Common->getDataUrl('media_files/'),
            );

            $media = $MedMediaModel->findActiveById($outletLogoMedia[0]['OutMediaObject']['omed_medi_id']);
            $outletLogo = Router::url($medConfigs['med_url'] . $media['MedMedia']['medi_filename'], array('full' => true, 'escape' => true));

        }

        //business day
        $businessDay = $PosBusinessDayModel->findActiveByOutletId($outlet['OutOutlet']['olet_id']);

        if (empty($businessDay))
            return 'missing_business_day';

        if (!empty($check))
            $businessPeriod = $PosBusinessPeriodModel->findActiveById($check['PosCheck']['chks_bper_id']);


        //shop
        if (!empty($outlet))
            $shop = $OutShopModel->findActiveById($outlet['OutOutlet']['olet_shop_id']);

        //check isHoliday, isDayBeforeHoliday, isSpecialDay, isDayBeforeSpecialDay
        $isHoliday = false;
        $isSpecialDay = false;
        $isDayBeforeHoliday = false;
        $isDayBeforeSpecialDay = false;
        $weekday = date("w", mktime(0, 0, 0, substr($businessDay['PosBusinessDay']['bday_date'], 5, 2), substr($businessDay['PosBusinessDay']['bday_date'], 8, 2), substr($businessDay['PosBusinessDay']['bday_date'], 0, 4)));
        $this->__checkCalendarHolidaySpecialDay($businessDay['PosBusinessDay']['bday_date'], $shop['OutShop']['shop_id'], $outlet['OutOutlet']['olet_id'], $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay);

        //get table information
        if (!empty($outlet))
            $floorPlan = $OutFloorPlanModel->findFloorPlanByOutlet($outlet['OutOutlet']['olet_id'], 1);
        if (!empty($floorPlan))
            $floorPlanId = $floorPlan[0]['OutFloorPlan']['flrp_id'];
        else
            $floorPlanId = 0;
        $floorPlanMapId = array();
        if (!empty($floorPlan)) {
            foreach ($floorPlan[0]['OutFloorPlanMap'] as $floorPlanMap)
                $floorPlanMapId[] = $floorPlanMap['flrm_id'];
        }
        if (!empty($checkTable)) {
            $tableInfo = $OutFloorPlanTableModel->find('all', array(
                    'conditions' => array(
                        'flrt_flrp_id' => $floorPlanId,
                        'flrt_flrm_id' => $floorPlanMapId,
                        'flrt_table' => $checkTable['PosCheckTable']['ctbl_table'],
                        'flrt_table_ext' => $checkTable['PosCheckTable']['ctbl_table_ext'],
                        'flrt_status' => ''
                    ),
                    'recursive' => -1
                )
            );
        }

        //get print station information
        $printStation = $PosStationModel->findActiveById($info['info']['stationId']);

        //get print user
        $printUser = $UserUserModel->findActiveById($info['info']['userId']);

        //get print queue override condition
        $printQueueOverrideConditions = $PosOverrideConditionModel->findAllActivePrtqConditionsByOutletId($outlet['OutOutlet']['olet_id']);

        // Update kitchen display txt file (only for change table and delete item)
        $menuItemPrintQueue = null;
        $printQueueList = array();

        if (isset($info['info']['citmIds']) && count($info['info']['citmIds']) > 0
            && ($type == 'change_table' || $type == 'delete_item' || $type == 'split_table' || $type == 'rush_order' || $type == 'pantry_message')) {

            App::import('Model', 'Pos.PosKitchenDisplayJob');
            $posKitchenDisplayJobModel = new PosKitchenDisplayJob();

            // Check if display control plugin exist
            if (array_key_exists("display_control", $this->controller->plugins)) {
                if (!class_exists('PosDisplayControlComponent'))
                    return 'fail_to_load_component';
                $posDisplayControlComponent = new PosDisplayControlComponent(new ComponentCollection());
                $posDisplayControlComponent->startup($this->controller);
            }

            $localTime = date('Y-m-d H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));
            $utcTime = date('Y-m-d H:i:s', Date::UTCTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], $localTime));

            $kitchenDisplayIds = array();
            if ($type == 'split_table') {

                //read item print queue list
                $menuItemPrintQueue = $MenuItemPrintQueueModel->find('all', array(
                        'conditions' => array(
                            'MenuItemPrintQueue.itpq_status' => '',
                        ),
                        'recursive' => -1
                    )
                );

                // Construct item array with print queue id
                if (!empty($menuItemPrintQueue)) {
                    for ($i = 0; $i < count($menuItemPrintQueue); $i++) {
                        $itemPrintQueue = $this->__getAvaliableItemPrintQueue($check['PosCheck']['chks_shop_id'], $check['PosCheck']['chks_olet_id'], $menuItemPrintQueue[$i]['MenuItemPrintQueue']['itpq_id']);
                        if ($itemPrintQueue == null)
                            continue;

                        $menuPrintQueueId = $menuItemPrintQueue[$i]['MenuItemPrintQueue']['itpq_id'];

                        if ($itemPrintQueue['PosItemPrintQueue']['itpq_station_printer'] == 0) {
                            $printQueueId = $itemPrintQueue['PosItemPrintQueue']['itpq_prtq_id'];
                            $prtPrintQueue = $PrtPrintQueueModel->findActiveById($printQueueId);
                            if (isset($prtPrintQueue['PrtPrintQueue']['prtq_id'])) {
                                $itemPrintQueue['PosItemPrintQueue']['name_l1'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l1'];
                                $itemPrintQueue['PosItemPrintQueue']['name_l2'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l2'];
                                $itemPrintQueue['PosItemPrintQueue']['name_l3'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l3'];
                                $itemPrintQueue['PosItemPrintQueue']['name_l4'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l4'];
                                $itemPrintQueue['PosItemPrintQueue']['name_l5'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l5'];
                            }
                        }
                        $printQueueList[$menuPrintQueueId] = $itemPrintQueue;
                    }
                }

                $posKitchenDisplayJobs = array();
                foreach ($info['info']['citmIds'] as $checkItemInfo) {
                    $checkItem = $PosCheckModel->PosCheckItem->findById($checkItemInfo['id'], -1);
                    $posKitchenDisplayJob = $posKitchenDisplayJobModel->findActiveByItemId($checkItem['PosCheckItem']['citm_id']);

                    if (empty($checkItem))
                        continue;

                    // if the item is delivered, do not save kitchen display job
                    if ($checkItem['PosCheckItem']['citm_delivery_time'] != null)
                        continue;

                    if (!empty($posKitchenDisplayJob)) {
                        if ($posKitchenDisplayJob['PosKitchenDisplayJob']['kdjb_chks_id'] != $checkItem['PosCheckItem']['citm_chks_id']) {
                            $posKitchenDisplayJobModel->updateAll(
                                array(
                                    'kdjb_chks_id' => "'" . $checkItem['PosCheckItem']['citm_chks_id'] . "'",
                                    'kdjb_modify_time' => "'" . $utcTime . "'",
                                    'kdjb_modify_loctime' => "'" . $localTime . "'",
                                    'kdjb_modify_by_user_id' => $info['info']['userId']
                                ),
                                array(
                                    'kdjb_citm_id' => $checkItem['PosCheckItem']['citm_id'],
                                    'kdjb_chks_id NOT LIKE' => $checkItem['PosCheckItem']['citm_chks_id']
                                )
                            );
                            if (!in_array($posKitchenDisplayJob['PosKitchenDisplayJob']['kdjb_kdis_id'], $kitchenDisplayIds))
                                array_push($kitchenDisplayIds, $posKitchenDisplayJob['PosKitchenDisplayJob']['kdjb_kdis_id']);
                        }
                    } else {
                        for ($i = 1; $i <= 10; $i++) {
                            if ($checkItem['PosCheckItem']['citm_print_queue' . $i . '_itpq_id'] == 0)
                                continue;

                            // construct kitchen display job array
                            $itemPrintQueue = $printQueueList[$checkItem['PosCheckItem']['citm_print_queue' . $i . '_itpq_id']];

                            if (!empty($itemPrintQueue) && $itemPrintQueue['PosItemPrintQueue']['itpq_kdis_id'] > 0) {
                                $saveField = array();
                                $saveField['PosKitchenDisplayJob']['kdjb_kdis_id'] = $itemPrintQueue['PosItemPrintQueue']['itpq_kdis_id'];
                                $saveField['PosKitchenDisplayJob']['kdjb_olet_id'] = $checkItem['PosCheckItem']['citm_olet_id'];
                                $saveField['PosKitchenDisplayJob']['kdjb_chks_id'] = $checkItem['PosCheckItem']['citm_chks_id'];
                                $saveField['PosKitchenDisplayJob']['kdjb_citm_id'] = $checkItem['PosCheckItem']['citm_id'];
                                $saveField['PosKitchenDisplayJob']['kdjb_create_time'] = $utcTime;
                                $saveField['PosKitchenDisplayJob']['kdjb_create_loctime'] = $localTime;
                                $saveField['PosKitchenDisplayJob']['kdjb_create_by_user_id'] = $info['info']['userId'];
                                array_push($posKitchenDisplayJobs, $saveField);

                                if (!in_array($itemPrintQueue['PosItemPrintQueue']['itpq_kdis_id'], $kitchenDisplayIds))
                                    array_push($kitchenDisplayIds, $itemPrintQueue['PosItemPrintQueue']['itpq_kdis_id']);
                            }
                        }
                    }
                }

                // save kitchen display jobs
                if (!empty($posKitchenDisplayJobs))
                    $posKitchenDisplayJobModel->saveAll($posKitchenDisplayJobs);
            } else {

                // get kitchen display ids which is needed to update txt file
                $itemIds = $checkItemIds = array();
                foreach ($info['info']['citmIds'] as $checkItemInfo) {
                    $checkItemId = $checkItemInfo['id'];
                    $itemIds[] = $checkItemId;
                    $checkItemIds[] = $checkItemInfo['id'];

                    if ($type == 'pantry_message') {
                        // load child items
                        $conditions = array(
                            'citm_parent_citm_id' => $checkItemId,
                            'citm_role' => 'c'
                        );
                        if ($type == 'delete_item') // for "delete_item", find deleted child items of deleted set menu
                            $conditions['citm_status'] = 'd';
                        else
                            $conditions['citm_status <>'] = 'd';

                        $childItems = $PosCheckModel->PosCheckItem->find('list', array(
                                'conditions' => $conditions,
                                'fields' => 'citm_id',
                                'recursive' => -1
                            )
                        );
                        if (!empty($childItems)) {
                            foreach ($childItems as $child) {
                                if (!in_array($child, $itemIds))
                                    $itemIds[] = $child;
                            }
                        }
                    }
                }

                // use item ids to find related kitchen display ids
                if (!empty($itemIds)) {
                    $kitchenDisplayIds = $posKitchenDisplayJobModel->find('list', array(
                            'conditions' => array(
                                'kdjb_citm_id' => $itemIds,
                                'kdjb_status' => ''
                            ),
                            'fields' => array('kdjb_kdis_id', 'kdjb_kdis_id'),
                            'group' => 'kdjb_kdis_id',
                            'recursive' => -1
                        )
                    );
                }

                if ($type == 'pantry_message') {

                    //	Get pantry message description if type is equal to pantry_message
                    $pantryMessage = $PosPantryMessageModel->findActiveById($info['header']['messages'][0]['message'], -1);

                    if (!empty($pantryMessage)) {

                        //	Use check item ids to find related kitchen display jobs
                        if (!empty($checkItemIds)) {
                            $posKitchenDisplayJobIds = $posKitchenDisplayJobModel->find('list', array(
                                    'conditions' => array(
                                        'kdjb_citm_id' => $checkItemIds,
                                        'kdjb_status' => ''
                                    ),
                                    'fields' => 'kdjb_id',
                                    'recursive' => -1
                                )
                            );

                            if (!empty($posKitchenDisplayJobIds)) {
                                $jobMsg = array('time' => date('H:i', strtotime($localTime)));
                                for ($index = 1; $index <= 5; $index++)
                                    $jobMsg['messageL' . $index] = urlencode($pantryMessage['PosPantryMessage']['panm_name_l' . $index]);    //	need to use urlencode as updateAll will remove the '\' in unicode

                                $posKitchenDisplayJobModel->updateAll(
                                    array(
                                        'kdjb_messages' => "'" . json_encode($jobMsg) . "'",
                                        'kdjb_modify_time' => "'" . $utcTime . "'",
                                        'kdjb_modify_loctime' => "'" . $localTime . "'",
                                        'kdjb_modify_by_user_id' => $info['info']['userId']
                                    ),
                                    array(
                                        'kdjb_id' => $posKitchenDisplayJobIds
                                    )
                                );
                            }
                        }
                    }
                }
            }

            // update pos_kitchen_displays txt file
            if (!empty($kitchenDisplayIds)) {
                foreach ($kitchenDisplayIds as $kitchenDisplayId) {
                    $posKitchenDisplayPath = $this->controller->Common->getDataPath(array('pos_kitchen_displays'), true);
                    $kitchenDisplayFile = $posKitchenDisplayPath . $kitchenDisplayId . '.txt';
                    $fp = fopen($kitchenDisplayFile, 'w');
                    if ($fp !== false) {
                        $nowtime = time();
                        fwrite($fp, date('YmdHis', $nowtime));
                        fclose($fp);
                    }
                }
            }
        }

        //get action print queue
        $conditionsCount = 0;
        $tmpOutletId = 0;
        $tmpShopId = 0;
        if ($newCheck) {
            $tmpShopId = $shop['OutShop']['shop_id'];
            $tmpOutletId = $outlet['OutOutlet']['olet_id'];
        } else {
            $tmpShopId = $check['PosCheck']['chks_shop_id'];
            $tmpOutletId = $check['PosCheck']['chks_olet_id'];
        }
        $actionPrtQConditions = array(
            'OR' => array(
                array(
                    'acpq_shop_id' => 0,
                    'acpq_olet_id' => 0,
                ),
                array(
                    'acpq_shop_id' => $tmpShopId,
                    'acpq_olet_id' => 0,
                ),
                array(
                    'acpq_shop_id' => $tmpShopId,
                    'acpq_olet_id' => $tmpOutletId,
                ),
            ),
            'AND' => array(
                'acpq_key' => $type,
                'acpq_status' => '',
            ),
        );

        $posActionPrintQueues = $PosActionPrintQueueModel->find('all', array(
                'conditions' => $actionPrtQConditions,
                'recursive' => -1,
                'order' => 'acpq_olet_id DESC, acpq_shop_id DESC'
            )
        );

        if (empty($posActionPrintQueues))
            return '';            //return if no action print queue find

        //re-sequence the ACL records by sorting them by priorities
        $countRec = count($posActionPrintQueues);
        $logValue = ceil(log10($countRec + 1));
        $offset = pow(10, $logValue);

        $actionPrintQueues = array();
        foreach ($posActionPrintQueues as $posActionPrintQueue) {
            $outletMark = '1';
            if (!empty($posActionPrintQueue['PosActionPrintQueue']['acpq_olet_id'])) $outletMark = '0';

            $shopMark = '1';
            if (!empty($posActionPrintQueue['PosActionPrintQueue']['acpq_shop_id'])) $shopMark = '0';

            $key = $outletMark . '-' . $shopMark;
            $posActionPrintQueue['group_key'] = $key;

            $actionPrintQueues[$key . '-' . $offset] = $posActionPrintQueue;
            $offset++;
        }
        ksort($actionPrintQueues);        // sort the action print queue

        //get the action slip header information
        $header = array();
        if (isset($info['header']))
            $header = $info['header'];
        if (isset($info['info'])) {
            if (isset($info['info']['table']))
                $check['table'] = $info['info']['table'];
            else
                $check['table'] = "";
            if (isset($info['info']['userName']))
                $userName = $info['info']['userName'];
            else
                $userName = "";
        }
        if (isset($info['info']['citmIds']) && count($info['info']['citmIds']) > 0)
            $haveItem = true;

        //get void reason if type is equal to delete_item/void_check
        if (strcmp($type, "delete_item") == 0 || strcmp($type, "void_check") == 0)
            $voidReason = $PosVoidReasonModel->findActiveById($info['header']['messages'][0]['message'], -1);

        //read item print queue list
        if ($menuItemPrintQueue == null) { // already retrieve $menuItemPrintQueue before, no need to retrieve again
            $menuItemPrintQueue = $MenuItemPrintQueueModel->find('all', array(
                    'conditions' => array(
                        'MenuItemPrintQueue.itpq_status' => '',
                    ),
                    'recursive' => -1
                )
            );
            //$printQueueList = array();
            if (!empty($menuItemPrintQueue)) {
                for ($i = 0; $i < count($menuItemPrintQueue); $i++) {
                    if (!$newCheck)
                        $itemPrintQueue = $this->__getAvaliableItemPrintQueue($check['PosCheck']['chks_shop_id'], $check['PosCheck']['chks_olet_id'], $menuItemPrintQueue[$i]['MenuItemPrintQueue']['itpq_id']);

                    if (!isset($itemPrintQueue) || $itemPrintQueue == null)
                        continue;

                    $menuPrintQueueId = $menuItemPrintQueue[$i]['MenuItemPrintQueue']['itpq_id'];

                    if ($itemPrintQueue['PosItemPrintQueue']['itpq_station_printer'] == 0) {
                        $printQueueId = $itemPrintQueue['PosItemPrintQueue']['itpq_prtq_id'];
                        $prtPrintQueue = $PrtPrintQueueModel->findActiveById($printQueueId);
                        if (isset($prtPrintQueue['PrtPrintQueue']['prtq_id'])) {
                            $itemPrintQueue['PosItemPrintQueue']['name_l1'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l1'];
                            $itemPrintQueue['PosItemPrintQueue']['name_l2'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l2'];
                            $itemPrintQueue['PosItemPrintQueue']['name_l3'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l3'];
                            $itemPrintQueue['PosItemPrintQueue']['name_l4'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l4'];
                            $itemPrintQueue['PosItemPrintQueue']['name_l5'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l5'];
                        }
                    }
                    $printQueueList[$menuPrintQueueId] = $itemPrintQueue;
                }
            }
        }

        //construct printing variables
        $vars = array();
        $vars['StationName'] = "";
        $vars['ShopName'] = "";
        $vars['TableName'] = "";
        $vars['TableNumber'] = "";
        $vars['OutletLogo'] = "";
        $vars['OutletName'] = "";
        $vars['Address'] = "";
        $vars['Greeting'] = "";
        $vars['OutletCode'] = "";
        $vars['OutletCurrencyCode'] = "";
        $vars['Phone'] = "";
        $vars['Fax'] = "";
        $vars['DollarSign'] = "";
        $vars['BusinessDate'] = "";
        $vars['CheckOpenEmployee'] = "";
        $vars['CheckOpenEmployeeFirstName'] = "";
        $vars['CheckOpenEmployeeLastName'] = "";
        $vars['CheckOwnerEmployee'] = "";
        $vars['CheckOwnerEmployeeFirstName'] = "";
        $vars['CheckOwnerEmployeeLastName'] = "";
        $vars['CheckNumber'] = "";
        $vars['CheckTitle'] = "";
        $vars['CheckGuests'] = "";
        $vars['CheckOpenDate'] = "";
        $vars['CheckOpenTime'] = "";
        $vars['CheckOpenEmployeeNum'] = "";
        $vars['PrintEmployee'] = "";
        $vars['PrintEmployeeFirstName'] = "";
        $vars['PrintEmployeeLastName'] = "";
        $vars['PrintDate'] = "";
        $vars['PrintTime'] = "";
        $vars['PrintEmployeeNum'] = "";
        $vars['CheckMealPeriodName'] = "";
        $vars['CheckMealShortPeriodName'] = "";
        $vars['ReceiptPrintCount'] = 0;
        $vars['PrintCount'] = 0;
        $vars['ReprintReceipt'] = 0;
        $vars['CheckTotal'] = 0;
        $vars['SCTotal'] = 0;
        $vars['TaxTotal'] = 0;
        $vars['TotalItem'] = 0;
        $vars['CheckItemGrossTotal'] = 0;
        $vars['CheckItemDiscountTotal'] = 0;
        $vars['CheckMembershipIntfPointBalance'] = "";
        $vars['CheckMembershipIntfLocalBalance'] = "";
        $vars['CheckMembershipIntfPointAvailable'] = "";
        $vars['CheckMembershipIntfTotalPointsBalance'] = "";
        $vars['CheckMembershipIntfAccountName'] = "";
        $vars['CheckMembershipIntfMemberNumber'] = "";
        $vars['CheckMembershipIntfMemberOtherName'] = "";
        $vars['CheckMembershipIntfMemberType'] = "";
        $vars['CheckLoyaltyCardNumber'] = "";
        $vars['CheckLoyaltyPointBalance'] = "";
        $vars['CheckLoyaltyBalanceExpireThisMonth'] = "";
        $vars['CheckLoyaltyBalanceExpireNextMonth'] = "";
        $vars['CheckLoyaltyTopUpTransDate'] = "";
        $vars['CheckLoyaltyTopUpRefId'] = "";
        $vars['CheckLoyaltyTopUpAmount'] = "";
        $vars['DiscountTotal'] = 0;
        $vars['CheckItemTotal'] = 0;
        $vars['CheckRoundTotal'] = 0;
        $vars['CheckDiscountTotal'] = 0;
        $vars['PayAmountTotal'] = 0;
        $vars['TipsTotal'] = 0;
        $vars['Changes'] = 0;
        $vars['CheckCloseEmployee'] = "";
        $vars['CheckCloseEmployeeFirstName'] = "";
        $vars['CheckCloseEmployeeLastName'] = "";
        $vars['CheckCloseDate'] = "";
        $vars['CheckCloseTime'] = "";
        $vars['Message1'] = "";
        $vars['Message2'] = "";
        $vars['Message3'] = "";
        $vars['Message4'] = "";
        $vars['Message5'] = "";
        $vars['CheckTakeout'] = 0;
        $vars['CheckNonRevenue'] = 0;
        $vars['CheckOrderMode'] = "";
        $vars['AuthAcquirerData'] = "";
        $vars['AuthAcquirerDatetime'] = "";
        $vars['AuthAcquirerMerchant'] = "";
        $vars['AuthAcquirerName'] = "";
        $vars['AuthAcquirerTerminal'] = "";
        $vars['AuthAmount'] = "";
        $vars['AuthAmountTotal'] = "";
        $vars['AuthCardNumber'] = "";
        $vars['AuthCode'] = "";
        $vars['AuthCurrencyCode'] = "";
        $vars['AuthCustomerData'] = "";
        $vars['AuthECashBalance'] = "";
        $vars['AuthEmployeeCode'] = "";
        $vars['AuthEmployeeName'] = "";
        $vars['AuthEmployeeNameL1'] = "";
        $vars['AuthEmployeeNameL2'] = "";
        $vars['AuthEmployeeNameL3'] = "";
        $vars['AuthEmployeeNameL4'] = "";
        $vars['AuthEmployeeNameL5'] = "";
        $vars['AuthEmployeeFirstName'] = "";
        $vars['AuthEmployeeFirstNameL1'] = "";
        $vars['AuthEmployeeFirstNameL2'] = "";
        $vars['AuthEmployeeFirstNameL3'] = "";
        $vars['AuthEmployeeFirstNameL4'] = "";
        $vars['AuthEmployeeFirstNameL5'] = "";
        $vars['AuthEmployeeLastName'] = "";
        $vars['AuthEmployeeLastNameL1'] = "";
        $vars['AuthEmployeeLastNameL2'] = "";
        $vars['AuthEmployeeLastNameL3'] = "";
        $vars['AuthEmployeeLastNameL4'] = "";
        $vars['AuthEmployeeLastNameL5'] = "";
        $vars['AuthEmv'] = "";
        $vars['AuthEmvData'] = "";
        $vars['AuthEntryMode'] = "";
        $vars['AuthIcCardSequence'] = "";
        $vars['AuthInvoiceNumber'] = "";
        $vars['AuthIssuer'] = "";
        $vars['AuthIntlCardTraceNum'] = "";
        $vars['AuthMerchantData'] = "";
        $vars['AuthReferenceNumber'] = "";
        $vars['AuthSignFree'] = "";
        $vars['AuthSignFreeData'] = "";
        $vars['AuthSlipType'] = "";
        $vars['AuthTerminalSequence'] = "";
        $vars['AuthTips'] = "";
        $vars['AuthTransactionDateTime'] = "";
        $vars['AuthTraceNumber'] = "";
        $vars['OtherInformation'] = "";
        $vars['HotelCurrency'] = "";
        $vars['AwardList'] = array();
        $vars['TipTrackingToEmployees'] = array();
        $vars['TipTrackingFromEmployee'] = "";
        $vars['TipTrackingFromEmployeeFirstName'] = "";
        $vars['TipTrackingFromEmployeeLastName'] = "";
        $vars['TipTrackingFromEmployeeNumber'] = "";
        $vars['TipTrackingTipBalance'] = "";
        $vars['TipTrackingServiceChargeBalance'] = "";
        $vars['TipTrackingDirectTipBalance'] = "";
        for ($index = 1; $index <= 5; $index++) {
            $vars['StationNameL' . $index] = "";
            $vars['TableNameL' . $index] = "";
            $vars['ShopNameL' . $index] = "";
            $vars['OutletNameL' . $index] = "";
            $vars['AddressL' . $index] = "";
            $vars['GreetingL' . $index] = "";
            $vars['CheckOpenEmployeeL' . $index] = "";
            $vars['CheckOpenEmployeeFirstNameL' . $index] = "";
            $vars['CheckOpenEmployeeLastNameL' . $index] = "";
            $vars['CheckOwnerEmployeeL' . $index] = "";
            $vars['CheckOwnerEmployeeFirstNameL' . $index] = "";
            $vars['CheckOwnerEmployeeLastNameL' . $index] = "";
            $vars['PrintEmployeeL' . $index] = "";
            $vars['PrintEmployeeFirstNameL' . $index] = "";
            $vars['PrintEmployeeLastNameL' . $index] = "";
            $vars['SC' . $index] = 0;
            $vars['CheckCloseEmployeeL' . $index] = "";
            $vars['CheckCloseEmployeeFirstNameL' . $index] = "";
            $vars['CheckCloseEmployeeLastNameL' . $index] = "";
            $vars['Message1L' . $index] = "";
            $vars['Message2L' . $index] = "";
            $vars['Message3L' . $index] = "";
            $vars['Message4L' . $index] = "";
            $vars['Message5L' . $index] = "";
            $vars['TipTrackingFromEmployeeFirstNameL' . $index] = "";
            $vars['TipTrackingFromEmployeeLastNameL' . $index] = "";
            $vars['TipTrackingFromEmployeeL' . $index] = "";
        }
        for ($index = 1; $index <= 25; $index++)
            $vars['Tax' . $index] = 0;

        if (!empty($checkTable)) {
            $vars['TableNumber'] = (($checkTable['PosCheckTable']['ctbl_table'] == 0) ? "" : $checkTable['PosCheckTable']['ctbl_table']) . $checkTable['PosCheckTable']['ctbl_table_ext'];
        }
        if (!empty($tableInfo)) {
            $vars['TableNameL1'] = $tableInfo[0]['OutFloorPlanTable']['flrt_name_l1'];
            $vars['TableNameL2'] = $tableInfo[0]['OutFloorPlanTable']['flrt_name_l2'];
            $vars['TableNameL3'] = $tableInfo[0]['OutFloorPlanTable']['flrt_name_l3'];
            $vars['TableNameL4'] = $tableInfo[0]['OutFloorPlanTable']['flrt_name_l4'];
            $vars['TableNameL5'] = $tableInfo[0]['OutFloorPlanTable']['flrt_name_l5'];
        }
        if (!empty($shop)) {
            $vars['ShopNameL1'] = $shop['OutShop']['shop_name_l1'];
            $vars['ShopNameL2'] = $shop['OutShop']['shop_name_l2'];
            $vars['ShopNameL3'] = $shop['OutShop']['shop_name_l3'];
            $vars['ShopNameL4'] = $shop['OutShop']['shop_name_l4'];
            $vars['ShopNameL5'] = $shop['OutShop']['shop_name_l5'];
        }
        $vars['OutletLogo'] = $outletLogo;
        if (!empty($outlet)) {
            $vars['OutletCurrencyCode'] = $outlet['OutOutlet']['olet_currency_code'];
            $vars['OutletNameL1'] = $outlet['OutOutlet']['olet_name_l1'];
            $vars['OutletNameL2'] = $outlet['OutOutlet']['olet_name_l2'];
            $vars['OutletNameL3'] = $outlet['OutOutlet']['olet_name_l3'];
            $vars['OutletNameL4'] = $outlet['OutOutlet']['olet_name_l4'];
            $vars['OutletNameL5'] = $outlet['OutOutlet']['olet_name_l5'];
            $vars['AddressL1'] = $outlet['OutOutlet']['olet_addr_l1'];
            $vars['AddressL2'] = $outlet['OutOutlet']['olet_addr_l2'];
            $vars['AddressL3'] = $outlet['OutOutlet']['olet_addr_l3'];
            $vars['AddressL4'] = $outlet['OutOutlet']['olet_addr_l4'];
            $vars['AddressL5'] = $outlet['OutOutlet']['olet_addr_l5'];
            $vars['Phone'] = $outlet['OutOutlet']['olet_phone'];
            $vars['Fax'] = $outlet['OutOutlet']['olet_fax'];
        }
        if (!empty($printStation)) {
            $vars['StationNameL1'] = $printStation['PosStation']['stat_name_l1'];
            $vars['StationNameL2'] = $printStation['PosStation']['stat_name_l2'];
            $vars['StationNameL3'] = $printStation['PosStation']['stat_name_l3'];
            $vars['StationNameL4'] = $printStation['PosStation']['stat_name_l4'];
            $vars['StationNameL5'] = $printStation['PosStation']['stat_name_l5'];
        }
        if (!$newCheck) {
            $vars['CheckNumber'] = $check['PosCheck']['chks_check_prefix_num'];
            $vars['CheckGuests'] = $check['PosCheck']['chks_guests'];
            $vars['CheckTotal'] = number_format($check['PosCheck']['chks_check_total'], $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");
            if (strcmp($check['PosCheck']['chks_ordering_type'], "t") == 0)
                $vars['CheckTakeout'] = 1;
            if (strcmp($check['PosCheck']['chks_non_revenue'], "y") == 0)
                $vars['CheckNonRevenue'] = 1;
            $vars['CheckOrderMode'] = $check['PosCheck']['chks_ordering_mode'];
        }

        if (!empty($shop))
            $vars['PrintTime'] = date('H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));
        else
            $vars['PrintTime'] = date('H:i:s');
        if (!empty($printUser)) {
            for ($langIndex = 1; $langIndex <= 5; $langIndex++) {
                $vars['PrintEmployeeFirstNameL' . $langIndex] = $printUser['UserUser']['user_first_name_l' . $langIndex];
                $vars['PrintEmployeeLastNameL' . $langIndex] = $printUser['UserUser']['user_last_name_l' . $langIndex];
                $vars['PrintEmployeeL' . $langIndex] = $vars['PrintEmployeeLastNameL' . $langIndex] . ' ' . $vars['PrintEmployeeFirstNameL' . $langIndex];
            }
        }

        //GC Member Balance
        if ($type == 'member_balance' || $type == 'member_enrollment') {
            if (isset($info['info']['memberNumber']))
                $vars['CheckMembershipIntfMemberNumber'] = $info['info']['memberNumber'];
            if (isset($info['info']['memberName']))
                $vars['CheckMembershipIntfAccountName'] = $info['info']['memberName'];
            if (isset($info['info']['memberTier']))
                $vars['CheckMembershipIntfMemberType'] = $info['info']['memberTier'];
            if (isset($info['info']['memberCurrentPoint']))
                $vars['CheckMembershipIntfPointBalance'] = $info['info']['memberCurrentPoint'];
            if (isset($info['info']['memberEquivCurrentPoint']))
                $vars['CheckMembershipIntfLocalBalance'] = $info['info']['memberEquivCurrentPoint'];
            if (isset($info['info']['memberExpiringPoint']))
                $vars['CheckMembershipIntfPointsAvailable'] = $info['info']['memberExpiringPoint'];
            if (isset($info['info']['memberEquivExpiringPoint']))
                $vars['CheckMembershipIntfTotalPointsBalance'] = $info['info']['memberEquivExpiringPoint'];
            if (isset($info['info']['memberExpiryDate']))
                $vars['CheckMembershipIntfExpiryDate'] = $info['info']['memberExpiryDate'];
        }

        if ($type == 'award_list') {
            $awardList = array();
            $awardArray = array();
            $award = "";

            if (isset($info['info']['award'])) {
                foreach ($info['info']['award'] as $key => $value) {
                    $awardArray['AwardPoints'] = $value['points'];
                    $awardArray['AwardAmount'] = $value['amount'];
                    $awardList[] = $awardArray;
                }
                $vars['AwardList'] = $awardList;
            }
            $vars['HotelCurrency'] = $info['info']['currency'];
        }

        //Loyalty Balance
        if ($type == 'loyalty_balance') {
            if (isset($info['info']['balanceDetail']['cardNumber']))
                $vars['CheckLoyaltyCardNumber'] = $info['info']['balanceDetail']['cardNumber'];
            if (isset($info['info']['balanceDetail']['balance']))
                $vars['CheckLoyaltyPointBalance'] = $info['info']['balanceDetail']['balance'];
            if (isset($info['info']['balanceDetail']['cardBalance']))
                $vars['CheckLoyaltyCardBalance'] = $info['info']['balanceDetail']['cardBalance'];
            if (isset($info['info']['balanceDetail']['memberNumber']))
                $vars['CheckLoyaltyMemberNumber'] = $info['info']['balanceDetail']['memberNumber'];
            if (isset($info['info']['balanceDetail']['guestName']))
                $vars['CheckLoyaltyMemberName'] = $info['info']['balanceDetail']['guestName'];
            if (isset($info['info']['balanceDetail']['balanceExpireThisMonth']))
                $vars['CheckLoyaltyBalanceExpireThisMonth'] = $info['info']['balanceDetail']['balanceExpireThisMonth'];
            if (isset($info['info']['balanceDetail']['balanceExpireNextMonth']))
                $vars['CheckLoyaltyBalanceExpireNextMonth'] = $info['info']['balanceDetail']['balanceExpireNextMonth'];
        }

        //Loyalty Auto Top up Slip
        if ($type == 'loyalty_auto_top_up') {
            if (isset($info['info']['topUpDetail']['transDate']))
                $vars['CheckLoyaltyTopUpTransDate'] = $info['info']['topUpDetail']['transDate'];
            if (isset($info['info']['topUpDetail']['refId']))
                $vars['CheckLoyaltyTopUpRefId'] = $info['info']['topUpDetail']['refId'];
            if (isset($info['info']['topUpDetail']['cardNumber']))
                $vars['CheckLoyaltyCardNumber'] = $info['info']['topUpDetail']['cardNumber'];
            if (isset($info['info']['topUpDetail']['topUpAmount']))
                $vars['CheckLoyaltyTopUpAmount'] = $info['info']['topUpDetail']['topUpAmount'];
        }

        // set Message(L1-5)
        if (isset($info['header']['messages'][0]['message'])) {
            if ((strcmp($type, "delete_item") == 0 || strcmp($type, "void_check") == 0) && !empty($voidReason)) {
                for ($index = 1; $index <= 5; $index++)
                    $vars['Message1L' . $index] = $voidReason['PosVoidReason']['vdrs_name_l' . $index];
            } else if (strcmp($type, "pantry_message") == 0 && !empty($pantryMessage)) {
                for ($index = 1; $index <= 5; $index++)
                    $vars['Message1L' . $index] = $pantryMessage['PosPantryMessage']['panm_name_l' . $index];
            } else {
                $vars['Message1'] = $info['header']['messages'][0]['message'];
                for ($index = 1; $index <= 5; $index++)
                    $vars['Message1L' . $index] = $info['header']['messages'][0]['message'];
            }
        }
        if (isset($info['header']['messages'][1]['message'])) {
            if ($type == 'change_table' || $type == 'merge_table' || $type == 'split_table' || $type == 'change_table_item' || $type == 'merge_table_item') {
                $vars['Message2'] = $info['header']['messages'][1]['message']['OriginalTableNameL1'] . ' -> ' . $info['header']['messages'][1]['message']['TargetTableNameL1'];
                for ($index = 1; $index <= 5; $index++)
                    $vars['Message2L' . $index] = $info['header']['messages'][1]['message']['OriginalTableNameL' . $index] . ' -> ' . $info['header']['messages'][1]['message']['TargetTableNameL' . $index];
            } else {
                $vars['Message2'] = $info['header']['messages'][1]['message'];
                for ($index = 1; $index <= 5; $index++)
                    $vars['Message2L' . $index] = $info['header']['messages'][1]['message'];
            }
        }
        if (isset($info['header']['messages'][2]['message'])) {
            $vars['Message3'] = $info['header']['messages'][2]['message'];
            for ($index = 1; $index <= 5; $index++)
                $vars['Message3L' . $index] = $info['header']['messages'][2]['message'];
        }
        if (isset($info['header']['messages'][3]['message'])) {
            $vars['Message4'] = $info['header']['messages'][3]['message'];
            for ($index = 1; $index <= 5; $index++)
                $vars['Message4L' . $index] = $info['header']['messages'][3]['message'];
        }
        if (isset($info['header']['messages'][4]['message'])) {
            $vars['Message5'] = $info['header']['messages'][4]['message'];
            for ($index = 1; $index <= 5; $index++)
                $vars['Message5L' . $index] = $info['header']['messages'][4]['message'];
        }

        if (isset($info['header']['otherInfo']))
            $vars['OtherInformation'] = json_encode($info['header']['otherInfo'], true);

        $menuItems = array();

        // read all check items
        $checkItems = array();
        $checkItemCount = 1;
        if ($haveItem) {
            foreach ($info['info']['citmIds'] as $checkItemInfo) {
                $checkItemId = $checkItemInfo['id'];
                $checkItem = $PosCheckModel->PosCheckItem->findById($checkItemId, -1);
                if (empty($checkItem))
                    continue;

                if ($checkItem['PosCheckItem']['citm_item_id'] > 0) {
                    if (!isset($menuItems[$checkItem['PosCheckItem']['citm_item_id']])) {
                        $menuItem = $MenuItemModel->findActiveById($checkItem['PosCheckItem']['citm_item_id']);
                        $menuItems[$checkItem['PosCheckItem']['citm_item_id']] = $menuItem;
                    } else
                        $menuItem = $menuItems[$checkItem['PosCheckItem']['citm_item_id']];

                    if (!empty($menuItem))
                        for ($index = 1; $index <= 5; $index++)
                            $checkItem['PosCheckItem']['item_info_l' . $index] = $menuItem['MenuItem']['item_info_l' . $index];
                } else {
                    $checkItem['PosCheckItem']['item_info_l1'] = "";
                    $checkItem['PosCheckItem']['item_info_l2'] = "";
                    $checkItem['PosCheckItem']['item_info_l3'] = "";
                    $checkItem['PosCheckItem']['item_info_l4'] = "";
                    $checkItem['PosCheckItem']['item_info_l5'] = "";
                }

                if ($checkItem['PosCheckItem']['citm_modifier_count'] > 0) {
                    if ($type == "delete_item" || $type == "void_check") {
                        $checkItemModifiers = $PosCheckModel->PosCheckItem->find('all', array(
                                'conditions' => array(
                                    'PosCheckItem.citm_parent_citm_id' => $checkItem['PosCheckItem']['citm_id'],
                                    'PosCheckItem.citm_role' => 'm'
                                ),
                                'order' => 'PosCheckItem.citm_id',
                                'recursive' => -1
                            )
                        );
                    } else
                        $checkItemModifiers = $PosCheckModel->PosCheckItem->findModifierByParentItemId($checkItem['PosCheckItem']['citm_id'], -1);
                    if (!empty($checkItemModifiers))
                        $checkItem['Modifiers'] = $checkItemModifiers;
                }
                if (isset($checkItemInfo['splitItemParentItemid']))
                    $checkItem['splitItemParentItemid'] = $checkItemInfo['splitItemParentItemid'];

                $checkItems[] = $checkItem;
            }
        }

        // loop the action print queue list
        $actionPrintQueueCount = 1;
        $groupKey = "";
        $printFormats = array();
        $printJobArray = array();
        $printQueueArray = array();
        $consolidatePrintQueueArray = array();
        foreach ($actionPrintQueues as $actionPrintQueue) {
            $printQueueArray = array();
            $consolidatePrintQueueArray = array();

            if ($actionPrintQueueCount == 1)
                $groupKey = $actionPrintQueue['group_key'];
            $actionPrintQueueCount++;

            // Display mode for display control system
            $displayMode = '';
            if (isset($actionPrintQueue['PosActionPrintQueue']['acpq_display_mode']))
                $displayMode = $actionPrintQueue['PosActionPrintQueue']['acpq_display_mode'];

            // break the loop if not same as first appear group key
            if (strcmp($groupKey, $actionPrintQueue['group_key']) != 0)
                break;

            if (!isset($printFormats[$actionPrintQueue['PosActionPrintQueue']['acpq_pfmt_id']])) {
                $printFormat = $PosPrintFormatModel->findActiveById($actionPrintQueue['PosActionPrintQueue']['acpq_pfmt_id']);
                if (empty($printFormat))
                    continue;
                $printFormats[$actionPrintQueue['PosActionPrintQueue']['acpq_pfmt_id']] = $printFormat;
            } else
                $printFormat = $printFormats[$actionPrintQueue['PosActionPrintQueue']['acpq_pfmt_id']];

            $printFormatId = $printFormat['PosPrintFormat']['pfmt_id'];

            //	Check if the print format view template file exists. If not, re-generate it
            if ($printFormat['PosPrintFormat']['pfmt_render_type'] == 't') {
                $renderView = "print_format_txt_" . $printFormatId;
                $printFileFmt = 'TXT';
                $printFileExt = '.txt';
                $PosPrintFormatModel->checkPrintFormatPlainTextViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $renderView, $this->controller->languages);
            } else if ($printFormat['PosPrintFormat']['pfmt_render_type'] == 'h') {
                $renderView = "print_format_html_" . $printFormatId;
                $printFileFmt = 'WEBPAGE';
                $printFileExt = '.html';
                $PosPrintFormatModel->checkPrintFormatHtmlViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $renderView, $this->controller->languages);
            } else if ($printFormat['PosPrintFormat']['pfmt_render_type'] == 'p') {
                $renderView = "print_format_pdf_" . $printFormatId;
                $printFileFmt = 'PDF';
                $printFileExt = '.pdf';
                $PosPrintFormatModel->checkPrintFormatTcpdfViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $renderView, $this->controller->languages);
            } else {
                $renderView = "print_format_pfi_" . $printFormatId;
                $printFileFmt = 'PFILE';
                $printFileExt = '.pfi';
                $PosPrintFormatModel->checkPrintFormatPfileViewFile($printFormat['PosPrintFormat']['pfmt_format'], $printFormat['PosPrintFormat']['pfmt_type'], $shareDataPath . $renderView, $this->controller->languages);
            }

            $prtFmtDefaultLang = 1;
            if ($printFormat['PosPrintFormat']['pfmt_lang'] != 0)
                $prtFmtDefaultLang = $printFormat['PosPrintFormat']['pfmt_lang'];
            else if ($posLangIndex != 0)
                $prtFmtDefaultLang = $posLangIndex;

            //change the default language value
            if ((strcmp($type, "delete_item") == 0 || strcmp($type, "void_check") == 0) && !empty($voidReason))
                $vars['Message1'] = $voidReason['PosVoidReason']['vdrs_name_l' . $prtFmtDefaultLang];
            else if (strcmp($type, "pantry_message") == 0 && !empty($pantryMessage))
                $vars['Message1'] = $pantryMessage['PosPantryMessage']['panm_name_l' . $prtFmtDefaultLang];

            //get date format
            if ($newCheck)
                $this->__updateDateFormat($printFormat, $outlet['OutOutlet']['olet_date_format'], $shop['OutShop']['shop_timezone'], $shop['OutShop']['shop_timezone_name'], $businessDay['PosBusinessDay']['bday_date'], "", "", "", $vars, "generateMultipleSpecialSlip");
            else
                $this->__updateDateFormat($printFormat, $outlet['OutOutlet']['olet_date_format'], $shop['OutShop']['shop_timezone'], $shop['OutShop']['shop_timezone_name'], $businessDay['PosBusinessDay']['bday_date'], $check['PosCheck']['chks_open_loctime'], $check['PosCheck']['chks_print_loctime'], $check['PosCheck']['chks_close_loctime'], $vars, "generateMultipleSpecialSlip");

            if (isset($info['info']['cardAuthority'])) {
                $acquirerInfo = $info['info']['cardAuthority']['pgtx_acquirer_info'];
                $otherInfo = $info['info']['cardAuthority']['pgtx_other_info'];
                $vars['AuthAmountTotal'] = $info['info']['authAmount'];

                $vars['AuthAcquirerData'] = $acquirerInfo['data'];
                $vars['AuthAcquirerDatetime'] = $acquirerInfo['datetime'];
                $vars['AuthAcquirerMerchant'] = $acquirerInfo['merchant_id'];
                $vars['AuthAcquirerName'] = $acquirerInfo['name'];
                $vars['AuthAcquirerTerminal'] = $acquirerInfo['terminal'];
                $vars['AuthAmount'] = $info['info']['cardAuthority']['pgtx_amount'];
                $vars['AuthCardNumber'] = $info['info']['cardAuthority']['pgtx_masked_pan'];
                $vars['AuthCode'] = $info['info']['cardAuthority']['pgtx_auth_code'];
                $vars['AuthCurrencyCode'] = $otherInfo['currency_code'];
                $vars['AuthECashBalance'] = $otherInfo['ecash_balance'];
                $vars['AuthEmployeeCode'] = $info['info']['cardAuthority']['pgtx_action_user_id'];
                $vars['AuthCustomerData'] = base64_decode($info['info']['cardAuthority']['pgtx_customer_copy']);
                $vars['AuthMerchantData'] = base64_decode($info['info']['cardAuthority']['pgtx_merchant_copy']);

                $paymentEmployee = array();
                $paymentEmployee = $UserUserModel->findActiveById($info['info']['cardAuthority']['pgtx_action_user_id'], -1);
                if ($paymentEmployee != null) {
                    $vars['AuthEmployeeFirstName'] = $paymentEmployee['UserUser']['user_first_name_l' . $prtFmtDefaultLang];
                    $vars['AuthEmployeeFirstNameL1'] = $paymentEmployee['UserUser']['user_first_name_l1'];
                    $vars['AuthEmployeeFirstNameL2'] = $paymentEmployee['UserUser']['user_first_name_l2'];
                    $vars['AuthEmployeeFirstNameL3'] = $paymentEmployee['UserUser']['user_first_name_l3'];
                    $vars['AuthEmployeeFirstNameL4'] = $paymentEmployee['UserUser']['user_first_name_l4'];
                    $vars['AuthEmployeeFirstNameL5'] = $paymentEmployee['UserUser']['user_first_name_l5'];
                    $vars['AuthEmployeeLastName'] = $paymentEmployee['UserUser']['user_last_name_l' . $prtFmtDefaultLang];
                    $vars['AuthEmployeeLastNameL1'] = $paymentEmployee['UserUser']['user_last_name_l1'];
                    $vars['AuthEmployeeLastNameL2'] = $paymentEmployee['UserUser']['user_last_name_l2'];
                    $vars['AuthEmployeeLastNameL3'] = $paymentEmployee['UserUser']['user_last_name_l3'];
                    $vars['AuthEmployeeLastNameL4'] = $paymentEmployee['UserUser']['user_last_name_l4'];
                    $vars['AuthEmployeeLastNameL5'] = $paymentEmployee['UserUser']['user_last_name_l5'];
                    $vars['AuthEmployeeName'] = $vars['AuthEmployeeLastName'] . ' ' . $vars['AuthEmployeeFirstName'];
                    $vars['AuthEmployeeNameL1'] = $vars['AuthEmployeeLastNameL1'] . ' ' . $vars['AuthEmployeeFirstNameL1'];
                    $vars['AuthEmployeeNameL2'] = $vars['AuthEmployeeLastNameL2'] . ' ' . $vars['AuthEmployeeFirstNameL2'];
                    $vars['AuthEmployeeNameL3'] = $vars['AuthEmployeeLastNameL3'] . ' ' . $vars['AuthEmployeeFirstNameL3'];
                    $vars['AuthEmployeeNameL4'] = $vars['AuthEmployeeLastNameL4'] . ' ' . $vars['AuthEmployeeFirstNameL4'];
                    $vars['AuthEmployeeNameL5'] = $vars['AuthEmployeeLastNameL5'] . ' ' . $vars['AuthEmployeeFirstNameL5'];
                }

                $vars['AuthEmv'] = $otherInfo['emv'];
                $vars['AuthEmvData'] = $otherInfo['emv_data'];
                $vars['AuthEntryMode'] = $info['info']['cardAuthority']['pgtx_entry_mode'];
                $vars['AuthIcCardSequence'] = $otherInfo['ic_card_seq'];
                $vars['AuthInvoiceNumber'] = $info['info']['cardAuthority']['pgtx_invoice_num'];
                $vars['AuthIssuer'] = $info['info']['cardAuthority']['pgtx_issuer'];
                $vars['AuthIntlCardTraceNum'] = $otherInfo['intl_card_trace_num'];
                $vars['AuthReferenceNumber'] = $info['info']['cardAuthority']['pgtx_ref_num'];
                $vars['AuthSignFree'] = $otherInfo['sign_free'];
                $vars['AuthSignFreeData'] = $otherInfo['sign_free_data'];
                $vars['AuthTerminalSequence'] = $otherInfo['terminal_seq'];
                $vars['AuthTips'] = $info['info']['cardAuthority']['pgtx_tips'];
                $vars['AuthTransactionDateTime'] = $info['info']['cardAuthority']['pgtx_action_time'];
                $vars['AuthTraceNumber'] = $otherInfo['intl_card_trace_num'];

                if (isset($info['info']['AuthSlipType']))
                    $vars['AuthSlipType'] = $info['info']['AuthSlipType'];
                else
                    $vars['AuthSlipType'] = 'm';
            }

            //Tip Tracking Slip
            if (isset($info['info']['tipTrackingInfo'])) {
                $fromUser = $UserUserModel->findActiveById($info['info']['tipTrackingInfo']['fromEmployeeId']);
                if (!empty($fromUser)) {
                    for ($langIndex = 1; $langIndex <= 5; $langIndex++) {
                        $vars['TipTrackingFromEmployeeFirstNameL' . $langIndex] = $fromUser['UserUser']['user_first_name_l' . $langIndex];
                        $vars['TipTrackingFromEmployeeLastNameL' . $langIndex] = $fromUser['UserUser']['user_last_name_l' . $langIndex];
                        $vars['TipTrackingFromEmployeeL' . $langIndex] =
                            $vars['TipTrackingFromEmployeeLastNameL' . $langIndex] . ' ' . $vars['TipTrackingFromEmployeeFirstNameL' . $langIndex];
                    }
                    $vars['TipTrackingFromEmployeeFirstName'] = $fromUser['UserUser']['user_first_name_l' . $prtFmtDefaultLang];
                    $vars['TipTrackingFromEmployeeLastName'] = $fromUser['UserUser']['user_last_name_l' . $prtFmtDefaultLang];
                    $vars['TipTrackingFromEmployee'] = $vars['TipTrackingFromEmployeeLastName'] . ' ' . $vars['TipTrackingFromEmployeeFirstName'];
                    $vars['TipTrackingFromEmployeeNumber'] = $fromUser['UserUser']['user_number'];
                }
                $toEmployeeList = array();
                $toEmployee = array();
                foreach ($info['info']['tipTrackingInfo']['toEmployeeIds'] as $toEmployeeArray) {
                    foreach ($toEmployeeArray as $toEmployeeId => $amountArray) {
                        $toUser = $UserUserModel->findActiveById($toEmployeeId);
                        if (!empty($toUser)) {
                            for ($langIndex = 1; $langIndex <= 5; $langIndex++) {
                                $toEmployee['TipTrackingToEmployeeFirstNameL' . $langIndex] = $toUser['UserUser']['user_first_name_l' . $langIndex];
                                $toEmployee['TipTrackingToEmployeeLastNameL' . $langIndex] = $toUser['UserUser']['user_last_name_l' . $langIndex];
                                $toEmployee['TipTrackingToEmployeeL' . $langIndex] =
                                    $toEmployee['TipTrackingToEmployeeLastNameL' . $langIndex] . ' ' . $toEmployee['TipTrackingToEmployeeFirstNameL' . $langIndex];
                            }
                            $toEmployee['TipTrackingToEmployeeFirstName'] = $toUser['UserUser']['user_first_name_l' . $prtFmtDefaultLang];
                            $toEmployee['TipTrackingToEmployeeLastName'] = $toUser['UserUser']['user_last_name_l' . $prtFmtDefaultLang];
                            $toEmployee['TipTrackingToEmployee'] =
                                $toEmployee['TipTrackingToEmployeeLastName'] . ' ' . $toEmployee['TipTrackingToEmployeeFirstName'];
                            $toEmployee['TipTrackingToEmployeeNumber'] = $toUser['UserUser']['user_number'];
                            $toEmployee['TipTrackingTipOutAmount'] = $amountArray[0];
                            $toEmployee['TipTrackingServiceChargeOutAmount'] = $amountArray[1];
                            $toEmployee['TipTrackingDirectTipOutAmount'] = $amountArray[2];
                            $toEmployeeList[] = $toEmployee;
                        }
                    }
                }
                $vars['TipTrackingToEmployees'] = $toEmployeeList;
                if (isset($info['info']['tipTrackingInfo']['tipBalance']))
                    $vars['TipTrackingTipBalance'] = $info['info']['tipTrackingInfo']['tipBalance'];
                if (isset($info['info']['tipTrackingInfo']['scBalance']))
                    $vars['TipTrackingServiceChargeBalance'] = $info['info']['tipTrackingInfo']['scBalance'];
                if (isset($info['info']['tipTrackingInfo']['directTipBalance']))
                    $vars['TipTrackingDirectTipBalance'] = $info['info']['tipTrackingInfo']['directTipBalance'];
            }

            $Items = array();
            if ($haveItem) {
                //grouping the item according to print format setup
                $groupedCheckItems = array();

                //Display Control item
                $displayControlItemArray = array();

                if ($printFormat['PosPrintFormat']['pfmt_group_item_by'] == 'c') {
                    foreach ($checkItems as $checkItem) {
                        if (count($groupedCheckItems) == 0)
                            $groupedCheckItems[] = $checkItem;
                        else {
                            $itemGrouped = false;
                            $itemPrtModifierIds = array();
                            if ($checkItem['PosCheckItem']['citm_modifier_count'] > 0) {
                                foreach ($checkItem['Modifiers'] as $checkItemModifier)
                                    $itemPrtModifierIds[] = $checkItemModifier['PosCheckItem']['citm_item_id'];
                            }

                            for ($itemIndex = 0; $itemIndex < count($groupedCheckItems); $itemIndex++) {
                                //whether same menu item id
                                if ($groupedCheckItems[$itemIndex]['PosCheckItem']['citm_item_id'] != $checkItem['PosCheckItem']['citm_item_id'])
                                    continue;

                                //whether same takeout status
                                if ($groupedCheckItems[$itemIndex]['PosCheckItem']['citm_ordering_type'] != $checkItem['PosCheckItem']['citm_ordering_type'])
                                    continue;

                                //whether same printing modifiers
                                if ($groupedCheckItems[$itemIndex]['PosCheckItem']['citm_modifier_count'] != $checkItem['PosCheckItem']['citm_modifier_count'])
                                    continue;
                                $samePrintingModi = true;
                                if (isset($groupedCheckItems[$itemIndex]['Modifiers'])) {
                                    foreach ($groupedCheckItems[$itemIndex]['Modifiers'] as $checkItemModifier) {
                                        if (!in_array($checkItemModifier['PosCheckItem']['citm_item_id'], $itemPrtModifierIds)) {
                                            $samePrintingModi = false;
                                            break;
                                        }
                                    }
                                }
                                if (!$samePrintingModi)
                                    continue;

                                $itemGrouped = true;
                                $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_round_total'] += $checkItem['PosCheckItem']['citm_round_total'];
                                $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_total'] += $checkItem['PosCheckItem']['citm_total'];
                                $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_round_amount'] += $checkItem['PosCheckItem']['citm_round_amount'];
                                $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_carry_total'] += $checkItem['PosCheckItem']['citm_carry_total'];
                                $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_qty'] += $checkItem['PosCheckItem']['citm_qty'];
                                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                                    $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_sc' . $scIndex] += $checkItem['PosCheckItem']['citm_sc' . $scIndex];
                                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                                    $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_tax' . $taxIndex] += $checkItem['PosCheckItem']['citm_tax' . $taxIndex];
                                for ($inclTaxRefIndex = 1; $inclTaxRefIndex <= 4; $inclTaxRefIndex++)
                                    $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_incl_tax_ref' . $inclTaxRefIndex] += $checkItem['PosCheckItem']['citm_incl_tax_ref' . $inclTaxRefIndex];
                                $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_pre_disc'] += $checkItem['PosCheckItem']['citm_pre_disc'];
                                $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_mid_disc'] += $checkItem['PosCheckItem']['citm_mid_disc'];
                                $groupedCheckItems[$itemIndex]['PosCheckItem']['citm_post_disc'] += $checkItem['PosCheckItem']['citm_post_disc'];
                                break;
                            }

                            if (!$itemGrouped)
                                $groupedCheckItems[] = $checkItem;
                        }
                    }
                } else
                    $groupedCheckItems = $checkItems;

                foreach ($groupedCheckItems as $checkItem) {
                    //$modifiers = null;
                    if (isset($checkItem['Modifiers']))
                        $modifiers = $checkItem['Modifiers'];
                    else
                        $modifiers = array();
                    $checkItemId = $checkItem['PosCheckItem']['citm_id'];

                    // create basic item params
                    $itemParams = $this->__createItemBasicParams($type, $checkId, $checkItem, $actionPrintQueue, $printQueueArray, $modifiers, $info, $vars['PrintTime']);
                    if (strcmp($type, "delete_item") == 0 && $checkItem['PosCheckItem']['citm_void_time'] == null)
                        $itemParams['QtyAfterDelete'] = $checkItem['PosCheckItem']['citm_qty'];

                    // Directly get the station printer setting as print queue
                    if ($actionPrintQueue['PosActionPrintQueue']['acpq_method'] == "1")
                        $printQueueArray[1] = $printStation['PosStation']['stat_station_printer1_prtq_id'];
                    else if ($actionPrintQueue['PosActionPrintQueue']['acpq_method'] == "2")
                        $printQueueArray[1] = $printStation['PosStation']['stat_station_printer2_prtq_id'];

                    foreach ($printQueueArray as $key => $printQueue) {
                        if ($printQueue == 0)
                            continue;

                        if (!isset($printQueueList[$printQueue]))
                            continue;

                        //	Check if display control plugin exists
                        if (array_key_exists("display_control", $this->controller->plugins)) {
                            $params2 = array(
                                'outletId' => $check['PosCheck']['chks_olet_id'],
                                'stationId' => intval($check['PosCheck']['chks_print_stat_id']),
                                'section' => 'system',
                                'variable' => 'audit_log_level'
                            );
                        }
                        $prtPrintQueueId = $this->__getPrintJobPrintQueueId($printStation, $printQueueList[$printQueue], $printQueueOverrideConditions, $businessDay['PosBusinessDay']['bday_date'], substr($check['PosCheck']['chks_open_loctime'], 11, 8), substr($checkItem['PosCheckItem']['citm_order_loctime'], 11, 8), $checkTable['PosCheckTable']['ctbl_table'], $checkTable['PosCheckTable']['ctbl_table_ext'], $checkItem['PosCheckItem']['citm_ordering_type'], $businessPeriod['PosBusinessPeriod']['bper_perd_id'], $check['PosCheck']['chks_ctyp_id'], $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay, $weekday);
                        if ($prtPrintQueueId == 0)
                            continue;

                        $this->__addAdditionalVar($printQueueList[$printQueue], $prtFmtDefaultLang, $shop, $outlet, $printUser, $printStation, $tableInfo, $vars);

                        $this->__addAdditionalItemParams($prtFmtDefaultLang, $checkItem['PosCheckItem'], null, null, null, null, (!empty($modifiers)) ? $modifiers : null, $itemParams);

                        // Check if the print queue is consolidate or not
                        $printMode = "";
                        if (strcmp($type, "rush_order") == 0)
                            $printMode = $actionPrintQueue['PosActionPrintQueue']['acpq_print_mode'];
                        else
                            $printMode = $printQueueList[$printQueue]['PosItemPrintQueue']['itpq_print_mode'];

                        if ($printMode == 'c')
                            $bIsConsolidate = true;
                        else
                            $bIsConsolidate = false;

                        if ($bIsConsolidate === false) {
                            $Items = array();
                            $Items[] = $itemParams;

                            if (strcmp($type, "rush_order") == 0)
                                $this->__packingRushOrderSpeacialSlipMessage($vars, $checkItem['PosCheckItem']);

                            //Construct the var array for view render
                            $vars['Items'] = array();
                            $vars['Items'] = $Items;
                            $tpls = json_decode($printFormat['PosPrintFormat']['pfmt_format'], true);

                            $printCtrls = array('mediaUrl' => $this->controller->Common->getDataUrl('media_files/'));

                            // Output the rendered page into HTML
                            if ($checkItemId > 0)
                                $outputFileName = $check['PosCheck']['chks_check_num'] . "-" . $type . "-" . date('YmdHis') . "-" . $checkItemId . "-" . $prtPrintQueueId . "-" . $numOfSlip;
                            else
                                $outputFileName = $check['PosCheck']['chks_check_num'] . "-" . $type . "-" . date('YmdHis') . "-" . $prtPrintQueueId;

                            $outputFile = $configPath . $outputFileName . $printFileExt;
                            $outputDest = 'F';
                            $outputView = new View($this->controller, false);
                            App::build(array('View' => array($shareDataPath)));
                            $outputView->viewPath = '';
                            $outputView->set(compact('tpls', 'vars', 'outputFile', 'outputDest', 'printCtrls'));
                            $viewContent = @$outputView->render($renderView, '');        //	Render with '@' to ignore all error to log
                            if ($printFormat['PosPrintFormat']['pfmt_render_type'] != 'p')
                                file_put_contents($outputFile, $viewContent);

                            $param = array();
                            $param['printJobFile'] = Router::url($configUrl . $outputFileName . $printFileExt, array('full' => true, 'escape' => true));
                            $param['printQ'] = $prtPrintQueueId;
                            $param['printJobFileMediaType'] = $printFileFmt;
                            $param['printJobFileType'] = 'ACTSLIP';

                            // Kitchen display system
                            if (array_key_exists("display_control", $this->controller->plugins) && isset($printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id']) && $printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id'] > 0) {
                                if ($displayMode == '' || strcmp($type, "rush_order") == 0)
                                    $this->__createJobToDisplayScreen($vars, $checkTable['PosCheckTable']['ctbl_chks_id'], $type, $printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id'], $posLangIndex, $displayMode);
                                else if ($displayMode == 'c') {
                                    if (isset($displayControlItemArray[$printQueue]))
                                        $itemArray = $displayControlItemArray[$printQueue];
                                    else
                                        $itemArray = array();
                                    $itemArray[] = $itemParams;
                                    $displayControlItemArray[$printQueue] = $itemArray;
                                }
                            }
                            array_push($printJobArray, $param);
                            $numOfSlip++;
                        } else {
                            // Handle the rush order in combine print mode
                            if (strcmp($type, "rush_order") == 0 && array_key_exists("display_control", $this->controller->plugins) &&
                                isset($printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id']) && $printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id'] > 0) {
                                $this->__packingRushOrderSpeacialSlipMessage($vars, $checkItem['PosCheckItem']);
                                //Construct the var array for view render
                                $Items = array();
                                $Items[] = $itemParams;
                                $vars['Items'] = array();
                                $vars['Items'] = $Items;
                                $this->__createJobToDisplayScreen($vars, $checkTable['PosCheckTable']['ctbl_chks_id'], $type, $printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id'], $posLangIndex, $displayMode);
                            }

                            if (isset($consolidatePrintQueueArray[$printQueue]))
                                $itemArray = $consolidatePrintQueueArray[$printQueue];
                            else
                                $itemArray = array();
                            $itemArray[] = $itemParams;
                            // Kitchen display system
                            if (array_key_exists("display_control", $this->controller->plugins) && isset($printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id']) && $printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id'] > 0) {
                                if ($displayMode == 'c' && strcmp($type, "rush_order") != 0) {
                                    if (isset($displayControlItemArray[$printQueue]))
                                        $itemArrayForDisplayControl = $displayControlItemArray[$printQueue];
                                    else
                                        $itemArrayForDisplayControl = array();
                                    $itemArrayForDisplayControl[] = $itemParams;
                                    $displayControlItemArray[$printQueue] = $itemArrayForDisplayControl;
                                }
                            }
                            $consolidatePrintQueueArray[$printQueue] = $itemArray;
                        }
                    }
                }

                // Send kitchen display job if display mode is combine item
                if (array_key_exists("display_control", $this->controller->plugins) && strcmp($type, "rush_order") != 0) {
                    foreach ($displayControlItemArray as $displayControlPrintQueue => $itemArray) {
                        $printQueue = $printQueueList[$displayControlPrintQueue];
                        $vars['Items'] = array();
                        $vars['Items'] = $itemArray;
                        $this->__createJobToDisplayScreen($vars, $checkTable['PosCheckTable']['ctbl_chks_id'], $type, $printQueue['PosItemPrintQueue']['itpq_dpsn_id'], $posLangIndex, $displayMode);
                    }
                }

                // Process consolidate slip
                if (count($consolidatePrintQueueArray) > 0) {
                    foreach ($consolidatePrintQueueArray as $consolidPrintQueue => $itemArray) {
                        $params = array();
                        $printQueue = $printQueueList[$consolidPrintQueue];

                        // Get print job print queue Id
                        $overridePrtqItemArray = array();
                        $printJobPrintQueueId = $this->__getPrintJobPrintQueueId($printStation, $printQueue, $printQueueOverrideConditions, $businessDay['PosBusinessDay']['bday_date'], substr($check['PosCheck']['chks_open_loctime'], 11, 8), "", $checkTable['PosCheckTable']['ctbl_table'], $checkTable['PosCheckTable']['ctbl_table_ext'], "", $businessPeriod['PosBusinessPeriod']['bper_perd_id'], $check['PosCheck']['chks_ctyp_id'], $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay, $weekday, $itemArray, $overridePrtqItemArray);
                        if ($overridePrtqItemArray != null && !empty($overridePrtqItemArray)) {
                            $baseItemArray = array();
                            $overridedItemArray = array();
                            $overridedItemIndexArray = array();
                            foreach ($overridePrtqItemArray as $overridedPrtqId => $overrideItemIndex) {
                                foreach ($overrideItemIndex as $overrideIndex) {
                                    if ($overridedPrtqId > 0)
                                        $overridedItemArray[$overridedPrtqId][] = $itemArray[$overrideIndex];
                                    $overridedItemIndexArray[] = $overrideIndex;
                                }
                            }

                            for ($index = 0; $index < count($itemArray); $index++) {
                                $baseItem = true;
                                foreach ($overridedItemIndexArray as $key => $overrideItemIndex) {
                                    if ($index == $overrideItemIndex) {
                                        $baseItem = false;
                                        break;
                                    }
                                }
                                if ($baseItem)
                                    $baseItemArray[] = $itemArray[$index];
                            }
                            $itemArray = $baseItemArray;
                        }

                        if ($printJobPrintQueueId > 0) {
                            // Get print format default language
                            $posPrintFormat = $printFormat;
                            if ($prtFmtDefaultLang > 0) {
                                $this->__addAdditionalVar($printQueue, $prtFmtDefaultLang, $shop, $outlet, $printUser, $printStation, $tableInfo, $vars);

                                if ($posPrintFormat['PosPrintFormat']['pfmt_sort_item_by1'] != "") {
                                    $sortedItemArray = $this->__sortItemArray($posPrintFormat['PosPrintFormat']['pfmt_sort_item_by1'], $itemArray);
                                    if (!empty($sortedItemArray))
                                        $itemArray = $sortedItemArray;

                                    if ($posPrintFormat['PosPrintFormat']['pfmt_sort_item_by2'] != "") {
                                        $sortedItemArray = $this->__sortItemArrayBaseOnGroup($posPrintFormat['PosPrintFormat']['pfmt_sort_item_by1'], $posPrintFormat['PosPrintFormat']['pfmt_sort_item_by2'], $itemArray);
                                        if (!empty($sortedItemArray))
                                            $itemArray = $sortedItemArray;
                                    }
                                }
                                for ($index = 1; $index < count($itemArray); $index++)
                                    unset($itemArray[$index]['ItemMenuId']);

                                $Items = array();
                                $Items = $itemArray;

                                //Construct the var array for view render
                                $vars['Items'] = array();
                                $vars['Items'] = $itemArray;
                                $tpls = json_decode($printFormat['PosPrintFormat']['pfmt_format'], true);
                                $printCtrls = array('mediaUrl' => $this->controller->Common->getDataUrl('media_files/'));

                                //output the rendered page into HTML
                                $outputFileName = $check['PosCheck']['chks_check_num'] . "-" . $type . "-" . date('YmdHis') . "-" . $printJobPrintQueueId . "-" . $numOfSlip;
                                $outputFile = $configPath . $outputFileName . $printFileExt;
                                $outputDest = 'F';
                                $outputView = new View($this->controller, false);
                                App::build(array('View' => array($shareDataPath)));
                                $outputView->viewPath = '';
                                $outputView->set(compact('tpls', 'vars', 'outputFile', 'outputDest', 'printCtrls'));
                                $viewContent = @$outputView->render($renderView, '');        //	Render with '@' to ignore all error to log
                                if ($printFormat['PosPrintFormat']['pfmt_render_type'] != 'p')
                                    file_put_contents($outputFile, $viewContent);

                                // kitchen display system
                                if ($displayMode == '' && strcmp($type, "rush_order") != 0)
                                    $this->__createJobToDisplayScreen($vars, $checkTable['PosCheckTable']['ctbl_chks_id'], $type, $printQueue['PosItemPrintQueue']['itpq_dpsn_id'], $posLangIndex, $displayMode);
                                $param = array();
                                $param['printJobFile'] = Router::url($configUrl . $outputFileName . $printFileExt, array('full' => true, 'escape' => true));
                                $param['printQ'] = $printJobPrintQueueId;
                                $param['printJobFileMediaType'] = $printFileFmt;
                                $param['printJobFileType'] = 'ACTSLIP';
                                array_push($printJobArray, $param);
                                $numOfSlip++;
                            }
                        }

                        if ($overridePrtqItemArray != null && !empty($overridePrtqItemArray)) {
                            foreach ($overridedItemArray as $overridedPrtqId => $overridePrtqItems) {
                                $this->__updatePrintQueueName($overridedPrtqId, $printQueue);

                                $posPrintFormat = $printFormat;

                                if ($prtFmtDefaultLang > 0) {
                                    $this->__addAdditionalVar($printQueue, $prtFmtDefaultLang, $shop, $outlet, $printUser, $printStation, $tableInfo, $vars);

                                    if ($posPrintFormat['PosPrintFormat']['pfmt_sort_item_by1'] != "") {
                                        $sortedItemArray = $this->__sortItemArray($posPrintFormat['PosPrintFormat']['pfmt_sort_item_by1'], $overridePrtqItems);
                                        if (!empty($sortedItemArray))
                                            $overridePrtqItems = $sortedItemArray;

                                        $sortedItemArray = $this->__sortItemArrayBaseOnGroup($posPrintFormat['PosPrintFormat']['pfmt_sort_item_by1'], $posPrintFormat['PosPrintFormat']['pfmt_sort_item_by2'], $overridePrtqItems);
                                        if (!empty($sortedItemArray))
                                            $overridePrtqItems = $sortedItemArray;
                                    }
                                    for ($index = 1; $index < count($overridePrtqItems); $index++)
                                        unset($overridePrtqItems[$index]['ItemMenuId']);

                                    $Items = $overridePrtqItems;

                                    //Construct the var array for view render
                                    $vars['Items'] = array();
                                    $vars['Items'] = $Items;
                                    $tpls = json_decode($printFormat['PosPrintFormat']['pfmt_format'], true);

                                    $printCtrls = array('mediaUrl' => $this->controller->Common->getDataUrl('media_files/'));

                                    //output the rendered page into HTML
                                    $outputFileName = $check['PosCheck']['chks_check_num'] . "-" . $type . "-" . date('YmdHis') . "-" . $overridedPrtqId . "-" . $numOfSlip;
                                    $outputDest = 'F';
                                    $outputFile = $configPath . $outputFileName . $printFileExt;
                                    $outputView = new View($this->controller, false);
                                    $outputView->set(compact('tpls', 'vars', 'outputFile', 'outputDest', 'printCtrls'));
                                    $outputView->viewPath = '';
                                    $viewContent = @$outputView->render($renderView, '');
                                    if ($printFormat['PosPrintFormat']['pfmt_render_type'] != 'p')
                                        file_put_contents($outputFile, $viewContent);

                                    // kitchen display system
                                    if ($displayMode == '' && strcmp($type, "rush_order") != 0)
                                        $this->__createJobToDisplayScreen($vars, $checkTable['PosCheckTable']['ctbl_chks_id'], $type, $printQueue['PosItemPrintQueue']['itpq_dpsn_id'], $posLangIndex, $displayMode);

                                    $param = array();
                                    $param['printJobFile'] = Router::url($configUrl . $outputFileName . $printFileExt, array('full' => true, 'escape' => true));
                                    $param['printQ'] = $overridedPrtqId;
                                    $param['printJobFileMediaType'] = $printFileFmt;
                                    $param['printJobFileType'] = 'ACTSLIP';
                                    array_push($printJobArray, $param);
                                    $numOfSlip++;
                                }
                            }
                        }
                    }
                }
            } else {
                if ($actionPrintQueue['PosActionPrintQueue']['acpq_method'] == "s")
                    $printQueueArray[1] = $actionPrintQueue['PosActionPrintQueue']['acpq_itpq_id'];

                // Directly get the station printer setting as print queue
                else if ($actionPrintQueue['PosActionPrintQueue']['acpq_method'] == "1") {
                    $printQueueArray[1] = $printStation['PosStation']['stat_station_printer1_prtq_id'];
                } else if ($actionPrintQueue['PosActionPrintQueue']['acpq_method'] == "2") {
                    $printQueueArray[1] = $printStation['PosStation']['stat_station_printer2_prtq_id'];
                }
                $Items = array();
                foreach ($printQueueArray as $key => $printQueue) {
                    if ($printQueue > 0) {
                        if (!empty($printQueueList) && $printQueueList[$printQueue]['PosItemPrintQueue']['itpq_station_printer'] > 0) {
                            if ($printQueueList[$printQueue]['PosItemPrintQueue']['itpq_station_printer'] == 1)
                                $prtPrintQueueId = $printStation['PosStation']['stat_station_printer1_prtq_id'];
                            else
                                $prtPrintQueueId = $printStation['PosStation']['stat_station_printer2_prtq_id'];
                            $prtPrintQueue = $PrtPrintQueueModel->findActiveById($prtPrintQueueId);
                            if (empty($prtPrintQueue))
                                continue;
                            if (isset($prtPrintQueue['PrtPrintQueue']['prtq_id'])) {
                                $printQueueList[$printQueue]['PosItemPrintQueue']['name_l1'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l1'];
                                $printQueueList[$printQueue]['PosItemPrintQueue']['name_l2'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l2'];
                                $printQueueList[$printQueue]['PosItemPrintQueue']['name_l3'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l3'];
                                $printQueueList[$printQueue]['PosItemPrintQueue']['name_l4'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l4'];
                                $printQueueList[$printQueue]['PosItemPrintQueue']['name_l5'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l5'];
                            }
                        } else if ($actionPrintQueue['PosActionPrintQueue']['acpq_method'] == "1" || $actionPrintQueue['PosActionPrintQueue']['acpq_method'] == "2") {
                            $prtPrintQueueId = $printQueue;
                        } else
                            $prtPrintQueueId = $printQueueList[$printQueue]['PosItemPrintQueue']['itpq_prtq_id'];

                        if (!$newCheck)
                            $prtPrintQueueId = $this->__checkPrintQueueOverride($printQueueOverrideConditions, $prtPrintQueueId, $businessDay['PosBusinessDay']['bday_date'], substr($check['PosCheck']['chks_open_loctime'], 11, 8), "", $checkTable['PosCheckTable']['ctbl_table'], $checkTable['PosCheckTable']['ctbl_table_ext'], "", $printStation['PosStation']['stat_stgp_id'], $businessPeriod['PosBusinessPeriod']['bper_perd_id'], $check['PosCheck']['chks_ctyp_id'], $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay, $weekday);

                        if ($prtPrintQueueId == 0)
                            continue;
                        if (!empty($shop))
                            $vars['ShopName'] = $shop['OutShop']['shop_name_l' . $prtFmtDefaultLang];
                        if (!empty($outlet)) {
                            $vars['OutletName'] = $outlet['OutOutlet']['olet_name_l' . $prtFmtDefaultLang];
                            $vars['Address'] = $outlet['OutOutlet']['olet_addr_l' . $prtFmtDefaultLang];
                        }
                        if (!empty($tableInfo))
                            $vars['TableName'] = $tableInfo[0]['OutFloorPlanTable']['flrt_name_l' . $prtFmtDefaultLang];

                        if (!empty($printUser)) {
                            $vars['PrintEmployeeFirstName'] = $printUser['UserUser']['user_first_name_l' . $prtFmtDefaultLang];
                            $vars['PrintEmployeeLastName'] = $printUser['UserUser']['user_last_name_l' . $prtFmtDefaultLang];
                            $vars['PrintEmployee'] = $vars['PrintEmployeeLastName'] . ' ' . $vars['PrintEmployeeFirstName'];
                        }

                        if (!empty($printQueueList)) {
                            $vars['PrintQueueName'] = $printQueueList[$printQueue]['PosItemPrintQueue']['name_l' . $prtFmtDefaultLang];
                            $vars['PrintQueueNameL1'] = $printQueueList[$printQueue]['PosItemPrintQueue']['name_l1'];
                            $vars['PrintQueueNameL2'] = $printQueueList[$printQueue]['PosItemPrintQueue']['name_l2'];
                            $vars['PrintQueueNameL3'] = $printQueueList[$printQueue]['PosItemPrintQueue']['name_l3'];
                            $vars['PrintQueueNameL4'] = $printQueueList[$printQueue]['PosItemPrintQueue']['name_l4'];
                            $vars['PrintQueueNameL5'] = $printQueueList[$printQueue]['PosItemPrintQueue']['name_l5'];
                        }

                        // Construct the var array for view render
                        $vars['Items'] = $Items;
                        $tpls = json_decode($printFormat['PosPrintFormat']['pfmt_format'], true);

                        $printCtrls = array('mediaUrl' => $this->controller->Common->getDataUrl('media_files/'));

                        // Output the rendered page into HTML
                        if (!$newCheck)
                            $outputFileName = $check['PosCheck']['chks_check_num'] . "-" . $type . "-" . date('YmdHis') . "-" . $prtPrintQueueId . "-" . $numOfSlip;
                        else
                            $outputFileName = $type . "-" . date('YmdHis') . "-" . $prtPrintQueueId . "-" . $numOfSlip;

                        if (isset($vars['AuthCode']) && !empty($vars['AuthCode'])) {
                            if (isset($vars['AuthSlipType']))
                                $outputFileName .= "-" . $vars['AuthCode'] . "-" . $vars['AuthSlipType'];
                            else
                                $outputFileName .= "-" . $vars['AuthCode'];
                        }

                        $outputFile = $configPath . $outputFileName . $printFileExt;
                        $outputDest = 'F';

                        // kitchen display system
                        if (array_key_exists("display_control", $this->controller->plugins) && !empty($printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id']))
                            $this->__createJobToDisplayScreen($vars, $checkTable['PosCheckTable']['ctbl_chks_id'], $type, $printQueueList[$printQueue]['PosItemPrintQueue']['itpq_dpsn_id'], $posLangIndex, $displayMode);

                        $outputView = new View($this->controller, false);
                        App::build(array('View' => array($shareDataPath)));
                        $outputView->viewPath = '';
                        $outputView->set(compact('tpls', 'vars', 'outputFile', 'outputDest', 'printCtrls'));
                        $viewContent = @$outputView->render($renderView, '');
                        if ($printFormat['PosPrintFormat']['pfmt_render_type'] != 'p')
                            file_put_contents($outputFile, $viewContent);

                        $param = array();
                        $param['printJobFile'] = Router::url($configUrl . $outputFileName . $printFileExt, array('full' => true, 'escape' => true));
                        $param['printQ'] = $prtPrintQueueId;
                        $param['printJobFileMediaType'] = $printFileFmt;
                        $param['printJobFileType'] = 'ACTSLIP';
                        array_push($printJobArray, $param);
                        $numOfSlip++;

                        // Updated var before print a slip
                        if (isset($vars['AuthSlipType']))
                            $vars['AuthSlipType'] = "c";
                    }
                }
            }
        }

        if (count($printJobArray) > 0) {
            App::import('Component', 'Printing.PrintingApiGeneral');
            $PrintingApiGeneral = new PrintingApiGeneralComponent(new ComponentCollection());
            $PrintingApiGeneral->startup($this->controller);

            $reply = array();
            $PrintingApiGeneral->addPrintJobs($printJobArray, $reply);
        }

        return '';
    }

    /**
     * The generate output of the testing printer slip
     * @param integer $posItpqId pos_item_print_queue id
     * @param integer $userId user id
     * @param integer $stationId station id
     */
    public function generateTestingPrinterSlip($posItpqId, $userId, $stationId)
    {
        Configure::write('debug', 0);

        //	Check if printing plugin exists
        if (!array_key_exists("printing", $this->controller->plugins))
            return 'load_model_error';

        //	Load required model
        $modelArray = array('Pos.PosConfig', 'Pos.PosStation', 'Pos.PosItemPrintQueue', 'Pos.PosBusinessDay', 'User.UserUser', 'Outlet.OutShop', 'Menu.MenuItemPrintQueue', 'Printing.PrtPrintQueue');
        if ($this->controller->importModels($modelArray) !== true)
            return 'load_model_error';

        $PosConfigModel = $this->controller->PosConfig;
        $PosStationModel = $this->controller->PosStation;
        $PosItemPrintQueueModel = $this->controller->PosItemPrintQueue;
        $PosBusinessDayModel = $this->controller->PosBusinessDay;
        $UserUserModel = $this->controller->UserUser;
        $OutShopModel = $this->controller->OutShop;
        $MenuItemPrintQueue = $this->controller->MenuItemPrintQueue;
        $PrtPrintQueueModel = $this->controller->PrtPrintQueue;

        //	Add this data path in View folder and set The viewPath be empty (default is the controller name)
        App::build(array('View' => array(ROOT . DS . APP_DIR . DS . 'Plugin' . DS . 'Pos' . DS . 'View' . DS . 'PrintFormats')));

        //	Handle for old print service
        $printServerConfig = Configure::read('Printing.print_service');
        if ($printServerConfig == "1.0") {
            $renderView = "testing_slip_html";
            $printFileFmt = 'WEBPAGE';
            $printFileExt = '.html';
            $this->controller->layout = 'print_slip';
        } else {
            $renderView = "testing_slip";
            $printFileFmt = 'PFILE';
            $printFileExt = '.pfi';
            $this->controller->layout = 'ajax';
        }

        $configPath = $this->controller->Common->getDataPath(array('pos_print_jobs'), true);
        $configUrl = $this->controller->Common->getDataUrl('pos_print_jobs/');
        if (empty($configPath) || empty($configUrl))
            return;

        //get print user
        $printUser = $UserUserModel->findActiveById($userId);

        //get print station
        $station = $PosStationModel->find('first', array(
                'conditions' => array('stat_id' => $stationId),
                'recursive' => -1
            )
        );

        //get outlet
        $outlet = $OutShopModel->OutOutlet->findActiveById($station['PosStation']['stat_olet_id']);

        //get shop
        $shop = $OutShopModel->findActiveById($outlet['OutOutlet']['olet_shop_id']);

        //get business day
        $businessDay = null;
        if (!empty($outlet))
            $businessDay = $PosBusinessDayModel->findActiveByOutletId($outlet['OutOutlet']['olet_id']);

        //get pos item print queue
        $posItemPrintQueue = $PosItemPrintQueueModel->findActiveById($posItpqId);

        //get printing print queue
        $prtPrintQueue = null;
        if ($posItemPrintQueue != null) {
            if ($posItemPrintQueue['PosItemPrintQueue']['itpq_station_printer'] == 0) {
                $itemPrintQueue = $MenuItemPrintQueue->findActiveById($posItemPrintQueue['PosItemPrintQueue']['itpq_itpq_id']);
                $prtPrintQueue = $PrtPrintQueueModel->findActiveById($posItemPrintQueue['PosItemPrintQueue']['itpq_prtq_id']);
                $prtPrintQueueId = $posItemPrintQueue['PosItemPrintQueue']['itpq_prtq_id'];
            } else if ($posItemPrintQueue['PosItemPrintQueue']['itpq_station_printer'] == 1) {
                $itemPrintQueue = $MenuItemPrintQueue->findActiveById($posItemPrintQueue['PosItemPrintQueue']['itpq_itpq_id']);
                $prtPrintQueue = $PrtPrintQueueModel->findActiveById($station['PosStation']['stat_station_printer1_prtq_id']);
                $prtPrintQueueId = $station['PosStation']['stat_station_printer1_prtq_id'];
            } else if ($posItemPrintQueue['PosItemPrintQueue']['itpq_station_printer'] == 2) {
                $itemPrintQueue = $MenuItemPrintQueue->findActiveById($posItemPrintQueue['PosItemPrintQueue']['itpq_itpq_id']);
                $prtPrintQueue = $PrtPrintQueueModel->findActiveById($station['PosStation']['stat_station_printer2_prtq_id']);
                $prtPrintQueueId = $station['PosStation']['stat_station_printer2_prtq_id'];
            }
        }

        //construct printing variables
        $vars = array();
        $vars['PrintQueueName'] = "";
        /*if($prtPrintQueue != null)
			$vars['PrintQueueName'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l1'];*/
        if ($itemPrintQueue != null)
            $vars['PrintQueueName'] = $itemPrintQueue['MenuItemPrintQueue']['itpq_name_l1'];
        $vars['PrintEmployeeFirstName'] = $printUser['UserUser']['user_first_name_l1'];
        $vars['PrintEmployeeLastName'] = $printUser['UserUser']['user_last_name_l1'];
        $vars['PrintEmployee'] = $vars['PrintEmployeeLastName'] . ' ' . $vars['PrintEmployeeFirstName'];

        if (!empty($businessDay))
            $vars['BusinessDate'] = $businessDay['PosBusinessDay']['bday_date'];
        else
            $vars['BusinessDate'] = "";
        $vars['PrintDate'] = date('Y-m-d', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));
        $vars['PrintTime'] = date('H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));

        $printJobArray = array();
        // Output the rendered page into HTML
        $outputFileName = "testPrinter-" . date('YmdHis') . "-" . $prtPrintQueueId . $printFileExt;
        $outputFile = $configPath . $outputFileName;
        $outputView = new View($this->controller, false);
        $outputView->set(compact('vars'));
        $outputView->viewPath = '';

        $viewContent = @$outputView->render($renderView);
        file_put_contents($outputFile, $viewContent);

        $param = array();
        $param['printJobFile'] = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));
        $param['printQ'] = $prtPrintQueueId;
        $param['printJobFileMediaType'] = $printFileFmt;
        $param['printJobFileType'] = 'TESTSLIP';
        array_push($printJobArray, $param);

        if (count($printJobArray) > 0) {
            App::import('Component', 'Printing.PrintingApiGeneral');
            $this->PrintingApiGeneral = new PrintingApiGeneralComponent(new ComponentCollection());
            $this->PrintingApiGeneral->startup($this->controller);

            $reply = array();
            $this->PrintingApiGeneral->addPrintJobs($printJobArray, $reply);

        }

        // Leave controller without any result view rendered
        return Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));
    }

    /**
     * Generate output of the user time in/out slip
     * @param integer $outletId Outlet ID
     * @      integer $businessDayId        BusinessDayID
     * @      integer $userId                User ID
     * @      integer $langIndex            Current language index in client
     * @      integer $printQueueId            Print queue ID
     * @      string  $resultFileName        A variable to store the result file name
     */
    public function generateUserTimeOutSlip($businessDayId, $outletId, $userId, $printQueueId, $langIndex, &$resultFileName = '')
    {
        Configure::write('debug', 0);
        set_time_limit(600);

        //	Check if printing plugin exists
        if (!array_key_exists("printing", $this->controller->plugins))
            return 'load_model_error';

        //	Load required model
        $modelArray = array('SysConfig', 'User.UserUser', 'Outlet.OutShop', 'Outlet.OutOutlet', 'Pos.PosBusinessDay', 'Pos.PosUserTimeInOutLog');
        if ($this->controller->importModels($modelArray) !== true)
            return 'load_model_error';

        $SysConfigModel = $this->controller->SysConfig;
        $UserUserModel = $this->controller->UserUser;
        $OutShopModel = $this->controller->OutShop;
        $OutOutletModel = $this->controller->OutOutlet;
        $PosBusinessDayModel = $this->controller->PosBusinessDay;
        $PosUserTimeInOutModel = $this->controller->PosUserTimeInOutLog;

        //add this data path in View folder and set the viewPath be empty (default is the controller name)
        App::build(array('View' => array(ROOT . DS . APP_DIR . DS . 'Plugin' . DS . 'Pos' . DS . 'View' . DS . 'PosUserTimeOutSlipFormats')));

        //	Handle for old print service
        $printServerConfig = Configure::read('Printing.print_service');
        if ($printServerConfig == "1.0") {
            $renderView = 'user_time_out_slip_format_html';
            $printFileFmt = 'WEBPAGE';
            $printFileExt = '.html';
            $this->controller->layout = 'print_slip';
        } else {
            $renderView = 'user_time_out_slip_format';
            $printFileFmt = 'PFILE';
            $printFileExt = '.pfi';
            $this->controller->layout = 'ajax';
        }

        //get the target print job path
        $configPath = $this->controller->Common->getDataPath(array('pos_print_jobs'), true);
        $configUrl = $this->controller->Common->getDataUrl('pos_print_jobs/');
        if (empty($configPath) || empty($configUrl))
            return "missing_config_path";

        //get language information
        $currentLangIndex = 1;
        $langUrl = "eng";
        $sysConfig = $SysConfigModel->findConfig('system', 'language_code', $langIndex);
        if (!empty($sysConfig)) {
            if ($sysConfig[0]['SysConfig']['scfg_value'] != null && $sysConfig[0]['SysConfig']['scfg_value'] != "") {
                $langJSONInfo = json_decode($sysConfig[0]['SysConfig']['scfg_value'], true);
                $langInfo[$sysConfig[0]['SysConfig']['scfg_index']]['url'] = $langJSONInfo['url'];

                $currentLangIndex = $langIndex;
                $langUrl = $langJSONInfo['url'];
            }
        }

        //get outlet information
        $outlet = $OutOutletModel->findActiveById($outletId, -1);
        if (empty($outlet))
            return;

        //get shop information
        $shop = $OutShopModel->findActiveById($outlet['OutOutlet']['olet_shop_id'], -1);
        if (empty($shop))
            return;

        //get user information
        $user = $UserUserModel->findActiveById($userId, -1);

        //get business day information
        $businessDay = $PosBusinessDayModel->findActiveById($businessDayId, -1);
        if (empty($businessDay))
            return;

        // get related record by business date
        $posBusinessDays = $PosBusinessDayModel->findAllByDate($businessDay['PosBusinessDay']['bday_date']);
        $businessDayIds = array();
        foreach ($posBusinessDays as $posBusinessDay) {
            $businessDayIds[$posBusinessDay['PosBusinessDay']['bday_id']] = $posBusinessDay['PosBusinessDay']['bday_id'];
        }

        //get check information
        $userTimeOut = $PosUserTimeInOutModel->findActiveByBdayIdUserId($businessDayId, $userId, -1);

        //initialize the report array
        $reportDetails = array();
        $reportDetails['ShopNameL1'] = $shop['OutShop']['shop_name_l1'];
        $reportDetails['ShopNameL2'] = $shop['OutShop']['shop_name_l2'];
        $reportDetails['ShopNameL3'] = $shop['OutShop']['shop_name_l3'];
        $reportDetails['ShopNameL4'] = $shop['OutShop']['shop_name_l4'];
        $reportDetails['ShopNameL5'] = $shop['OutShop']['shop_name_l5'];
        $reportDetails['OutletNameL1'] = $outlet['OutOutlet']['olet_name_l1'];
        $reportDetails['OutletNameL2'] = $outlet['OutOutlet']['olet_name_l2'];
        $reportDetails['OutletNameL3'] = $outlet['OutOutlet']['olet_name_l3'];
        $reportDetails['OutletNameL4'] = $outlet['OutOutlet']['olet_name_l4'];
        $reportDetails['OutletNameL5'] = $outlet['OutOutlet']['olet_name_l5'];
        $reportDetails['BusinessDate'] = $businessDay['PosBusinessDay']['bday_date'];
        $reportDetails['PrintDateTime'] = date('Y-m-d H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));
        $reportDetails['UserNameL1'] = $user['UserUser']['user_last_name_l1'] . ' ' . $user['UserUser']['user_first_name_l1'];
        $reportDetails['UserNameL2'] = $user['UserUser']['user_last_name_l2'] . ' ' . $user['UserUser']['user_first_name_l2'];
        $reportDetails['UserNameL3'] = $user['UserUser']['user_last_name_l3'] . ' ' . $user['UserUser']['user_first_name_l3'];
        $reportDetails['UserNameL4'] = $user['UserUser']['user_last_name_l4'] . ' ' . $user['UserUser']['user_first_name_l4'];
        $reportDetails['UserNameL5'] = $user['UserUser']['user_last_name_l5'] . ' ' . $user['UserUser']['user_first_name_l5'];
        $reportDetails['TimeIn'] = $userTimeOut['PosUserTimeInOutLog']['utio_in_loctime'];
        $reportDetails['TimeOut'] = $userTimeOut['PosUserTimeInOutLog']['utio_out_loctime'];

        $timeIn = strToTime($userTimeOut['PosUserTimeInOutLog']['utio_in_loctime']);
        $timeOut = strToTime($userTimeOut['PosUserTimeInOutLog']['utio_out_loctime']);
        $timeDiff = $timeOut - $timeIn;
        $hourDiff = $timeDiff / 3600;
        $minDiff = ($timeDiff % 3600) / 60;
        $secDiff = $timeDiff % 60;
        $reportDetails['TotalTime'] = sprintf("%02d:%02d:%02d", $hourDiff, $minDiff, $secDiff);
        $totalHour = sprintf("%02d", $hourDiff);
        $totalMin = sprintf("%02d", $minDiff);
        $totalHour += round(($totalMin / 60), 2);
        $reportDetails['TotalHour'] = sprintf("%.2f", $totalHour);

        //generate ouput html file
        $outputFileName = "user_time_out_" . $outlet['OutOutlet']['olet_id'] . "_" . date('Ymd', strtotime($businessDay['PosBusinessDay']['bday_date'])) . "_" . date('YmdHis') . $printFileExt;
        $outputFile = $configPath . $outputFileName;
        $outputView = new View($this->controller, false);
        $outputView->set(compact('currentLangIndex', 'reportDetails', 'langUrl'));
        $outputView->viewPath = '';

        $viewContent = @$outputView->render($renderView);
        file_put_contents($outputFile, $viewContent);

        $resultFileName = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));

        App::import('Component', 'Printing.PrintingApiGeneral');
        $this->PrintingApiGeneral = new PrintingApiGeneralComponent(new ComponentCollection());
        $this->PrintingApiGeneral->startup($this->controller);

        $reply = array();

        $param = array();
        $param['printJobFile'] = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));
        $param['printQ'] = $printQueueId;
        $param['printJobFileMediaType'] = $printFileFmt;
        $param['printJobFileType'] = 'OTHERS';
        $this->PrintingApiGeneral->addPrintJob($param, $reply);

        return '';
    }

    /**
     * Generate output of the Octopus add value slip
     * @param integer $duplicate Duplicate slip    0 - not duplicate slip; 1 - duplicate slip
     * @      integer $outletId                Outlet ID
     * @      string $deviceId                Device ID
     * @      string $udsn                    UDSN
     * @      string $cardId                Card ID
     * @      string $cardType                Card Type
     * @      double $transAmount            Transaction amount
     * @      double $remainAmount            Card remain amount
     * @      integer $printQueueId            Print queue ID
     * @      integer $langIndex            Current language index in client
     * @      string  $resultFileName        A variable to store the result file name
     */
    public function generateOctopusSlip($duplicate, $outletId, $deviceId, $udsn, $cardId, $cardType, $transAmount, $remainAmount, $transactionNum, $lastAddValueType, $lastAddValueDate, $transactionTime, $printQueueId, $langIndex, &$resultFileName = '')
    {
        Configure::write('debug', 0);
        set_time_limit(600);

        //	Check if printing plugin exists
        if (!array_key_exists("printing", $this->controller->plugins))
            return 'load_model_error';

        //	Load required model
        $modelArray = array('SysConfig', 'Outlet.OutShop', 'Outlet.OutOutlet');
        if ($this->controller->importModels($modelArray) !== true)
            return 'load_model_error';

        $SysConfigModel = $this->controller->SysConfig;
        $OutShopModel = $this->controller->OutShop;
        $OutOutletModel = $this->controller->OutOutlet;

        //add this data path in View folder and set the viewPath be empty (default is the controller name)
        App::build(array('View' => array(ROOT . DS . APP_DIR . DS . 'Plugin' . DS . 'Pos' . DS . 'View' . DS . 'PosOctopusSlipFormats')));

        //	Handle for old print service
        $printServerConfig = Configure::read('Printing.print_service');
        if ($printServerConfig == "1.0") {
            $renderView = 'add_value_slip_format_html';
            $printFileFmt = 'WEBPAGE';
            $printFileExt = '.html';
            $this->controller->layout = 'print_slip';
        } else {
            $renderView = 'add_value_slip_format';
            $printFileFmt = 'PFILE';
            $printFileExt = '.pfi';
            $this->controller->layout = 'ajax';
        }

        //get the target print job path
        $configPath = $this->controller->Common->getDataPath(array('pos_print_jobs'), true);
        $configUrl = $this->controller->Common->getDataUrl('pos_print_jobs/');
        if (empty($configPath) || empty($configUrl))
            return "missing_config_path";

        //get language information
        $currentLangIndex = 1;
        $langUrl = "eng";
        $sysConfig = $SysConfigModel->findConfig('system', 'language_code', $langIndex);
        if (!empty($sysConfig)) {
            if ($sysConfig[0]['SysConfig']['scfg_value'] != null && $sysConfig[0]['SysConfig']['scfg_value'] != "") {
                $langJSONInfo = json_decode($sysConfig[0]['SysConfig']['scfg_value'], true);
                $langInfo[$sysConfig[0]['SysConfig']['scfg_index']]['url'] = $langJSONInfo['url'];

                $currentLangIndex = $langIndex;
                $langUrl = $langJSONInfo['url'];
            }
        }

        //get outlet information
        $outlet = $OutOutletModel->findActiveById($outletId, -1);
        if (empty($outlet))
            return;

        //get shop information
        $shop = $OutShopModel->findActiveById($outlet['OutOutlet']['olet_shop_id'], -1);
        if (empty($shop))
            return;

        //initialize the report array
        $slipDetails = array();
        $slipDetails['OutletNameL1'] = $outlet['OutOutlet']['olet_name_l1'];
        $slipDetails['OutletNameL2'] = $outlet['OutOutlet']['olet_name_l2'];
        $slipDetails['OutletNameL3'] = $outlet['OutOutlet']['olet_name_l3'];
        $slipDetails['OutletNameL4'] = $outlet['OutOutlet']['olet_name_l4'];
        $slipDetails['OutletNameL5'] = $outlet['OutOutlet']['olet_name_l5'];
        $slipDetails['PrintDateTime'] = date('Y-m-d H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));
        $slipDetails['DeviceId'] = $deviceId;
        $slipDetails['Udsn'] = $udsn;
        $slipDetails['Duplicate'] = $duplicate;
        $slipDetails['CardId'] = $cardId;
        $slipDetails['CardType'] = $cardType;
        $slipDetails['TranAmount'] = $transAmount;
        $slipDetails['RemainAmount'] = $remainAmount;
        $slipDetails['TransactionNum'] = $transactionNum;
        $slipDetails['LastAddValueType'] = $lastAddValueType;
        $slipDetails['LastAddValueDate'] = $lastAddValueDate;
        $slipDetails['TransactionTime'] = $transactionTime;

        //generate ouput html file
        $outputFileName = "octopus_" . $cardId . "_" . date('YmdHis') . "_" . $duplicate . $printFileExt;
        $outputFile = $configPath . $outputFileName;
        $outputView = new View($this->controller, false);
        $outputView->set(compact('currentLangIndex', 'slipDetails', 'langUrl'));
        $outputView->viewPath = '';

        $viewContent = @$outputView->render($renderView);
        file_put_contents($outputFile, $viewContent);

        $resultFileName = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));

        App::import('Component', 'Printing.PrintingApiGeneral');
        $this->PrintingApiGeneral = new PrintingApiGeneralComponent(new ComponentCollection());
        $this->PrintingApiGeneral->startup($this->controller);

        $reply = array();

        $param = array();
        $param['printJobFile'] = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));
        $param['printQ'] = $printQueueId;
        $param['printJobFileMediaType'] = $printFileFmt;
        $param['printJobFileType'] = 'OTHERS';
        $this->PrintingApiGeneral->addPrintJob($param, $reply);

        return '';
    }

    /**
     * Generate output of payment interface slip
     * @param array $interface Interface record
     * @      integer $checkId        CheckId
     * @      integer $result        Result: 0-success, 1-error
     * @      integer $paytype        Interface's corresponding paytype
     * @      string $errorCode        Error code
     * @      string $errorMessage    Error message
     */
    public function printInterfaceAlertSlip($printQueueId, $interfaceId, $outletId, $checkId, $result, $errorCode, $errorMessage, $langIndex)
    {
        Configure::write('debug', 0);
        set_time_limit(600);

        //	Check if printing plugin exists
        if (!array_key_exists("printing", $this->controller->plugins))
            return 'load_model_error';

        //	Check if printing plugin exists
        if (!array_key_exists("interface", $this->controller->plugins))
            return 'load_model_error';

        if ($printQueueId == 0)
            return;

        //	Load required model
        $modelArray = array('SysConfig', 'Outlet.OutShop', 'Outlet.OutOutlet', 'Pos.PosCheck', 'Pos.PosBusinessDay', 'Interface.InfInterface');
        if ($this->controller->importModels($modelArray) !== true)
            return 'load_model_error';

        $SysConfigModel = $this->controller->SysConfig;
        $OutShopModel = $this->controller->OutShop;
        $OutOutletModel = $this->controller->OutOutlet;
        $PosCheckModel = $this->controller->PosCheck;
        $InfInterfaceModel = $this->controller->InfInterface;
        $PosBusinessDayModel = $this->controller->PosBusinessDay;

        //add this data path in View folder and set the viewPath be empty (default is the controller name)
        App::build(array('View' => array(ROOT . DS . APP_DIR . DS . 'Plugin' . DS . 'Pos' . DS . 'View' . DS . 'PrintFormats')));

        //	Handle for old print service
        $printServerConfig = Configure::read('Printing.print_service');
        if ($printServerConfig == "1.0") {
            $renderView = 'payment_interface_slip_format_html';
            $printFileFmt = 'WEBPAGE';
            $printFileExt = '.html';
            $this->controller->layout = 'print_slip';
        } else {
            $renderView = 'payment_interface_slip_format';
            $printFileFmt = 'PFILE';
            $printFileExt = '.pfi';
            $this->controller->layout = 'ajax';
        }

        //get the target print job path
        $configPath = $this->controller->Common->getDataPath(array('pos_print_jobs'), true);
        $configUrl = $this->controller->Common->getDataUrl('pos_print_jobs/');
        if (empty($configPath) || empty($configUrl))
            return "missing_config_path";

        //get language information
        $currentLangIndex = 1;
        $langUrl = "eng";
        $sysConfig = $SysConfigModel->findConfig('system', 'language_code', $langIndex);
        if (!empty($sysConfig)) {
            if ($sysConfig[0]['SysConfig']['scfg_value'] != null && $sysConfig[0]['SysConfig']['scfg_value'] != "") {
                $langJSONInfo = json_decode($sysConfig[0]['SysConfig']['scfg_value'], true);
                $langInfo[$sysConfig[0]['SysConfig']['scfg_index']]['url'] = $langJSONInfo['url'];

                $currentLangIndex = $langIndex;
                $langUrl = $langJSONInfo['url'];
            }
        }

        // get interface record
        $interface = $InfInterfaceModel->findActiveById($interfaceId, 1);
        if (empty($interface))
            return;

        //get outlet information
        $outlet = $OutOutletModel->findActiveById($outletId, -1);
        if (empty($outlet))
            return;

        //get shop information
        $shop = $OutShopModel->findActiveById($outlet['OutOutlet']['olet_shop_id'], -1);
        if (empty($shop))
            return;

        //get business day record
        $businessDay = $PosBusinessDayModel->findActiveByOutletId($outletId);

        //get check information
        $check = $PosCheckModel->find('first', array(
                'conditions' => array('chks_id' => $checkId),
                'recursive' => 1
            )
        );
        if (empty($check))
            return 'missing_check (id: ' . $checkId . ')';

        //get check table info
        $checkTable = $PosCheckModel->PosCheckTable->findByCheckId($check['PosCheck']['chks_id']);

        //initialize the report array
        $slipDetails = array();
        $slipDetails['OutletName'] = $outlet['OutOutlet']['olet_name_l' . $langIndex];
        $slipDetails['PrintDateTime'] = date('Y-m-d H:i:s', Date::localTimestamp($shop['OutShop']['shop_timezone'] * 60, $shop['OutShop']['shop_timezone_name'], time()));
        if (!empty($checkTable)) {
            $slipDetails['TableNumber'] = (($checkTable['PosCheckTable']['ctbl_table'] == 0) ? "" : $checkTable['PosCheckTable']['ctbl_table']) . $checkTable['PosCheckTable']['ctbl_table_ext'];
        }

        $slipDetails['PayResult'] = $result;
        if ($result == 0) {
            $slipDetails['CheckNumber'] = $check['PosCheck']['chks_check_prefix_num'];
            $slipDetails['CheckItemTotal'] = number_format($check['PosCheck']['chks_check_total'], $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");
            $slipDetails['CheckTotal'] = number_format($check['PosCheck']['chks_check_total'], $businessDay['PosBusinessDay']['bday_check_decimal'], ".", "");
            $slipDetails['CheckDiscounts'] = array();
            if (isset($check['PosCheckDiscount']) && !empty($check['PosCheckDiscount'])) {
                foreach ($check['PosCheckDiscount'] as $disc) {
                    $slipDetails['CheckDiscounts'][] = array(
                        'DiscountName' => $disc['cdis_name_l' . $langIndex],
                        'DiscountAmount' => number_format($disc['cdis_round_total'], $businessDay['PosBusinessDay']['bday_disc_decimal'], ".", "")
                    );
                }
            }
            foreach ($check['PosCheckPayment'] as $checkPayment) {
                $slipDetails['Payments'][] = array(
                    'PaymentName' => $checkPayment['cpay_name_l' . $langIndex],
                    'PaymentAmount' => number_format($checkPayment['cpay_pay_total'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                    'PaymentTotal' => number_format(($checkPayment['cpay_pay_total'] + $checkPayment['cpay_pay_tips']), $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                    'PaymentTips' => number_format($checkPayment['cpay_pay_tips'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                    'PaymentChanges' => number_format($checkPayment['cpay_pay_change'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", ""),
                    'PaymentSurcharge' => isset($checkPayment['cpay_pay_surcharge']) ? number_format($checkPayment['cpay_pay_surcharge'], $businessDay['PosBusinessDay']['bday_pay_decimal'], ".", "") : 0,
                );
            }

        } else {
            $slipDetais['ErrorCode'] = $errorCode;
            $slipDetails['ErrorMessage'] = $errorMessage;
        }

        //generate output html file
        $outputFileName = "payment_interface_" . $checkId . "_" . date('YmdHis') . $printFileExt;
        $outputFile = $configPath . $outputFileName;
        $outputView = new View($this->controller, false);
        $outputView->set(compact('currentLangIndex', 'slipDetails', 'langUrl'));
        $outputView->viewPath = '';

        $viewContent = @$outputView->render($renderView);
        file_put_contents($outputFile, $viewContent);

        $resultFileName = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));

        App::import('Component', 'Printing.PrintingApiGeneral');
        $this->PrintingApiGeneral = new PrintingApiGeneralComponent(new ComponentCollection());
        $this->PrintingApiGeneral->startup($this->controller);

        $reply = array();
        $param['printJobFile'] = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));
        $param['printQ'] = $printQueueId;
        $param['printJobFileMediaType'] = $printFileFmt;
        $param['printJobFileType'] = 'OTHERS';
        $this->PrintingApiGeneral->addPrintJob($param, $reply);

        return '';
    }

    /**
     * Generate output of payment interface slip
     * @param integer $printQueueId Print queue ID
     * @      String $type            Print type
     * @      String $langIndex        Language index
     * @      array $printInfo        Print information
     */
    public function printPaymentInterfaceAlertSlip($printQueueId, $type, $langIndex, $printInfo)
    {
        Configure::write('debug', 0);
        set_time_limit(600);

        //	Check if printing plugin exists
        if (!array_key_exists("printing", $this->controller->plugins))
            return 'load_model_error';

        //	Check if printing plugin exists
        if (!array_key_exists("interface", $this->controller->plugins))
            return 'load_model_error';

        if ($printQueueId == 0)
            return;

        //	Load required model
        $modelArray = array('SysConfig', 'Interface.InfInterface');
        if ($this->controller->importModels($modelArray) !== true)
            return 'load_model_error';

        $SysConfigModel = $this->controller->SysConfig;
        $InfInterfaceModel = $this->controller->InfInterface;

        //add this data path in View folder and set the viewPath be empty (default is the controller name)
        App::build(array('View' => array(ROOT . DS . APP_DIR . DS . 'Plugin' . DS . 'Pos' . DS . 'View' . DS . 'PrintFormats')));

        //	Handle for old print service
        $printServerConfig = Configure::read('Printing.print_service');
        if ($printServerConfig == "1.0") {
            $renderView = 'payment_interface_slip_format_html';
            $printFileFmt = 'WEBPAGE';
            $printFileExt = '.html';
            $this->controller->layout = 'print_slip';
        } else {
            $renderView = 'payment_interface_slip_format';
            $printFileFmt = 'PFILE';
            $printFileExt = '.pfi';
            $this->controller->layout = 'ajax';
        }

        //get the target print job path
        $configPath = $this->controller->Common->getDataPath(array('pos_print_jobs'), true);
        $configUrl = $this->controller->Common->getDataUrl('pos_print_jobs/');
        if (empty($configPath) || empty($configUrl))
            return "missing_config_path";

        //get language information
        $currentLangIndex = 1;
        $langUrl = "eng";
        $sysConfig = $SysConfigModel->findConfig('system', 'language_code', $langIndex);
        if (!empty($sysConfig)) {
            if ($sysConfig[0]['SysConfig']['scfg_value'] != null && $sysConfig[0]['SysConfig']['scfg_value'] != "") {
                $langJSONInfo = json_decode($sysConfig[0]['SysConfig']['scfg_value'], true);
                $langInfo[$sysConfig[0]['SysConfig']['scfg_index']]['url'] = $langJSONInfo['url'];

                $currentLangIndex = $langIndex;
                $langUrl = $langJSONInfo['url'];
            }
        }

        App::import('Component', 'Printing.PrintingApiGeneral');
        $this->PrintingApiGeneral = new PrintingApiGeneralComponent(new ComponentCollection());
        $this->PrintingApiGeneral->startup($this->controller);

        if ($type == "scan_pay_void") {
            foreach ($printInfo['checkPayments'] as $posCheckPayment) {
                $slipDetails = array(
                    'MerchantName' => '',
                    'MerchantId' => '',
                    'StationCode' => '',
                    'EmployeeNumber' => '',
                    'TransactionTime' => '',
                    'TransactionNumber' => '',
                    'OriginalTransactionNumber' => '',
                    'PlatformTransactionNumber' => '',
                    'TransactionAmount' => '',
                    'ActualPayAmt' => '',
                    'InvoiceAmt' => '',
                    'PaymentName' => '',
                    'ChannelTransactionNumber' => '',
                    'PaymentAccountNumber' => ''
                );

                if (isset($posCheckPayment['checkExtraInfos'])) {
                    foreach ($posCheckPayment['checkExtraInfos'] as $extraInfo) {
                        if ($extraInfo['ckei_by'] != "payment")
                            continue;

                        if ($extraInfo['ckei_variable'] == "internal_use") {
                            $internalUseValue = json_decode($extraInfo['ckei_value'], true);

                            if (isset($internalUseValue['merchantName']))
                                $slipDetails['MerchantName'] = $internalUseValue['merchantName'];
                            if (isset($internalUseValue['merchantId']))
                                $slipDetails['MerchantId'] = $internalUseValue['merchantId'];
                            if (isset($internalUseValue['stationCode']))
                                $slipDetails['StationCode'] = $internalUseValue['stationCode'];
                            if (isset($internalUseValue['employeeCode']))
                                $slipDetails['EmployeeNumber'] = $internalUseValue['employeeCode'];
                            if (isset($internalUseValue['transactionTime']))
                                $slipDetails['TransactionTime'] = $internalUseValue['transactionTime'];
                            if (isset($internalUseValue['transactionPayTotal']))
                                $slipDetails['TransactionAmount'] = $internalUseValue['transactionPayTotal'];
                            if (isset($internalUseValue['invoiceTotal']))
                                $slipDetails['InvoiceAmt'] = $internalUseValue['invoiceTotal'];
                            if (isset($internalUseValue['channelTransactionNum']))
                                $slipDetails['ChannelTransactionNumber'] = $internalUseValue['channelTransactionNum'];
                            if (isset($internalUseValue['platformTransactionNum']))
                                $slipDetails['PlatformTransactionNumber'] = $internalUseValue['platformTransactionNum'];
                        } else if ($extraInfo['ckei_variable'] == "trace_id") {
                            $slipDetails['TransactionNumber'] = "R_" . $extraInfo['ckei_value'];
                            $slipDetails['OriginalTransactionNumber'] = $extraInfo['ckei_value'];
                        } else if ($extraInfo['ckei_variable'] == "account_number")
                            $slipDetails['PaymentAccountNumber'] = $extraInfo['ckei_value'];
                    }
                }

                $slipDetails['ActualPayAmt'] = $posCheckPayment['cpay_pay_total'];
                $slipDetails['PaymentName'] = $posCheckPayment['cpay_name_l' . $currentLangIndex];
                $slipDetails['type'] = "scan_pay_void";

                //generate output html file
                $outputFileName = "payment_interface_" . $posCheckPayment['cpay_id'] . "_" . date('YmdHis') . $printFileExt;
                $outputFile = $configPath . $outputFileName;
                $outputView = new View($this->controller, false);
                $outputView->set(compact('currentLangIndex', 'slipDetails', 'langUrl'));
                $outputView->viewPath = '';

                $viewContent = @$outputView->render($renderView);
                file_put_contents($outputFile, $viewContent);

                $resultFileName = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));

                $reply = array();
                $param['printJobFile'] = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));
                $param['printQ'] = $printQueueId;
                $param['printJobFileMediaType'] = $printFileFmt;
                $param['printJobFileType'] = 'OTHERS';
                $this->PrintingApiGeneral->addPrintJob($param, $reply);
            }
        }

        return '';
    }

    /**
     * Generate output of loyalty svc transfer card slip
     * @param integer $printQueueId Print queue ID
     * @      String $langIndex        Language index
     * @      array $printInfo        Print information
     */
    public function printLoyaltyTransferCardSlip($printQueueId, $langIndex, $printInfo)
    {
        Configure::write('debug', 0);
        set_time_limit(600);

        //	Check if printing plugin exists
        if (!array_key_exists("printing", $this->controller->plugins))
            return 'load_model_error';

        //	Check if printing plugin exists
        if (!array_key_exists("interface", $this->controller->plugins))
            return 'load_model_error';

        if ($printQueueId == 0)
            return;

        //	Load required model
        $modelArray = array('SysConfig', 'Outlet.OutShop', 'Outlet.OutOutlet', 'Pos.PosCheck', 'Pos.PosBusinessDay', 'Interface.InfInterface');
        if ($this->controller->importModels($modelArray) !== true)
            return 'load_model_error';

        $SysConfigModel = $this->controller->SysConfig;

        //add this data path in View folder and set the viewPath be empty (default is the controller name)
        App::build(array('View' => array(ROOT . DS . APP_DIR . DS . 'Plugin' . DS . 'Pos' . DS . 'View' . DS . 'PrintFormats')));

        //	Handle for old print service
        $printServerConfig = Configure::read('Printing.print_service');
        if ($printServerConfig == "1.0") {
            $renderView = 'loyalty_svc_transfer_card_slip_format_html';
            $printFileFmt = 'WEBPAGE';
            $printFileExt = '.html';
            $this->controller->layout = 'print_slip';
        } else {
            $renderView = 'loyalty_svc_transfer_card_slip_format';
            $printFileFmt = 'PFILE';
            $printFileExt = '.pfi';
            $this->controller->layout = 'ajax';
        }

        //get the target print job path
        $configPath = $this->controller->Common->getDataPath(array('pos_print_jobs'), true);
        $configUrl = $this->controller->Common->getDataUrl('pos_print_jobs/');
        if (empty($configPath) || empty($configUrl))
            return "missing_config_path";

        //get language information
        $currentLangIndex = 1;
        $langUrl = "eng";
        $sysConfig = $SysConfigModel->findConfig('system', 'language_code', $langIndex);
        if (!empty($sysConfig)) {
            if ($sysConfig[0]['SysConfig']['scfg_value'] != null && $sysConfig[0]['SysConfig']['scfg_value'] != "") {
                $langJSONInfo = json_decode($sysConfig[0]['SysConfig']['scfg_value'], true);
                $langInfo[$sysConfig[0]['SysConfig']['scfg_index']]['url'] = $langJSONInfo['url'];

                $currentLangIndex = $langIndex;
                $langUrl = $langJSONInfo['url'];
            }
        }

        //initialize the report array
        $slipDetails = array();
        $slipDetails['type'] = 'transfer_card';
        $slipDetails['original_card'] = $printInfo['original_card'];
        $slipDetails['destination_card'] = $printInfo['destination_card'];
        $slipDetails['member_no'] = $printInfo['member_no'];
        $slipDetails['card_type'] = $printInfo['card_type'];
        $slipDetails['svc_balance'] = $printInfo['svc_balance'];
        $slipDetails['loyalty_points'] = $printInfo['loyalty_points'];
        $slipDetails['transfer_date_and_time'] = $printInfo['transfer_date_and_time'];
        $slipDetails['printCount'] = $printInfo['printCount'];

        //generate output html file
        $outputFileName = "loyalty_interface_transfer" . $printInfo['original_card'] . "_to_" . $printInfo['destination_card'] . "_" . $printInfo['printCount'] . "_" . date('YmdHis') . $printFileExt;
        $outputFile = $configPath . $outputFileName;
        $outputView = new View($this->controller, false);
        $outputView->set(compact('currentLangIndex', 'slipDetails', 'langUrl'));
        $outputView->viewPath = '';

        $viewContent = @$outputView->render($renderView);
        file_put_contents($outputFile, $viewContent);
        $resultFileName = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));

        App::import('Component', 'Printing.PrintingApiGeneral');
        $this->PrintingApiGeneral = new PrintingApiGeneralComponent(new ComponentCollection());
        $this->PrintingApiGeneral->startup($this->controller);

        $reply = array();
        $param['printJobFile'] = Router::url($configUrl . $outputFileName, array('full' => true, 'escape' => true));
        $param['printQ'] = $printQueueId;
        $param['printJobFileMediaType'] = $printFileFmt;
        $param['printJobFileType'] = 'OTHERS';
        $this->PrintingApiGeneral->addPrintJob($param, $reply);

        return '';
    }

    //initialize the check level printing variable
    private function __initializeCheckVars(&$vars, $type = 1, $supportTaiWanGUI = false)
    {
        $scCount = $checkInfoCount = 5;
        $taxCount = 25;

        $vars['StationName'] = "";
        $vars['ShopName'] = "";
        $vars['TableName'] = "";
        $vars['TableNumber'] = "";
        $vars['OutletLogo'] = "";
        $vars['OutletName'] = "";
        $vars['Address'] = "";
        $vars['Greeting'] = "";
        $vars['OutletCode'] = "";
        $vars['OutletCurrencyCode'] = "";
        $vars['Phone'] = "";
        $vars['Fax'] = "";
        $vars['DollarSign'] = "";
        $vars['BusinessDate'] = "";
        $vars['CheckOpenEmployee'] = "";
        $vars['CheckOpenEmployeeFirstName'] = "";
        $vars['CheckOpenEmployeeLastName'] = "";
        $vars['CheckOwnerEmployee'] = "";
        $vars['CheckOwnerEmployeeFirstName'] = "";
        $vars['CheckOwnerEmployeeLastName'] = "";
        $vars['CheckNumber'] = "";
        $vars['CheckTitle'] = "";
        $vars['CheckGuests'] = "";
        $vars['CheckOpenDate'] = "";
        $vars['CheckOpenTime'] = "";
        $vars['CheckOpenEmployeeNum'] = "";
        $vars['PrintEmployee'] = "";
        $vars['PrintEmployeeFirstName'] = "";
        $vars['PrintEmployeeLastName'] = "";
        $vars['PrintDate'] = "";
        $vars['PrintTime'] = "";
        $vars['PrintEmployeeNum'] = "";
        $vars['PrintCount'] = 0;
        $vars['ReprintReceipt'] = 0;
        $vars['CheckTotal'] = 0;
        for ($scIndex = 1; $scIndex <= $scCount; $scIndex++) {
            $vars['SCName' . $scIndex] = "";
            $vars['SCShortName' . $scIndex] = "";
            $vars['SC' . $scIndex] = 0;
        }
        $vars['SCTotal'] = 0;
        for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++) {
            $vars['TaxName' . $taxIndex] = "";
            $vars['TaxShortName' . $taxIndex] = "";
            $vars['TaxRate' . $taxIndex] = 0;
            $vars['Tax' . $taxIndex] = 0;
            $vars['DiscountForTax' . $taxIndex] = 0;
            $vars['CheckItemTotalWithTax' . $taxIndex] = 0;
            $vars['SCTotalWithTax' . $taxIndex] = 0;
        }
        $vars['TaxTotal'] = 0;
        $vars['DiscountForTaxTotal'] = 0;
        $vars['TotalItem'] = 0;
        $vars['CheckItemGrossTotal'] = 0;
        $vars['CheckItemDiscountTotal'] = 0;
        $vars['DiscountTotal'] = 0;
        $vars['CheckItemTotal'] = 0;
        $vars['CheckRoundTotal'] = 0;
        $vars['CheckDiscountTotal'] = 0;
        for ($checkInfoIndex = 1; $checkInfoIndex <= $checkInfoCount; $checkInfoIndex++)
            $vars['CheckInfo' . $checkInfoIndex] = "";
        $vars['PayAmountTotal'] = 0;
        $vars['TipsTotal'] = 0;
        $vars['Changes'] = 0;
        $vars['CheckCloseEmployee'] = "";
        $vars['CheckCloseEmployeeFirstName'] = "";
        $vars['CheckCloseEmployeeLastName'] = "";
        $vars['CheckCloseDate'] = "";
        $vars['CheckCloseTime'] = "";
        $vars['CheckMemberNum'] = "";
        $vars['CheckMemberName1'] = "";
        $vars['CheckMemberName2'] = "";
        $vars['CheckMemberDisplayName'] = "";
        $vars['CheckMemberSpending'] = 0;
        $vars['CheckTakeout'] = 0;
        $vars['CheckNonRevenue'] = 0;
        $vars['CheckAdvanceOrder'] = 0;
        $vars['CheckOrderMode'] = "";
        $vars['CheckVoided'] = 0;
        $vars['CheckCallNumber'] = "";
        $vars['ContinuousPrintPageNumber'] = 0;
        $vars['ContinuousPrintLineNumber'] = 0;
        $vars['OgsPayMatchNumber'] = "";
        $vars['OgsPaytype'] = "";
        $vars['OgsPayUrl'] = "";
        $vars['OgsEInvoiceQRCode'] = "";
        $vars['OgsPayReceiptUrl'] = "";
        $vars['OgsEInvoiceQRCodeError'] = 0;
        $vars['CheckVoidedReason'] = "";
        if ($type == 2) {
            $vars['CheckReleasePaymentCount'] = 0;
            $vars['CheckLastReleasePaymentTotal'] = 0;
        }
        for ($index = 1; $index <= 5; $index++) {
            $vars['StationNameL' . $index] = "";
            $vars['TableNameL' . $index] = "";
            $vars['ShopNameL' . $index] = "";
            $vars['OutletNameL' . $index] = "";
            $vars['AddressL' . $index] = "";
            $vars['GreetingL' . $index] = "";
            $vars['CheckOpenEmployeeL' . $index] = "";
            $vars['CheckOpenEmployeeFirstNameL' . $index] = "";
            $vars['CheckOpenEmployeeLastNameL' . $index] = "";
            $vars['CheckOwnerEmployeeL' . $index] = "";
            $vars['CheckOwnerEmployeeFirstNameL' . $index] = "";
            $vars['CheckOwnerEmployeeLastNameL' . $index] = "";
            $vars['PrintEmployeeL' . $index] = "";
            $vars['PrintEmployeeFirstNameL' . $index] = "";
            $vars['PrintEmployeeLastNameL' . $index] = "";
            for ($scIndex = 1; $scIndex <= $scCount; $scIndex++) {
                $vars['SCName' . $scIndex . 'L' . $index] = "";
                $vars['SCShortName' . $scIndex . 'L' . $index] = "";
            }
            for ($taxIndex = 1; $taxIndex <= $taxCount; $taxIndex++) {
                $vars['TaxName' . $taxIndex . 'L' . $index] = "";
                $vars['TaxShortName' . $taxIndex . 'L' . $index] = "";
            }
            $vars['CheckCloseEmployeeL' . $index] = "";
            $vars['CheckVoidedReasonL' . $index] = "";
            $vars['CheckMealPeriodNameL' . $index] = "";
            $vars['CheckMealShortPeriodNameL' . $index] = "";
        }
        for ($index = 1; $index <= 4; $index++) {
            $vars['InclusiveTaxRef' . $index] = 0;
        }
        $vars['ReservationPaymentTotal'] = 0;

        //For TaiWan GUI
        if ($type == 2) {
            $vars['GUIYear'] = "";
            $vars['GUIMonthPeriod'] = "";
            $vars['GUINumber'] = "";
            $vars['GUIType'] = "";
            $vars['GUIRandomNum'] = "";
            $vars['GUISellerNum'] = "";
            $vars['GUIBarcode'] = "";
            $vars['GUILeftQRCode'] = "";
            $vars['GUIRightQRCode'] = "";
            $vars['GUIRefNum'] = "";
            $vars['GUIPrintCount'] = "";
            $vars['GUIPrintTotal'] = 0;
            $vars['GUIVatTotal'] = 0;
            $vars['GUICarrierId'] = "";
        }

        //For LPS-SVC membership interface
        $vars['CheckMembershipIntfAccountNumber'] = "";
        $vars['CheckMembershipIntfAccountName'] = "";
        $vars['CheckMembershipIntfMemberNumber'] = "";
        $vars['CheckMembershipIntfCardNumber'] = "";
        $vars['CheckMembershipIntfNric'] = "";
        $vars['CheckMembershipIntfPointBalance'] = "";
        $vars['CheckMembershipIntfPointEarn'] = "";
        $vars['CheckMembershipIntfEventOrderNumberForAdd'] = "";
        $vars['CheckMembershipIntfEventOrderDepositBalance'] = 0;
        $vars['CheckMembershipIntfEventOrderDepositForAdd'] = 0;
        $vars['CheckMembershipIntfEventOrderNumberForUse'] = 0;
        $vars['CheckMembershipIntfEventOrderDepositForUse'] = 0;
        $vars['CheckMembershipIntfStoreValueBalance'] = 0;
        $vars['CheckMembershipIntfOriginalPoints'] = 0;
        $vars['CheckMembershipIntfPostedPointsUse'] = 0;
        $vars['CheckMembershipIntfPostedAmountUse'] = 0;
        $vars['CheckMembershipIntfMemberType'] = "";
        $vars['CheckMembershipIntfMemberSurname'] = "";
        $vars['CheckMembershipIntfLocalBalance'] = 0;
        $vars['CheckMembershipIntfPointsAvailable'] = 0;
        $vars['CheckMembershipIntfTotalPointsBalance'] = 0;
        $vars['CheckMembershipIntfMaxRedemptionPoints'] = 0;
        $vars['CheckMembershipIntfPointsToUse'] = 0;
        $vars['CheckMembershipIntfMaxRedemptionAmount'] = 0;
        $vars['CheckMembershipIntfAmountToUse'] = 0;
        $vars['CheckMembershipIntfRefundAmount'] = 0;
        $vars['CheckMembershipIntfRefundPoints'] = 0;
        $vars['CheckMembershipIntfVoucherValue'] = 0;
        $vars['CheckMembershipIntfNoRedemption'] = 'false';
        $vars['CheckMembershipIntfMemberRef'] = "";
        $vars['CheckMembershipIntfAmountForEarnPoint'] = "";
        $vars['CheckMembershipIntfAttachedAtPayment'] = 0;

        //For Loyalty Interface
        $vars['CheckLoyaltyMemberName'] = "";
        $vars['CheckLoyaltyMemberNumber'] = "";
        $vars['CheckLoyaltyCardNumber'] = "";
        $vars['CheckLoyaltyPointEarn'] = "";
        $vars['CheckLoyaltyPointBalance'] = "";
        $vars['CheckLoyaltyPointExpiryDate'] = "";
        $vars['CheckLoyaltyPointRedeem'] = "";
        $vars['CheckLoyaltyBalanceExpireThisMonth'] = "";
        $vars['CheckLoyaltyBalanceExpireNextMonth'] = "";
        $vars['CheckLoyaltyTopUpTransDate'] = "";
        $vars['CheckLoyaltyTopUpRefId'] = "";
        $vars['CheckLoyaltyTopUpAmount'] = "";

        //For Gaming Interface
        $vars['CheckGamingIntfMemberNumber'] = "";
        $vars['CheckGamingIntfMemberName'] = "";
        $vars['CheckGamingIntfMemberCardNumber'] = "";
        $vars['CheckGamingIntfPointBalance'] = "";
        $vars['CheckGamingIntfPointDepartment'] = "";
        $vars['CheckGamingIntfInputMethod'] = "";
        $vars['CheckGamingIntfAccountNumber'] = "";
        $vars['CheckGamingIntfCardType'] = "";
        $vars['CheckGamingIntfDiscountRate'] = "";
        $vars['CheckGamingIntfMemberFirstName'] = "";
        $vars['CheckGamingIntfMemberLastName'] = "";

        //For Advance Order
        $vars['CheckReferenceNumber'] = "";
        $vars['CheckPickupDate'] = "";
        $vars['CheckGuestPhone'] = "";
        $vars['CheckGuestName'] = "";
        $vars['CheckGuestFax'] = "";
        $vars['CheckGuestNote'] = "";
        $vars['CheckDepositAmount'] = "";

        //For QR Code
        $vars['QRCode'] = "";

        //For Bonus Code
        $vars['BonusCode1'] = "";
        $vars['BonusCode2'] = "";
        $vars['BonusCode3'] = "";
        $vars['BonusCode4'] = "";
        $vars['BonusCode5'] = "";
        $vars['BonusCode6'] = "";
        $vars['BonusCode7'] = "";
        $vars['BonusCode8'] = "";
        $vars['BonusCode9'] = "";
        $vars['BonusCode10'] = "";
    }

    //get the available item print queue by shop id, olet id and menu item print queue id
    private function __getAvaliableItemPrintQueue($shopId, $outletId, $menuItpqId)
    {
        $itemPrintQueue = null;

        //	Get action print queue
        $conditionsCount = 0;
        $itemPrtQConditions = array(
            'OR' => array(
                array(
                    'itpq_shop_id' => 0,
                    'itpq_olet_id' => 0,
                ),
                array(
                    'itpq_shop_id' => $shopId,
                    'itpq_olet_id' => 0,
                ),
                array(
                    'itpq_shop_id' => $shopId,
                    'itpq_olet_id' => $outletId,
                ),
            ),
            'AND' => array(
                'itpq_itpq_id' => $menuItpqId,
                'itpq_status' => '',
            ),
        );
        $PosItemPrintQueueModel = $this->controller->PosItemPrintQueue;
        $posItemPrintQueues = $PosItemPrintQueueModel->find('all', array(
                'conditions' => $itemPrtQConditions,
                'recursive' => -1,
                'order' => 'itpq_olet_id DESC, itpq_shop_id DESC'
            )
        );

        //	Re-sequence the item print queue records by sorting them by priorities
        $countRec = count($posItemPrintQueues);
        $logValue = ceil(log10($countRec + 1));
        $offset = pow(10, $logValue);

        $itemPrtQueues = array();
        foreach ($posItemPrintQueues as $posItemPrintQueue) {
            $outletMark = '1';
            if (!empty($posItemPrintQueue['PosItemPrintQueue']['itpq_olet_id'])) $outletMark = '0';

            $shopMark = '1';
            if (!empty($posItemPrintQueue['PosItemPrintQueue']['itpq_shop_id'])) $shopMark = '0';

            $key = $outletMark . '-' . $shopMark;
            $posItemPrintQueue['group_key'] = $key;

            $itemPrtQueues[$key . '-' . $offset] = $posItemPrintQueue;
            $offset++;
        }
        ksort($itemPrtQueues);        // sort the action print queue

        foreach ($itemPrtQueues as $itmPrtQ) {
            $itemPrintQueue = $itmPrtQ;
            break;
        }

        return $itemPrintQueue;
    }

    //generating sorting information for getting pos_check_items
    private function __generatingItemSortingInformation($firstCriteria, $sortingMethod)
    {
        $sortingInfo = '';
        if (!$firstCriteria)
            $sortingInfo .= ' ,';


        switch ($sortingMethod) {
            case '1':
                $sortingInfo .= 'citm_name_l1';
                break;
            case '2':
                $sortingInfo .= 'citm_name_l2';
                break;
            case '3':
                $sortingInfo .= 'citm_name_l3';
                break;
            case '4':
                $sortingInfo .= 'citm_name_l4';
                break;
            case '5':
                $sortingInfo .= 'citm_name_l5';
                break;
            case 'c':
                $MenuItemCategoryModel = $this->controller->MenuItemCategory;
                $itemCategories = $MenuItemCategoryModel->createTree();
                $sortingInfo .= '(CASE citm_icat_id';
                $currentSequence = 1;
                foreach ($itemCategories as $itemCategory) {
                    $sortingInfo .= ' WHEN \'' . $itemCategory['MenuItemCategory']['icat_id'] . '\' THEN ' . $currentSequence;
                    $currentSequence++;
                    if (isset($itemCategory['children']) && count($itemCategory['children']) > 0) {
                        foreach ($itemCategory['children'] as $childItemCat) {
                            $sortingInfo .= ' WHEN \'' . $childItemCat['MenuItemCategory']['icat_id'] . '\' THEN ' . $currentSequence;
                            $currentSequence++;
                        }
                    }
                }
                $sortingInfo .= ' WHEN \'0\' THEN ' . $currentSequence;
                $sortingInfo .= ' END)';
                break;
            case 'd':
                $MenuItemDeptModel = $this->controller->MenuItemDept;
                $itemDepartments = $MenuItemDeptModel->createTree();
                $sortingInfo .= '(CASE citm_idep_id';
                $currentSequence = 1;
                foreach ($itemDepartments as $itemDept) {
                    $sortingInfo .= ' WHEN \'' . $itemDept['MenuItemDept']['idep_id'] . '\' THEN ' . $currentSequence;
                    $currentSequence++;
                    if (isset($itemDept['children']) && count($itemDept['children']) > 0) {
                        foreach ($itemDept['children'] as $childItemDept) {
                            $sortingInfo .= ' WHEN \'' . $childItemDept['MenuItemDept']['idep_id'] . '\' THEN ' . $currentSequence;
                            $currentSequence++;
                        }
                    }
                }
                $sortingInfo .= ' WHEN \'0\' THEN ' . $currentSequence;
                $sortingInfo .= ' END)';
                break;
            case 'u':
                $MenuItemCourseModel = $this->controller->MenuItemCourse;
                $itemCourses = $MenuItemCourseModel->find('all', array(
                        'fields' => array('icou_id'),
                        'conditions' => array('icou_status' => ''),
                        'order' => 'icou_seq',
                        'recursive' => -1
                    )
                );
                $sortingInfo .= ' (CASE citm_icou_id';
                $currentSequence = 1;
                foreach ($itemCourses as $itemCourse) {
                    $sortingInfo .= ' WHEN \'' . $itemCourse['MenuItemCourse']['icou_id'] . '\' THEN ' . $currentSequence;
                    $currentSequence++;
                }
                $sortingInfo .= ' WHEN \'0\' THEN ' . $currentSequence;
                $sortingInfo .= ' END)';
                break;
            case 's':
                $sortingInfo .= 'citm_seat';
                break;
            case 't':
                $sortingInfo .= 'citm_id';
                break;
            default:
                $sortingInfo .= 'citm_seq, citm_seat';
                break;
        }

        return $sortingInfo;
    }

    //generating sorting information for getting pos_check_items [for receipt only]
    private function __sortingItemForReceipt($sortingMethod, $items)
    {
        $sortingField = '';
        $sortingArray = array();

        switch ($sortingMethod) {
            case '1':
                $sortingField = 'citm_name_l1';
                break;
            case '2':
                $sortingField = 'citm_name_l2';
                break;
            case '3':
                $sortingField = 'citm_name_l3';
                break;
            case '4':
                $sortingField = 'citm_name_l4';
                break;
            case '5':
                $sortingField = 'citm_name_l5';
                break;
            case 'c':
                $sortingField = 'citm_icat_id';
                $MenuItemCategoryModel = $this->controller->MenuItemCategory;
                $sortedTargetOrder = array();
                $itemCategories = $MenuItemCategoryModel->createTree();

                foreach ($itemCategories as $itemCategory)
                    $this->__getTreeContent("category", $itemCategory, $sortedTargetOrder);
                $sortedTargetOrder[0] = array();
                break;
            case 'd':
                $sortingField = 'citm_idep_id';
                $MenuItemDeptModel = $this->controller->MenuItemDept;
                $sortedTargetOrder = array();
                $itemDepartments = $MenuItemDeptModel->createTree();

                foreach ($itemDepartments as $itemDept)
                    $this->__getTreeContent("department", $itemDept, $sortedTargetOrder);
                $sortedTargetOrder[0] = array();
                break;
            case 'u':
                $sortingField = 'citm_icou_id';
                $MenuItemCourseModel = $this->controller->MenuItemCourse;
                $sortedTargetOrder = array();
                $itemCourses = $MenuItemCourseModel->find('all', array(
                        'fields' => array('icou_id'),
                        'conditions' => array('icou_status' => ''),
                        'order' => 'icou_seq',
                        'recursive' => -1
                    )
                );

                foreach ($itemCourses as $itemCourse)
                    $this->__getTreeContent("course", $itemCourse, $sortedTargetOrder);
                $sortedTargetOrder[0] = array();
                break;
            case 's':
                $sortingField = 'citm_seat';
                $tempOrder = array();
                foreach ($items as $item) {
                    if ($item['citm_seat'] == 0)
                        continue;

                    if (!in_array($item['citm_seat'], $tempOrder))
                        $tempOrder[] = $item['citm_seat'];
                }
                natsort($tempOrder);
                foreach ($tempOrder as $key => $value)
                    $sortedTargetOrder[$value] = array();
                $sortedTargetOrder[0] = array();
                break;
            case 't':
                $sortingField .= 'citm_order_loctime';
                break;
            default:
                $sortingField .= 'citm_seq';
                break;
        }

        $itemsToSort = array();
        for ($index = 0; $index < count($items); $index++) {
            if ($sortingMethod == '')
                $itemsToSort['item_' . $index] = $items[$index]['citm_seq'] . '_' . $items[$index]['citm_seat'];
            else
                $itemsToSort['item_' . $index] = $items[$index][$sortingField];
        }

        if ($sortingMethod == 'c' || $sortingMethod == 'd' || $sortingMethod == 'u' || $sortingMethod == 's') {
            foreach ($itemsToSort as $key => $value) {
                if (isset($sortedTargetOrder[$value])) {
                    $tok = strtok($key, "_");
                    $tok = strtok("_");
                    if ($tok !== false)
                        $sortedTargetOrder[$value][] = $items[$tok];
                }
            }

            $sortedCheckItems = array();
            foreach ($sortedTargetOrder as $key => $itemList) {
                if (count($itemList) > 0) {
                    for ($i = 0; $i < count($itemList); $i++) {
                        $sortedCheckItems[] = $itemList[$i];
                    }
                }
            }

        } else {
            natsort($itemsToSort);

            //re-generate the check item list
            $sortedCheckItems = array();
            foreach ($itemsToSort as $key => $value) {
                $tok = strtok($key, "_");
                $tok = strtok("_");

                if ($tok !== false)
                    $sortedCheckItems[] = $items[$tok];
            }
        }

        return $sortedCheckItems;
    }

    // Get the tree children
    private function __getTreeContent($type, $tree, &$sortedTargetOrder)
    {
        $idFieldName = "";
        $tableName = "";
        if ($type == "category") {
            $idFieldName = "icat_id";
            $tableName = "MenuItemCategory";
        } else if ($type == "department") {
            $idFieldName = "idep_id";
            $tableName = "MenuItemDept";
        } else if ($type == "course") {
            $idFieldName = "icou_id";
            $tableName = "MenuItemCourse";
        }

        $sortedTargetOrder[$tree[$tableName][$idFieldName]] = array();
        if (!isset($tree['children']) || count($tree['children']) == 0)
            return;

        foreach ($tree['children'] as $treeChildren)
            $this->__getTreeContent($type, $treeChildren, $sortedTargetOrder);
    }

    // Create print jobs for a item
    private function __createItemBasicParams($type, $checkId, $checkItem, $posActionPrintQueue, &$printQueueArray, &$modifiers, $info, $varPrintTime)
    {

        $item = array(
            'ItemId' => "",
            'ItemName' => "",
            'ItemShortName' => "",
            'ItemInfo' => "",
            'SetMenuName' => "",
            'ItemCatName' => "",
            'ItemDept' => "",
            'ItemCourseName' => "",
            'ItemCode' => "",
            'ItemQuantity' => "",
            'ItemOriginalPrice' => "",
            'ItemPrice' => "",
            'ItemDiscountTotal' => "",
            'ItemTakeout' => 0,
            'SetMenuCode' => "",
            'Modifiers' => "",
        );

        for ($index = 1; $index <= 5; $index++) {
            $item['ItemNameL' . $index] = "";
            $item['ItemShortNameL' . $index] = "";
            $item['ItemInfoL' . $index] = "";
            $item['ItemCatNameL' . $index] = "";
            $item['ItemDeptL' . $index] = "";
            $item['ItemCourseNameL' . $index] = "";
            $item['SetMenuNameL' . $index] = "";
        }

        if (strcmp($type, "rush_order") == 0) {
            // Message1: Warning/Serious Warning
            if ($checkItem['PosCheckItem']['citm_rush_count'] + 1 > 2)
                $vars['Message1'] = 1;
            else
                $vars['Message1'] = 0;
            for ($index = 1; $index <= 5; $index++)
                $vars['Message1L' . $index] = $vars['Message1'];

            // Message2: Rush count
            $vars['Message2'] = $checkItem['PosCheckItem']['citm_rush_count'] + 1;
            for ($index = 1; $index <= 5; $index++)
                $vars['Message2L' . $index] = $checkItem['PosCheckItem']['citm_rush_count'] + 1;

            // Message3: Item's Order Time
            $vars['Message3'] = date('H:i:s', strtotime($checkItem['PosCheckItem']['citm_order_loctime']));
            for ($index = 1; $index <= 5; $index++)
                $vars['Message3L' . $index] = date('H:i:s', strtotime($checkItem['PosCheckItem']['citm_order_loctime']));

            // Message: Time Difference from order time to now
            $orderTime = strtotime($checkItem['PosCheckItem']['citm_order_loctime']);
            $printTime = strtotime($varPrintTime);
            $diff = abs($printTime - $orderTime);
            $diffHour = floor($diff / 60 / 60);
            $diffMin = floor($diff / 60 % 60);
            $diffSec = floor($diff % 60);

            $vars['Message4'] = sprintf("%02d:%02d:%02d", $diffHour, $diffMin, $diffSec);
            for ($index = 1; $index <= 5; $index++)
                $vars['Message4L' . $index] = sprintf("%02d:%02d:%02d", $diffHour, $diffMin, $diffSec);
        }

        //takeout indicator
        if ($checkItem['PosCheckItem']['citm_ordering_type'] == 't')
            $item['ItemTakeout'] = 1;

        //get item category
        if ($checkItem['PosCheckItem']['citm_icat_id'] > 0) {
            $MenuItemCategoryModel = $this->controller->MenuItemCategory;
            $itemCategory = $MenuItemCategoryModel->findActiveById($checkItem['PosCheckItem']['citm_icat_id']);
            if (!empty($itemCategory))
                for ($index = 1; $index <= 5; $index++)
                    $item['ItemCatNameL' . $index] = $itemCategory['MenuItemCategory']['icat_name_l' . $index];
        }

        //get item department
        if ($checkItem['PosCheckItem']['citm_idep_id'] > 0) {
            $MenuItemDeptModel = $this->controller->MenuItemDept;
            $itemDept = $MenuItemDeptModel->findActiveById($checkItem['PosCheckItem']['citm_idep_id']);
            if (!empty($itemDept))
                for ($index = 1; $index <= 5; $index++)
                    $item['ItemDeptL' . $index] = $itemDept['MenuItemDept']['idep_name_l' . $index];
        }

        //get course item
        if ($checkItem['PosCheckItem']['citm_icou_id'] > 0) {
            $MenuItemCourseModel = $this->controller->MenuItemCourse;
            $itemCourse = $MenuItemCourseModel->findActiveById($checkItem['PosCheckItem']['citm_icou_id']);
            if (!empty($itemCourse))
                for ($index = 1; $index <= 5; $index++)
                    $item['ItemCourseNameL' . $index] = $itemCourse['MenuItemCourse']['icou_name_l' . $index];
        }

        //get modifiers
        if ($checkItem['PosCheckItem']['citm_modifier_count'] > 0) {
            $modifierList = array();
            if (!empty($modifiers)) {
                foreach ($modifiers as $modifier)
                    $modifierList[] = array(
                        'ModifierName' => "",
                        'ModifierNameL1' => $modifier['PosCheckItem']['citm_name_l1'],
                        'ModifierNameL2' => $modifier['PosCheckItem']['citm_name_l2'],
                        'ModifierNameL3' => $modifier['PosCheckItem']['citm_name_l3'],
                        'ModifierNameL4' => $modifier['PosCheckItem']['citm_name_l4'],
                        'ModifierNameL5' => $modifier['PosCheckItem']['citm_name_l5'],
                        'ModifierShortName' => "",
                        'ModifierShortNameL1' => $modifier['PosCheckItem']['citm_short_name_l1'],
                        'ModifierShortNameL2' => $modifier['PosCheckItem']['citm_short_name_l2'],
                        'ModifierShortNameL3' => $modifier['PosCheckItem']['citm_short_name_l3'],
                        'ModifierShortNameL4' => $modifier['PosCheckItem']['citm_short_name_l4'],
                        'ModifierShortNameL5' => $modifier['PosCheckItem']['citm_short_name_l5']
                    );
            }
            $item['Modifiers'] = $modifierList;
        }

        $item['ItemCode'] = $checkItem['PosCheckItem']['citm_code'];
        $item['ItemId'] = $checkItem['PosCheckItem']['citm_id'];
        for ($index = 1; $index <= 5; $index++) {
            $item['ItemNameL' . $index] = $checkItem['PosCheckItem']['citm_name_l' . $index];
            $item['ItemShortNameL' . $index] = $checkItem['PosCheckItem']['citm_short_name_l' . $index];
            $item['ItemInfoL' . $index] = $checkItem['PosCheckItem']['citm_short_name_l' . $index];
            $item['SetMenuNameL' . $index] = "";
        }

        // include partial send pending item information if exists
        if (isset($info['info']['partialSendQty']) && !empty($info['info']['partialSendQty']))
            $item['partialSendQty'] = $info['info']['partialSendQty'];

        if (strcmp($type, "delete_item") == 0 && isset($info['info']['removeQty'])) {
            $found = false;
            for ($index = 0; $index < count($info['info']['removeQty']); $index++) {
                if (isset($info['info']['removeQty'][$index][$checkItem['PosCheckItem']['citm_id']])) {
                    $item['ItemQuantity'] = $info['info']['removeQty'][$index][$checkItem['PosCheckItem']['citm_id']];
                    $found = true;
                }
            }
            if (!$found)
                $item['ItemQuantity'] = $checkItem['PosCheckItem']['citm_qty'];
        } else
            $item['ItemQuantity'] = $checkItem['PosCheckItem']['citm_qty'];
        $item['ItemOriginalPrice'] = $checkItem['PosCheckItem']['citm_original_price'];
        $item['ItemPrice'] = $checkItem['PosCheckItem']['citm_round_total'] + $checkItem['PosCheckItem']['citm_pre_disc'] + $checkItem['PosCheckItem']['citm_mid_disc'] + $checkItem['PosCheckItem']['citm_post_disc'];
        $item['ItemDiscountTotal'] = $checkItem['PosCheckItem']['citm_pre_disc'] + $checkItem['PosCheckItem']['citm_mid_disc'] + $checkItem['PosCheckItem']['citm_post_disc'];
        $item['SetMenuCode'] = "";

        // get the split item parent item id
        if (isset($checkItem['splitItemParentItemid']))
            $item['SplitItemParentItemId'] = $checkItem['splitItemParentItemid'];

        //get the print queue according to action print queue's method
        if ($posActionPrintQueue['PosActionPrintQueue']['acpq_method'] == "") {
            for ($i = 1; $i <= 10; $i++)
                $printQueueArray[$i] = $checkItem['PosCheckItem']['citm_print_queue' . $i . '_itpq_id'];

        } else if ($posActionPrintQueue['PosActionPrintQueue']['acpq_method'] == "f") {
            for ($i = 1; $i <= 10; $i++) {
                if ($checkItem['PosCheckItem']['citm_print_queue' . $i . '_itpq_id'] > 0) {
                    $printQueueArray[1] = $checkItem['PosCheckItem']['citm_print_queue' . $i . '_itpq_id'];
                    break;
                }
            }

        } else if ($posActionPrintQueue['PosActionPrintQueue']['acpq_method'] == "l") {
            for ($i = 1; $i <= 10; $i++) {
                if ($checkItem['PosCheckItem']['citm_print_queue' . $i . '_itpq_id'])
                    $printQueueArray[1] = $checkItem['PosCheckItem']['citm_print_queue' . $i . '_itpq_id'];
            }

        } else if ($posActionPrintQueue['PosActionPrintQueue']['acpq_method'] == "s")
            $printQueueArray[1] = $posActionPrintQueue['PosActionPrintQueue']['acpq_itpq_id'];

        return $item;
    }

    //get the corresponding highest level of department
    private function __getHighestLevelDepartmentId($depts, $deptId)
    {
        $highestLevelDeptId = 0;
        $find = false;

        foreach ($depts as $department) {
            if ($department['MenuItemDept']['idep_id'] == $deptId)
                $find = true;

            if (!$find && isset($department['children']) && !empty($department['children']) && count($department['children']) > 0) {
                $ret = $this->__getHighestLevelDepartmentId($department['children'], $deptId);
                if ($ret > 0)
                    $find = true;
            }

            if ($find) {
                $highestLevelDeptId = $department['MenuItemDept']['idep_id'];
                break;
            }
        }

        return $highestLevelDeptId;
    }

    //get the corresponding highest level of category
    private function __getHighestLevelCategoryId($categories, $categoryId)
    {
        $highestLevelCatId = 0;
        $find = false;

        foreach ($categories as $category) {
            if ($category['MenuItemCategory']['icat_id'] == $categoryId)
                $find = true;

            if (!$find && isset($category['children']) && !empty($category['children']) && count($category['children']) > 0) {
                $ret = $this->__getHighestLevelCategoryId($category['children'], $categoryId);
                if ($ret > 0)
                    $find = true;
            }

            if ($find) {
                $highestLevelCatId = $category['MenuItemCategory']['icat_id'];
                break;
            }
        }

        return $highestLevelCatId;
    }

    // Update print queue name information
    private function __updatePrintQueueName($prtPrintQueueId, &$printQueue)
    {
        $PrtPrintQueueModel = $this->controller->PrtPrintQueue;
        $prtPrintQueue = $PrtPrintQueueModel->findActiveById($prtPrintQueueId);
        if (!empty($prtPrintQueue) && isset($prtPrintQueue['PrtPrintQueue']['prtq_id'])) {
            $printQueue['PosItemPrintQueue']['name_l1'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l1'];
            $printQueue['PosItemPrintQueue']['name_l2'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l2'];
            $printQueue['PosItemPrintQueue']['name_l3'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l3'];
            $printQueue['PosItemPrintQueue']['name_l4'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l4'];
            $printQueue['PosItemPrintQueue']['name_l5'] = $prtPrintQueue['PrtPrintQueue']['prtq_name_l5'];
        }
    }

    // Get print queue Id of printing module
    private function __getPrintJobPrintQueueId($itemOrderStation, &$printQueue, $prtQueueOverrideConditions, $businessDate, $checkCreateTime, $itemOrderTime, $tableNo, $tableExt, $orderingType, $periodId, $customTypeId, $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay, $weekday, $itemArray = null, &$overridePrtqItemArray = null)
    {

        //handle station printer
        if ($printQueue['PosItemPrintQueue']['itpq_station_printer'] > 0) {
            if ($printQueue['PosItemPrintQueue']['itpq_station_printer'] == 1)
                $printJobPrintQueueId = $itemOrderStation['PosStation']['stat_station_printer1_prtq_id'];
            else
                $printJobPrintQueueId = $itemOrderStation['PosStation']['stat_station_printer2_prtq_id'];

            $PrtPrintQueueModel = $this->controller->PrtPrintQueue;
            $stationPrintQueue = $PrtPrintQueueModel->findActiveById($printJobPrintQueueId);
            if (!empty($stationPrintQueue) && isset($stationPrintQueue['PrtPrintQueue']['prtq_id'])) {
                $printQueue['PosItemPrintQueue']['name_l1'] = $stationPrintQueue['PrtPrintQueue']['prtq_name_l1'];
                $printQueue['PosItemPrintQueue']['name_l2'] = $stationPrintQueue['PrtPrintQueue']['prtq_name_l2'];
                $printQueue['PosItemPrintQueue']['name_l3'] = $stationPrintQueue['PrtPrintQueue']['prtq_name_l3'];
                $printQueue['PosItemPrintQueue']['name_l4'] = $stationPrintQueue['PrtPrintQueue']['prtq_name_l4'];
                $printQueue['PosItemPrintQueue']['name_l5'] = $stationPrintQueue['PrtPrintQueue']['prtq_name_l5'];
            }
        } else
            $printJobPrintQueueId = $printQueue['PosItemPrintQueue']['itpq_prtq_id'];

        //check print queue override
        $overridePrintQueueId = $this->__checkPrintQueueOverride($prtQueueOverrideConditions, $printJobPrintQueueId, $businessDate, $checkCreateTime, $itemOrderTime, $tableNo, $tableExt, $orderingType, $itemOrderStation['PosStation']['stat_stgp_id'], $periodId, $customTypeId, $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay, $weekday, true, $itemArray, $overridePrtqItemArray);
        if ($overridePrintQueueId != $printJobPrintQueueId) {
            $PrtPrintQueueModel = $this->controller->PrtPrintQueue;
            $overridePrintQueue = $PrtPrintQueueModel->findActiveById($overridePrintQueueId);
            if (!empty($overridePrintQueue)) {
                $printJobPrintQueueId = $overridePrintQueueId;
                $printQueue['PosItemPrintQueue']['name_l1'] = $overridePrintQueue['PrtPrintQueue']['prtq_name_l1'];
                $printQueue['PosItemPrintQueue']['name_l2'] = $overridePrintQueue['PrtPrintQueue']['prtq_name_l2'];
                $printQueue['PosItemPrintQueue']['name_l3'] = $overridePrintQueue['PrtPrintQueue']['prtq_name_l3'];
                $printQueue['PosItemPrintQueue']['name_l4'] = $overridePrintQueue['PrtPrintQueue']['prtq_name_l4'];
                $printQueue['PosItemPrintQueue']['name_l5'] = $overridePrintQueue['PrtPrintQueue']['prtq_name_l5'];
            }
        }

        return $printJobPrintQueueId;
    }

    // Check print queue override
    private function __checkPrintQueueOverride($prtQueueOverrideConditions, $originalPrtqId, $businessDate, $checkCreateTime, $itemOrderTime, $tableNo, $tableExt, $itemOrderingType, $stationGroupId, $periodId, $customTypeId, $isHoliday, $isDayBeforeHoliday, $isSpecialDay, $isDayBeforeSpecialDay, $weekday, $withItem = false, $itemArray = null, &$overridePrtqItemArray = null)
    {
        $overrideConditions = array();
        $targetPrtqId = $originalPrtqId;

        if (empty($prtQueueOverrideConditions))
            return $targetPrtqId;

        $lowestPriorityConditions = array();
        for ($index = 0; $index < count($prtQueueOverrideConditions); $index++) {
            if ($prtQueueOverrideConditions[$index]['PosOverrideCondition']['over_from_prtq_id'] == $originalPrtqId)
                $overrideConditions[] = $prtQueueOverrideConditions[$index]['PosOverrideCondition'];
        }

        if (empty($overrideConditions))
            return $targetPrtqId;

        for ($i = 0; $i < count($overrideConditions); $i++) {
            $overrideCondition = $overrideConditions[$i];

            //checking date range, time range, table range, station group, period, special hour
            $dateRangeChecking = false;
            $timeRangeChecking = false;
            $tableRangeChecking = false;
            $orderingTypeChecking = false;
            $stationGroupChecking = false;
            $periodChecking = false;
            $specialHourChecking = true;
            $customTypeChecking = false;

            /* date range */
            if ($overrideCondition['over_start_date'] == "") {
                if ($overrideCondition['over_end_date'] == "" || strcmp($overrideCondition['over_end_date'], $businessDate) >= 0)
                    $dateRangeChecking = true;
            } else if (strcmp($overrideCondition['over_start_date'], $businessDate) == 0) {
                if ($overrideCondition['over_end_date'] == "" || strcmp($overrideCondition['over_end_date'], $businessDate) >= 0)
                    $dateRangeChecking = true;
            } else if (strcmp($overrideCondition['over_start_date'], $businessDate) < 0) {
                if ($overrideCondition['over_end_date'] == "" || strcmp($overrideCondition['over_end_date'], $businessDate) >= 0)
                    $dateRangeChecking = true;
            }

            /* time range */
            if ($withItem)
                $time = $itemOrderTime;
            else
                $time = "";
            if ($overrideCondition['over_time_by'] == "c")
                $time = $checkCreateTime;
            if ($overrideCondition['over_start_time'] == "") {
                if ($overrideCondition['over_end_time'] == "" || strcmp($overrideCondition['over_end_time'], $time) >= 0)
                    $timeRangeChecking = true;
            } else if (strcmp($overrideCondition['over_start_time'], $time) == 0) {
                if ($overrideCondition['over_end_time'] == "" || strcmp($overrideCondition['over_end_time'], $time) >= 0)
                    $timeRangeChecking = true;
            } else if (strcmp($overrideCondition['over_start_time'], $time) < 0) {
                if ($overrideCondition['over_end_time'] == "" || strcmp($overrideCondition['over_end_time'], $time) >= 0)
                    $timeRangeChecking = true;
            }

            /* table range */
            $filterTableNo = false;
            $filterTableExt = false;
            if ($overrideCondition['over_start_table'] > 0 || $overrideCondition['over_end_table'] > 0 || $overrideCondition['over_start_table_ext'] != "" || $overrideCondition['over_end_table_ext'] != "") {
                $filterTableNo = $this->__filterTableByNo($overrideCondition['over_start_table'], $tableNo, $overrideCondition['over_end_table']);

                $filterTableExt = $this->__filterTableByExt($overrideCondition['over_start_table_ext'], $tableExt, $overrideCondition['over_end_table_ext']);

                if ($filterTableNo && $filterTableExt)
                    $tableRangeChecking = true;
            } else if ($overrideCondition['over_start_table'] == 0 && $overrideCondition['over_end_table'] == 0 && $overrideCondition['over_start_table_ext'] == "" && $overrideCondition['over_end_table_ext'] == "")
                $tableRangeChecking = true;

            /* ordering type */
            if ($overrideCondition['over_ordering_type'] == "")
                $orderingTypeChecking = true;
            else {
                if ($withItem && $itemArray != null && !empty($itemArray)) {
                    $overridePrtqArray = array();
                    $modifiedItemCount = 0;

                    for ($index = 0; $index < count($itemArray); $index++) {
                        if ($overrideCondition['over_ordering_type'] == "t" && $itemArray[$index]['ItemTakeout'] == 1) {
                            $overridePrtqArray[$overrideCondition['over_to_prtq_id']][] = $index;
                            $modifiedItemCount++;
                        }
                    }

                    if ($modifiedItemCount == count($itemArray)) {
                        $orderingTypeChecking = true;
                        $overridePrtqItemArray = null;
                    } else
                        $overridePrtqItemArray = $overridePrtqArray;
                } else {
                    if ($overrideCondition['over_ordering_type'] == $itemOrderingType)
                        $orderingTypeChecking = true;
                }
            }

            /* station group */
            if ($overrideCondition['over_stgp_id'] == 0 || $overrideCondition['over_stgp_id'] == $stationGroupId)
                $stationGroupChecking = true;

            /* period */
            if ($overrideCondition['over_perd_id'] == 0 || $overrideCondition['over_perd_id'] == $periodId)
                $periodChecking = true;

            /* custom type */
            if ($overrideCondition['over_ctyp_id'] == 0 || $overrideCondition['over_ctyp_id'] == $customTypeId)
                $customTypeChecking = true;

            //checking special day, day before special day, holiday day, day before holiday, week mask
            $specialControlChecking = false;
            $specialControlFulfill = false;
            if ($specialControlFulfill == false && $isSpecialDay && $overrideCondition['over_special_day'] != "") {
                if ($overrideCondition['over_special_day'] == "y") {
                    $specialControlChecking = true;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_special_day'] == "z" && substr($overrideCondition['over_week_mask'], $weekday, 1) == 1) {
                    $specialControlChecking = true;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_special_day'] == "n") {
                    $specialControlChecking = false;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_special_day'] == "x" && substr($overrideCondition['over_week_mask'], $weekday, 1) == 1) {
                    $specialControlChecking = false;
                    $specialControlFulfill = true;
                }
            }
            if ($specialControlFulfill == false && $isDayBeforeSpecialDay && $overrideCondition['over_day_before_special_day'] != "") {
                if ($overrideCondition['over_day_before_special_day'] == "y") {
                    $specialControlChecking = true;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_day_before_special_day'] == "z" && substr($overrideCondition['over_week_mask'], $weekday, 1) == 1) {
                    $specialControlChecking = true;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_day_before_special_day'] == "n") {
                    $specialControlChecking = false;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_day_before_special_day'] == "x" && substr($overrideCondition['over_week_mask'], $weekday, 1) == 1) {
                    $specialControlChecking = false;
                    $specialControlFulfill = true;
                }
            }
            if ($specialControlFulfill == false && $isHoliday && $overrideCondition['over_holiday'] != "") {
                if ($overrideCondition['over_holiday'] == "y") {
                    $specialControlChecking = true;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_holiday'] == "z" && substr($overrideCondition['over_week_mask'], $weekday, 1) == 1) {
                    $specialControlChecking = true;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_holiday'] == "n") {
                    $specialControlChecking = false;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_holiday'] == "x" && substr($overrideCondition['over_week_mask'], $weekday, 1) == 1) {
                    $specialControlChecking = false;
                    $specialControlFulfill = true;
                }
            }
            if ($specialControlFulfill == false && $isDayBeforeHoliday && $overrideCondition['over_day_before_holiday'] != "") {
                if ($overrideCondition['over_day_before_holiday'] == "y") {
                    $specialControlChecking = true;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_day_before_holiday'] == "z" && substr($overrideCondition['over_week_mask'], $weekday, 1) == 1) {
                    $specialControlChecking = true;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_day_before_holiday'] == "n") {
                    $specialControlChecking = false;
                    $specialControlFulfill = true;
                } else if ($overrideCondition['over_day_before_holiday'] == "x" && substr($overrideCondition['over_week_mask'], $weekday, 1) == 1) {
                    $specialControlChecking = false;
                    $specialControlFulfill = true;
                }
            }
            if ($specialControlFulfill == false && substr($overrideCondition['over_week_mask'], $weekday, 1) == 1)
                $specialControlChecking = true;

            if ($dateRangeChecking && $timeRangeChecking && $tableRangeChecking && $orderingTypeChecking && $stationGroupChecking && $periodChecking && $customTypeChecking && $specialHourChecking && $specialControlChecking) {
                $targetPrtqId = $overrideCondition['over_to_prtq_id'];
                break;
            }
        }

        return $targetPrtqId;
    }

    private function __filterTableByNo($startTable, $curTable, $endTable)
    {
        if ($startTable == 0 && $endTable == 0 && $curTable != 0)
            return false;
        //	no upper limit for table no
        if ($endTable == 0) {
            if ($startTable <= $curTable)
                return true;
        } //	with or without lower limit for table no
        else {
            if ($startTable <= $curTable && $curTable <= $endTable)
                return true;
        }
        return false;
    }

    // Update the date related variable according to print format
    private function __updateDateFormat($posPrintFormat, $outletDateFormatIndex, $shopTimeZone, $shopTimeZoneName, $businessDate, $openTime, $printTime, $closeTime, &$vars, $functionName)
    {
        $dateFormatIndex = 0;
        if ($posPrintFormat['PosPrintFormat']['pfmt_date_format'] == 0)
            $dateFormatIndex = $outletDateFormatIndex;
        else
            $dateFormatIndex = $posPrintFormat['PosPrintFormat']['pfmt_date_format'];

        switch ($dateFormatIndex) {
            case 8:
                $dateFormat = "m-d-Y";
                break;
            case 7:
                $dateFormat = "M j, Y";
                break;
            case 6:
                $dateFormat = "j M, Y";
                break;
            case 5:
                $dateFormat = "j/n/Y";
                break;
            case 4:
                $dateFormat = "j-n-Y";
                break;
            case 3:
                $dateFormat = "d-m-Y";
                break;
            case 2:
                $dateFormat = "Y-n-j";
                break;
            case 0:
            case 1:
            default:
                $dateFormat = "Y-m-d";
                break;
        }
        $vars['BusinessDate'] = date($dateFormat, strtotime($businessDate));

        switch ($functionName) {
            case "generateCheckReceiptSlip":
            case "generateCheckSlip":
                $vars['CheckOpenDate'] = !empty($openTime) ? date($dateFormat, strtotime($openTime)) : "";
                $vars['PrintDate'] = !empty($printTime) ? date($dateFormat, strtotime($printTime)) : date($dateFormat, Date::localTimestamp($shopTimeZone * 60, $shopTimeZoneName, time()));
                $vars['CheckCloseDate'] = !empty($closeTime) ? date($dateFormat, strtotime($closeTime)) : "";
                break;
            case "generateMultipleSpecialSlip":
                $vars['PrintDate'] = date($dateFormat, Date::localTimestamp($shopTimeZone * 60, $shopTimeZoneName, time()));
                break;
            default:
                break;
        }
    }

    private function __filterTableByExt($startTableExt, $curTableExt, $endTableExt)
    {
        //no upper limit for table extension
        if ($endTableExt == "") {
            if (strcmp($startTableExt, $curTableExt) <= 0)
                return true;
        } //	with or without lower limit for table extension
        else {
            if (strcmp($startTableExt, $curTableExt) <= 0 && strcmp($curTableExt, $endTableExt) <= 0)
                return true;
        }
        return false;
    }

    // Get print format
    private function __getPosPrintFormat($printQueue)
    {
        $PosPrintFormatModel = $this->controller->PosPrintFormat;
        $printFormat = $PosPrintFormatModel->findActiveById($printQueue['PosItemPrintQueue']['itpq_pfmt_id']);
        if (!empty($printFormat))
            return $printFormat;
        else
            return null;
    }

    // Add addition Var by print queue and print formate default lang
    private function __addAdditionalVar($printQueue, $prtFmtDefaultLang, $shop, $outlet, $itemOrderUser, $itemOrderStation, $tableInfo, &$vars)
    {

        //constuct the vars for print format setup
        if (!empty($itemOrderStation))
            $vars['StationName'] = $itemOrderStation['PosStation']['stat_name_l' . $prtFmtDefaultLang];
        if (!empty($shop))
            $vars['ShopName'] = $shop['OutShop']['shop_name_l' . $prtFmtDefaultLang];
        if (!empty($outlet)) {
            $vars['OutletName'] = $outlet['OutOutlet']['olet_name_l' . $prtFmtDefaultLang];
            $vars['Address'] = $outlet['OutOutlet']['olet_addr_l' . $prtFmtDefaultLang];
        }
        if (!empty($tableInfo))
            $vars['TableName'] = $tableInfo[0]['OutFloorPlanTable']['flrt_name_l' . $prtFmtDefaultLang];
        if (!empty($printQueue))
            $vars['PrintQueueName'] = $printQueue['PosItemPrintQueue']['name_l' . $prtFmtDefaultLang];
        if (!empty($itemOrderUser)) {
            $vars['PrintEmployeeFirstName'] = $this->__checkStringExist($itemOrderUser['UserUser'], "user_first_name_l" . $prtFmtDefaultLang);
            $vars['PrintEmployeeLastName'] = $this->__checkStringExist($itemOrderUser['UserUser'], "user_last_name_l" . $prtFmtDefaultLang);
            $vars['PrintEmployee'] = $vars['PrintEmployeeLastName'] . ' ' . $vars['PrintEmployeeFirstName'];
        }

        for ($index = 1; $index <= 5; $index++) {
            if (!empty($printQueue))
                $vars['PrintQueueNameL' . $index] = $printQueue['PosItemPrintQueue']['name_l' . $index];
        }

    }

    // Create kitchen slip head section var
    private function __addAdditionalItemParams($prtFmtDefaultLang, $checkItem, $parentCheckItem, $itemCourse, $itemCategory, $itemDept, $modifiers, &$item)
    {
        //constuct the vars for print format setup
        if ($prtFmtDefaultLang > 0) {
            $item['ItemName'] = $checkItem['citm_name_l' . $prtFmtDefaultLang];
            $item['ItemShortName'] = $checkItem['citm_short_name_l' . $prtFmtDefaultLang];
            $item['ItemInfo'] = $checkItem['item_info_l' . $prtFmtDefaultLang];
            if ($checkItem['citm_icou_id'] > 0 && !empty($itemCourse))
                $item['ItemCourseName'] = $itemCourse['MenuItemCourse']['icou_name_l' . $prtFmtDefaultLang];
            if ($checkItem['citm_icat_id'] > 0 && !empty($itemCategory))
                $item['ItemCatName'] = $itemCategory['MenuItemCategory']['icat_name_l' . $prtFmtDefaultLang];
            if ($checkItem['citm_idep_id'] > 0 && !empty($itemDept))
                $item['ItemDept'] = $itemDept['MenuItemDept']['idep_name_l' . $prtFmtDefaultLang];
            if (count($item['Modifiers']) > 0 && !empty($modifiers)) {
                for ($j = 0; $j < count($modifiers); $j++) {
                    $item['Modifiers'][$j]['ModifierName'] = $modifiers[$j]['PosCheckItem']['citm_name_l' . $prtFmtDefaultLang];
                    $item['Modifiers'][$j]['ModifierShortName'] = $modifiers[$j]['PosCheckItem']['citm_short_name_l' . $prtFmtDefaultLang];
                }
            }
            if (isset($parentCheckItem))
                $item['SetMenuName'] = $parentCheckItem['citm_name_l' . $prtFmtDefaultLang];
        }
        $item['DeliveryTime'] = $checkItem['citm_delivery_time'];
    }

    //sort item array by type
    private function __sortItemArray($sortType, $itemArray)
    {
        $sortItemArray = array();

        if ($sortType == "s")
            $sortTypeName = "ItemSeatNum";
        else if ($sortType == "u") {
            $sortTypeName = "ItemCourseNum";
            $MenuItemCourseModel = $this->controller->MenuItemCourse;
            $itemCourse = $MenuItemCourseModel->find('all', array(
                    'fields' => array('icou_id'),
                    'conditions' => array('icou_status' => ''),
                    'order' => 'icou_seq',
                    'recursive' => -1
                )
            );
            $sortedItemCourseId = array();
            for ($i = 0; $i < count($itemCourse); $i++)
                $sortedItemCourseId[$itemCourse[$i]['MenuItemCourse']['icou_id']] = ($i + 1);
            for ($i = 0; $i < count($itemArray); $i++) {
                if ($itemArray[$i]['ItemCourseNum'] != 0)
                    $itemArray[$i]['ItemCourseNum'] = $sortedItemCourseId[$itemArray[$i]['ItemCourseNum']];
            }
        } else if ($sortType == "q")
            $sortTypeName = "ItemSequence";
        else
            $sortTypeName = "ItemMenuId";

        $sortItemArray = $this->__multiDemensionArraySort($itemArray, $sortTypeName);

        return $sortItemArray;
    }

    //sort item array base on group and by type
    private function __sortItemArrayBaseOnGroup($groupBy, $sortType, $itemArray)
    {
        $groupByArray = array();
        $finalItemArray = array();

        if ($groupBy == "s")
            $groupTypeName = "ItemSeatNum";
        else if ($groupBy == "u")
            $groupTypeName = "ItemCourseNum";
        else if ($groupBy == "q")
            $groupTypeName = "ItemSequence";
        else
            $groupTypeName = "ItemMenuId";

        if ($sortType == "s")
            $sortTypeName = "ItemSeatNum";
        else if ($sortType == "u")
            $sortTypeName = "ItemCourseNum";
        else if ($sortType == "q")
            $sortTypeName = "ItemSequence";
        else
            $sortTypeName = "ItemMenuId";

        foreach ($itemArray as $item)
            $groupByArray[$item[$groupTypeName]][] = $item;

        foreach ($groupByArray as $key => $itemInGroupArray) {
            $sortItemArray = $this->__multiDemensionArraySort($itemInGroupArray, $sortTypeName);
            if (!empty($sortItemArray))
                foreach ($sortItemArray as $sortedKey => $sortedItem)
                    $finalItemArray[] = $sortedItem;
        }

        $currentGroup = "";
        $pastGroup = "";
        for ($i = 0; $i < count($finalItemArray); $i++) {
            $currentGroup = $finalItemArray[$i][$groupTypeName];
            if (strcmp($currentGroup, $pastGroup) != 0) {
                $finalItemArray[$i]['ItemGroupStart'] = 1;
                if (($i - 1) >= 0)
                    $finalItemArray[($i - 1)]['ItemGroupEnd'] = 1;
            }
            if ($i == (count($finalItemArray) - 1))
                $finalItemArray[$i]['ItemGroupEnd'] = 1;
            $pastGroup = $currentGroup;
        }

        return $finalItemArray;
    }

    //sorting multi-demensional array
    private function __multiDemensionArraySort($inputArray, $key)
    {
        $sorter = array();
        $ret = array();
        reset($inputArray);
        foreach ($inputArray as $index => $value) {
            $sorter[$index] = $value[$key];
        }
        natsort($sorter);

        foreach ($sorter as $index => $value) {
            $ret[$index] = $inputArray[$index];
        }

        return $ret;
    }

    //get outCalendar with sorting cateria
    private function __checkCalendarHolidaySpecialDay($date, $shopId, $outletId, &$isHoliday, &$isDayBeforeHoliday, &$isSpecialDay, &$isDayBeforeSpecialDay)
    {
        $OutCalendarModel = $this->controller->OutCalendar;
        $isHoliday = false;
        $isSpecialDay = false;
        $isDayBeforeHoliday = false;
        $isDayBeforeSpecialDay = false;

        $todayCalendars = $OutCalendarModel->findAllByShopOletDate($shopId, $outletId, $date);
        if (!empty($todayCalendars)) {
            //	Re-sequence the records by sorting them by priorities
            $countRec = count($todayCalendars);
            $logValue = ceil(log10($countRec + 1));
            $offset = pow(10, $logValue);

            $todayCalendarAcls = array();
            foreach ($todayCalendars as $todayCalendar) {
                $outletMark = '1';
                if (!empty($todayCalendar['OutCalendar']['cald_olet_id'])) $outletMark = '0';

                $shopMark = '1';
                if (!empty($todayCalendar['OutCalendar']['cald_shop_id'])) $shopMark = '0';

                $key = $outletMark . '-' . $shopMark;
                $todayCalendar['group_key'] = $key;

                $todayCalendarAcls[$key . '-' . $offset] = $todayCalendar;
                $offset++;
            }
            ksort($todayCalendarAcls);        // sort the ACL records by priorities

            foreach ($todayCalendarAcls as $todayCalendarAcl) {
                if ($todayCalendarAcl['OutCalendar']['cald_type'] == "h")
                    $isHoliday = true;
                else if ($todayCalendarAcl['OutCalendar']['cald_type'] == "s")
                    $isSpecialDay = true;
                break;
            }
        }

        $tomorrow = mktime(0, 0, 0, substr($date, 5, 2), substr($date, 8, 2) + 1, substr($date, 0, 4));
        $tomorrowCalendars = $OutCalendarModel->findAllByShopOletDate($shopId, $outletId, date('Y-m-d', $tomorrow));
        if (!empty($tomorrowCalendars)) {
            //	Re-sequence the records by sorting them by priorities
            $countRec = count($tomorrowCalendars);
            $logValue = ceil(log10($countRec + 1));
            $offset = pow(10, $logValue);

            $tomorrowCalendarAcls = array();
            foreach ($tomorrowCalendars as $tomorrowCalendar) {
                $outletMark = '1';
                if (!empty($tomorrowCalendar['OutCalendar']['cald_olet_id'])) $outletMark = '0';

                $shopMark = '1';
                if (!empty($tomorrowCalendar['OutCalendar']['cald_shop_id'])) $shopMark = '0';

                $key = $outletMark . '-' . $shopMark;
                $tomorrowCalendar['group_key'] = $key;

                $tomorrowCalendarAcls[$key . '-' . $offset] = $tomorrowCalendar;
                $offset++;
            }
            ksort($tomorrowCalendarAcls);        // sort the ACL records by priorities

            foreach ($tomorrowCalendarAcls as $tomorrowCalendarAcl) {
                if ($tomorrowCalendarAcl['OutCalendar']['cald_type'] == "h")
                    $isDayBeforeHoliday = true;
                else if ($tomorrowCalendarAcl['OutCalendar']['cald_type'] == "s")
                    $isDayBeforeSpecialDay = true;
                break;
            }
        }
    }

    //calculate the item's tax/sc for malaysia tax
    private function __calculateMalaysiaTaxAnaylsis(&$check, &$checkItems, $businessDay, $posTaxTypes)
    {
        if (isset($check['PosCheck']))
            $posCheck = &$check['PosCheck'];
        else
            $posCheck = &$check;
        if (isset($businessDay['PosBusinessDay']))
            $businessDay = $businessDay['PosBusinessDay'];
        else
            $businessDay = $businessDay;

        $taxTypes = array();
        foreach ($posTaxTypes as $posTaxType)
            $taxTypes[$posTaxType['PosTaxScType']['txsc_number']] = $posTaxType['PosTaxScType'];

        //check whether having item is inclusive tax with no breakdown
        $haveInclusiveTaxNoBreakDown = false;
        for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
            if (isset($checkItems[$itemIndex]['PosCheckItem']))
                $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
            else
                $posItem = &$checkItems[$itemIndex];

            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                if ($posItem['citm_charge_tax' . $taxIndex] == "n") {
                    $haveInclusiveTaxNoBreakDown = true;
                    break;
                }
            }

            if ($haveInclusiveTaxNoBreakDown)
                break;
        }

        if ($haveInclusiveTaxNoBreakDown) {
            /********************************************************************************************************************************/
            /********************************************************************************************************************************/
            /***** FOR INCLUSIVE NO BREAKDOWN, ONLY HANDLE PRE-DISCOUNT. IF NEED TO USE POST-DISCOUNT, NEED TO USE INCLUSIVE BREAKDOWN. *****/
            /********************************************************************************************************************************/
            /********************************************************************************************************************************/

            //calculate service charge 1-5 round total
            //calculate tax 1-25 on item round total and tax x on item service charge 1-5
            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                //calculate the item discount base for item total, sc, tax
                $posItem['itemDiscountRoundTotal'] = 0;
                $posItem['itemDiscountNotRoundTotal'] = 0;
                $posItem['itemDiscountType'] = 'b';
                if (isset($posItem['PosCheckDiscount'])) {
                    foreach ($posItem['PosCheckDiscount'] as $itemDisc) {
                        $posItem['itemDiscountRoundTotal'] += $itemDisc['cdis_round_total'];
                        $posItem['itemDiscountNotRoundTotal'] += $itemDisc['cdis_total'];
                        $posItem['itemDiscountType'] = $itemDisc['cdis_type'];
                    }
                }

                $sumOfDiscountAmount = 0;
                $posItem['discount_on_item_total'] = $posItem['itemDiscountNotRoundTotal'];
                $sumOfDiscountAmount += $posItem['discount_on_item_total'];
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $posItem['discount_on_sc' . $scIndex] = 0;
                }
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    $posItem['discount_on_tax' . $taxIndex] = 0;
                }
                if ($sumOfDiscountAmount != $posItem['itemDiscountNotRoundTotal'])
                    $posItem['discount_on_item_total'] += $posItem['itemDiscountNotRoundTotal'] - $sumOfDiscountAmount;

                //calculate the check discount base for item total, sc, tax
                $posItem['checkDiscountRoundTotal'] = 0;
                $posItem['checkDiscountNotRoundTotal'] = 0;
                $posItem['check_discount_on_item_total'] = 0;
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $posItem['check_discount_on_sc' . $scIndex] = 0;
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                    $posItem['check_discount_on_tax' . $taxIndex] = 0;
                if (isset($posItem['PosCheckDiscountItem'])) {
                    $checkDiscounts = array();
                    $oldCheck = false;
                    if (isset($check['checkDiscounts']))
                        $checkDiscounts = $check['checkDiscounts'];
                    else {
                        $checkDiscounts = $check['PosCheckDiscount'];
                        $oldCheck = true;
                    }

                    foreach ($posItem['PosCheckDiscountItem'] as $checkDiscItem) {
                        $posItem['checkDiscountRoundTotal'] += $checkDiscItem['cdit_round_total'];
                        $posItem['checkDiscountNotRoundTotal'] += $checkDiscItem['cdit_total'];
                        $checkDiscountType = 'b';
                        foreach ($checkDiscounts as $checkDisc) {
                            if ($oldCheck) {
                                if ($checkDisc['cdis_id'] == $checkDiscItem['cdit_cdis_id']) {
                                    $checkDiscountType = $checkDisc['cdis_type'];
                                    break;
                                }
                            } else {
                                if ($checkDisc['cdis_seq'] == $checkDiscItem['cdis_seq']) {
                                    $checkDiscountType = $checkDisc['cdis_type'];
                                    break;
                                }
                            }
                        }

                        $sumOfCheckDiscountAmount = 0;
                        $posItem['check_discount_on_item_total'] += $checkDiscItem['cdit_total'];
                        $sumOfCheckDiscountAmount += $checkDiscItem['cdit_total'];
                        for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                            $checkDiscountOnSC = 0;
                        }
                        for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                            $checkDiscountTax = 0;
                        }
                        if ($sumOfCheckDiscountAmount != $checkDiscItem['cdit_round_total'])
                            $posItem['check_discount_on_item_total'] += $checkDiscItem['cdit_round_total'] - $sumOfCheckDiscountAmount;
                    }
                }

                //calculate the sc charge with rounding
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $posItem['citm_sc' . $scIndex . '_round'] = 0;
                    $posItem['citm_sc' . $scIndex . '_round'] = Math::doRounding($posItem['citm_sc' . $scIndex], $businessDay['bday_sc_round'], $businessDay['bday_sc_decimal']);
                }

                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    $posItem['tax' . $taxIndex . '_on_citm_round_total'] = 0;
                    $posItem['tax' . $taxIndex . '_on_item_discount'] = 0;
                    for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                        $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] = 0;

                    if (isset($taxTypes[$taxIndex]) && $posItem['citm_charge_tax' . $taxIndex] == "n") {
                        if ($taxIndex <= 4) {
                            $posItem['tax' . $taxIndex . '_on_citm_round_total'] = $posItem['citm_incl_tax_ref' . $taxIndex];
                            $posItem['tax' . $taxIndex . '_on_item_discount'] = $posItem['discount_on_tax' . $taxIndex] * $taxTypes[$taxIndex]['txsc_rate'];
                        }

                        for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                            if (isset($taxTypes[$taxIndex]) && substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1")
                                $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] = $posItem['citm_sc' . $scIndex] * $taxTypes[$taxIndex]['txsc_rate'];

                            $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] = Math::doRounding($posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex], $businessDay['bday_sc_round'], $businessDay['bday_sc_decimal']);
                        }
                    }

                    $posItem['tax' . $taxIndex . '_on_citm_round_total'] = Math::doRounding($posItem['tax' . $taxIndex . '_on_citm_round_total'], $businessDay['bday_tax_round'], $businessDay['bday_tax_decimal']);
                    $posItem['tax' . $taxIndex . '_on_item_discount'] = Math::doRounding($posItem['tax' . $taxIndex . '_on_item_discount'], $businessDay['bday_tax_round'], $businessDay['bday_tax_decimal']);
                }
            }

            //using the item's service charge(1-5) round total compare with chks_sc(1-5) to calculate the item service charge round amount
            //if having round amount, the amount will be added to the first non-zero item's service charge round total
            for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                $sumOfSCTotalOnItem = 0;
                for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                    if (isset($checkItems[$itemIndex]['PosCheckItem']))
                        $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                    else
                        $posItem = &$checkItems[$itemIndex];

                    $sumOfSCTotalOnItem += $posItem['citm_sc' . $scIndex . '_round'];
                }
                if ($posCheck['chks_sc' . $scIndex] != $sumOfSCTotalOnItem) {
                    $difference = $posCheck['chks_sc' . $scIndex] - $sumOfSCTotalOnItem;
                    for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                        if (isset($checkItems[$itemIndex]['PosCheckItem']))
                            $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                        else
                            $posItem = &$checkItems[$itemIndex];

                        if ($posItem['citm_sc' . $scIndex . '_round'] > 0) {
                            $posItem['citm_sc' . $scIndex . '_round'] += $difference;
                            break;
                        }
                    }
                }
            }

            //initialize the variable
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $itemTaxAppliedType[$taxIndex] = "";
                $item_total_with_no_tax[$taxIndex] = 0;
                $item_total_with_no_break_tax[$taxIndex] = 0;
                $item_total_with_tax[$taxIndex] = 0;
                $tax_on_check_item[$taxIndex] = 0;
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $sc_total_with_no_tax[$scIndex][$taxIndex] = 0;
                    $sc_total_with_no_break_tax[$scIndex][$taxIndex] = 0;
                    $sc_total_with_tax[$scIndex][$taxIndex] = 0;
                    $tax_on_check_sc[$scIndex][$taxIndex] = 0;
                }
            }
            //calculate the tax base for tax1-25 on check item and tax base for tax1-25 on check service charge1-5
            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    if (!isset($taxTypes[$taxIndex]))
                        continue;

                    if ($itemTaxAppliedType[$taxIndex] == '' && $posItem['citm_charge_tax' . $taxIndex] != '')
                        $itemTaxAppliedType[$taxIndex] = $posItem['citm_charge_tax' . $taxIndex];

                    if ($posItem['citm_charge_tax' . $taxIndex] == "")
                        $item_total_with_no_tax[$taxIndex] += $posItem['citm_total'];
                    else if ($posItem['citm_charge_tax' . $taxIndex] == "n")
                        $item_total_with_no_break_tax[$taxIndex] += $posItem['citm_total'];
                    else
                        $item_total_with_tax[$taxIndex] += $posItem['citm_total'];

                    for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                        if (substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1") {
                            if ($posItem['citm_charge_tax' . $taxIndex] == "")
                                $sc_total_with_no_tax[$scIndex][$taxIndex] += $posItem['citm_sc' . $scIndex];
                            else if ($posItem['citm_charge_tax' . $taxIndex] == "n")
                                $sc_total_with_no_break_tax[$scIndex][$taxIndex] += ($posItem['citm_sc' . $scIndex] - $posItem['citm_incl_tax_ref1'] - $posItem['citm_incl_tax_ref2'] - $posItem['citm_incl_tax_ref3'] - $posItem['citm_incl_tax_ref4']);
                            else
                                $sc_total_with_tax[$scIndex][$taxIndex] += $posItem['citm_sc' . $scIndex];
                        }
                    }
                }
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['itemTotalWithNoTax'][$taxIndex] = $item_total_with_no_tax[$taxIndex];
                $posCheck['itemTotalWithNoBreakTax'][$taxIndex] = $item_total_with_no_break_tax[$taxIndex];
                $posCheck['itemTotalWithTax'][$taxIndex] = $item_total_with_tax[$taxIndex];
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $posCheck['scTotalWithNoTax'][$scIndex][$taxIndex] = $sc_total_with_no_tax[$scIndex][$taxIndex];
                    $posCheck['scTotalWithNoBreakTax'][$scIndex][$taxIndex] = $sc_total_with_no_break_tax[$scIndex][$taxIndex];
                    $posCheck['scTotalWithTax'][$scIndex][$taxIndex] = $sc_total_with_tax[$scIndex][$taxIndex];
                }
            }

            //using the above tax base compare with chks_tax1-25 to calculate the tax 1-25 round amount
            //if having round amount, the amount will be added to the first non-zero round total
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                if (!isset($taxTypes[$taxIndex]))
                    continue;

                $tempTax1 = 0.0;
                if ($itemTaxAppliedType[$taxIndex] == "n") {
                    $tempTax1 = $posCheck['chks_incl_tax_ref1'];
                    $tax_on_check_item[$taxIndex] = $item_total_with_no_break_tax[$taxIndex] * $taxTypes[$taxIndex]['txsc_rate'];
                } else {
                    $tempTax1 = $posCheck['chks_tax1'];
                    $tax_on_check_item[$taxIndex] = $item_total_with_tax[$taxIndex] * $taxTypes[$taxIndex]['txsc_rate'];
                }

                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    if (substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1")
                        $tax_on_check_sc[$scIndex][$taxIndex] = $sc_total_with_tax[$scIndex][$taxIndex] * $taxTypes[$taxIndex]['txsc_rate'];
                }

                $tax_on_check_item[$taxIndex] = Math::doRounding($tax_on_check_item[$taxIndex], $businessDay['bday_tax_round'], $businessDay['bday_tax_decimal']);
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $tax_on_check_sc[$scIndex][$taxIndex] = Math::doRounding($tax_on_check_sc[$scIndex][$taxIndex], $businessDay['bday_tax_round'], $businessDay['bday_tax_decimal']);

                $sumOfTaxOnCheck = $tax_on_check_item[$taxIndex] + $tax_on_check_sc[1][$taxIndex] + $tax_on_check_sc[2][$taxIndex] + $tax_on_check_sc[3][$taxIndex] + $tax_on_check_sc[4][$taxIndex] + $tax_on_check_sc[5][$taxIndex];
                if ($tempTax1 != $sumOfTaxOnCheck) {
                    $difference = $tempTax1 - $sumOfTaxOnCheck;
                    if ($tax_on_check_item[$taxIndex] > 0)
                        $tax_on_check_item[$taxIndex] += $difference;
                    else if ($tax_on_check_sc[1][$taxIndex] > 0)
                        $tax_on_check_sc[1][$taxIndex] += $difference;
                    else if ($tax_on_check_sc[2][$taxIndex] > 0)
                        $tax_on_check_sc[2][$taxIndex] += $difference;
                    else if ($tax_on_check_sc[3][$taxIndex] > 0)
                        $tax_on_check_sc[3][$taxIndex] += $difference;
                    else if ($tax_on_check_sc[4][$taxIndex] > 0)
                        $tax_on_check_sc[4][$taxIndex] += $difference;
                    else if ($tax_on_check_sc[5][$taxIndex] > 0)
                        $tax_on_check_sc[5][$taxIndex] += $difference;
                }
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['taxOnCheckItem'][$taxIndex] = $tax_on_check_item[$taxIndex];
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $posCheck['taxOnCheckSc'][$scIndex][$taxIndex] = $tax_on_check_sc[$scIndex][$taxIndex];
            }

            //using the tax1-25 on check service charge1-5 compare with item's tax1-25 on item's service charge1-5 round total to calculate the round amount
            //using the tax1-25 on check item 1-5 compare with item's tax1-25 on item round total to calculate the round amount
            //if having round amount, the amount will be added to the first non-zero round total
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                if (!isset($taxTypes[$taxIndex]))
                    continue;

                $sumOfTaxOnItem = 0;
                for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                    if (isset($checkItems[$itemIndex]['PosCheckItem']))
                        $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                    else
                        $posItem = &$checkItems[$itemIndex];

                    $sumOfTaxOnItem += $posItem['tax' . $taxIndex . '_on_citm_round_total'];
                }
                if ($tax_on_check_item[$taxIndex] != $sumOfTaxOnItem) {
                    $difference = $tax_on_check_item[$taxIndex] - $sumOfTaxOnItem;
                    for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                        if (isset($checkItems[$itemIndex]['PosCheckItem']))
                            $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                        else
                            $posItem = &$checkItems[$itemIndex];

                        if ($posItem['tax' . $taxIndex . '_on_citm_round_total'] > 0) {
                            $posItem['tax' . $taxIndex . '_on_citm_round_total'] += $difference;
                            break;
                        }
                    }
                }

                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    if (substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1") {
                        $sumOfTaxOnItemSC = 0;
                        for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                            if (isset($checkItems[$itemIndex]['PosCheckItem']))
                                $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                            else
                                $posItem = &$checkItems[$itemIndex];

                            $sumOfTaxOnItemSC += $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex];
                        }
                        if ($tax_on_check_sc[$scIndex][$taxIndex] != $sumOfTaxOnItemSC) {
                            $difference = $tax_on_check_sc[$scIndex][$taxIndex] - $sumOfTaxOnItemSC;
                            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                                else
                                    $posItem = &$checkItems[$itemIndex];

                                if ($posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] > 0) {
                                    $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] += $difference;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            //calculate the item gross total on with tax and no tax
            //calculate the item SC1-5 total with tax and not tax
            //calculate the discount total on tax and sc
            $posCheck['itemGrossTotalWithNoTax'] = 0;
            $posCheck['discountTotalOnItemTotalWithNoTax'] = 0;
            for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                $posCheck['itemSCTotalWithNoTax'][$scIndex] = 0;
                $posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex] = 0;
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['itemGrossTotalWithTax'][$taxIndex] = 0;
                $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] = 0;
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] = 0;
                    $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex] = 0;
                }
            }
            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                $oneOfTaxCharged = false;
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    if (!isset($taxTypes[$taxIndex]))
                        continue;

                    if ($posItem['citm_charge_tax' . $taxIndex] == "n" && $taxTypes[$taxIndex]['txsc_rate'] > 0) {
                        $posCheck['itemGrossTotalWithTax'][$taxIndex] += $posItem['citm_total'];
                        $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] += ($posItem['discount_on_item_total'] + $posItem['check_discount_on_item_total']);
                        $oneOfTaxCharged = true;

                        for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                            if (substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1") {
                                $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] += $posItem['citm_sc' . $scIndex];
                                $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex] += ($posItem['discount_on_sc' . $scIndex] + $posItem['check_discount_on_sc' . $scIndex]);
                            }
                        }

                        break;
                    }
                }
                if (!$oneOfTaxCharged) {
                    $posCheck['itemGrossTotalWithNoTax'] += $posItem['citm_total'];
                    $posCheck['discountTotalOnItemTotalWithNoTax'] += ($posItem['discount_on_item_total'] + $posItem['check_discount_on_item_total']);
                    for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                        $posCheck['itemSCTotalWithNoTax'][$scIndex] += $posItem['citm_sc' . $scIndex];
                        $posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex] += ($posItem['discount_on_sc' . $scIndex] + $posItem['check_discount_on_sc' . $scIndex]);
                    }
                }
            }
            $posCheck['itemGrossTotalWithNoTax'] = Math::doRounding($posCheck['itemGrossTotalWithNoTax'], $businessDay['bday_item_round'], $businessDay['bday_item_decimal']);
            $posCheck['discountTotalOnItemTotalWithNoTax'] = Math::doRounding($posCheck['discountTotalOnItemTotalWithNoTax'], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);
            for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                $posCheck['itemSCTotalWithNoTax'][$scIndex] = Math::doRounding($posCheck['itemSCTotalWithNoTax'][$scIndex], $businessDay['bday_sc_round'], $businessDay['bday_sc_decimal']);
                $posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex] = Math::doRounding($posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['itemGrossTotalWithTax'][$taxIndex] = Math::doRounding($posCheck['itemGrossTotalWithTax'][$taxIndex], $businessDay['bday_item_round'], $businessDay['bday_item_decimal']);
                $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] = Math::doRounding($posCheck['discountTotalOnItemTotalWithTax'][$taxIndex], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] = Math::doRounding($posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex], $businessDay['bday_sc_round'], $businessDay['bday_sc_decimal']);
                    $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex] = Math::doRounding($posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);
                }
            }

            $sumOfItemGrossTotal = 0;
            $sumOfItemGrossTotal += $posCheck['itemGrossTotalWithNoTax'];
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                $sumOfItemGrossTotal += $posCheck['itemGrossTotalWithTax'][$taxIndex];
            if ($sumOfItemGrossTotal != $posCheck['chks_item_total']) {
                $difference = $posCheck['chks_item_total'] - $sumOfItemGrossTotal;
                if ($posCheck['itemGrossTotalWithNoTax'] > 0)
                    $posCheck['itemGrossTotalWithNoTax'] += $difference;
                else {
                    for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                        if ($posCheck['itemGrossTotalWithTax'][$taxIndex] > 0) {
                            $posCheck['itemGrossTotalWithTax'][$taxIndex] += $difference;
                            break;
                        }
                    }
                }
            }

            for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                $sumOfItemScTotal = 0;
                $sumOfItemScTotal += $posCheck['itemSCTotalWithNoTax'][$scIndex];
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                    $sumOfItemScTotal += $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex];
                if ($sumOfItemScTotal != $posCheck['chks_sc' . $scIndex]) {
                    $difference = $posCheck['chks_sc' . $scIndex] - $sumOfItemScTotal;
                    if ($posCheck['itemSCTotalWithNoTax'][$scIndex] > 0)
                        $posCheck['itemSCTotalWithNoTax'][$scIndex] += $difference;
                    else {
                        for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                            if ($posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] > 0) {
                                $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] += $difference;
                                break;
                            }
                        }
                    }
                }
            }

            //calculate the item discount and check discount on tax 1-25
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['discountOnTax'][$taxIndex] = 0;
            }
            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    $posCheck['discountOnTax'][$taxIndex] += ($posItem['discount_on_tax' . $taxIndex] + $posItem['check_discount_on_tax' . $taxIndex]);
                }
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                $posCheck['discountOnTax'][$taxIndex] = Math::doRounding($posCheck['discountOnTax'][$taxIndex], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);

            $sumOfDiscount = 0;
            $sumOfDiscount += $posCheck['discountTotalOnItemTotalWithNoTax'];
            for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                $sumOfDiscount += $posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex];
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $sumOfDiscount += $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex];
                $sumOfDiscount += $posCheck['discountOnTax'][$taxIndex];
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $sumOfDiscount += $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex];
            }
            $discountTotalForWholeCheck = $posCheck['chks_pre_disc'] + $posCheck['chks_mid_disc'] + $posCheck['chks_post_disc'];
            if ($sumOfDiscount != $discountTotalForWholeCheck) {
                $difference = $discountTotalForWholeCheck - $sumOfDiscount;
                if ($posCheck['discountTotalOnItemTotalWithNoTax'] > 0)
                    $posCheck['discountTotalOnItemTotalWithNoTax'] += $difference;
                else {
                    for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                        if ($posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] != 0) {
                            $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] += $difference;
                            break;
                        }
                    }
                }
            }

            //calculate the check item net total with tax
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['checkItemNetTotalWithTax'][$taxIndex] = 0;
                if ($taxIndex <= 4)
                    $posCheck['checkItemNetTotalWithTax'][$taxIndex] = ($posCheck['itemGrossTotalWithTax'][$taxIndex] - $posCheck['chks_incl_tax_ref' . $taxIndex] + $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex]);
                else
                    $posCheck['checkItemNetTotalWithTax'][$taxIndex] = ($posCheck['itemGrossTotalWithTax'][$taxIndex] + $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex]);
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $posCheck['checkItemNetTotalWithTax'][$taxIndex] += ($posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] + $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex]);
            }

            //do summary about the tax1-25 on service charge 1-5
            for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                    $taxTotalOnSC[$scIndex][$taxIndex] = 0;

            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                        $taxTotalOnSC[$scIndex][$taxIndex] += $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex];
            }
            for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                    $posCheck['taxTotalOnSC'][$scIndex][$taxIndex] = $taxTotalOnSC[$scIndex][$taxIndex];

        } else {
            //calculate service charge 1-5 round total
            //calculate tax 1-25 on item round total and tax x on item service charge 1-5
            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                //calculate the item discount base for item total, sc, tax
                $posItem['itemDiscountRoundTotal'] = 0;
                $posItem['itemDiscountNotRoundTotal'] = 0;
                $posItem['itemDiscountType'] = 'b';
                if (isset($posItem['PosCheckDiscount'])) {
                    foreach ($posItem['PosCheckDiscount'] as $itemDisc) {
                        $posItem['itemDiscountRoundTotal'] += $itemDisc['cdis_round_total'];
                        $posItem['itemDiscountNotRoundTotal'] += $itemDisc['cdis_total'];
                        $posItem['itemDiscountType'] = $itemDisc['cdis_type'];
                    }
                }

                $sumOfDiscountAmount = 0;
                if ($posItem['citm_original_price'] == 0)
                    $posItem['discount_on_item_total'] = 0;
                else
                    $posItem['discount_on_item_total'] = ($posItem['citm_total'] / $posItem['citm_original_price']) * $posItem['itemDiscountNotRoundTotal'];
                $sumOfDiscountAmount += $posItem['discount_on_item_total'];
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    if ($posItem['itemDiscountType'] == 'm' || $posItem['itemDiscountType'] == 'a') {
                        if ($posItem['citm_original_price'] == 0)
                            $posItem['discount_on_sc' . $scIndex] = 0;
                        else
                            $posItem['discount_on_sc' . $scIndex] = ($posItem['citm_sc' . $scIndex] / $posItem['citm_original_price']) * $posItem['itemDiscountNotRoundTotal'];
                        $sumOfDiscountAmount += $posItem['discount_on_sc' . $scIndex];
                    } else
                        $posItem['discount_on_sc' . $scIndex] = 0;
                }
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    if ($posItem['itemDiscountType'] == 'a') {
                        if ($posItem['citm_original_price'] == 0)
                            $posItem['discount_on_tax' . $taxIndex] = 0;
                        else
                            $posItem['discount_on_tax' . $taxIndex] = ($posItem['citm_tax' . $taxIndex] / $posItem['citm_original_price']) * $posItem['itemDiscountNotRoundTotal'];
                        $sumOfDiscountAmount += $posItem['discount_on_tax' . $taxIndex];
                    } else
                        $posItem['discount_on_tax' . $taxIndex] = 0;
                }
                if ($sumOfDiscountAmount != $posItem['itemDiscountNotRoundTotal'])
                    $posItem['discount_on_item_total'] += $posItem['itemDiscountNotRoundTotal'] - $sumOfDiscountAmount;

                //calculate the check discount base for item total, sc, tax
                $posItem['checkDiscountRoundTotal'] = 0;
                $posItem['checkDiscountNotRoundTotal'] = 0;
                $posItem['check_discount_on_item_total'] = 0;
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $posItem['check_discount_on_sc' . $scIndex] = 0;
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                    $posItem['check_discount_on_tax' . $taxIndex] = 0;
                if (isset($posItem['PosCheckDiscountItem'])) {
                    $checkDiscounts = array();
                    $oldCheck = false;
                    if (isset($check['checkDiscounts']))
                        $checkDiscounts = $check['checkDiscounts'];
                    else {
                        $checkDiscounts = $check['PosCheckDiscount'];
                        $oldCheck = true;
                    }

                    foreach ($posItem['PosCheckDiscountItem'] as $checkDiscItem) {
                        $posItem['checkDiscountRoundTotal'] += $checkDiscItem['cdit_round_total'];
                        $posItem['checkDiscountNotRoundTotal'] += $checkDiscItem['cdit_total'];
                        $checkDiscountType = 'b';
                        foreach ($checkDiscounts as $checkDisc) {
                            if ($oldCheck) {
                                if ($checkDisc['cdis_id'] == $checkDiscItem['cdit_cdis_id']) {
                                    $checkDiscountType = $checkDisc['cdis_type'];
                                    break;
                                }
                            } else {
                                if ($checkDisc['cdis_seq'] == $checkDiscItem['cdis_seq']) {
                                    $checkDiscountType = $checkDisc['cdis_type'];
                                    break;
                                }
                            }
                        }

                        $sumOfCheckDiscountAmount = 0;
                        if ($posItem['citm_original_price'] == 0)
                            $checkDiscountOnItem = 0;
                        else
                            $checkDiscountOnItem = ($posItem['citm_total'] / $posItem['citm_original_price']) * $checkDiscItem['cdit_total'];
                        $posItem['check_discount_on_item_total'] += $checkDiscountOnItem;
                        $sumOfCheckDiscountAmount += $checkDiscountOnItem;
                        for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                            $checkDiscountOnSC = 0;
                            if ($checkDiscountType == 'm' || $checkDiscountType == 'a') {
                                if ($posItem['citm_original_price'] == 0)
                                    $checkDiscountOnSC = 0;
                                else
                                    $checkDiscountOnSC = ($posItem['citm_sc' . $scIndex] / $posItem['citm_original_price']) * $checkDiscItem['cdit_total'];
                                $posItem['check_discount_on_sc' . $scIndex] += $checkDiscountOnSC;
                                $sumOfCheckDiscountAmount += $checkDiscountOnSC;
                            }
                        }
                        for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                            $checkDiscountTax = 0;
                            if ($checkDiscountType == 'a') {
                                if ($posItem['citm_original_price'] == 0)
                                    $checkDiscountTax = 0;
                                else
                                    $checkDiscountTax = ($posItem['citm_tax' . $taxIndex] / $posItem['citm_original_price']) * $checkDiscItem['cdit_total'];
                                $posItem['check_discount_on_tax' . $taxIndex] += $checkDiscountTax;
                                $sumOfCheckDiscountAmount += $checkDiscountTax;
                            }
                        }
                        if ($sumOfCheckDiscountAmount != $checkDiscItem['cdit_round_total'])
                            $posItem['check_discount_on_item_total'] += $checkDiscItem['cdit_round_total'] - $sumOfCheckDiscountAmount;
                    }
                }

                //calculate the sc charge with rounding
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $posItem['citm_sc' . $scIndex . '_round'] = 0;
                    $posItem['citm_sc' . $scIndex . '_round'] = Math::doRounding($posItem['citm_sc' . $scIndex], $businessDay['bday_sc_round'], $businessDay['bday_sc_decimal']);
                }

                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    $posItem['tax' . $taxIndex . '_on_citm_round_total'] = 0;
                    $posItem['tax' . $taxIndex . '_on_item_discount'] = 0;
                    for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                        $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] = 0;

                    if (isset($taxTypes[$taxIndex]) && ($posItem['citm_charge_tax' . $taxIndex] == "c" || $posItem['citm_charge_tax' . $taxIndex] == "i")) {
                        $posItem['tax' . $taxIndex . '_on_citm_round_total'] = ($posItem['citm_total'] + $posItem['itemDiscountNotRoundTotal'] + $posItem['checkDiscountNotRoundTotal']) * $taxTypes[$taxIndex]['txsc_rate'];
                        $posItem['tax' . $taxIndex . '_on_item_discount'] = $posItem['discount_on_tax' . $taxIndex] * $taxTypes[$taxIndex]['txsc_rate'];

                        for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                            if (isset($taxTypes[$taxIndex]) && substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1")
                                $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] = $posItem['citm_sc' . $scIndex] * $taxTypes[$taxIndex]['txsc_rate'];

                            $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] = Math::doRounding($posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex], $businessDay['bday_sc_round'], $businessDay['bday_sc_decimal']);
                        }
                    }

                    $posItem['tax' . $taxIndex . '_on_citm_round_total'] = Math::doRounding($posItem['tax' . $taxIndex . '_on_citm_round_total'], $businessDay['bday_tax_round'], $businessDay['bday_tax_decimal']);
                    $posItem['tax' . $taxIndex . '_on_item_discount'] = Math::doRounding($posItem['tax' . $taxIndex . '_on_item_discount'], $businessDay['bday_tax_round'], $businessDay['bday_tax_decimal']);
                }
            }

            //using the item's service charge(1-5) round total compare with chks_sc(1-5) to calculate the item service charge round amount
            //if having round amount, the amount will be added to the first non-zero item's service charge round total
            for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                $sumOfSCTotalOnItem = 0;
                for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                    if (isset($checkItems[$itemIndex]['PosCheckItem']))
                        $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                    else
                        $posItem = &$checkItems[$itemIndex];

                    $sumOfSCTotalOnItem += $posItem['citm_sc' . $scIndex . '_round'];
                }
                if ($posCheck['chks_sc' . $scIndex] != $sumOfSCTotalOnItem) {
                    $difference = $posCheck['chks_sc' . $scIndex] - $sumOfSCTotalOnItem;
                    for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                        if (isset($checkItems[$itemIndex]['PosCheckItem']))
                            $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                        else
                            $posItem = &$checkItems[$itemIndex];

                        if ($posItem['citm_sc' . $scIndex . '_round'] > 0) {
                            $posItem['citm_sc' . $scIndex . '_round'] += $difference;
                            break;
                        }
                    }
                }
            }

            //initialize the variable
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $item_total_with_no_tax[$taxIndex] = 0;
                $item_total_with_no_break_tax[$taxIndex] = 0;
                $item_total_with_tax[$taxIndex] = 0;
                $tax_on_check_item[$taxIndex] = 0;
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $sc_total_with_no_tax[$scIndex][$taxIndex] = 0;
                    $sc_total_with_no_break_tax[$scIndex][$taxIndex] = 0;
                    $sc_total_with_tax[$scIndex][$taxIndex] = 0;
                    $tax_on_check_sc[$scIndex][$taxIndex] = 0;
                }
            }
            //calculate the tax base for tax1-25 on check item and tax base for tax1-25 on check service charge1-5
            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    if (!isset($taxTypes[$taxIndex]))
                        continue;

                    if ($posItem['citm_charge_tax' . $taxIndex] == "")
                        $item_total_with_no_tax[$taxIndex] += $posItem['citm_total'];
                    else if ($posItem['citm_charge_tax' . $taxIndex] == "n")
                        $item_total_with_no_break_tax[$taxIndex] += $posItem['citm_total'];
                    else
                        $item_total_with_tax[$taxIndex] += $posItem['citm_total'];

                    for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                        if (substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1") {
                            if ($posItem['citm_charge_tax' . $taxIndex] == "")
                                $sc_total_with_no_tax[$scIndex][$taxIndex] += $posItem['citm_sc' . $scIndex];
                            else if ($posItem['citm_charge_tax' . $taxIndex] == "n")
                                $sc_total_with_no_break_tax[$scIndex][$taxIndex] += $posItem['citm_sc' . $scIndex];
                            else
                                $sc_total_with_tax[$scIndex][$taxIndex] += $posItem['citm_sc' . $scIndex];
                        }
                    }
                }
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['itemTotalWithNoTax'][$taxIndex] = $item_total_with_no_tax[$taxIndex];
                $posCheck['itemTotalWithNoBreakTax'][$taxIndex] = $item_total_with_no_break_tax[$taxIndex];
                $posCheck['itemTotalWithTax'][$taxIndex] = $item_total_with_tax[$taxIndex];
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $posCheck['scTotalWithNoTax'][$scIndex][$taxIndex] = $sc_total_with_no_tax[$scIndex][$taxIndex];
                    $posCheck['scTotalWithNoBreakTax'][$scIndex][$taxIndex] = $sc_total_with_no_break_tax[$scIndex][$taxIndex];
                    $posCheck['scTotalWithTax'][$scIndex][$taxIndex] = $sc_total_with_tax[$scIndex][$taxIndex];
                }
            }

            //using the above tax base compare with chks_tax1-25 to calculate the tax 1-25 round amount
            //if having round amount, the amount will be added to the first non-zero round total
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                if (!isset($taxTypes[$taxIndex]))
                    continue;

                $tax_on_check_item[$taxIndex] = $item_total_with_tax[$taxIndex] * $taxTypes[$taxIndex]['txsc_rate'];
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    if (substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1")
                        $tax_on_check_sc[$scIndex][$taxIndex] = $sc_total_with_tax[$scIndex][$taxIndex] * $taxTypes[$taxIndex]['txsc_rate'];
                }

                $tax_on_check_item[$taxIndex] = Math::doRounding($tax_on_check_item[$taxIndex], $businessDay['bday_tax_round'], $businessDay['bday_tax_decimal']);
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $tax_on_check_sc[$scIndex][$taxIndex] = Math::doRounding($tax_on_check_sc[$scIndex][$taxIndex], $businessDay['bday_tax_round'], $businessDay['bday_tax_decimal']);

                $sumOfTaxOnCheck = $tax_on_check_item[$taxIndex] + $tax_on_check_sc[1][$taxIndex] + $tax_on_check_sc[2][$taxIndex] + $tax_on_check_sc[3][$taxIndex] + $tax_on_check_sc[4][$taxIndex] + $tax_on_check_sc[5][$taxIndex];
                if ($posCheck['chks_tax1'] != $sumOfTaxOnCheck) {
                    $difference = $posCheck['chks_tax1'] - $sumOfTaxOnCheck;
                    if ($tax_on_check_item[$taxIndex] > 0)
                        $tax_on_check_item[$taxIndex] += $difference;
                    else if ($tax_on_check_sc[1][$taxIndex] > 0)
                        $tax_on_check_sc[1][$taxIndex] += $difference;
                    else if ($tax_on_check_sc[2][$taxIndex] > 0)
                        $tax_on_check_sc[2][$taxIndex] += $difference;
                    else if ($tax_on_check_sc[3][$taxIndex] > 0)
                        $tax_on_check_sc[3][$taxIndex] += $difference;
                    else if ($tax_on_check_sc[4][$taxIndex] > 0)
                        $tax_on_check_sc[4][$taxIndex] += $difference;
                    else if ($tax_on_check_sc[5][$taxIndex] > 0)
                        $tax_on_check_sc[5][$taxIndex] += $difference;
                }
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['taxOnCheckItem'][$taxIndex] = $tax_on_check_item[$taxIndex];
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $posCheck['taxOnCheckSc'][$scIndex][$taxIndex] = $tax_on_check_sc[$scIndex][$taxIndex];
            }

            //using the tax1-25 on check service charge1-5 compare with item's tax1-25 on item's service charge1-5 round total to calculate the round amount
            //using the tax1-25 on check item 1-5 compare with item's tax1-25 on item round total to calculate the round amount
            //if having round amount, the amount will be added to the first non-zero round total
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                if (!isset($taxTypes[$taxIndex]))
                    continue;

                $sumOfTaxOnItem = 0;
                for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                    if (isset($checkItems[$itemIndex]['PosCheckItem']))
                        $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                    else
                        $posItem = &$checkItems[$itemIndex];

                    $sumOfTaxOnItem += $posItem['tax' . $taxIndex . '_on_citm_round_total'];
                }
                if ($tax_on_check_item[$taxIndex] != $sumOfTaxOnItem) {
                    $difference = $tax_on_check_item[$taxIndex] - $sumOfTaxOnItem;
                    for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                        if (isset($checkItems[$itemIndex]['PosCheckItem']))
                            $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                        else
                            $posItem = &$checkItems[$itemIndex];

                        if ($posItem['tax' . $taxIndex . '_on_citm_round_total'] > 0) {
                            $posItem['tax' . $taxIndex . '_on_citm_round_total'] += $difference;
                            break;
                        }
                    }
                }

                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    if (substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1") {
                        $sumOfTaxOnItemSC = 0;
                        for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                            if (isset($checkItems[$itemIndex]['PosCheckItem']))
                                $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                            else
                                $posItem = &$checkItems[$itemIndex];

                            $sumOfTaxOnItemSC += $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex];
                        }
                        if ($tax_on_check_sc[$scIndex][$taxIndex] != $sumOfTaxOnItemSC) {
                            $difference = $tax_on_check_sc[$scIndex][$taxIndex] - $sumOfTaxOnItemSC;
                            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                                else
                                    $posItem = &$checkItems[$itemIndex];

                                if ($posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] > 0) {
                                    $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex] += $difference;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            //calculate the item gross total on with tax and no tax
            //calculate the item SC1-5 total with tax and not tax
            //calculate the discount total on tax and sc
            $posCheck['itemGrossTotalWithNoTax'] = 0;
            $posCheck['discountTotalOnItemTotalWithNoTax'] = 0;
            for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                $posCheck['itemSCTotalWithNoTax'][$scIndex] = 0;
                $posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex] = 0;
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['itemGrossTotalWithTax'][$taxIndex] = 0;
                $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] = 0;
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] = 0;
                    $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex] = 0;
                }
            }
            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                $oneOfTaxCharged = false;
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    if (!isset($taxTypes[$taxIndex]))
                        continue;

                    if (($posItem['citm_charge_tax' . $taxIndex] == "c" || $posItem['citm_charge_tax' . $taxIndex] == "i") && $taxTypes[$taxIndex]['txsc_rate'] > 0) {
                        $posCheck['itemGrossTotalWithTax'][$taxIndex] += $posItem['citm_total'];
                        $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] += ($posItem['discount_on_item_total'] + $posItem['check_discount_on_item_total']);
                        $oneOfTaxCharged = true;

                        for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                            if (substr($taxTypes[$taxIndex]['txsc_include_tax_sc_mask'], ($scIndex - 1), 1) == "1") {
                                $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] += $posItem['citm_sc' . $scIndex];
                                $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex] += ($posItem['discount_on_sc' . $scIndex] + $posItem['check_discount_on_sc' . $scIndex]);
                            }
                        }

                        break;
                    }
                }
                if (!$oneOfTaxCharged) {
                    $posCheck['itemGrossTotalWithNoTax'] += $posItem['citm_total'];
                    $posCheck['discountTotalOnItemTotalWithNoTax'] += ($posItem['discount_on_item_total'] + $posItem['check_discount_on_item_total']);
                    for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                        $posCheck['itemSCTotalWithNoTax'][$scIndex] += $posItem['citm_sc' . $scIndex];
                        $posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex] += ($posItem['discount_on_sc' . $scIndex] + $posItem['check_discount_on_sc' . $scIndex]);
                    }
                }
            }
            $posCheck['itemGrossTotalWithNoTax'] = Math::doRounding($posCheck['itemGrossTotalWithNoTax'], $businessDay['bday_item_round'], $businessDay['bday_item_decimal']);
            $posCheck['discountTotalOnItemTotalWithNoTax'] = Math::doRounding($posCheck['discountTotalOnItemTotalWithNoTax'], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);
            for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                $posCheck['itemSCTotalWithNoTax'][$scIndex] = Math::doRounding($posCheck['itemSCTotalWithNoTax'][$scIndex], $businessDay['bday_sc_round'], $businessDay['bday_sc_decimal']);
                $posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex] = Math::doRounding($posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['itemGrossTotalWithTax'][$taxIndex] = Math::doRounding($posCheck['itemGrossTotalWithTax'][$taxIndex], $businessDay['bday_item_round'], $businessDay['bday_item_decimal']);
                $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] = Math::doRounding($posCheck['discountTotalOnItemTotalWithTax'][$taxIndex], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);
                for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                    $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] = Math::doRounding($posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex], $businessDay['bday_sc_round'], $businessDay['bday_sc_decimal']);
                    $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex] = Math::doRounding($posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);
                }
            }

            $sumOfItemGrossTotal = 0;
            $sumOfItemGrossTotal += $posCheck['itemGrossTotalWithNoTax'];
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                $sumOfItemGrossTotal += $posCheck['itemGrossTotalWithTax'][$taxIndex];
            if ($sumOfItemGrossTotal != $posCheck['chks_item_total']) {
                $difference = $posCheck['chks_item_total'] - $sumOfItemGrossTotal;
                if ($posCheck['itemGrossTotalWithNoTax'] > 0)
                    $posCheck['itemGrossTotalWithNoTax'] += $difference;
                else {
                    for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                        if ($posCheck['itemGrossTotalWithTax'][$taxIndex] > 0) {
                            $posCheck['itemGrossTotalWithTax'][$taxIndex] += $difference;
                            break;
                        }
                    }
                }
            }

            for ($scIndex = 1; $scIndex <= 5; $scIndex++) {
                $sumOfItemScTotal = 0;
                $sumOfItemScTotal += $posCheck['itemSCTotalWithNoTax'][$scIndex];
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                    $sumOfItemScTotal += $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex];
                if ($sumOfItemScTotal != $posCheck['chks_sc' . $scIndex]) {
                    $difference = $posCheck['chks_sc' . $scIndex] - $sumOfItemScTotal;
                    if ($posCheck['itemSCTotalWithNoTax'][$scIndex] > 0)
                        $posCheck['itemSCTotalWithNoTax'][$scIndex] += $difference;
                    else {
                        for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                            if ($posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] > 0) {
                                $posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] += $difference;
                                break;
                            }
                        }
                    }
                }
            }

            //calculate the item discount and check discount on tax 1-25
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['discountOnTax'][$taxIndex] = 0;
            }
            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                    $posCheck['discountOnTax'][$taxIndex] += ($posItem['discount_on_tax' . $taxIndex] + $posItem['check_discount_on_tax' . $taxIndex]);
                }
            }
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                $posCheck['discountOnTax'][$taxIndex] = Math::doRounding($posCheck['discountOnTax'][$taxIndex], $businessDay['bday_disc_round'], $businessDay['bday_disc_decimal']);

            $sumOfDiscount = 0;
            $sumOfDiscount += $posCheck['discountTotalOnItemTotalWithNoTax'];
            for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                $sumOfDiscount += $posCheck['discountTotalOnSCTotalWithNoTax'][$scIndex];
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $sumOfDiscount += $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex];
                $sumOfDiscount += $posCheck['discountOnTax'][$taxIndex];
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $sumOfDiscount += $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex];
            }
            $discountTotalForWholeCheck = $posCheck['chks_pre_disc'] + $posCheck['chks_mid_disc'] + $posCheck['chks_post_disc'];
            if ($sumOfDiscount != $discountTotalForWholeCheck) {
                $difference = $discountTotalForWholeCheck - $sumOfDiscount;
                if ($posCheck['discountTotalOnItemTotalWithNoTax'] > 0)
                    $posCheck['discountTotalOnItemTotalWithNoTax'] += $difference;
                else {
                    for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                        if ($posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] != 0) {
                            $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex] += $difference;
                            break;
                        }
                    }
                }
            }

            //calculate the check item net total with tax
            for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++) {
                $posCheck['checkItemNetTotalWithTax'][$taxIndex] = 0;
                $posCheck['checkItemNetTotalWithTax'][$taxIndex] = ($posCheck['itemGrossTotalWithTax'][$taxIndex] + $posCheck['discountTotalOnItemTotalWithTax'][$taxIndex]);
                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    $posCheck['checkItemNetTotalWithTax'][$taxIndex] += ($posCheck['itemSCTotalWithTax'][$scIndex][$taxIndex] + $posCheck['discountTotalOnSCTotalWithTax'][$scIndex][$taxIndex]);
            }

            //do summary about the tax1-25 on service charge 1-5
            for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                    $taxTotalOnSC[$scIndex][$taxIndex] = 0;

            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                    for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                        $taxTotalOnSC[$scIndex][$taxIndex] += $posItem['tax' . $taxIndex . '_on_citm_sc' . $scIndex];
            }
            for ($scIndex = 1; $scIndex <= 5; $scIndex++)
                for ($taxIndex = 1; $taxIndex <= 25; $taxIndex++)
                    $posCheck['taxTotalOnSC'][$scIndex][$taxIndex] = $taxTotalOnSC[$scIndex][$taxIndex];
        }
    }

    //generate the printing variable value for TaiWan GUI
    private function __generateTaiWanGuiPrintVariable(&$vars, $stationId, $stationParams, $shopTimezone, $prtFmtDefaultLang, $taxTypes, $businessDay, $check, $checkItems, $taiwanGuiTrans = null)
    {
        $stationParamsJson = json_decode($stationParams, true);
        if (isset($stationParamsJson['tgui']['seller']))
            $sellerNumber = str_pad($stationParamsJson['tgui']['seller'], 8, "0", STR_PAD_LEFT);
        else {
            App::import('Model', 'Pos.PosConfig');
            $posConfigModel = new PosConfig();
            $posConfig = null;
            $posConfigList = $posConfigModel->findAllActiveBySectionVariable($stationId, 0, 0, "taiwan_gui", "seller_number");
            if (!empty($posConfigList)) {
                foreach ($posConfigList as $posConfigObject) {
                    if ($posConfigObject['PosConfig']['scfg_record_id'] == $stationId) {
                        $posConfig = $posConfigObject;
                        break;
                    }
                }
            }

            if ($posConfig != null)
                $sellerNumber = str_pad($posConfig['PosConfig']['scfg_value'], 8, "0", STR_PAD_LEFT);
            else
                $sellerNumber = "";
        }

        $taxIndex = 0;
        $taxRateForGUI = 0;
        $buyerReferenceNumber = "";
        $PosTaiwanGuiTranModel = $this->controller->PosTaiwanGuiTran;
        $currentDateTimeString = date('Y-m-d H:i:s', strtotime($businessDay['bday_date']));
        $currentMonth = date('n', strtotime($currentDateTimeString));

        $vars['GUIYear'] = date('Y', strtotime($currentDateTimeString)) - 1911;
        if (($currentMonth % 2) == 0) {
            $lastMonth = $currentMonth - 1;
            if ($lastMonth == 0)
                $lastMonth = 12;
            $vars['GUIMonthPeriod'] = str_pad($lastMonth, 2, "0", STR_PAD_LEFT) . "-" . str_pad($currentMonth, 2, "0", STR_PAD_LEFT);
        } else {
            $nextMonth = $currentMonth + 1;
            if ($nextMonth == 13)
                $nextMonth = 1;
            $vars['GUIMonthPeriod'] = str_pad($currentMonth, 2, "0", STR_PAD_LEFT) . "-" . str_pad($nextMonth, 2, "0", STR_PAD_LEFT);
        }

        if ($taiwanGuiTrans == null)
            $taiwanGuiTrans = $PosTaiwanGuiTranModel->findAllByOutletAndCheckId($check['chks_olet_id'], $check['chks_id']);
        if (!empty($taiwanGuiTrans) && count($taiwanGuiTrans) > 0) {
            $twtxNumber = str_pad($taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_num'], 8, "0", STR_PAD_LEFT);

            $vars['GUINumber'] = $taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_prefix'] . '-' . $twtxNumber;
            $vars['GUIType'] = $taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_type'];

            //calculate the random number
            $randomNumberBasicBase = array(1 => 2, 2 => 4, 3 => 6, 4 => 8, 5 => 2, 6 => 4, 7 => 6, 8 => 8);
            $randomNumberFinal = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0);
            $randomNumberSum = 0;
            for ($baseIndex = 1; $baseIndex <= 8; $baseIndex++) {
                $baseNumber = substr($sellerNumber, ($baseIndex - 1), 1);
                $billNumber = substr($twtxNumber, ($baseIndex - 1), 1);
                $randomNumberSecondBase = $baseNumber * $randomNumberBasicBase[$baseIndex];
                $randomNumberFinal[$baseIndex] = $randomNumberSecondBase * $billNumber;
                $randomNumberSum += $randomNumberFinal[$baseIndex];
            }
            $vars['GUIRandomNum'] = $randomNumberSum * 3;

            $vars['GUISellerNum'] = $sellerNumber;
            $vars['GUIBarcode'] = $vars['GUIYear'] . (date('m', strtotime($currentDateTimeString))) . $taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_prefix'] . $twtxNumber . str_pad($vars['GUIRandomNum'], 4, "0", STR_PAD_LEFT);
            $vars['GUIRefNum'] = $taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_ref_num'];
            $vars['GUICarrierId'] = $taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_carrier'];

            //generate the left QR code
            /* calculate the tax on item */
            if ($vars['GUIType'] == "e")
                $taxIndex = $stationParamsJson['tgui']['ent_tax_index'];
            else if ($vars['GUIType'] == "b" || $vars['GUIType'] == "a" || $vars['GUIType'] == "f")
                $taxIndex = $stationParamsJson['tgui']['normal_tax_index'];

            $taxOnItems = 0;
            if ($taxIndex > 0) {
                foreach ($taxTypes as $taxType) {
                    if ($taxType['PosTaxScType']['txsc_number'] == $taxIndex) {
                        $taxRateForGUI = $taxType['PosTaxScType']['txsc_rate'];
                        break;
                    }
                }

                for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                    if (isset($checkItems[$itemIndex]['PosCheckItem']))
                        $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                    else
                        $posItem = &$checkItems[$itemIndex];

                    $taxOnItems += ($posItem['citm_total'] / (1 + $taxRateForGUI)) * $taxRateForGUI;
                }

                $taxOnItems = Math::doRounding($taxOnItems, $businessDay['bday_tax_round'], $businessDay['bday_tax_decimal']);
            }
            $itemTotalWithoutTax = $check['chks_item_total'] - $taxOnItems;

            $buyerReferenceNumber = "";
            if ($vars['GUIType'] == "b")
                $buyerReferenceNumber = $taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_ref_num'];

            /*calculate the check sum info */
            $plainText = $taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_prefix'] . $twtxNumber . str_pad($vars['GUIRandomNum'], 4, "0", STR_PAD_LEFT);
            $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);    //set encryption method with mode
            $plainText = $this->__pkcs5Padding($plainText, $size);    //padding plain text with pkcs5
            $key = hex2bin($stationParamsJson['tgui']['checksum_encrypt_key']);    //change key to byte
            $mcryptModule = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');    //create encrypt module
            $iv = base64_decode("Dt8lyToo17X/XkXaQvihuA==");
            mcrypt_generic_init($mcryptModule, $key, $iv);
            $cipherText = mcrypt_generic($mcryptModule, $plainText);    //really encrypt target plain text
            mcrypt_generic_deinit($mcryptModule);
            mcrypt_module_close($mcryptModule);    //close encrypt module
            $base64CipherText = base64_encode($cipherText);

            $vars['GUILeftQRCode'] = $taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_prefix'] . $twtxNumber . $vars['GUIYear'] . (date('md', strtotime($currentDateTimeString))) . str_pad($vars['GUIRandomNum'], 4, "0", STR_PAD_LEFT);
            $vars['GUILeftQRCode'] = $vars['GUILeftQRCode'] . (str_pad(dechex($itemTotalWithoutTax), 8, "0", STR_PAD_LEFT)) . (str_pad(dechex($check['chks_item_total']), 8, "0", STR_PAD_LEFT));
            $vars['GUILeftQRCode'] = $vars['GUILeftQRCode'] . (str_pad($buyerReferenceNumber, 8, "0")) . $sellerNumber . $base64CipherText;
            $vars['GUILeftQRCode'] = $vars['GUILeftQRCode'] . ":**********:" . count($checkItems) . ":" . count($checkItems) . ":1";

            //generate the right QR code
            $vars['GUIRightQRCode'] = "**";
            $includedItemCount = 0;
            for ($itemIndex = 0; $itemIndex < count($checkItems); $itemIndex++) {
                if ($includedItemCount >= 5)
                    break;

                if (isset($checkItems[$itemIndex]['PosCheckItem']))
                    $posItem = &$checkItems[$itemIndex]['PosCheckItem'];
                else
                    $posItem = &$checkItems[$itemIndex];

                if ($itemIndex > 0)
                    $vars['GUIRightQRCode'] = $vars['GUIRightQRCode'] . ":";
                $vars['GUIRightQRCode'] = $vars['GUIRightQRCode'] . $posItem['citm_name_l' . $prtFmtDefaultLang] . ":" . number_format($posItem['citm_qty'], $businessDay['bday_item_decimal'], ".", "") . ":" . number_format($posItem['citm_round_total'], $businessDay['bday_item_decimal'], ".", "");

                $includedItemCount++;
            }

            $vars['GUIPrintCount'] = $taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_print_count'];
            $vars['GUIPrintTotal'] = number_format($taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_print_total'], $businessDay['bday_item_decimal'], ".", "");
            $vars['GUIVatTotal'] = number_format($taiwanGuiTrans[0]['PosTaiwanGuiTran']['twtx_vat_total'], $businessDay['bday_item_decimal'], ".", "");
        }
    }

    //encryption padding method - pkcs5
    private function __pkcs5Padding($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    //get the corresponding payment/item check extra info
    private function __getExtraInfo($checkExtraInfos, $checkPaymentId, $checkItemId)
    {
        $extraInfos = array();

        if (!empty($checkExtraInfos)) {
            if ($checkPaymentId != 0) {
                for ($extraInfoIndex = 0; $extraInfoIndex < count($checkExtraInfos); $extraInfoIndex++) {
                    if ($checkExtraInfos[$extraInfoIndex]['PosCheckExtraInfo']['ckei_by'] == "payment" && $checkExtraInfos[$extraInfoIndex]['PosCheckExtraInfo']['ckei_cpay_id'] == $checkPaymentId)
                        $extraInfos[] = $checkExtraInfos[$extraInfoIndex]['PosCheckExtraInfo'];
                }
            } else {
                for ($extraInfoIndex = 0; $extraInfoIndex < count($checkExtraInfos); $extraInfoIndex++) {
                    if ($checkExtraInfos[$extraInfoIndex]['PosCheckExtraInfo']['ckei_by'] == "item" && $checkExtraInfos[$extraInfoIndex]['PosCheckExtraInfo']['ckei_citm_id'] == $checkItemId)
                        $extraInfos[] = $checkExtraInfos[$extraInfoIndex]['PosCheckExtraInfo'];
                }
            }
        }

        return $extraInfos;
    }

    private function __checkStringExist($data, $field, $default = "")
    {
        if (isset($data[$field]))
            return $data[$field];
        else
            return $default;
    }

    private function __checkNumericExist($data, $field, $default = 0)
    {
        if (isset($data[$field]))
            return $data[$field];
        else
            return $default;
    }

    /*
	 * Check if this outlet have setup for continuous printing
	 */
    private function __checkForContinuousPrint($stationId, $outletId)
    {
        App::import('Model', 'Pos.PosConfig');
        $posConfigModel = new PosConfig();
        $posConfigList = $posConfigModel->findAllActiveBySectionVariable($stationId, $outletId, '0', 'system', 'support_continuous_printing');

        if (!empty($posConfigList)) {
            foreach ($posConfigList as $posConfigObject) {
                foreach ($posConfigObject as $posConfigChildObject) {
                    if ($posConfigChildObject['scfg_value'] == 'true')
                        return true;
                }
            }
        }
        return false;
    }

    /*
	 * get config by location setup
	 */
    private function __getConfigByLocationValue($stationId, $outletId, $shopId, $section, $variable)
    {
        App::import('Model', 'Pos.PosConfig');
        $configValue = '';
        $posConfigModel = new PosConfig();
        $posConfigList = $posConfigModel->findAllActiveBySectionVariable($stationId, $outletId, $shopId, $section, $variable);
        if (!empty($posConfigList)) {
            foreach ($posConfigList as $posConfigObject) {
                foreach ($posConfigObject as $posConfigChildObject) {
                    $configValue = $posConfigChildObject['scfg_value'];
                    break;
                }
            }
        }
        return $configValue;
    }

    // Create Job to Display Screen
    private function __createJobToDisplayScreen($vars, $checkId, $type, $screenId, $languageIndex, $displayMode)
    {
        if (!class_exists('PosDisplayControlComponent'))
            return 'fail_to_load_component';

        $posDisplayControlComponent = new PosDisplayControlComponent(new ComponentCollection());
        $posDisplayControlComponent->startup($this->controller);

        if ($type == "change_table_item" || $type == "merge_table_item")
            return;

        $param = array();
        $param['checkInfo'] = $vars;
        $param['checkInfo']['CheckId'] = $checkId;
        $param['displayInfo']['type'] = $type;
        $param['displayInfo']['screenId'] = $screenId;
        $param['displayInfo']['languageIndex'] = $languageIndex;
        $param['displayInfo']['displayMode'] = $displayMode;

        // Split Set menu item when combine display mode
        if ($displayMode == 'c' && isset($vars['Items']) && count($vars['Items']) > 0) {
            $itemsSeparateBySetMenuId = array();
            foreach ($vars['Items'] as $item) {
                $itemArray = array();
                if (isset($itemsSeparateBySetMenuId[$item['SetMenuId']]))
                    $itemArray = $itemsSeparateBySetMenuId[$item['SetMenuId']];

                $itemArray[] = $item;
                $itemsSeparateBySetMenuId[$item['SetMenuId']] = $itemArray;
            }

            foreach ($itemsSeparateBySetMenuId as $items) {
                $param['checkInfo']['Items'] = $items;
                $posDisplayControlComponent->addJob($param);
            }
        } else
            $posDisplayControlComponent->addJob($param);
    }

    private function __packingRushOrderSpeacialSlipMessage(&$vars, $item)
    {
        // Message1: Warning/Serious Warning
        if ($item['citm_rush_count'] + 1 > 2)
            $vars['Message1'] = 1;
        else
            $vars['Message1'] = 0;
        for ($index = 1; $index <= 5; $index++)
            $vars['Message1L' . $index] = $vars['Message1'];

        // Message2: Rush count
        $vars['Message2'] = $item['citm_rush_count'];
        for ($index = 1; $index <= 5; $index++)
            $vars['Message2L' . $index] = $item['citm_rush_count'];

        // Message3: Item's Order Time
        $vars['Message3'] = date('H:i:s', strtotime($item['citm_order_loctime']));
        for ($index = 1; $index <= 5; $index++)
            $vars['Message3L' . $index] = date('H:i:s', strtotime($item['citm_order_loctime']));

        // Message: Time Difference from order time to now
        $orderTime = strtotime($item['citm_order_loctime']);
        $printTime = strtotime($vars['PrintTime']);
        $diff = abs($printTime - $orderTime);
        $diffHour = floor($diff / 60 / 60);
        $diffMin = floor($diff / 60 % 60);
        $diffSec = floor($diff % 60);

        $vars['Message4'] = sprintf("%02d:%02d:%02d", $diffHour, $diffMin, $diffSec);
        for ($index = 1; $index <= 5; $index++)
            $vars['Message4L' . $index] = sprintf("%02d:%02d:%02d", $diffHour, $diffMin, $diffSec);
    }

    private function __writePiiLog($moduleAlias = '', $action = '', $table = '', $recordIds = null, $desc = null, $params = null, $bSkipDataProp = false)
    {
        if (!array_key_exists('pii', $this->controller->plugins))
            // No PII module
            return null;

        App::import('Component', 'Pii');
        if (!class_exists('PiiComponent'))
            return 'fail_to_load_component';

        $this->Pii = new PiiComponent(new ComponentCollection());
        $this->Pii->startup($this->controller);

        // Add log to PII module
        $this->Pii->writeLogs($moduleAlias, $action, $table, $recordIds, $desc, $params, $bSkipDataProp);
    }

    private function __formItemsCheckingArray($Items = null)
    {
        $itemsCheckingList = array();

        for ($itemIndex = 0; $itemIndex < count($Items); $itemIndex++) {
            $itemsCheckingList[$itemIndex]['ChildItems'] = array();
            $itemsCheckingList[$itemIndex]['Modifiers'] = array();
            $itemsCheckingList[$itemIndex]['Discounts'] = array();

            if (!empty($Items[$itemIndex]['ChildItems'])) {
                foreach ($Items[$itemIndex]['ChildItems'] as $ChildItem) {
                    if (!array_key_exists($ChildItem['ChildItemId'], $itemsCheckingList[$itemIndex]['ChildItems'])) {
                        $itemsCheckingList[$itemIndex]['ChildItems'][$ChildItem['ChildItemId']] = 1;

                        $itemsCheckingList[$itemIndex]['ChildDetail'][$ChildItem['ChildItemId']]['Modifiers'] = array();
                        if (isset($ChildItem['Modifiers']) && count($ChildItem['Modifiers']) > 0) {
                            $childItemModifiersChecking = $ChildItem['Modifiers'];
                            foreach ($childItemModifiersChecking as $childItemModifierChecking) {
                                if (!array_key_exists($childItemModifierChecking['ModifierId'], $itemsCheckingList[$itemIndex]['ChildDetail'][$ChildItem['ChildItemId']]['Modifiers']))
                                    $itemsCheckingList[$itemIndex]['ChildDetail'][$ChildItem['ChildItemId']]['Modifiers'][$childItemModifierChecking['ModifierId']] = 1;
                                else
                                    $itemsCheckingList[$itemIndex]['ChildDetail'][$ChildItem['ChildItemId']]['Modifiers'][$childItemModifierChecking['ModifierId']] += 1;
                            }
                        }
                        $itemsCheckingList[$itemIndex]['ChildDetail'][$ChildItem['ChildItemId']]['Discounts'] = array();
                        if (isset($ChildItem['Discounts']) && count($ChildItem['Discounts']) > 0) {
                            $childItemDiscountsChecking = $ChildItem['Discounts'];
                            foreach ($childItemDiscountsChecking as $childItemDiscountChecking) {
                                if (!array_key_exists($childItemDiscountChecking['DiscountId'], $itemsCheckingList[$itemIndex]['ChildDetail'][$ChildItem['ChildItemId']]['Discounts']))
                                    $itemsCheckingList[$itemIndex]['ChildDetail'][$ChildItem['ChildItemId']]['Discounts'][$childItemDiscountChecking['DiscountId']] = 1;
                                else
                                    $itemsCheckingList[$itemIndex]['ChildDetail'][$ChildItem['ChildItemId']]['Discounts'][$childItemDiscountChecking['DiscountId']] += 1;
                            }
                        }
                    } else
                        $itemsCheckingList[$itemIndex]['ChildItems'][$ChildItem['ChildItemId']] += 1;
                }
            }
            if (!empty($Items[$itemIndex]['Modifiers'])) {
                foreach ($Items[$itemIndex]['Modifiers'] as $Modifier) {
                    if (!array_key_exists($Modifier['ModifierId'], $itemsCheckingList[$itemIndex]['Modifiers']))
                        $itemsCheckingList[$itemIndex]['Modifiers'][$Modifier['ModifierId']] = 1;
                    else
                        $itemsCheckingList[$itemIndex]['Modifiers'][$Modifier['ModifierId']] += 1;
                }
            }
            if (!empty($Items[$itemIndex]['Discounts'])) {
                foreach ($Items[$itemIndex]['Discounts'] as $Discount) {
                    if (!array_key_exists($Discount['DiscountId'], $itemsCheckingList[$itemIndex]['Discounts']))
                        $itemsCheckingList[$itemIndex]['Discounts'][$Discount['DiscountId']] = 1;
                    else
                        $itemsCheckingList[$itemIndex]['Discounts'][$Discount['DiscountId']] += 1;
                }
            }
        }

        return $itemsCheckingList;

    }

    private function __handleOnCheckTaxScRefs(&$vars, $businessDay, $taxScRefs)
    {
        if (empty($taxScRefs))
            return;

        $targetValues = array(
            //format - <Tax SC reference variable name> => <printing variable name>:<targer decimal setting for number format>:<Need to calculate total sum (boolean)>
            'incl_sc_ref' => 'InclusiveScRef:bday_sc_decimal:false',
            'round_post_disc_on_sc_ref' => 'DiscountOnSc:bday_disc_decimal:true',
            'round_post_disc_on_tax_ref' => 'DiscountOnTax:bday_disc_decimal:true'
        );
        $vars['DiscountOnScTotal'] = number_format(0.0, $businessDay['bday_disc_decimal'], '.', '');
        for ($i = 1; $i <= 5; $i++) {
            $vars['DiscountOnSc' . $i] = number_format(0.0, $businessDay['bday_disc_decimal'], '.', '');
            $vars['InclusiveScRef' . $i] = number_format(0.0, $businessDay['bday_disc_decimal'], '.', '');
        }
        $vars['DiscountOnTaxTotal'] = number_format(0.0, $businessDay['bday_disc_decimal'], '.', '');
        for ($i = 1; $i <= 25; $i++)
            $vars['DiscountOnTax' . $i] = number_format(0.0, $businessDay['bday_disc_decimal'], '.', '');

        foreach ($taxScRefs as $taxScRef) {
            foreach ($targetValues as $ctsrVariable => $printVar) {
                $printVar = explode(':', $printVar);
                if (strpos($taxScRef['ctsr_variable'], $ctsrVariable) !== false) {
                    $index = substr($taxScRef['ctsr_variable'], strlen($ctsrVariable));
                    $vars[$printVar[0] . $index] = number_format(($vars[$printVar[0] . $index] + $taxScRef['ctsr_value']), $businessDay[$printVar[1]], '.', '');
                    if ($printVar[2] == 'true')
                        $vars[$printVar[0] . 'Total'] = number_format(($vars[$printVar[0] . 'Total'] + $taxScRef['ctsr_value']), $businessDay[$printVar[1]], '.', '');
                }
            }
        }
    }

    //swipe breakdwon value to inclusive sc / tax
    private function __swipeBreakdownValue(&$checkInfo, &$checkExtraInfo, &$checkItems)
    {
        if ($checkExtraInfo == null || count($checkExtraInfo) == 0)
            return;

        $targetValue = $this->__getExtraInfoValue($checkExtraInfo, 'non_breakdown_details');
        if (empty($targetValue))
            return;

        // swipe check level information
        $nonBreakdownInfos = json_decode($targetValue, true);
        foreach ($nonBreakdownInfos as $key => $value) {
            if (!empty($value)) {
                if (isset($checkInfo['PosCheck']))
                    $checkInfo['PosCheck'][$key] = $value;
                else
                    $checkInfo['posCheck'][$key] = $value;
            }
        }
        if (isset($checkInfo['PosCheck']))
            $this->__rollBackScTaxValue($checkInfo['PosCheck'], 'chks');
        else
            $this->__rollBackScTaxValue($checkInfo['posCheck'], 'chks');

        // swipe item level information
        for ($i = 0; $i < count($checkItems); $i++) {
            if (!isset($checkItems[$i]['PosCheckExtraInfo']) || count($checkItems[$i]['PosCheckExtraInfo']) == 0)
                continue;

            $targetValue = $this->__getExtraInfoValue($checkItems[$i]['PosCheckExtraInfo'], 'non_breakdown_details');
            if (empty($targetValue))
                continue;
            $nonBreakdownInfos = json_decode($targetValue, true);
            foreach ($nonBreakdownInfos as $key => $value) {
                if (!empty($value))
                    $checkItems[$i][$key] = $value;
            }
            $this->__rollBackScTaxValue($checkItems[$i], 'citm');

            //swipe item modifiers
            if (isset($checkItems[$i]['citm_modifier_count']) && $checkItems[$i]['citm_modifier_count'] > 0) {
                for ($j = 0; $j < count($checkItems[$i]['ModifierList']); $j++) {
                    $targetValue = $this->__getExtraInfoValue($checkItems[$i]['ModifierList'][$j]['PosCheckExtraInfo'], 'non_breakdown_details');
                    if (empty($targetValue))
                        continue;

                    $nonBreakdownInfos = json_decode($targetValue, true);
                    foreach ($nonBreakdownInfos as $key => $value) {
                        if (!empty($value))
                            $checkItems[$i]['ModifierList'][$j][$key] = $value;
                    }
                    $this->__rollBackScTaxValue($checkItems[$i]['ModifierList'][$j], 'citm');
                }
            }

            //swipe item childs citm_child_count
            if (isset($checkItems[$i]['citm_child_count']) && $checkItems[$i]['citm_child_count'] > 0) {
                for ($j = 0; $j < count($checkItems[$i]['ChildItemList']); $j++) {
                    $targetValue = $this->__getExtraInfoValue($checkItems[$i]['ChildItemList'][$j]['PosCheckExtraInfo'], 'non_breakdown_details');
                    if (empty($targetValue))
                        continue;

                    $nonBreakdownInfos = json_decode($targetValue, true);
                    foreach ($nonBreakdownInfos as $key => $value) {
                        if (!empty($value))
                            $checkItems[$i]['ChildItemList'][$j][$key] = $value;
                    }
                    $this->__rollBackScTaxValue($checkItems[$i]['ChildItemList'][$j], 'citm');

                    if (!isset($checkItems[$i]['ChildItemList'][$j]['PosCheckDiscount']) || count($checkItems[$i]['ChildItemList'][$j]['PosCheckDiscount']) == 0)
                        continue;
                    for ($k = 0; $k < count($checkItems[$i]['ChildItemList'][$j]['PosCheckDiscount']); $k++) {
                        if (!isset($checkItems[$i]['ChildItemList'][$j]['PosCheckDiscount'][$k]['checkExtraInfos']) || count($checkItems[$i]['ChildItemList'][$j]['PosCheckDiscount'][$k]['checkExtraInfos']) == 0)
                            continue;
                        $this->__swipeDiscBreakdownValue($checkItems[$i]['ChildItemList'][$j]['PosCheckDiscount'][$k], $checkItems[$i]['ChildItemList'][$j]['PosCheckDiscount'][$k]['checkExtraInfos']);
                    }
                }
            }

            //swipe item pre discount
            if (!isset($checkItems[$i]['PosCheckDiscount']) || count($checkItems[$i]['PosCheckDiscount']) == 0)
                continue;
            for ($j = 0; $j < count($checkItems[$i]['PosCheckDiscount']); $j++) {
                if (!isset($checkItems[$i]['PosCheckDiscount'][$j]['checkExtraInfos']) || count($checkItems[$i]['PosCheckDiscount'][$j]['checkExtraInfos']) == 0)
                    continue;
                $this->__swipeDiscBreakdownValue($checkItems[$i]['PosCheckDiscount'][$j], $checkItems[$i]['PosCheckDiscount'][$j]['checkExtraInfos']);
            }
        }

        //swipe check discount checkDiscounts
        if (!isset($checkInfo['checkDiscounts']) || count($checkInfo['checkDiscounts']) == 0)
            return;
        for ($i = 0; $i < count($checkInfo['checkDiscounts']); $i++) {
            if (!isset($checkInfo['checkDiscounts'][$i]['checkExtraInfos']) || count($checkInfo['checkDiscounts'][$i]['checkExtraInfos']) == 0)
                continue;
            $this->__swipeDiscBreakdownValue($checkInfo['checkDiscounts'][$i], $checkInfo['checkDiscounts'][$i]['checkExtraInfos']);
        }
    }

    private function __swipeItemBreakdownValue(&$checkItem, &$itemExtraInfo)
    {
        if (count($itemExtraInfo) == 0)
            return;

        $targetValue = $this->__getExtraInfoValue($itemExtraInfo, 'non_breakdown_details');
        if (empty($targetValue))
            return;
        $nonBreakdownInfos = json_decode($targetValue, true);
        foreach ($nonBreakdownInfos as $key => $value) {
            if (!empty($value)) {
                $checkItem[$key] = $value;
            }
        }
        $this->__rollBackScTaxValue($checkItem, 'citm');
    }

    private function __getExtraInfoValue($extraInfos, $variable)
    {
        $targetValue = '';
        foreach ($extraInfos as $extraInfo) {
            if (isset($extraInfo['PosCheckExtraInfo']))
                $extraInfo = $extraInfo['PosCheckExtraInfo'];
            if ($extraInfo['ckei_variable'] == $variable) {
                $targetValue = $this->__checkStringExist($extraInfo, 'ckei_value');
                break;
            }
        }
        return $targetValue;
    }

    private function __rollBackScTaxValue(&$target, $prefix)
    {
        for ($i = 1; $i < 25; $i++) {
            if ($i <= 5) {
                $target[$prefix . '_sc' . $i] = 0.0;
                if ($prefix == 'citm' && $target[$prefix . '_charge_sc' . $i] == 'c')
                    $target[$prefix . '_charge_sc' . $i] = 'n';
            }
            $target[$prefix . '_tax' . $i] = 0.0;
            if ($prefix == 'citm' && $target[$prefix . '_charge_tax' . $i] == 'c')
                $target[$prefix . '_charge_tax' . $i] = 'n';
        }
    }

    private function __swipeDiscBreakdownValue(&$target, &$extraInfos)
    {
        if ($target['cdis_type'] != 'b')
            return;

        $targetValue = $this->__getExtraInfoValue($extraInfos, 'non_breakdown_details');
        if (empty($targetValue))
            return;
        $nonBreakdownInfos = json_decode($targetValue, true);
        foreach ($nonBreakdownInfos as $key => $value) {
            if ($key == 'checkDiscountItems')
                continue;
            if (!empty($value))
                $target[$key] = $value;
        }
    }
}

?>