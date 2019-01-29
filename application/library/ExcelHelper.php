<?php
namespace app\Library;
use think\Loader;
use PHPExcel_IOFactory;
use PHPExcel;
/**
 * PHP快速操作类
 */
class ExcelHelper{
    public function __construct() {
        //这些文件需要下载phpexcel，然后放在vendor文件里面。具体参考上一篇数据导出。
        vendor("phpexcel.PHPExcel");
        vendor("phpexcel.PHPExcel.IOFactory");
//        Vendor('PHPExcel.PHPExcel');
//        Vendor('PHPExcel.IOFactory');
//         vendor("PHPExcel.PHPExcel.Writer.Excel5");
//         vendor("PHPExcel.PHPExcel.Writer.Excel2007");
    }
    /**
     * 导出Excel
     * @param  [string] $expTitle     [文件名]
     * @param  [array] $cellName   [首行标题名称]
     * @param  [array] $data  [Excel内容]
     * @return [文件流]               [Excel文件下载]
     */
    public function exportExcel($fileName='',$cellTitle,$data){
        $fileName = $fileName .'_'. date('Ymd');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($cellTitle);
        $dataNum = count($data);

        $objPHPExcel = new PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        for($i=0;$i<$cellNum;$i++){
            //设置值
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $cellTitle[$i]);
            //设置居中
            $objPHPExcel->getActiveSheet()->getStyle($cellName[$i].'1')->applyFromArray([
                    'alignment' =>['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER]]
            );
            //设置边框
            $objPHPExcel->getActiveSheet()->getStyle($cellName[$i].'1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        }

        $keys=array_keys($data[0]);

        //设置自动宽start
        $arrWidth=[];
        for($i=0;$i<$cellNum;$i++)
        {
            $maxWidth=0;
            $newMaxWidth=0;

            for($c=0;$c<mb_strlen($cellTitle[$i]);$c++)
            {
                if($c<mb_strlen($cellTitle[$i]))
                {
                    $isStrlen = $this -> isStrlen(mb_strstr($cellTitle[$i],$c,1) );
                    if($isStrlen){
                        $newMaxWidth += 1.2;
                    }else{
                        $newMaxWidth += 2;
                    }
                }
            }
            $maxWidth=$newMaxWidth;

            for($j=0;$j<$dataNum;$j++)
            {
                $newMaxWidth=0;
                if($j<=50)
                {
//                   var_dump($data[$j][$keys[$i]]);exit();
                    for($c=0;$c<mb_strlen($data[$j][$keys[$i]]);$c++)
                    {
                        if($c<mb_strlen($data[$j][$keys[$i]]) )
                        {
                            $isStrlen = $this -> isStrlen(mb_strstr($data[$j][$keys[$i]],$c,1));
                            if($isStrlen){
                                $newMaxWidth += 1.2;
                            }else{
                                $newMaxWidth += 2;
                            }
                        }
                    }
                    if($newMaxWidth>$maxWidth)
                    {
                        $maxWidth=$newMaxWidth;
                    }
                }
            }
            $maxWidth=$maxWidth;
            // $maxWidth=300;
            if($maxWidth>55){
                $maxWidth=55;
            }
            $arrWidth[]=$maxWidth;
        }

        for($i=0;$i<$cellNum;$i++){
            //自动适应宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$i])->setWidth($arrWidth[$i]);
        }

        //设置自动宽end

        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                //设置值
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2), $data[$i][$keys[$j]])->getColumnDimension($cellName[$j]);
//                $objActSheet->setCellValueExplicit('A5', '847475847857487584',PHPExcel_Cell_DataType::TYPE_STRING);
                //设置居中
                $objPHPExcel->getActiveSheet()->getStyle($cellName[$j].($i+2))->applyFromArray([
                        'alignment' =>['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER]]
                );
                //设置边框
                $objPHPExcel->getActiveSheet()->getStyle($cellName[$j].($i+2))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            }
        }

        //冻结表头
//       $objPHPExcel->getActiveSheet()->freezePane('A1');
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->freezePane('A2');

        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum].($dataNum+1))->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER);

        //清除缓冲区,避免乱码
        ob_end_clean();

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xlsx"');
        header("Content-Disposition:attachment;filename=$fileName.xlsx");

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 导入Execl数据
     * @param  [string] $file [文件路径]
     * @return [array]       [Excel数据]
     */
    public function importExecl($filePath,$fileName=''){

        if($fileName==''){
            $fileName = end(explode('\\', end(explode('/', $filePath))));
        }

        $pars=explode('.',$_FILES['file']['name']);
        $extensionType = $this -> GetFileType(end($pars));

        $objReader = PHPExcel_IOFactory::createReader($extensionType);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($filePath);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
        $excelData = array();
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $excelData[$row-1][] =(string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }
        return $excelData;
    }
    /**
     * [GetFileType 获取文件类型]
     * @param [string] $fileType [文件后缀名]
     */
    public function GetFileType($fileType)
    {
        $extensionType=null;
        switch($fileType){
            case 'xlsx':			//	Excel (OfficeOpenXML) Spreadsheet
            case 'xlsm':			//	Excel (OfficeOpenXML) Macro Spreadsheet (macros will be discarded)
            case 'xltx':			//	Excel (OfficeOpenXML) Template
            case 'xltm':			//	Excel (OfficeOpenXML) Macro Template (macros will be discarded)
                $extensionType = 'Excel2007';
                break;
            case 'xls':				//	Excel (BIFF) Spreadsheet
            case 'xlt':				//	Excel (BIFF) Template
                $extensionType = 'Excel5';
                break;
            case 'ods':				//	Open/Libre Offic Calc
            case 'ots':				//	Open/Libre Offic Calc Template
                $extensionType = 'OOCalc';
                break;
            case 'slk':
                $extensionType = 'SYLK';
                break;
            case 'xml':				//	Excel 2003 SpreadSheetML
                $extensionType = 'Excel2003XML';
                break;
            case 'gnumeric':
                $extensionType = 'Gnumeric';
                break;
            case 'htm':
            case 'html':
                $extensionType = 'HTML';
                break;
            case 'csv':
                break;
            default:
                break;
        }
        return $extensionType;
    }
    /**
     * @param  [array] $field [字段与名称]
     * @param  [array] $data  [execlHelper处理过数据]
     * @return [type]        [description]
     */
    public static function convert_data($field,$data)
    {
        $result=[];
        foreach ($data as $key => $value) {
            if($key==0){
                continue;
            }
            $new_row=[];
            foreach ($value as $key2 => $value2) {
                foreach ($field as $f_key => $f_val) {
                    if($data[0][$key2]==$f_val){
                        $new_row[$f_key]=$value2;
                    }
                }
            }
            $result[]=$new_row;
        }
        return $result;
    }
    //判读是否为汉字
    public static function isChinese($str)
    {

//         if (preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$str)) {
//             return true;
//         } else {
//             return false;
//         }
    }
    //判断长度是否小于或等于1
    public static function isStrlen($str)
    {
        if(strlen($str)<=1){
            return true;
        }else{
            return false;
        }
    }

}