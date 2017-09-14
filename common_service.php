<?php
/**
 * Created by PhpStorm.
 * User: lvwu
 * Date: 16/2/22
 * Time: 14:43
 */

require_once "smartapp_service.php";
require_once "gaoxindishui_service.php";
//require_once(dirname ( __FILE__ ) . '/../../excel/reader.php');

require_once(dirname ( __FILE__ ) . '/../../excel-new/php-excel-reader/excel_reader2.php');
require_once(dirname ( __FILE__ ) . '/../../excel-new/SpreadsheetReader.php');
require_once(dirname ( __FILE__ ) . '/../../excel-new/ExcelReader.php');

define("GompanyUserModel","CompanyUser");
define("WorkingModel","Working");
define("TableAppModel","DataTable");
define("WorkTimeConfigModel","WorkTimeConfig");
define("WorkAttendanceModel","WorkAttendance");

define("QueQinApp_id","56a1834ee419be2e2d8b4584");
define('TimeType_Day', '天');
define('TimeType_Hour', '小时');

define('NianJia_usedDays', 'usedDays');
define('NianJia_usedDaysDetail', 'usedDaysDetail');

define('WorkTimeScore_AppId','571f1bc6e58a3a80018b45df'); //工时积分管理
//define('gextd_WorkTimeScore_AppId','5742622ce55baa055a8b457d'); //工时积分管理
function _isWorkTimeScoreAppId($app_id)
{
    if ($app_id==WorkTimeScore_AppId || $app_id == '5742622ce55baa055a8b457d')
        return true;

    return false;
}

function _isGaoxinGongZuoNuli($app_id){

    foreach(GaoxindishuiService::$companyAppIds as $company_id => $companyAppAry){
        if($companyAppAry[GaoXinDiShui_nuliCdCePing] == $app_id){
            return true;
        }
    }

    return false;
}

function _isGaoxinGeRenRenwu($app_id){

    foreach(GaoxindishuiService::$companyAppIds as $company_id => $companyAppAry){
        if($companyAppAry[GaoXinDiShui_geRenPingDing] == $app_id){
            return true;
        }
    }

    return false;
}


function _isGaoxinHuping($app_id){

    foreach(GaoxindishuiService::$companyAppIds as $company_id => $companyAppAry){
        if($companyAppAry[GaoXinDiShui_huPing] == $app_id){
            return true;
        }
    }

    return false;
}

define('ServicePath', dirname ( __FILE__ ));
define('TemplatesPath', dirname ( __FILE__ ) . "/../../../../scripts/templates/");

function readExcel($file)
{
    $resultArr = array();
    /*$resultArr2 = array();
    $data = new Spreadsheet_Excel_Reader();

    // Set output Encoding.
    $data->setOutputEncoding('utf-8');//GB2312
    $data->read($file);

    error_reporting(E_ALL ^ E_NOTICE);
    if (is_object($data)) {
        $contentArr = (array)($data);
    } else {
        $contentArr = &$data;
    }

    $startRow = $startCol = 1;
    $sheetsArr = $contentArr['boundsheets'];
    if (is_array($sheetsArr))
    {
        foreach($sheetsArr as $sheetIdx => $sheetInfo)
        {
            for ($i = $startRow; $i <= $data->sheets[$sheetIdx]['numRows']; $i++) {
                $items = array();
                for ($j = $startCol; $j <= $data->sheets[$sheetIdx]['numCols']; $j++) {
                    $value = $data->sheets[$sheetIdx]['cells'][$i][$j];
                    $items[] = $value; //if (!empty($value))
                }
                $resultArr[$sheetInfo['name']][] = $items;
            }
        }
    }*/

    return $resultArr;
}

function readExcel2($Filepath)
{
    var_dump($Filepath);
    //date_default_timezone_set('UTC');

    $StartMem = memory_get_usage();
    echo '---------------------------------'.PHP_EOL;
    echo 'Starting memory: '.$StartMem.PHP_EOL;
    echo '---------------------------------'.PHP_EOL;

    try
    {
        $excelReader = new ExcelReader();
        $Spreadsheet = &$excelReader->read($Filepath);

        $sheetIdx = $excelReader->getSheetIdxByName($Spreadsheet,'IWS FRC KA');
        $titleAry = array('产品线简称','产品线','产品代码','规格型号',"经销商价\n（不含税）",'9月底结存数量','10月出库数量','10月结存数量','11月计划进货数量','12月计划进货数量','1月计划进货数量','11月计划进货金额','12月计划进货金额','1月计划进货金额');
        $indexAry = $excelReader->getTitleIdxsByName($Spreadsheet,$sheetIdx,$titleAry,6);
        echo 'sheet index = '.$sheetIdx."\n";
        echo 'indexAry = ';
        var_dump($indexAry);
    }
    catch (Exception $E)
    {
        //echo $E -> getMessage();
    }
}


class CommonService
{
    public $userService;
    public $companyService;
    public $custom_version;//用户的当前版本号

    private $modelAry = array();

    function __construct()
    {
        vendor('users');
        $this->userService = new UsersService ();

        vendor('company');
        $this->companyService = new CompanyService();

        $this->custom_version = intval($_SERVER['HTTP_CUSTOM_VERSION']);
    }

    /******************************************************************************************
     ** 基础功能函数集
    /******************************************************************************************/
    function checkLoginInfo($user_id, $token, $company_id, &$checkResult)
    {
        $user_info = array();
        if (!$this->userService->checkTokenUser($user_id, $token, $err, $user_info)) {
            $checkResult = array(
                RETURN_RESULT => RESULT_FALSE,
                RETURN_ERRORCODE => ERROR_USER_TOKEN_ERROR,
                RETURN_MSG => "ERROR_USER_TOKEN_ERROR"
            );
            return false;
        }

        if (!$this->companyService->__isCompanyUser($user_id, $company_id)){
            $checkResult = array(
                RETURN_RESULT => RESULT_FALSE,
                RETURN_ERRORCODE => 'ERROR_NOT_COMPANY_MEMBER'
            );
            return false;
        }

        return true;
    }

    function checkLoginInfo2($user_id, $token, $app_id, &$checkResult)
    {
        $user_info = $err = false;
        if (!$this->userService->checkTokenUser($user_id, $token, $err, $user_info)) {
            $checkResult =  array(
                RETURN_RESULT => RESULT_FALSE,
                RETURN_ERRORCODE => ERROR_USER_TOKEN_ERROR,
                RETURN_MSG => 'ERROR_USER_TOKEN_ERROR'
            );
            return false;
        }

        if(!$app_id || !$user_info['company_id']){
            $checkResult =  array(
                RETURN_RESULT => RESULT_FALSE,
                RETURN_ERRORCODE => ERROR_PARAMS_ERROR
            );
            return false;
        }

        return true;
    }

    function _datePeriod($date, &$date_start, &$date_end)
    {
        $date_start = '';
        $date_end = '';
        if (!$date) {
            $date_start = date('Y-m-01');
            $date_end = date('Y-m-d');
        } else {
            if (substr($date, 0, 7) == date('Y-m')) {
                $date_start = date('Y-m-01');
                $date_end = date('Y-m-d');
            } else {
                $date_start = date('Y-m-01', strtotime($date));
                $date_end = date('Y-m-t 23:59:59', strtotime($date_start));
            }
        }
    }

    function _getModel($modelName){
        if($this->modelAry[$modelName]){
            return $this->modelAry[$modelName];
        }
        else{
            return $this->modelAry[$modelName] = loadModel($modelName,true);
        }
    }

    /**
     * @desc 根据两点间的经纬度计算距离
     * @param float $lat 纬度值
     * @param float $lng 经度值
     */
    function _getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367000; //approximate radius of earth in meters

        /*
        Convert these degrees to radians
        to work with the formula
        */

        $lat1 = ($lat1 * pi() ) / 180;
        $lng1 = ($lng1 * pi() ) / 180;

        $lat2 = ($lat2 * pi() ) / 180;
        $lng2 = ($lng2 * pi() ) / 180;

        /*
        Using the
        Haversine formula

        http://en.wikipedia.org/wiki/Haversine_formula

        calculate the distance
        */

        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance);
    }

    function _getTableappDataWithQuery($query, $field = array(), $sort = array()){
        $tableAppModel = $this->_getModel(TableAppModel);
        return $tableAppModel->findAll($query,$field, $sort);
    }

    function _getTableappDataBlocks($data){
        return  (is_array($data['user_list']) ? $data['user_list'] : array());
    }

    function _getTableappDataFieldValue($data, $field){
        if(is_array($data['user_list'])){
            foreach($data['user_list'] as $key=> $item){  //block
                if($key === $field){
                    return $item;
                }
                else if( is_array($item['excel_content_list'])){  //content
                    foreach($item['excel_content_list'] as $contentKey=> $contentItem){  //block
                        if($contentKey === $field){
                            return $contentItem;
                        }
                    }
                }
            }
        }
    }

    //对某个自定义应用若干字段设置值
    function _setTableappDataFieldValueArray(&$destData, $fieldValArr)
    {
        foreach($fieldValArr as $key=>$value){
            $item = $this->_getTableappDataFieldValue($destData,$key);
            self::_setValueFromValueItem($item,$value);
            $this->_setTableappDataFieldValue($destData, $key, $item);
        }
    }

    //对某个自定义应用的一个字段设置值
    function _setTableappDataFieldValue(&$data, $fieldKey, $filedValue)
    {
        if(is_array($data['user_list'])){
            foreach($data['user_list'] as $key=> &$item){  //block
                if($key === $fieldKey){
                    $item = $filedValue;
                }
                else if( is_array($item['excel_content_list'])){  //content
                    foreach($item['excel_content_list'] as $contentKey=> &$contentItem){
                        if($contentKey === $fieldKey){
                            $contentItem = $filedValue;
                        }
                    }
                }
            }
        }
    }

    //$keyField 处理完成后一条新记录的主键,如果$keyField为空,$is_value_unique参数无作用,返回一个数组
    function _formatTableAppArray($srcTableAppAry, $keyField, $fieldAry, $is_value_unique = false, $is_filter_empty = false){
        $ret = array();

        foreach($srcTableAppAry as $item){
            $new_item = array();
            foreach($fieldAry as $fieldKey=>$fieldItem){
                $value = $this->_getTableappDataFieldValue($item, $fieldKey);
                $temp_value =$this->_getValueFromValueItem($value);

                if($is_filter_empty ){
                    if(is_array($temp_value)){
                        if(count($temp_value) != 0){
                            $new_item[$fieldItem] =  $temp_value;
                        }
                    }else if(isset($temp_value)){
                        $new_item[$fieldItem] =  $temp_value;
                    }
                }
                else{
                    $new_item[$fieldItem] = $temp_value;
                }

            }

            if(empty($keyField) == false){
                $value = $this->_getTableappDataFieldValue($item,$keyField);
                $real_value = $this->_getValueFromValueItem($value);

                if($is_value_unique){
                    $ret[$real_value] = $new_item;
                }
                else{
                    if(!$ret[$real_value]){
                        $ret[$real_value] = array();
                    }

                    $ret[$real_value][] = $new_item;
                }
            }
            else{
                $ret[] = $new_item;
            }
        }

        return $ret;
    }

    static function _setValueFromValueItem(&$data ,$valueItem){
        if (!empty($data['key']))
        {
            $type_en = SmartappService::$FieldTypeEn[$data["value"]["type"]];

            if($type_en == 'table'){   //表格
                $data['value']['table'] = $valueItem;
                unset($data['value']['table_tpl']);
            }
            else if(self::_isSingleValueType($type_en)){
                if (is_array($valueItem['value']) && count($valueItem['value'])!=0){
                    $data['value']['value'] = $valueItem['value'][0];
                    $data['value']['value_name'] = $valueItem['value_name'][0];
                    $data['value']['values'] = array($valueItem['value'][0]);
                }
                else if(isset($valueItem['value'])){
                    $data['value']['value'] = $valueItem['value'];
                    $data['value']['value_name'] = $valueItem['value_name'];
                    $data['value']['values'] = array($valueItem['value']);
                }
            }
            else{
                if (is_array($valueItem['value']))
                {
                    $data['value']['values'] = $valueItem['value'];
                }
                else if(isset($valueItem['value']))
                {
                    $data['value']['values'] = array($valueItem['value']);
                }
                else{
                    $data['value']['values'] = array();
                }
            }
        }
        else if (!empty($data['excel_key']))
        {
            foreach($valueItem as $ky => $val)
            {
                $data[$ky] = $val;
            }
        }
    }

    static function _isSingleSelectType($type_en){
        $singleTypeAry = array('radiobox','select');
        if(in_array($type_en,$singleTypeAry)){
            return true;
        }
        return false;
    }

    function _getValueFromValueItem($valueItem){
        $type_en = SmartappService::$FieldTypeEn[$valueItem["value"]["type"]];

        if(self::_isSingleValueType($type_en)){
            if(self::_isSingleSelectType($type_en)){
                return $valueItem['value']['values'][0];
            }
            else{
                return $valueItem['value']['value'];
            }
        }
        else{
            if(isset($valueItem['value']['values']))
            {
                return $valueItem['value']['values'];
            }
        }

        return '';
    }

    static function _isSingleValueType($type_en){
        $singleTypeAry = array('text','textarea','number','date','time','customer','salechance','project','member','task','saletarget','group','phone');
        if(self::_isSingleSelectType($type_en) || in_array($type_en,$singleTypeAry)){
            return true;
        }
        return false;
    }

    function _getTableappTemplateInst($templetId = '',$smartApp = false)
    {
        if(!$smartApp){
            $templetModel = $this->_getModel('Smartapp');
            $query = array(
                '_id' => new MongoId($templetId), //"56a1834ee419be2e2d8b4584"), //$templetId
            );
            $templetInstance = $templetModel->find($query);
            $templetInstanceCp = $templetInstance;
        }
        else{
            $templetInstanceCp = $smartApp;
        }


        //模板改造
        if(is_array($templetInstanceCp['excel_app']['user_list']))
        {
            $templetInstance['excel_app']['user_list'] = array();
            foreach($templetInstanceCp['excel_app']['user_list'] as $userData){  //block
                $blcKey = $this->_getUserBlockKey($userData);
                $userDataCp = $userData;

                if( is_array($userData['excel_content_list'])){  //content
                    $userDataCp['excel_content_list'] = array();
                    foreach($userData['excel_content_list'] as $contentItem){
                        $itemKey = $this->_getExcelContentItemKey($contentItem);
                        $userDataCp['excel_content_list'][$itemKey] = $contentItem;
                    }
                }

                $templetInstance['excel_app']['user_list'][$blcKey] = $userDataCp;
            }
        }

        return $templetInstance;
    }

    function _excelFieldTitleMapItemKey($indexAry,$appId, &$mapArr)
    {
        $mapArr = array();
        $templetModel = $this->_getModel('Smartapp');
        $query = array(
            '_id' => new MongoId($appId),
        );
        $templetInstance = $templetModel->find($query);

        //模板遍历,映射item_key和FieldTitle
        if(is_array($templetInstance['excel_app']['user_list']))
        {
            foreach($templetInstance['excel_app']['user_list'] as $userData){  //block
                if( is_array($userData['excel_content_list'])){  //contentItems
                    foreach($userData['excel_content_list'] as $contentItem){
                        $itemKey = $this->_getExcelContentItemKey($contentItem);
                        $index = $indexAry[$contentItem['name']];//array_search($contentItem['name'], $rowTitles);
                        $mapArr[strval($index)] = $itemKey;
                    }
                }
            }
        }
    }

    static function _updateField(&$itemData,$itemData_new, $filter_field)
    {
        foreach ($itemData_new as $k => $val)
        {
            if (in_array($k,$filter_field))
            {
                continue;
            }

            if ($k)
                $itemData[$k] = $val;
        }
    }

    static function _updateDetailField(&$detailData,$detailData_new, $product_name_key = 'product_name')
    {
        $tmpArr = array();
        foreach ($detailData as $item)
        {
            //可能里面有数字，导致merge的时候key不一致
            $prd_name = ' '.$item[$product_name_key];
            $tmpArr[$prd_name] = $item;
        }


        $newArr = array();
        foreach ($detailData_new as $item)
        {
            $prd_name = ' '.$item[$product_name_key];
            $newArr[$prd_name] = $item;
        }

        $detailData = array_values(array_merge($tmpArr,$newArr));
    }

    static function _updateDetailNestingField(&$detailData,$detailData_new, $product_name_key = 'product_name')
    {
        $tmpArr = array();
        foreach ($detailData as $item)
        {
            $prd_name = ' '.$item[$product_name_key];
            $tmpArr[$prd_name] = $item;
        }

        $newArr = array();
        foreach ($detailData_new as $item)
        {
            $prd_name = ' '.$item[$product_name_key];
            $newArr[$prd_name] = $item;
        }

        $toAddAry = array_diff_key($newArr,$tmpArr);
        $retAry = array();
        foreach($tmpArr as $key => $item){
            if(isset($newArr[$key])){
                $retAry[$key] = array_merge($tmpArr[$key],$newArr[$key]);
            }
        }
        foreach($toAddAry as $key=>$item){
            $retAry[$key]  = $item;
        }

        $detailData = array_values($retAry);
    }

    static function _mergeNestingField(&$detailData,$detailData_new, $product_name_key = 'product_name')
    {
        $tmpArr = array();
        foreach ($detailData as $item)
        {
            $prd_name = ' '.$item[$product_name_key];
            $tmpArr[$prd_name] = $item;
        }

        $newArr = array();
        foreach ($detailData_new as $item)
        {
            $prd_name = ' '.$item[$product_name_key];
            $newArr[$prd_name] = $item;
        }

        $toAddAry = array_diff_key($newArr,$tmpArr);
        $toAddAry2 = array_diff_key($tmpArr,$newArr);
        $retAry = array();
        foreach($tmpArr as $key => $item){
            if(isset($newArr[$key])){
                $retAry[$key] = array_merge($tmpArr[$key],$newArr[$key]);
            }
        }
        foreach($toAddAry as $key=>$item){
            $retAry[$key]  = $item;
        }

        foreach($toAddAry2 as $key=>$item){
            $retAry[$key]  = $item;
        }

        $detailData = array_values($retAry);
    }

    //比较日期大小 $date1>=$date2 返回true
    static function _isDateGte($date1, $date2)
    {
        if(strtotime($date1) >= strtotime($date2)) {
            return true;
        }

        return false;
    }

    //比较日期大小 $date1 <= $date2 返回true
    function _isDateLte($date1, $date2)
    {
        if(strtotime($date2) >= strtotime($date1)) {
            return true;
        }

        return false;
    }

    //取小日期
    function _minDate($date1, $date2)
    {
        if(strtotime($date1) >= strtotime($date2)) {
            return $date2;
        }

        return $date1;
    }

    //取大日期
    function _maxDate($date1, $date2)
    {
        if(strtotime($date1) >= strtotime($date2)) {
            return $date1;
        }

        return $date2;
    }

    //输入参数是string类型
    function _diffDate($date1,$date2){
        if(strtotime($date1)>strtotime($date2)){
            $tmp=$date2;
            $date2=$date1;
            $date1=$tmp;
        }

        $Y1 = date('Y',strtotime($date1));
        $m1 = date('m',strtotime($date1));
        $d1 = date('d',strtotime($date1));

        $Y2 = date('Y',strtotime($date2));
        $m2 = date('m',strtotime($date2));
        $d2 = date('d',strtotime($date2));

        $Y=$Y2-$Y1;
        $m=$m2-$m1;
        $d=$d2-$d1;
        if($d<0){
            $d+=(int)date('t',strtotime("-1 month $date2"));
            $m--;
        }
        if($m<0){
            $m+=12;
            $Y--;
        }
        return array('year'=>$Y,'month'=>$m,'day'=>$d);
    }

    static function _log_error($msg) {
        vendor ( 'log' );
        $conf = array (
            'timeFormat' => '%H:%M:%S',
            'lineFormat' => '%1$s %5$s %6$s %7$s %8$s [%3$s]: %4$s',
            'backtraceLevel' => 3
        );
        $file_name = "lvwu";
        $logger = &Log::singleton ( 'file', LOGS . DS . $file_name . date ( "Ymd" ) . '.log', '', $conf );
        try {
            $logger->log ( $msg, PEAR_LOG_ERR );
        } catch ( Exception $e ) {

        }
    }

    //取汉字的第一个字的首字母
    static function getFirstCharter($str)
    {
        if(empty($str)){return '';}
        $fchar=ord($str{0});
        if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
        $s1=iconv('UTF-8','gb2312',$str);
        $s2=iconv('gb2312','UTF-8',$s1);
        $s=$s2==$str?$s1:$str;
        $asc=ord($s{0})*256+ord($s{1})-65536;
        if($asc>=-20319&&$asc<=-20284) return 'A';
        if($asc>=-20283&&$asc<=-19776) return 'B';
        if($asc>=-19775&&$asc<=-19219) return 'C';
        if($asc>=-19218&&$asc<=-18711) return 'D';
        if($asc>=-18710&&$asc<=-18527) return 'E';
        if($asc>=-18526&&$asc<=-18240) return 'F';
        if($asc>=-18239&&$asc<=-17923) return 'G';
        if($asc>=-17922&&$asc<=-17418) return 'H';
        if($asc>=-17417&&$asc<=-16475) return 'J';
        if($asc>=-16474&&$asc<=-16213) return 'K';
        if($asc>=-16212&&$asc<=-15641) return 'L';
        if($asc>=-15640&&$asc<=-15166) return 'M';
        if($asc>=-15165&&$asc<=-14923) return 'N';
        if($asc>=-14922&&$asc<=-14915) return 'O';
        if($asc>=-14914&&$asc<=-14631) return 'P';
        if($asc>=-14630&&$asc<=-14150) return 'Q';
        if($asc>=-14149&&$asc<=-14091) return 'R';
        if($asc>=-14090&&$asc<=-13319) return 'S';
        if($asc>=-13318&&$asc<=-12839) return 'T';
        if($asc>=-12838&&$asc<=-12557) return 'W';
        if($asc>=-12556&&$asc<=-11848) return 'X';
        if($asc>=-11847&&$asc<=-11056) return 'Y';
        if($asc>=-11055&&$asc<=-10247) return 'Z';
        return $str{0};
    }

    static function isFirstCharterA2z($str)
    {
        if(empty($str)) return false;
        $fchar=ord($str{0});
        if($fchar>=ord('A')&&$fchar<=ord('z')) return true;
    }

    static function str_each_insert($str, $substr)
    {
        $a = self::mb_str_split($str);
        $startstr = '';
        for($j=0; $j<count($a)-1; $j++){
            $startstr .= $a[$j] . $substr;
        }
        return $startstr . $a[count($a)-1];
    }

    static function mb_str_split($str){
        return preg_split('/(?<!^)(?!$)/u', $str );
    }

    static function str_replace_once($needle, $replace, $haystack) {
        // Looks for the first occurence of $needle in $haystack
        // and replaces it with $replace.
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            // Nothing found
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }

    static function _subChEnStr($str, $width)
    {
        if (empty($str)) return '';

        if(mb_strwidth($str, 'utf8')>$width)
        {
            // 此处设定从0开始截取，取10个追加...，使用utf8编码
            // 注意追加的...也会被计算到长度之内
            return mb_strimwidth($str, 0, $width, '', 'utf8');
        }
        else
        {
            return $str;
        }
    }

    static function _fieldLenLimit(&$field,$len)
    {
        if (!empty($field)) {
            $field = self::_subChEnStr($field, $len);
        }
    }

    static function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    //混合排序（ex.未完成与完成项的混合列表排序,$query 未完成的查询条件，$query_b 完成的查询条件）
    static function _sortComposeItemsList(&$items,$model,$limit,$page,$sort,$fields,$query,$query_b)
    {
        if(count($items)==$limit){   //足够一页

        }
        else if(count($items)>0){  //不足一页
            $left = $limit-count($items);

            //用通过的补齐
            $left_items = $model->findAll($query_b, $fields, $sort, $left, 1);
            $items = array_merge($items,$left_items);
        }
        else{       //无当前页
            $needSignsCount = $model->findCount($query);
            if($needSignsCount == 0){
                $items = $model->findAll($query_b, $fields, $sort, $limit, $page);
            }
            else{
                //重新计算去掉待做后新的分页系数grass
                $left = $limit*$page-$needSignsCount;
                $lower = $left-$limit;
                $left_limit = $limit;
                for(;;$left_limit++)
                {
                    $lower_page = intval($lower/$left_limit+1);
                    if($left<$lower_page*$left_limit){
                        break;
                    }
                }
                $left_page = $lower_page;
                //通过的
                $items = $model->findAll($query_b, $fields, $sort, $left_limit, $left_page);
                //取出真正的items
                $items = array_slice($items,$lower-($left_page-1)*$left_limit,$limit);
            }
        }
    }

    static function toMongoIds($str_ids)
    {
        $mongo_ids = array();
        foreach ($str_ids as $id)
        {
            $mongo_ids[] = new MongoId($id);
        }
        return $mongo_ids;
    }

    function _getGroupUserRole($company_id, $group_id, $user_id)
    {
        $groupUserModel = loadModel('GroupUser', true);

        $query = array(
            'user_id'  => $user_id,
            'company_id' => $company_id,
            'group_id' => $group_id,
        );
        $groupUser = $groupUserModel->find($query);
        if ($groupUser)
        {
            return $groupUser['user_role'];
        }

        return 0;
    }

    function _getInGroups($company_id, $user_id)
    {
        //找到$userName所在的groups
        $groupUserModel = loadModel('GroupUser', true);

        $query = array(
            'user_id'  => $user_id,
            'company_id' => $company_id,
            'is_temp' => array('$nin' => array(1, '1')),
            'group_special' => array('$ne' => 'no_group'),
        );
        $fields = array('group_id');
        $groupUsers = $groupUserModel->findAll($query, $fields);

        $groupIds = array();
        if (is_array($groupUsers) && count($groupUsers) > 0) {
            foreach ($groupUsers as $item) {
                $groupIds[] = $item['group_id'];
            }


            $query = array(
                '_id'  => array('$in' => $groupIds),
            );
            $fields = array('_id','group_parents');
            $groupModel = loadModel('Group', true);
            $groups = $groupModel->findAll($query, $fields);
            return $groups;
        }
        return array();
    }

    function _getTopGroupWithUid($company_id, $user_id = false, $exclude_group_ids = array())
    {
        //找到$userName所在的groups
        $groupUserModel = loadModel('GroupUser', true);

        $query = array(
            'company_id' => $company_id,
            'is_temp' => array('$nin' => array(1, '1')),
            'group_special' => array('$ne' => 'no_group'),
        );
        if ($user_id)
        {
            $query['user_id'] = $user_id;
        }
        $fields = array('group_id');
        $groupUsers = $groupUserModel->findAll($query, $fields);

        $groupIds = array();
        if (is_array($groupUsers) && count($groupUsers) > 0)
        {
            if(count($groupUsers)==1){      //只有1个部门，就不进行排除
                $groupIds[] = $groupUsers[0]['group_id'];
            }
            else{
                foreach ($groupUsers as $item)
                {
                    if(!in_array($item['group_id'],$exclude_group_ids)){
                        $groupIds[] = $item['group_id'];
                    }
                }
            }


            $query = array(
                '_id'  => array('$in' => $groupIds),
            );
            $fields = array('_id','group_name','group_parents');
            $groupModel = loadModel('Group', true);
            $groups = $groupModel->findAll($query, $fields);

            if (is_array($groups) && count($groups) > 0)
            {
                $grpParntsCnt = 1000;
                $topGrp = '';
                //找出groups中最高层级的一个
                foreach ($groups as $group)
                {
                    if(isset($group['group_parents']) && is_array($group['group_parents']) ){
                        if (count($group['group_parents']) < $grpParntsCnt)
                        {
                            $grpParntsCnt = count($group['group_parents']);
                            $topGrp = $group;
                        }
                    }
                    else{
                        $topGrp = $group;
                        $grpParntsCnt = 0;
                    }
                }

                return $topGrp;
            }
        }

        return '';
    }

    /******************************************************************************************
     ** 业务功能函数集
    /******************************************************************************************/
    function _getEntryCompanyData($company_id){
        $query = array(
            'app_id'=>'56cbb132e419be8f078b456c',
            'company_id'=>$company_id,
            'deleted'=>array('$ne' => 1),
        );

        $ret = $this->_getTableappDataWithQuery($query);
        return $this->_formatTableAppArray($ret, 'item_688', array('item_688'=>'user_id','item_689'=>'entry_date','item_690'=>'job_type','item_899'=>'group_type'),true);
    }

    function _getMemberEntryInfo($company_id, $user_id)
    {
        $entry_array = $this->_getEntryCompanyData($company_id);
        foreach ($entry_array as $person) {
            if ($user_id == $person['user_id'])
            {
                return $person;
            }
        }

        return '';
    }

    function _getQueQinData($company_id, $date){

        $date_start = date('Y-m-01', strtotime($date));
        $date_end = date('Y-m-t 23:59:59', strtotime($date_start));

        $query = array(
            'app_id'=>'56a1834ee419be2e2d8b4584',
            'company_id'=>$company_id,
            'deleted'=>array('$ne' => 1),
            'user_list.block_236.excel_content_list.item_643.value.value'=>array('$gte'=>$date_start),
            'user_list.block_236.excel_content_list.item_644.value.value'=>array('$lte'=>$date_end),
            'user_list.block_251.excel_content_list.item_695.value.values'=>array('$all'=>array('有效')),
        );

        $ret = $this->_getTableappDataWithQuery($query);
        return $this->_formatTableAppArray($ret, 'item_701', array('item_701'=>'user_id','item_658'=>'type','item_696'=>'time_type','item_697'=>'work_time','item_698'=>'kaoqin_count'));
    }

    function _getCompanyAttendCfgData($user_id, $companyId)
    {

        vendor('companyatdrule');
        $companyAtdRuleService = new CompanyatdruleService();
        $atdCfgDataArr = $companyAtdRuleService->_getCompanyAtdRule($user_id,$companyId);

        if($atdCfgDataArr === false){
            $companyModel = $this->_getModel('Company');
            $querry = array(
                '_id' => $companyId
            );
            $companyData = $companyModel->find($querry,array(),array());
            $atdCfgData = $companyData['atd_config'];
            $atdCfgDataArr = (Array)json_decode($atdCfgData, true);
        }


        return $atdCfgDataArr;
    }


    //业务功能 —— '申请流程'同步数据到'缺勤申请'
    function _setFlowDataToQueQinData($flowData)
    {
        try
        {
            //获取模板
            $queQinAppId = '56a1834ee419be2e2d8b4584';
            $templetInst = $this->_getTableappTemplateInst($queQinAppId);

            //填值
            $leave_type = $flowData['item']['subtype'];
            $leave_start_date = $flowData['item']['stime'];
            $leave_end_date = $flowData['item']['etime'];
            $leave_length = $flowData['item']['duration'];

            $queQinType = $this->_getQueQinTypeWithFlowType($leave_type);
            $caoqinCunt = $this->_addKaoqinCount($leave_length);
            $caoqinCunt = strval($caoqinCunt);
            rtrim(floatval($leave_length),0);
            $leave_length = strval($leave_length);

            $fieldSetArr = array(
                'item_643' => array('value'=>$leave_start_date,'value_name'=>$leave_start_date), //leave_start_date
                'item_644' => array('value'=>$leave_end_date,'value_name'=>$leave_end_date),  //leave_end_date

                'item_647' => array('value'=>'请假申请自动生成','value_name'=>'请假申请自动生成'),  //'外出事项'

                'item_658' => array('value'=>$queQinType,'value_name'=>$queQinType),  //leave_type
                'item_662' => array('value'=>'已确认','value_name'=>'已确认'),
                'item_695' => array('value'=>'有效','value_name'=>'有效'),
                'item_696' => array('value'=>'天','value_name'=>'天'),
                'item_697' => array('value'=>$leave_length,'value_name'=>$leave_length),  //leave_length
                'item_698' => array('value'=>$caoqinCunt,'value_name'=>$caoqinCunt),
                'item_701' => array('value'=>$flowData['user_id'],'value_name'=>$flowData['nickname']),
                'block_236' =>array('excel_user_id' => $flowData['user_id']),
                'block_242'=> array('excel_user_id' => '520c2a00f5b7877176000003'), //固定审批人员
                'block_251'=> array('excel_user_id' => '55e3aa01e58a3a9f798b458c'), //固定审批人员
            );

            $queqinData = array(
                'company_id' => '533e571ce419be201a000000',//$flowData['company_id'],
                'app_id' => $queQinAppId,
                'user_id' => $flowData['user_id'],
                'nickname' => $flowData['nickname'],
                'comments_count' => new MongoInt64(0),
            );

            $queqinData['user_list'] = $templetInst['excel_app']['user_list'];
            $this->_setTableappDataFieldValueArray($queqinData, $fieldSetArr);

            //保存,将新数据添加到缺勤申请表中
            $tableAppModel = $this->_getModel(TableAppModel);
            $rslt = $tableAppModel->save($queqinData);
        }
        catch (Exception $e)
        {

        }
    }

    //业务功能 —— '请年假流程'同步数据到'缺勤申请'
    function _setYearLeaveFlowDataToQueQinData($flowData)
    {
        try
        {
            //获取模板
            $queQinAppId = '56a1834ee419be2e2d8b4584';
            $templetInst = $this->_getTableappTemplateInst($queQinAppId);

            //填值
            $leave_start_date = $flowData['custom_items']['0']['value']['value'];
            $leave_end_date = $flowData['custom_items']['1']['value']['value'];
            $leave_length = $flowData['custom_items']['2']['value']['value'];

            $queQinType = '年假';
            $caoqinCunt = $this->_addKaoqinCount($leave_length);
            rtrim(floatval($leave_length),0); //去掉尾0
            $leave_length = strval($leave_length);

            $fieldSetArr = array(
                'item_643' => array('value'=>$leave_start_date,'value_name'=>$leave_start_date), //leave_start_date
                'item_644' => array('value'=>$leave_end_date,'value_name'=>$leave_end_date),  //leave_end_date

                'item_647' => array('value'=>'请年假申请自动生成','value_name'=>'请年假申请自动生成'),  //'外出事项'

                'item_658' => array('value'=>$queQinType,'value_name'=>$queQinType),  //leave_type
                'item_662' => array('value'=>'已确认','value_name'=>'已确认'),
                'item_695' => array('value'=>'有效','value_name'=>'有效'),
                'item_696' => array('value'=>'天','value_name'=>'天'),
                'item_697' => array('value'=>$leave_length,'value_name'=>$leave_length),  //leave_length
                'item_698' => array('value'=>strval($caoqinCunt),'value_name'=>strval($caoqinCunt)),
                'item_701' => array('value'=>$flowData['user_id'],'value_name'=>$flowData['nickname']),
                'block_236' =>array('excel_user_id' => $flowData['user_id']),
                'block_242'=> array('excel_user_id' => '520c2a00f5b7877176000003'),
                'block_251'=> array('excel_user_id' => '55e3aa01e58a3a9f798b458c'),
            );

            $queqinData = array(
                'company_id' => '533e571ce419be201a000000',//$flowData['company_id'],
                'app_id' => $queQinAppId,
                'user_id' => $flowData['user_id'],
                'nickname' => $flowData['nickname'],
                'comments_count' => new MongoInt64(0),
            );

            $queqinData['user_list'] = $templetInst['excel_app']['user_list'];
            $this->_setTableappDataFieldValueArray($queqinData, $fieldSetArr);

            //保存,将新数据添加到缺勤申请表中
            $tableAppModel = $this->_getModel(TableAppModel);
            $rslt = $tableAppModel->save($queqinData);
        }
        catch (Exception $e)
        {

        }
    }

    //业务功能 —— xxxN,($owner_info预留在这里，主要是经销商导入的和负责的可见性如何处理)
    function _setDataToTableApp($user_info, $app_id, $fieldSetArr, &$dataId, $owner_uid = false, $smartApp = false, $smartAppUpdate = false, &$allDataMap = false)
    {
        try
        {
            $tableAppModel = $this->_getModel(TableAppModel);
            $user_id = $user_info['user_id'];

            //获取模板
            $templetInst = $this->_getTableappTemplateInst($app_id,$smartApp);

            if ($smartAppUpdate)
            {
                //填值
                $tableAppData = array(
                    'company_id' => $user_info['company_id'],
                    'app_id' => $app_id,
                    'user_id' => $user_info['user_id'],
                    'nickname' => $user_info['nickname'],
                    'owner_uids' => array ($user_info['user_id']),
                    'comments_count' => new MongoInt64(0),
                );

                if ($dataId){
                    $tableAppData['_id'] = $dataId;
                }

                $tableAppData['user_list'] = $templetInst['excel_app']['user_list'];
            }
            else
            {
                if ($dataId){
                    $tableAppData = false;
                    if($allDataMap && isset($allDataMap[$dataId])){
                        $tableAppData = $allDataMap[$dataId];
                    }

                    if(!$tableAppData){
                        $query = array('_id' => $dataId);
                        $tableAppData = $tableAppModel->find($query);
                    }
                  
                    //补充缺失的模版字段,否则_setTableappDataFieldValueArray会跳过不存在的字段
                    $this->_fixTableDataUnExsitField($tableAppData, $templetInst);
                }
                else{
                    //填值
                    $tableAppData = array(
                        'company_id' => $user_info['company_id'],
                        'app_id' => $app_id,
                        'user_id' => $user_info['user_id'],
                        'nickname' => $user_info['nickname'],
                        'owner_uids' => array ($user_info['user_id']),
                        'comments_count' => new MongoInt64(0),
                    );

                    $tableAppData['user_list'] = $templetInst['excel_app']['user_list'];
                }
            }

            if ($owner_uid)
            {
                $tableAppData['owner_uids'] = array ($owner_uid);
            }

            $this->_setTableappDataFieldValueArray($tableAppData, $fieldSetArr);

            $tableAppModel->fillTableDataUseSearchField($tableAppData); //保存前添加搜索用的key值grass

//            vendor('tableapp');
//            $tableAppService = new TableappService();
//            $tableAppService->__cachePosterUidsByAppId($app_id,$user_id);

            $rslt = $tableAppModel->save($tableAppData);
            if($rslt){
                if (!$dataId)
                {
                    $dataId = $tableAppModel->getLastInsertID();
                    $tableAppData['_id'] = $dataId;
                }

                return $tableAppData;
            }
        }
        catch (Exception $e)
        {

        }

        return false;
    }

    function _fixTableDataUnExsitField(&$data, $templetInst){

        if(!isset($templetInst['excel_app']['user_list'])){
            return;
        }


        if(empty($data['user_list'])){
            $data['user_list'] = $templetInst['excel_app']['user_list'];
            return;
        }

        foreach ($templetInst['excel_app']['user_list'] as $block_key=>$block){
            $data_block = &$data['user_list'][$block_key];
            
            if($data_block){
                $diff_item_keys = array_diff_key($block['excel_content_list'],$data_block['excel_content_list']);
                foreach ($diff_item_keys as $tmp_item_key => $tmp_item_data){
                    $data_block['excel_content_list'][$tmp_item_key] = $tmp_item_data;
                }
            }
        }
    }

    //年假有效起始日期
    function _beginDateForUseYearLeaves($entryDate, $specifyDate)
    {
        $Ys = date('Y', strtotime($specifyDate));

        $Y = date('Y', strtotime($entryDate));
        $m = date('m', strtotime($entryDate));
        $d = date('d', strtotime($entryDate));

        //入职超过2年的,有效起始日期都可以这样计算
        $curYearPonit = $Ys . '-' . $m . '-' . $d;
        if(strtotime($specifyDate)>strtotime($entryDate)) {
            $dif = $this->_diffDate($specifyDate, $entryDate);
            if ($dif['year'] >= 2)
            {
                if(strtotime($specifyDate)>=strtotime($curYearPonit))
                {
                    $beginDate = $curYearPonit;
                    return $beginDate;
                }
                else
                {
                    $beginDate = ($Ys-1) . '-' . $m . '-' . $d;
                    return $beginDate;
                }
            }
        }

        //入职不超过2年的,有效起始日期都可以这样计算
        $beginDate = ($Y+1) . '-' . $m . '-' . $d;
        return $beginDate;
    }

    //年假有效截止日期
    function _endDateForUseYearLeaves($entryDate, $specifyDate)
    {
        $Ys = date('Y', strtotime($specifyDate));
        $ms = date('m', strtotime($specifyDate));
        $ds = date('d', strtotime($specifyDate));

        $Y = date('Y', strtotime($entryDate));
        $m = date('m', strtotime($entryDate));
        $d = date('d', strtotime($entryDate));

        //入职超过2年的,有效截止日期都可以这样计算
        $curYearPonit = $Ys . '-' . $m . '-' . $d;
        if(strtotime($specifyDate)>strtotime($entryDate)) {
            $dif = $this->_diffDate($specifyDate, $entryDate);
            if ($dif['year'] >= 2)
            {
                if(strtotime($specifyDate) < strtotime($curYearPonit))
                {
                    $endDate = $curYearPonit;
                    $endDate = date("Y-m-d 23:59:59",strtotime("-1 day $endDate"));
                    return $endDate;
                }
                else
                {
                    $endDate = ($Ys+1) . '-' . $m . '-' . $d;
                    $endDate = date("Y-m-d 23:59:59",strtotime("-1 day $endDate"));
                    return $endDate;
                }
            }
        }

        //入职不超过2年的,有效截止日期都可以这样计算
        $endDate = ($Y+2) . '-' . $m . '-' . $d;
        $endDate = date("Y-m-d 23:59:59",strtotime("-1 day $endDate"));
        return $endDate;
    }

    //可享年假天数
    function _canHaveYearLeaveCnt($entryYearsCnt)
    {
        //(可自建应用查询，方便管理员配置)
        if ($entryYearsCnt < 1)
        {
            return 0;
        }
        else if ($entryYearsCnt < 3)
        {
            return 5;
        }
        else if ($entryYearsCnt < 5)
        {
            return 10;
        }
        else
        {
            return 15;
        }
    }

    //查询已使用年假天数
    function _getUsedYearLeaveDays($company_id, $entryInfo, $specifyDate)
    {
        //$entryInfo中各对象内部数据字段结构["56f490ede419be81358b4592"]=>array(3) {user_id entry_date job_type}
        if (!is_array($entryInfo)) return '';

        $entryArr = array();
        //一.查询一次,获取一堆数据
        //各成员年假周期所在时间段不同,查询的时候,年假有效起始日期最早的,作为查询起点,可保证数据无遗漏
        $stratPoint = $specifyDate;
        $endPoint = date('Y-m-t 23:59:59', strtotime($specifyDate));
        foreach ($entryInfo as $personEntryInfo) {
            $entryDate = $personEntryInfo['entrydate'];
            $yBgnDate = $this->_beginDateForUseYearLeaves($entryDate, $specifyDate);
            $yEndDate = $this->_endDateForUseYearLeaves($entryDate, $specifyDate);

            $stratPoint = $this->_minDate($stratPoint, $yBgnDate);
            $endPoint = $this->_maxDate($endPoint, $yEndDate);

            $personEntryInfo['yBgnDate'] = $yBgnDate;
            $personEntryInfo['yEndDate'] = $yEndDate;
            array_push($entryArr,$personEntryInfo);
        }

        $date_start = $stratPoint;
        $date_end = $endPoint;

        $query = array(
            'app_id'=> QueQinApp_id,
            'company_id'=>$company_id,
            'deleted'=>array('$ne' => 1),
            'user_list.block_236.excel_content_list.item_643.value.value'=>array('$gte'=>$date_start),
            'user_list.block_236.excel_content_list.item_644.value.value'=>array('$lte'=>$date_end),
            'user_list.block_236.excel_content_list.item_658.value.values'=>array('$all'=>array('年假')),
            'user_list.block_251.excel_content_list.item_695.value.values'=>array('$all'=>array('有效')),
        );

        $ret = $this->_getTableappDataWithQuery($query);
        $yearLeaveData = $this->_formatTableAppArray($ret, 'item_701', array('item_701'=>'user_id','item_658'=>'type','item_643'=>'start_time','item_696'=>'time_type','item_697'=>'time_len'));

        //二.从上步骤获得的数据中筛选计算得出结果数据
        $nianJiaArray = array();
        foreach ($entryArr as $personEntryInfo) {
            $userId = $personEntryInfo['user_id'];
            $usedDayCnt = 0;
            if (empty($yearLeaveData[$userId]))
            {
                $nianJiaArray[$userId][NianJia_usedDays] = $usedDayCnt;
                continue;
            }

            $nianJiaArray[$userId][NianJia_usedDaysDetail] = array();
            foreach ($yearLeaveData[$userId] as $usedNianjiaItem)
            {
                //如果请的年假是在周期内的,累加
                if ($this->_isDateGte($usedNianjiaItem['start_time'], $personEntryInfo['yBgnDate'])
                    && $this->_isDateLte($usedNianjiaItem['start_time'], $personEntryInfo['yEndDate']))
                {
                    if($usedNianjiaItem['time_type']== TimeType_Day){
                        $usedDayCnt += floatval($usedNianjiaItem['time_len']);
                    }
                    else if($usedNianjiaItem['time_type']== TimeType_Hour){
                        $usedDayCnt += floatval($usedNianjiaItem['time_len']) / 7.5;
                    }

                    unset($usedNianjiaItem['user_id']);
                    array_push($nianJiaArray[$userId][NianJia_usedDaysDetail],$usedNianjiaItem);
                }
            }
            $nianJiaArray[$userId][NianJia_usedDays] = $usedDayCnt;
        }

        return $nianJiaArray;
    }


    private function _addKaoqinCount($leave_length)
    {
        $caoqinCunt = $leave_length *2;
        rtrim($caoqinCunt,0);
        return $caoqinCunt;
    }

    private function _getQueQinTypeWithFlowType($flowType){
        $queqintypes = array (
            0 => '因公外勤',
            1 => '因病缺勤',
            2 => '因私缺勤',
            3 => '补打卡',
            4 => '年假',
            5 => '带薪假',
        );

        $queQinType = '';
        switch ($flowType)
        {
            case 1:
                $queQinType = $queqintypes[2];
                break;
            case 2:
                $queQinType = $queqintypes[1];
                break;
            case 3:
                $queQinType = $queqintypes[5];
                break;
            case 4:
                $queQinType = $queqintypes[5];
                break;
            case 5:
                $queQinType = $queqintypes[4];
                break;
            case 6:
                $queQinType = $queqintypes[5];
                break;
            case 7:
                $queQinType = $queqintypes[5];
                break;
            case 8:
                $queQinType = $queqintypes[5];
                break;
        }

        return $queQinType;
    }

    private function _getUserBlockKey($userData)
    {
        foreach ($userData as $key => $item) {
            if ($key === 'excel_key') {
                if (!empty($item)) {
                    return $item;
                }
            }
        }

        return '';
    }

    private function _getExcelContentItemKey($ecItem)
    {
        foreach ($ecItem as $contentKey=> $contentItem)
        {
            if($contentKey === 'key') {
                return $contentItem;
            }
        }

        return '';
    }

    private function _getExcelContentItemName($ecItem)
    {
        foreach ($ecItem as $contentKey=> $contentItem)
        {
            if($contentKey === 'name') {
                return $contentItem;
            }
        }

        return '';
    }

    //获取公司工作月报
    function _getUserWorkTable($company_id,$date)
    {
        $date_start = $date_end = false;
        $this->_datePeriod($date,$date_start,$date_end);
        $add_today = false;

        if (!$date || substr($date, 0, 7) == date('Y-m')) {
            $add_today = true;
        }

        $workModel = $this->_getModel(WorkingModel);
        $query = array(
            'company_id' => $company_id,
            'type' => 'work',
            'date' => array('$gte' => $date_start)
        );
        if (!$add_today) {
            $query = array(
                'company_id' => $company_id,
                'type' => 'work',
                'date' => array(
                    '$gte' => $date_start,
                    '$lte' => $date_end,
                )
            );
        }

        $data = $workModel->findAll($query, array(), array('start_time' => -1));

        $now = time();
        $res = array();

        if ($data) {
            foreach ($data as &$dobj) {
                if (empty($dobj['end'])) {
                    if ($now >= $dobj['pre_end']) {
                        $dobj['end'] = date('H:i', $dobj['pre_end']);
                        $dobj['cost_time'] = ceil(($dobj['pre_end'] - $dobj['start_time']) / 60);
                        $cost_time = $dobj['cost_time'];
                    } else {
                        $cost_time = ceil(($now - $dobj['start_time']) / 60);
                    }
                } else {
                    $cost_time = $dobj['cost_time'];
                }
                if (empty($res[$dobj['user_id']])) {
                    $res[$dobj['user_id']] = array(
                        'user_id' => $dobj['user_id'],
                        'nickname' => $dobj['nickname'],
                        'worked_hour' => round($cost_time / 60, 1)
                    );

                } else {
                    $res[$dobj['user_id']]['worked_hour'] += round($cost_time / 60, 1);
                }
            }
        }
        $configModel = $this->_getModel(WorkTimeConfigModel);
        $query = array(
            'company_id' => $company_id,
            'month' => date('Y-m', strtotime($date_start))
        );
        $oldConfig = $configModel->find($query);
        if (empty($oldConfig)) {
            $oldConfig = array(
                'work_day' => 22,
                'min_hour' => 7,
                'max_hour' => 7.5
            );
        }
        foreach ($res as &$res_obj) {
            $res_obj['min_hour'] = $oldConfig['min_hour'] * $oldConfig['work_day'];
            $res_obj['work_day'] = $oldConfig['work_day'];
            $res_obj['max_hour'] = $oldConfig['max_hour'] * $oldConfig['work_day'];
            $res_obj['distance'] = 0;
            if ($res_obj['worked_hour'] < $res_obj['min_hour']) {
                $res_obj['distance'] = round($res_obj['worked_hour'] - $res_obj['min_hour'], 1);
            }
            if ($res_obj['worked_hour'] > $res_obj['max_hour']) {
                $res_obj['distance'] = round($res_obj['worked_hour'] - $res_obj['max_hour'], 1);
            }
        }

        $res['work_day'] = $oldConfig['work_day'];
        return $res;
    }

    function _getDutyInfoList($company_id, $date)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $date_start = $date_end = '';
        $this->_datePeriod($date, $date_start, $date_end);
        $date_start = strtotime($date_start);
        $date_end = strtotime($date_end);

        $workAtdModel = $this->_getModel('WorkAttendance');

        $query = array(
            'company_id' => $company_id,
            'date' => array(
                '$gte' => $date_start,
                '$lte' => $date_end,
            )
        );

        $sort = array(
            'user_id'=> 1,
            'date' => 1,
            'time' => 1,
        );

        $workAtdListArr = $workAtdModel->findAll($query, array(), $sort);
        $workAtdList = array();
        foreach ($workAtdListArr as $item) {
            if (isset($workAtdList[$item['user_id']])) {
                $workAtdList[$item['user_id']][] = $item;
            } else {
                $workAtdList[$item['user_id']] = array($item);
            }
        }

        return $workAtdList;
    }

    static function sendNotice($module, $company_id, $target_uid, $content = false, $msg_type = false, $opts = array())
    {
        if (empty($company_id) || !$target_uid || !$content || empty($module))
            return false;

        $type = "{$module}_notice";
        $msg = array(
            'user_id' => '52396ae0f5b787a72000000c',
            'nickname' => '客户小梦',
            'company_id' => $company_id,
            'p2p_another_uid' => $target_uid,
            'type' => $type,
            'content' => $content,
            'params' => array(
                'to_company_id'=> $company_id,
                'sub_type' => $msg_type ? $msg_type : $type,
            ),
            'created' => date('Y-m-d H:i:s')
        );
        if (!empty($opts)) {
            $msg['params'] = array_merge($msg['params'], $opts);
        }

        require_once 'taskbase_service.php';
        $taskbaseService = new TaskbaseService();
        $taskbaseService->_senMsgToGroupOrP2PNotTask($msg, $mid);
    }

    /**
     * Description:快速排序主流程函数
     */
    static function quickSortProcess(&$array, $begin, $end, $key = false)
    {
        if(empty($key)){
            return;
        }

        $low = $begin;
        $high = $end;

        #同时移动low和high,low找比$arr[$start]大的元素,high找比$arr[$start]小的元素
        #交换大小元素位置,知道low=high
        while ($low != $high) {
            while ($array[ $low ][ $key ] <= $array[ $begin ][ $key ] && $low != $high) {
                ++$low;
            }
            while ($array[ $high ][ $key ] >= $array[ $begin ][ $key ] && $low != $high) {
                --$high;
            }
            $temp = $array[ $low ];
            $array[ $low ] = $array[ $high ];
            $array[ $high ] = $temp;
        }

        #如果low和high指向的元素小于$arr[$start],交换$arr[$start]和这个元素
        #否则交换$arr[$start]和low指向的前一个元素,然后进入递归
        if ($low != $begin && $array[ $low ][ $key ] > $array[ $begin ][ $key ]) $low--;
        $temp = $array[ $low ];
        $array[ $low ] = $array[ $begin ];
        $array[ $begin ] = $temp;

        #递归中止条件是切分后的部分只剩下一个元素
        if ($low - 1 > $begin) self::quickSortProcess($array, $begin, $low - 1, $key);
        if ($low + 1 < $end) self::quickSortProcess($array, $low + 1, $end, $key);
    }

    /******************************************************************************************
     ** Test模块
    /******************************************************************************************/
//    function test()
//    {
//        /*$rowTitles = array('维修站名称','负责人','电话','地址','类型','经销商',);
//        $app_id = '582aa09fe58a3ad4288b45fe';
//        $this->_excelFieldTitleMapItemKey($rowTitles,$app_id, $mapArr);
//        var_dump($mapArr);*/
//        //$file = TemplatesPath . "test_bytes.xlsx";//"633c1795_bytes进销存预测.xlsx";  276f85d6_进.xlsx
//        //$res = readExcel2($file);
//
//        //CommonService::_log_error($res);
//        //var_dump($_SERVER['HTTP_HOST']);
//        echo 'start';
//
//        $sale_chance = array (
//            '_id' => "589ac8d44dbf6653008b456e",
//            'company_id' => '58218b47e58a3ad23f8b463a',
//            'creator_uid' => '58228598e419be29538b45a9',
//            'creator_nickname' => '李莎',
//            'name' => '测试',
//            'name_en' => 'CeShi',
//            'comments_count' => new MongoInt64(0),
//            'is_miss' => new MongoInt64(0),
//            'created_at' => new MongoInt64(1486483200),
//            'complete_date' => '2017-02-08 15:30:08',
//            'customer_id' => '58786acf4dbf6609008b4601',
//            'customer_name' => 'Polo',
//            'estimated_deal_date' => new MongoInt64(253402185600),
//            'real_deal_date' => new MongoInt64(253402185600),
//            'estimated_deal_sum' => new MongoInt64(0),
//            'real_deal_sum' => new MongoInt64(0),
//            'sale_source' => '商机分享',
//            'sale_phase_key' => '签订合同',
//            'share_sale_chance_id' => '589ac7234dbf66ff018b4570',
//            'sale_phase_value' => new MongoInt64(100),
//            'customer_type' => '经销商',
//            'channel' => 'VS',
//            'owner' => '58228598e419be29538b45a9',
//            'owner_name' => '李莎',
//            'products' =>
//                array (
//                ),
//            'created' => '2017-02-08 15:29:24',
//            'modified' => '2017-02-08 15:29:24',
//            'appr_info' =>
//                array (
//                    'file_ids' =>
//                        array (
//                            0 => '589ac8fb4dbf6653008b456f',
//                        ),
//                ),
//            'appr_user' =>
//                array (
//                    'user_id' => '',
//                    'status' => new MongoInt64(1),
//                ),
//        );
//
//
//        vendor('sharesalechance');
//        ShareSalechanceService::_addUpdateShareWeixinMsg($sale_chance);
//    }
//
//    function testLocation($atdLat, $atdLng)
//    {
//        $cfgLongitude = 104.056731;
//        $cfgLatitude = 30.586036;
//
//        $rslt = $this->_getDistance($cfgLatitude,$cfgLongitude, $atdLat, $atdLng);
//        var_dump($rslt);
//    }
//
//    function testGetEntryCompanyData()
//    {
//        $data = $this->_getEntryCompanyData('533e571ce419be201a000000');
//        var_dump($data);
//    }
//
//    function testQueQin()
//    {
//        $test = 'hello world;Hello everyone';
//        var_dump($test);
//
//        $this->_log_error(json_encode($test));
//        $company_id = '533e571ce419be201a000000';
//        $dateStart = '2016-02-01';
//
//        //获取缺勤记录表
//        $queqinData = $this->_getQueQinData($company_id,$dateStart);
//        var_dump($queqinData);
//    }
//
//    function testgetUserWorkTable()
//    {
//        $company_id = '533e571ce419be201a000000';
//        $dateStart = '2016-02-01';
//
//        $workingTimeData = $this->_getUserWorkTable($company_id,$dateStart);
//        var_dump($workingTimeData);
//    }
//
//    function testGetDutyInfoList()
//    {
//        $company_id = '533e571ce419be201a000000';
//        $dateStart = '2016-02-01';
//
//        $dutyData = $this->_getDutyInfoList($company_id,$dateStart);
//        var_dump($dutyData);
//    }
//
//    function testgetMonthStartAndEnd()
//    {
//        $date = '2016-02-01';
//        $value = getMonthStartAndEnd($date);
//        var_dump($value);
//    }
//
//    function testGetCompanyAttendCfgData()
//    {
//        $company_id = '533e571ce419be201a000000';
//
//        $atdCfgData = $this->_getCompanyAttendCfgData($company_id);
//
//        var_dump($atdCfgData);
//    }
//
//    function  testDelQueqinItem()
//    {
//        $id = false;
//        extract($_POST);
//        $tableAppModel = $this->_getModel(TableAppModel);
//        $queqinId = new MongoId($id);
//        $rslt = $tableAppModel->del($queqinId);
//        var_dump($rslt);
//    }
//
//
//    function testSetFlowDataToQueQinData()
//    {
//        ///test 参数----------------------------------------------
//        $flowQuery = array(
//            '_id' => new MongoId("56d5701de419be1c738b4578"),
//        );
//        $flowModel = $this->_getModel('CompanyFlow');
//
//        $flowData = $flowModel->find($flowQuery);
//        ///------------------------------------------------------
//
//        $this->_setFlowDataToQueQinData($flowData);
//    }
//
//
//    function testCheckLoginInfo()
//    {
//        $user_id = $token = $company_id = false;
//        extract($_POST);
//        $user_info = array();
//        if (!$this->userService->checkTokenUser($user_id, $token, $err, $user_info)) {
//            return array(
//                RETURN_RESULT => RESULT_FALSE,
//                RETURN_ERRORCODE => ERROR_USER_TOKEN_ERROR,
//                RETURN_MSG => "ERROR_USER_TOKEN_ERROR"
//            );
//        }
//
//        if (!$this->companyService->__isCompanyUser($user_id, $company_id))
//            return array(
//                RETURN_RESULT => RESULT_FALSE,
//                RETURN_ERRORCODE => 'ERROR_NOT_COMPANY_MEMBER'
//            );
//    }

}

