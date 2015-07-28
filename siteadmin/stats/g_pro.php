<?
$rpath = "../../";
require_once($_SERVER['DOCUMENT_ROOT'] . "/classes/stdf.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/classes/country.php");

$idMonth = date('m'); //��������� �����
$idYear = date('Y'); //��������� ���
$iBarWidth = (is_numeric(InGet('y')) && !is_numeric(InGet('m')))?30:20; //������ ������
$iHeight = 20; //������ �����
$hbl = 30;
$sFont = ABS_PATH.'/siteadmin/account/Aricyr.ttf';
$DB = new DB('master');

function getOP($date_from='2006-10-10', $date_to='now()', $bYear=false) {
    global $iMonth,$iYear, $DB;
	if ($bYear) {
		$sql = "SELECT COUNT(*) as cnt, to_char(from_date,'MM') FROM orders WHERE (payed=true AND orders.active=true AND ordered=true AND from_date >= '".$date_from."' AND from_date < '".$date_to."' GROUP BY to_char(from_date,'MM') ORDER BY to_char(from_date,'MM')";
        //from_date < now() AND from_date+to_date > now())";
	}
	else {
        $res = array();
        $n = 0;
        $iMaxDays = $iMax = ($bYear)?12:date('t',mktime(0,0,0, $iMonth, 1, $iYear));
        $d = preg_replace("/-\d{1,2}$/","",$date_from);
        for($i=1; $i<=$iMaxDays; $i++) {
            if($i<10) { $ii = '0'.$i; } else { $ii = $i; }
		    $sql = "SELECT COUNT(*) as cnt,
                    SUM(u.role::bit(1)::integer) as cnt_emp,
                    SUM(1 - u.role::bit(1)::integer) as cnt_frl
                    FROM orders
                    LEFT JOIN users u ON u.uid = orders.from_id
                    WHERE payed=true AND orders.active=true AND ordered=true AND from_date < '".$d.'-'.$ii."' AND from_date+to_date+COALESCE(freeze_to, '0')::interval > '".$d.'-'.$ii."'";
            $result = $DB->row($sql);
            //$r[0]['cnt'] = $DB->val($sql);
//echo $sql.'<br>';
            //if(isset($r[0]['cnt'])) {
            if($result) {
                $res[$n]['cnt'] = $result['cnt'];
                $res[$n]['cnt_frl'] = (int)$result['cnt_frl'];
                $res[$n]['cnt_emp'] = (int)$result['cnt_emp'];
                $res[$n]['_day'] = $i;
                $n++;
            }
        }
	}
    
    // ������ ��� �����
    /*
    $s = 9000;
    $sm = 9300;
    foreach(range(0,31) as $k) {
        $cnt = rand($s, $sm);
        $cnt_frl = rand($s-($s/10), $cnt-($s/10));
        
        $res[$k] = array(
            'cnt' => $cnt,
            'cnt_frl' => $cnt_frl,
            'cnt_emp' => ( $cnt - $cnt_frl ),
            '_day'    => ($k+1)
        );
        
        if($k%3 == 0) {
            $s -= ($s/10);
            $sm -= ($s/10);
        }
    }*/
//	$res = pg_query(DBConnect(),$sql);
	return $res;
}

$bYear = false;
if (is_numeric(InGet('y'))) {
	if (is_numeric(InGet('m'))) {
		$date_from = InGet('y').'-'.InGet('m').'-1';
		$date_to = InGet('y').'-'.InGet('m').'-'.date('t',mktime(0,0,0, InGet('m')+1, null, InGet('y')));

		$iMonth = InGet('m');
		$iYear = InGet('y');
	}
	else {
		$date_from = InGet('y').'-1-1';
		$date_to   = (InGet('y')+1).'-01-01';
		$bYear = true;
		$iMonth = $idMonth;
		$iYear = InGet('y');
	}
}
else {
	//echo $idMonth.'<br>';
	//echo date('t',mktime(0,0,0, intval($idMonth), 1, intval($idYear)));
	$date_from = $idYear.'-'.$idMonth.'-1';
	$date_to = $idYear.'-'.$idMonth.'-'.date('t',mktime(0,0,0, intval($idMonth), 1, intval($idYear)));
	$iMonth = $idMonth;
	$iYear = $idYear;
}


$iMaxDays = $iMax = ($bYear)?12:date('t',mktime(0,0,0, $iMonth, 1, $iYear)); //���������� ������������� ���������� ����\������� � ������� ������\����
$iFMperPX = (!$bYear)?30:(30*10); //�������

$graphStyle[3]['text'] = '������������';
$graphStyle[2]['text'] = '����������';
$graphStyle[1]['text'] = '�����';
$graphStyle[3]['val'] = 'cnt_emp';
$graphStyle[2]['val'] = 'cnt_frl';
$graphStyle[1]['val'] = 'cnt';


for ($i=1; $i<=3; $i++) {
	for ($j=0; $j<=$iMaxDays; $j++) {
		$graphValues[$i][$j] = 0;
	}
}


$imgHeight = 0;
for ($i=1; $i<=count($graphStyle); $i++) {
	$res = getOP($date_from, $date_to, $bYear);
	$aTemp = $res;

    $value = $graphStyle[$i]['val'];
    
	if (isset($aTemp[0]['_day'])) {
		$graphStyle[$i]['max'] = $aTemp[0][$graphStyle[$i]['val']]/$iFMperPX;
//        $graphStyle[$i]['max'] = $aTemp[0]['cnt'];
		for ($j=0; $j<count($aTemp); $j++) {
			$iAmount = $aTemp[$j][$value]/$iFMperPX;
            $ii = $aTemp[$j][$value];
//            $iAmount = $aTemp[$j]['cnt'];
			if ($iAmount > $graphStyle[$i]['max']) {
				$graphStyle[$i]['max'] = $iAmount; //��������� ������������ ������ ����� �������
			}

			$graphValues[$i][$aTemp[$j]['_day']-1] = $iAmount;
			$graphValuesV[$i][$aTemp[$j]['_day']-1] = $ii;
		}
		$imgHeight += $graphStyle[$i]['max'];
	}
}
//print_r($graphValues2);
$k = 0; $graphStyle[0]['max'] = 0;
for ($i=0; $i<=$iMaxDays; $i++) {
	$iSumm = 0; $iSumm2 = 0; $ii = 0;
	for ($j=1; $j<count($graphValues); $j++) {
		if (isset($graphValues[$j][$i])) {
			$iSumm += $graphValues[$j][$i];
            $ii += $graphValuesV[$j][$i];
		}
	}


	$graphValues[0][$k] = $iSumm;
    $graphValuesV[0][$k] = $ii;
	if ($iSumm > $graphStyle[0]['max'])
	$graphStyle[0]['max'] = $iSumm;
	$k++;
}
//print_r($graphValues2);

$imgHeight += count($graphValues)*$hbl; //���������� ���������� � ������������ ������ �������
$imgWidth = $iMax*$iBarWidth+100;


$image=imagecreate($imgWidth, $imgHeight); //������� ������ � ������ ������������ ������ � ������.
imagecolorallocate($image, 255, 255, 255);

$graphStyle[1]['color'] = imagecolorallocate($image, 0, 0, 0); //�����
$graphStyle[2]['color'] = imagecolorallocate($image, 103, 135, 179); //����������
$graphStyle[3]['color'] = imagecolorallocate($image, 111, 177, 92); //������������



$colorWhite=imagecolorallocate($image, 255, 255, 255);
$colorGrey=imagecolorallocate($image, 192, 192, 192);
$colorDarkBlue=imagecolorallocate($image, 153, 153, 153);

for ($i=1; $i<count($graphValues); $i++) {
	//��������� ������ ������ ���������� �������
	if ($i > 1) {
		$iMaxHeight = $graphValues[$i-1][0];
		for ($k=1; $k<count($graphValues[$i-1]); $k++) {
			$iMaxHeight = ($graphValues[$i-1][$k] > $iMaxHeight)?$graphValues[$i-1][$k]:$iMaxHeight;
		}
		$iHeight += $iMaxHeight+$hbl; // +15 - ���������� ����� ���������
	}

	for ($j=0; $j<count($graphValues[$i]); $j++) {

		imageline($image, $j*$iBarWidth+2 + 100, $imgHeight-$iHeight, $j*$iBarWidth+$iBarWidth + 100, $imgHeight-$iHeight, $colorGrey);
		//if ($i==1) {
			$iz = ($j+1 > 9)?3.7:2.5;
			imagefttext($image, '7', 0, $j*$iBarWidth+round($iBarWidth/$iz) + 100, $imgHeight-$iHeight + 12, $colorDarkBlue, $sFont, $j+1);
		//}

		if ($graphValues[$i][$j]) {
			imagefilledrectangle($image, $j*$iBarWidth+2 + 100, ($imgHeight-$iHeight-round($graphValues[$i][$j])), ($j+1)*$iBarWidth + 100, $imgHeight-$iHeight, $graphStyle[$i]['color']);
			//������� ���������� FM
			$addD = ($i == 8)?2:1; ///���� �������, �� ��������� ����� �� 2
			$color = (!$i)?$graphStyle[$i]['color']:$colorDarkBlue;
            if($i!=0) {
			    imagefttext($image, '7', 0, $j*$iBarWidth + 100+2, $imgHeight-$iHeight-$graphValues[$i][$j]-2, $color, $sFont, round($graphValuesV[$i][$j]));
            } else {
                $iCount = 0;
                for($k=1; $k<count($graphValues2); $k++) {
                    $addD = ($k == 8)?2:1;
                    $iCount += round($graphValues2[$k][$j]/$addD);
                }
                imagefttext($image, '7', 0, $j*$iBarWidth + 100+2, $imgHeight-$iHeight-$graphValues[$i][$j]-2, $color, $sFont, round($graphValuesV[$i][$j]));
            }
		}
	}
	$iFontSizeTitle = 8;
	$aBox = imageftbbox($iFontSizeTitle, 0, $sFont,$graphStyle[$i]['text']);
	$width = abs($aBox[0]) + abs($aBox[2]);
	imagefttext($image, $iFontSizeTitle, 0, 90-$width, $imgHeight-$iHeight, $graphStyle[$i]['color'], $sFont, $graphStyle[$i]['text']);
}


$aMonthes[1] = '������';
$aMonthes[2] = '�������';
$aMonthes[3] = '����';
$aMonthes[4] = '������';
$aMonthes[5] = '���';
$aMonthes[6] = '����';
$aMonthes[7] = '����';
$aMonthes[8] = '������';
$aMonthes[9] = '��������';
$aMonthes[10] = '�������';
$aMonthes[11] = '������';
$aMonthes[12] = '�������';

$sString = 'PRO ������������';
imagefttext($image, '18', 0, 100, 20, $colorGrey, $sFont, $sString);

header("Pragma: no-cache");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sun, 1 Jan 1995 01:00:00 GMT"); // ��� �����-������ ����� ��������� ����
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // ��� ������� �������, ��� ��� ������ ������ �������
header("Content-type: image/png");
imagepng($image);
imagedestroy($image);

?>