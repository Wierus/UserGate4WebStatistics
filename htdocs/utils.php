<?php

require_once 'settings.php';

function getIP() {
	if (isset($_GET['ip'])) {
		return ip2long($_GET['ip']);
	}
	return ip2long($_SERVER['REMOTE_ADDR']);
}

function getSortItem() {
	if (isset($_GET['sortItem'])) {
		if (($_GET['sortItem'] == 'TIME_BEGIN') || ($_GET['sortItem'] == 'BYTES_RECV') || ($_GET['sortItem'] == 'BYTES_SENT') || ($_GET['sortItem'] == 'DURATION')) {
			return $_GET['sortItem'];
		}
	}
	return 'TIME_BEGIN';
}

/*
	mktime($hour, $minute, $second, $month, $day, $year)
	j | ���� ������ ��� ������� �����             | ��  1 �� 31
	n | ���������� ����� ������ ��� ������� ����� | ��  1 �� 12
	d | ���� ������ � �������� ������             | �� 01 �� 31
	m | ���������� ����� ������ � �������� ������ | �� 01 �� 12
	Y | ���������� ����� ����, 4 �����            | �������: 1999, 2003
*/

// ������ ������� ���
function getStartTimeForDate($year, $month, $day) {
	return mktime(0, 0, 0, $month, $day, $year);
}

// ��������� ������� ���
function getFinishTimeForDate($year, $month, $day) {
	return mktime(0, 0, -1, $month, $day + 1, $year);
}

function getStartTime() {
	if (isset($_POST['startDay'])) {
		return getStartTimeForDate($_POST['startYear'], $_POST['startMonth'], $_POST['startDay']);
	}
	$nowTime = time();
	return getStartTimeForDate(date('Y', $nowTime), date('n', $nowTime), date('j', $nowTime));
}

function getFinishTime() {
	if (isset($_POST['startDay'])) {
		return getFinishTimeForDate($_POST['finishYear'], $_POST['finishMonth'], $_POST['finishDay']);
	}
	$nowTime = time();
	return getFinishTimeForDate(date('Y', $nowTime), date('n', $nowTime), date('j', $nowTime));
}

function getNextTime($currentTime) {
	return getStartTimeForDate(date('Y', $currentTime), date('n', $currentTime), date('j', $currentTime) + 1);
}

function getDateTime() {
	$time = isset($_GET['dateTime']) ? (int)($_GET['dateTime']) : time();
	return getStartTimeForDate(date('Y', $time), date('n', $time), date('j', $time));
}

function getIntervals($startTime, $finishTime) {
	$intervals = array();
	$currentTime = $startTime;
	$nextTime = getNextTime($currentTime);
	$completed = false;
	while (true) {
		if ($nextTime > $finishTime) {
			$nextTime = $finishTime;
		}
		if ($nextTime == $finishTime) {
			$completed = true;
		}
		if (!$completed) {
			$intervals[] = array($currentTime, $nextTime - 1);
			$currentTime = $nextTime;
			$nextTime = getNextTime($currentTime);
		}
		else {
			$intervals[] = array($currentTime, $nextTime);
			return $intervals;
		}
	}
}

function getResourceNameByResourceID($resourceID, $resourceResult) {
	mssql_data_seek($resourceResult, 0);
	while ($row = mssql_fetch_array($resourceResult)) {
		if ($row['RESOURCE_ID'] == $resourceID) {
			return $row['RESOURCE_NAME'];
		}
	}
}

function printSelectTimeForm($startTime, $finishTime) {
	echo '<form method="post" action="">';
	echo '<table class="SelectTimeForm" align="center">';
	echo '<tr>';
	echo '<td>��:</td>';
	echo '<td>';
	printSelectTimeElement('startDay',   MinDay,   MaxDay,   date('j', $startTime));
	printSelectTimeElement('startMonth', MinMonth, MaxMonth, date('n', $startTime));
	printSelectTimeElement('startYear',  MinYear,  MaxYear,  date('Y', $startTime));
	echo '</td>';
	echo '<td>��:</td>';
	echo '<td>';
	printSelectTimeElement('finishDay',   MinDay,   MaxDay,   date('j', $finishTime));
	printSelectTimeElement('finishMonth', MinMonth, MaxMonth, date('n', $finishTime));
	printSelectTimeElement('finishYear',  MinYear,  MaxYear,  date('Y', $finishTime));
	echo '</td>';
	echo '</tr>';
	echo '</table>';
	echo '<p class="Button"><input type="submit" value="����������" /></p>';
	echo '</form>';
}

function printSelectTimeElement($name, $minValue, $maxValue, $selectedValue) {
	echo '<select name="' . $name . '">';
	for ($i = $minValue; $i < $maxValue + 1; $i++) {
		if ((strcmp($name, 'startMonth') == 0) || (strcmp($name, 'finishMonth') == 0)) {
			$value = getMonthName($i);
		}
		else {
			$value = $i;
		}
		if ($i == $selectedValue) {
			echo '<option selected="selected" value="' . $i . '">' . $value . '</option>';
		}
		else {
			echo '<option value="' . $i . '">' . $value . '</option>';
		}
	}
	echo '</select>';
}

function getMonthName($index) {
	switch ($index) {
		case  1: {
			return '������';
			break;
		}
		case  2: {
			return '�������';
			break;
		}
		case  3: {
			return '�����';
			break;
		}
		case  4: {
			return '������';
			break;
		}
		case  5: {
			return '���';
			break;
		}
		case  6: {
			return '����';
			break;
		}
		case  7: {
			return '����';
			break;
		}
		case  8: {
			return '�������';
			break;
		}
		case  9: {
			return '��������';
			break;
		}
		case 10: {
			return '�������';
			break;
		}
		case 11: {
			return '������';
			break;
		}
		case 12: {
			return '�������';
			break;
		}
	}
}

function printFormatInt($value) {
	$s = (string)$value;
	$length = strlen($s);
	$rest = $length;
	$count = 0;
	while ($rest >= 3) {
		$rest -= 3;
		$count++;
	}
	$result = '';
	$index = 0;
	for ($i = 0; $i < $rest; $i++) {
		$result .= $s{$index};
		$index++;
	}
	for ($i = 0; $i < $count; $i++) {
		$result .= ' ' . $s{$index} . $s{$index + 1} . $s{$index + 2};
		$index += 3;
	}
	return $result;
}

function printHeaderTable() {
	echo '<table cellspacing="0" cellpadding="0" border="0" width="100%">';
	echo '<tr>';
	echo '<td valign="top"><img src="/images/title_stat.gif" alt="" width="572" height="72" valign="top" /></td>';
	echo '<td width="100%">';
	echo '<table cellspacing="0" cellpadding="0" border="0" width="100%" height="82">';
	echo '<tr>';
	echo '<td bgcolor="#FFFFFF" width="100%" height="1"><img src="/images/spacer.gif" alt="" height="1" width="1" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td bgcolor="#3292F3" width="100%"><img src="/images/spacer.gif" alt="" width="1" height="70" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td bgcolor="#0066FF" width="100%" height="1"><img src="/images/spacer.gif" alt="" height="1" width="1" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td class="text"><img src="/images/spacer.gif" alt="" height="10" width="1" /></td>';
	echo '</tr>';
	echo '</table>';
	echo '</td>';
	echo '<td valign="center"><a href="http://%%PROXY_HOME%%/"><img src="/images/title3.gif" alt="" width="190" height="82" valign="bottom" border="0" /></a></td>';
	echo '</tr>';
	echo '</table>';
}

function printFooterTable() {
	echo '<table cellspacing="0" cellpadding="0" border="0" width="100%">';
	echo '<tr>';
	echo '<td width="100%">';
	echo '<table cellspacing="0" cellpadding="0" border="0" width="100%">';
	echo '<tr>';
	echo '<td width="100%"><img src="/images/spacer.gif" alt="" width="1" height="10" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td bgcolor="#0066FF" width="100%"><img src="/images/spacer.gif" alt="" width="1" height="1" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td bgcolor="#3292F3" width="100%" align="left" valign="center" class="iform" align="center">';
	echo '<b>Copyright &copy; 2002-2006 by eSafeLine</b><img src="/images/spacer.gif" width="1" height="13">';
	echo '</td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td bgcolor="#3292F3" width="100%" align="left" valign="center" class="iform" align="center"><img src="/images/spacer.gif" width="1" height="7"></td>';
	echo '</tr>';
	echo '</table>';
	echo '</td>';
	echo '<td valign="top"><img src="/images/footer.gif" width="190" height="33"></td>';
	echo '</tr>';
	echo '</table>';
}

?>
