<?php

 include(IA_ROOT.'/mpdf60/mpdf.php');

$mpdf=new mPDF('UTF-8','A4','','',15,15,44,15);
$mpdf->useAdobeCJK = true;
$mpdf->SetAutoFont(AUTOFONT_ALL);
$mpdf->SetDisplayMode('fullpage');
//$mpdf->watermark_font = 'GB';
//$mpdf->SetWatermarkText('中国水印',0.1);
$url = 'http://www.lexiangpingou.cn/';
$strContent = file_get_contents($url);

//print_r($strContent);die;
$mpdf->showWatermarkText = true;
$mpdf->SetAutoFont();
//$mpdf->SetHTMLHeader( '头部' );
//$mpdf->SetHTMLFooter( '底部' );
$mpdf->WriteHTML($strContent);
$mpdf->Output('ss.pdf');
//$mpdf->Output('tmp.pdf',true);
//$mpdf->Output('tmp.pdf','d');
//$mpdf->Output();
exit;
?>