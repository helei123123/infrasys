<?php
/**
 * Somerset313 Sales Export Shell Class
 *
 * This file define the Console shell of Messaging
 * @author    kwan tong
 * @copyright Copyright (c) 2014, Infrasys International Ltd.
 */

App::uses('Shell', 'Console');
App::uses('Security', 'Utility');
App::uses('Component', 'Controller');

class TrainingSalesExportShell extends AppShell
{
    /**
     * interface code
     */
    private $interfaceCode = "";

    /**
     * Tenant code
     */
    private $tenantMachineId = "";

    /**
     * Tax Charge Type
     */
    private $taxChargeType = "";

    /**
     * Export Diretory
     */
    private $exportPath = "";

    /**FTP
     * Message Interface Code
     */
    private $messageInterfaceCode = "";

    /**FTP
     * FTP Target Path
     */
    private $ftpTargetPath = "";

    /**
     * FTP Host
     */
    private $ftpHost = "";

    /**
     * FTP Port
     */
    private $ftpPort = "";

    /**
     * FTP Username
     */
    private $ftpUsername = "";

    /**
     * FTP Password
     */
    private $ftpPassword = "";

    /**
     * date mode
     */
    private $dateMode = "d";

    /**
     * sales date
     */
    private $salesDate = "";

    /**
     * Quiet display
     * @var <type>
     */
    private $bQuiet = false;

    /**
     * Log needed
     * @var <type>
     */
    private $bLogNeeded = true;

    /**
     * Store the cookie (used for non multi-threading)
     * @var <type>
     */
    public $cookie = '';

    public $uses = array('Interface.InfInterface', 'Outlet.OutOutlet', 'Outlet.OutShop', 'Messaging.MsgInterface', 'Pos.PosCheckPayment', 'Pos.PosBusinessDay', 'Pos.PosCheck', 'Pos.PosCheckItem', 'Pos.PosCheckDiscount', 'Pos.PosInterfaceConfig', 'Pos.PosPaymentMethod');

    /**
     * Override the initialize function
     */
    public function initialize()
    {
        $this->loadSystemConfig();
        parent::initialize();
    }

    public function main($interfaceCode = null)
    {
        $ftpValid = true;
        $newBatchLine = false;
        // Load required components
        $components = array('' => array('Common'));
        $loadComponents = $this->loadComponents($components);
        if ($loadComponents !== true) {
            $this->outMsg('Fail to load ' . $loadComponents);
            return (false);        // return false for fail
        }
        //Check the module existence
        if (!array_key_exists("interface", $this->plugins)) {
            CakeLog::write('salesDataExport', 'TrainingSalesExport - Module "Interface" not exist');
            $this->outMsg("Module \"Interface\" not exist");
            return;
        }
        //Get params or arguments
        if ($interfaceCode == null) {
            $parser = $this->getOptionParser();

            if (isset($this->params['interfaceCode'])) {
                $this->interfaceCode = $this->params['interfaceCode'];
                if ($this->interfaceCode != "")
                    $this->outMsg("Training Sales Export Interface code:" . $this->interfaceCode);
            }
            if (isset($this->params['dateMode'])) {
                $this->dateMode = $this->params['dateMode'];
            }
            if (isset($this->params['salesDate'])) {
                $this->salesDate = $this->params['salesDate'];
            }
            if (isset($this->params['logNeeded']) && $this->params['logNeeded'] == 0)
                $this->bLogNeeded = false;

            if (!empty($this->params['quiet']))
                $this->bQuiet = true;
        } else {
            $this->interfaceCode = $interfaceCode;
            $this->bQuiet = true;
        }

        //Get interface setup information
        if (strcmp($this->interfaceCode, "") == 0) {
            CakeLog::write('salesDataExport', 'TrainingSalesExport - No interface code provided');
            $this->outMsg("No TrainingSalesExport data export interface code provided");
            return;
        }

        $trainingSalesExportInf = $this->InfInterface->findActiveByCode($this->interfaceCode, 1);
        $interfaceSetup = json_decode($trainingSalesExportInf['InfInterface']['intf_settings'], true);
        if (empty($interfaceSetup['analysis_setup']['params']['tenant_machine_id']['value'])) {
            CakeLog::write('salesDataExport', 'TrainingSalesExport - Empty tenant machine ID');
            $this->outMsg('Empty tenant machine ID');
            return;
        } else {
            $this->tenantMachineId = $interfaceSetup['analysis_setup']['params']['tenant_machine_id']['value'];
        }
        if (empty($interfaceSetup['export_file_setup']['params']['export_path']['value'])) {
            CakeLog::write('salesDataExport', 'TrainingSalesExport - Empty export path');
            $this->outMsg('Empty export path');
            return;
        } else {
            $strlen = strlen($interfaceSetup['export_file_setup']['params']['export_path']['value']);
            if (substr($interfaceSetup['export_file_setup']['params']['export_path']['value'], ($strlen - 1), 1) != "\\")
                $interfaceSetup['export_file_setup']['params']['export_path']['value'] .= "\\";

            $this->exportPath = $interfaceSetup['export_file_setup']['params']['export_path']['value'];
        }
        //Get sales date format
        if (!empty($this->salesDate)) {
            $startDateTime = DateTime::createFromFormat('Y-m-d', $this->salesDate);
            $dateTimeError = DateTime::getLastErrors();
            if ($dateTimeError['warning_count'] + $dateTimeError['error_count'] > 0) {
                CakeLog::write('salesDataExport', 'GreatWorldCitySalesExport - Invalid sales date');
                $this->outMsg("Invalid sales date");
                return;
            }
        }
        ///////////////////////////////////////////////////////////////////////
        //Get FTP server information
        if ($ftpValid) {
            $ftpInterfaceConfigs = $this->MsgInterface->find('first', array(
                    'conditions' => array(
                        'MsgInterface.intf_code' => $this->messageInterfaceCode,
                        'MsgInterface.intf_status' => '',
                    ),
                    'recursive' => -1
                )
            );

            if (empty($ftpInterfaceConfigs)) {
                CakeLog::write('salesDataExport', 'GreatWorldCitySalesExport - No messaging interface exist, code:' . $this->messageInterfaceCode);
                $this->outMsg("No messaging interface exist");
                $ftpValid = false;
            }

            if ($ftpInterfaceConfigs['MsgInterface']['intf_settings'] == null) {
                CakeLog::write('salesDataExport', 'GreatWorldCitySalesExport - No messaging interface setting exist');
                $this->outMsg("No message interface setting exist");
                $ftpValid = false;
            }

            $msgInterfaceSetup = json_decode($ftpInterfaceConfigs['MsgInterface']['intf_settings'], true);

            if (empty($msgInterfaceSetup['host'])) {
                CakeLog::write('salesDataExport', 'GreatWorldCitySalesExport - Empty ftp host');
                $this->outMsg("Empty ftp host");
                $ftpValid = false;
            } else
                $this->ftpHost = $msgInterfaceSetup['host'];

            if (empty($msgInterfaceSetup['port'])) {
                CakeLog::write('salesDataExport', 'GreatWorldCitySalesExport - Empty ftp port');
                $this->outMsg("Empty ftp port");
                $ftpValid = false;
            } else
                $this->ftpPort = $msgInterfaceSetup['port'];

            if (empty($msgInterfaceSetup['username'])) {
                CakeLog::write('salesDataExport', 'GreatWorldCitySalesExport - Empty ftp username');
                $this->outMsg("Empty ftp username");
                $ftpValid = false;
            } else
                $this->ftpUsername = $msgInterfaceSetup['username'];

            if (empty($msgInterfaceSetup['password'])) {
                CakeLog::write('salesDataExport', 'GreatWorldCitySalesExport - Empty ftp password');
                $this->outMsg("Empty ftp password");
                $ftpValid = false;
            } else {
                //decrypt the password
                if (!empty($msgInterfaceSetup['encrypted'])) {
                    $encrypted = pack("H*", $msgInterfaceSetup['password']);
                    $password = Security::rijndael($encrypted, $this->encryptKey, 'decrypt');
                } else
                    $password = $msgInterfaceSetup['password'];
                $this->ftpPassword = $password;
            }
        }
        //Get POS interface config setting
        $posInterfaceConfigs = $this->PosInterfaceConfig->find('all', array(
                'conditions' => array(
                    'PosInterfaceConfig.icfg_intf_id' => $trainingSalesExportInf['InfInterface']['intf_id'],
                    'PosInterfaceConfig.icfg_status' => '',
                ),
                'recursive' => -1)
        );
        if (empty($posInterfaceConfigs)) {
            CakeLog::write('salesDataExport', 'TrainingSalesExport - No POS interface config setting exist');
            $this->outMsg("No POS interface config setting exist");
            return;
        } else {
            $posInterfaceConfigForOutletShop = array();
            for ($i = 0; $i < count($posInterfaceConfigs); $i++) {
                if ($posInterfaceConfigs[$i]['PosInterfaceConfig']['icfg_shop_id'] != 0 && $posInterfaceConfigs[$i]['PosInterfaceConfig']['icfg_olet_id'] != 0)
                    $posInterfaceConfigForOutletShop[] = $posInterfaceConfigs[$i];
            }

            if (count($posInterfaceConfigForOutletShop) == 0) {
                CakeLog::write('salesDataExport', 'TrainingSalesExport - No POS interface config setting for outlet level');
                $this->outMsg("No POS interface config setting for outlet level");
                return;
            }
        }
        //Generate the sales data
        for ($i = 0; $i < count($posInterfaceConfigForOutletShop); $i++) {
            //Generate the sales date
            $shopConfig = $this->OutShop->findActiveById($posInterfaceConfigForOutletShop[$i]['PosInterfaceConfig']['icfg_shop_id']);

            $todayDay = date("Y-m-d", strtotime($shopConfig['OutShop']['shop_timezone'] . " min"));
            if ($this->dateMode == "d")
                $salesDate = $todayDay;
            else if ($this->dateMode == "l")
                $salesDate = date("Y-m-d", strtotime($todayDay . "-1 day"));
            else if ($this->dateMode == "c")
                $salesDate = (!empty($this->salesDate)) ? $this->salesDate : $todayDay;
            //Get business day record
            $outletId = $posInterfaceConfigForOutletShop[$i]['PosInterfaceConfig']['icfg_olet_id'];
            $businessDay = $this->PosBusinessDay->findByDateOutletId($salesDate, $outletId);
            //Get Batch ID
            $batchId = 0;
            $systemDataPath = $this->Common->getDataPath();
            $batchIdFileName = 'batchID_' . $trainingSalesExportInf['InfInterface']['intf_id'] . '_' . $outletId . '.txt';
            $batchIdFilePath = $systemDataPath . 'interface_vendors\pos_export_program\training_sales_export\\' . $batchIdFileName;

            if (file_exists($batchIdFilePath)) {
                $batchIdFile = fopen($batchIdFilePath, "r");
                $tok = 0;
                $tmpDate = '';
                $tmpid = 0;
                //find if the date have already used before
                while (($line = fgets($batchIdFile)) !== false) {
                    $tok = strtok($line, ",");
                    $tmpDate = $tok;
                    $tok = strtok(",");
                    $tmpid = trim($tok);
                    if (strcmp($tmpDate, date('Y-m-d', strtotime($salesDate))) == 0) {
                        $batchId = $tmpid;
                        break;
                    } else {
                        $newBatchLine = true;
                        $batchId = $tmpid + 1;
                    }
                }
                fclose($batchIdFile);
            } else
                $batchId = 1;
            //set receipt GtoSales GSt Discount
            $GTO = 0;
            $GST = 0;
            $Discount = 0;
            $Receipt = 0;
            $salesDataArrays = array(24);
            if (!empty($businessDay)) {
                //Get checks by business day
                $posChecks = $this->PosCheck->find('all', array(
                        'conditions' => array(
                            'PosCheck.chks_bday_id' => $businessDay['PosBusinessDay']['bday_id'],
                            'PosCheck.chks_non_revenue' => '',
                            'PosCheck.chks_status ' => '',
                        ),
                        'recursive' => 1
                    )
                );
                for ($i = 0; $i < 24; $i++) {
                    for ($j = 0; $j < count($posChecks); $j++) {
                        $salesTime = date('H', strtotime($posChecks[$j]['PosCheck']['chks_open_loctime']));
                        if ($i == $salesTime) {
                            $GTO += $posChecks[$j]['PosCheck']['chks_item_total'] + $posChecks[$j]['PosCheck']['chks_sc1'] + $posChecks[$j]['PosCheck']['chks_sc2'] + $posChecks[$j]['PosCheck']['chks_sc3'] + $posChecks[$j]['PosCheck']['chks_sc4'] + $posChecks[$j]['PosCheck']['chks_sc5']
                                + $posChecks[$j]['PosCheck']['chks_pre_disc'] + $posChecks[$j]['PosCheck']['chks_mid_disc'] + $posChecks[$j]['PosCheck']['chks_post_disc'] -
                                $posChecks[$j]['PosCheck']['chks_incl_tax_ref1'] - $posChecks[$j]['PosCheck']['chks_incl_tax_ref2'] - $posChecks[$j]['PosCheck']['chks_incl_tax_ref3'] -
                                $posChecks[$j]['PosCheck']['chks_incl_tax_ref4'];
                            $GST += $posChecks[$j]['PosCheck']['chks_incl_tax_ref1'] + $posChecks[$j]['PosCheck']['chks_incl_tax_ref2'] + $posChecks[$j]['PosCheck']['chks_incl_tax_ref3'] +
                                $posChecks[$j]['PosCheck']['chks_incl_tax_ref4'];
                            $Discount += ($posChecks[$j]['PosCheck']['chks_pre_disc'] + $posChecks[$j]['PosCheck']['chks_mid_disc'] + $posChecks[$j]['PosCheck']['chks_post_disc']) * (-1);
                            $Receipt += 1;
                        }
//                        echo '$GTO: '.$GST.'$Discount'.$Discount.'$Receipt'.$Receipt;
                    }
                    $hour ='0';
                    if($i<10){
                        $hour.=$i;
                    }else{
                        $hour=$i;
                    }
                    $salesDataArray = $this->__createInfoStructure(date('dmY', strtotime($salesDate)));
                    $salesDataArray['machineId'] = $this->tenantMachineId;
                    $salesDataArray['date'] = $salesDate;
                    $salesDataArray['batchId'] = $batchId;
                    $salesDataArray['hour'] = $hour;
                    $salesDataArray['receipt'] = $Receipt;
                    $salesDataArray['gto'] = number_format($GTO, 2, '.', '');
                    $salesDataArray['gst'] = number_format($GST, 2, '.', '');
                    $salesDataArray['discount'] = number_format($Discount, 2, '.', '');
                    $salesDataArray['noOfPaxForFBOnly'] = 0;
                    $salesDataArrays[$i] = $salesDataArray;
                    $GTO = 0;
                    $GST = 0;
                    $Discount = 0;
                    $Receipt = 0;
                }
            } else {
                for ($i = 0; $i < 24; $i++) {
                    $hour ='0';
                    if($i<10){
                        $hour.=$i;
                    }else{
                        $hour=$i;
                    }
                    $salesDataArray = $this->__createInfoStructure(date('dmY', strtotime($salesDate)));
                    $salesDataArray['machineId'] = $this->tenantMachineId;
                    $salesDataArray['date'] = $salesDate;
                    $salesDataArray['batchId'] = $batchId;
                    $salesDataArray['hour'] = $hour;
                    $salesDataArray['receipt'] = 0;
                    $salesDataArray['gto'] = '0.00';
                    $salesDataArray['gst'] = '0.00';
                    $salesDataArray['discount'] = '0.00';
                    $salesDataArray['noOfPaxForFBOnly'] = 0;
                    $salesDataArrays[$i] = $salesDataArray;
                }
            }
            $fileName = sprintf("%s%s%s%s", 'D', $this->tenantMachineId . '_', date('Ymd', strtotime($salesDate)), '.txt');
            $filePath = $this->exportPath . $fileName;
            if (!file_exists($filePath))
                $this->makePath($filePath);
            $resultFile = fopen($filePath, 'w');
            if ($resultFile === false) {
                CakeLog::write('salesDataExport', 'TrainingSalesExport - The final export file has been generated failed');
                $this->outMsg("The final export file has been generated failed");
                return;
            }
            //Attach filter to output file
            for ($i = 0; $i < count($salesDataArrays); $i++) {
                foreach ($salesDataArrays[$i] as $key => $salesData) {
                    fputs($resultFile, $salesData);
                    fputs($resultFile, '|');
                }
                fputs($resultFile, "\r\n");
            }
            fclose($resultFile);
            CakeLog::write('salesDataExport', 'TrainingSalesExport - The sales export file has been generated :' . $filePath);
            $this->outMsg("The sales export file has been generated :" . $filePath);

            ///////////////////////////////////////////////////////////////
            //Update next batch id file
            if (!file_exists($batchIdFilePath)) {
                $this->makePath($batchIdFilePath);
                $batchIdFile = fopen($batchIdFilePath, 'a');
                fwrite($batchIdFile, sprintf("%s,%d", date('Y-m-d', strtotime($salesDate)), $batchId));
                fclose($batchIdFile);
            } else {
                if ($newBatchLine) {
                    $batchIdFile = fopen($batchIdFilePath, 'a');
                    fwrite($batchIdFile, sprintf("\r\n%s,%d", date('Y-m-d', strtotime($salesDate)), $batchId));
                    fclose($batchIdFile);
                }
            }
            if ($ftpValid) {
                $connectOpts = array(
                    'host' => $this->ftpHost,
                    'port' => $this->ftpPort,
                    'username' => $this->ftpUsername,
                    'password' => $this->ftpPassword,
                    'type' => 'ftp',
                    'passive_mode' => false,
                    'timeout' => 90,    //	No need for sftp
                );
            }
        }

    }


    /**
     * Get Option Parser
     * @return <type>
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->addOption('interfaceCode', array(
            'short' => 'c',
            'help' => 'Somerset313 Sales Export Interface Code',
        ))->addOption('dateMode', array(
            'short' => 'm',
            'help' => 'Date Mode',
        ))->addOption('salesDate', array(
            'short' => 'd',
            'help' => 'Sales Date',
        ))->addOption('quiet', array(
            'short' => 'q',
            'help' => 'Debug mode',
        ))->description('Somerset313 Sakes Export Interface');

        return $parser;
    }

    /**
     * Output message to console with UTC time
     * @param <type> $msg
     */
    public function outMsg($msg)
    {
        if ($this->bQuiet)
            return;
        $nowTm = time() + $this->timeZone;
        $this->out('[' . date('Y-m-d H:i:s', $nowTm) . ' Interface] ' . $msg);
    }

    /**
     * Make a nested path , creating directories down the pat Recursion !!
     */
    public function makePath($path)
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        if (is_dir($dir))
            return true;
        else {
            if ($this->makePath($dir)) {
                if (mkdir($dir)) {
                    chmod($dir, 0777);
                    return true;
                }
            }
        }

        return false;
    }

    public function __createInfoStructure($salesDate){
    		$salesDataArray = array(
    			"machineId" => '',
    			"date" => $salesDate,
    			"batchId" => number_format(0, 0, '.', ''),
    			"hours" => '',
    			"receiptCount" => number_format(0, 0, '.', ''),
    			"gto" => number_format(0, 2, '.', ''),
    			"gst" => number_format(0, 2, '.', ''),
    			"discount" => number_format(0, 2, '.', ''),
    			"noOfPaxForFBOnly" => 0
    		);

    		return $salesDataArray;
    	}

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Minor class
// filter class that applies CRLF line endings
class crlf_filter extends php_user_filter
{
    function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            // make sure the line endings aren't already CRLF
            $bucket->data = preg_replace("/(?<!\r)\n/", "\r\n", $bucket->data);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }
}

?>