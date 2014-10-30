<?php

require_once 'utils.php';

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
echo '<html xmlns="http://www.w3.org/1999/xhtml">';
echo '<head>';
echo '<title>Статистика</title>';
echo '<meta http-equiv="content-type" content="text/html; charset=windows-1251" />';
echo '<link rel="stylesheet" type="text/css" href="styles.css" />';
echo '<link rel="stylesheet" type="text/css" href="style.css" />';
echo '</head>';
echo '<body bgcolor="#99CCFF" background="/images/background.gif" bottommargin="0" leftmargin="0" rightmargin="0" topmargin="0" marginheight="0" marginwidth="0">';
printHeaderTable();
echo '<table cellspacing="5" cellpadding="5" border="0" width="100%" height="80%">';
echo '<tr>';
echo '<td width="100%" valign="top">';
$ip = getIP();
if ($ip == ip2long(UG4Address)) {
	echo '<p class="Header">Подробная статистика недоступна, проверьте настройки подключения.</p>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';
	printFooterTable();
	echo '</body>';
	echo '</html>';
	return;
}
$startTime  = getStartTime();
$finishTime = getFinishTime();
$intervals = getIntervals($startTime, $finishTime);
$intervalsCount = count($intervals);
$totalBytesRecv = 0;
$totalBytesSent = 0;
$totalDuration  = 0;
printSelectTimeForm($startTime, $finishTime);
mssql_connect(MSSQLServer, MSSQLLogin, MSSQLPassword);
mssql_select_db(MSSQLDatabase);
echo '<p class="Header">Статистика по дням</p>';
echo '<table class="StatisticsTable" align="center">';
echo '<thead>';
echo '<tr>';
echo '<td>Дата</td>';
echo '<td>Получено</td>';
echo '<td>Отправлено</td>';
echo '<td>Длительность</td>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';
for ($i = 0; $i < $intervalsCount; $i++) {
	$query = '
		SELECT SUM([BYTES_RECV]) AS [BYTES_RECV], SUM([BYTES_SENT]) AS [BYTES_SENT], SUM([TIME_END] - [TIME_BEGIN]) AS [DURATION] FROM [CONNECTIONS] WHERE (
			([TIME_BEGIN] BETWEEN ' . $intervals[$i][0] . ' AND ' . $intervals[$i][1] . ') AND ([SRC_IP] = ' . $ip . ')
		)
	';
	$result = mssql_query($query);
	$row = mssql_fetch_array($result);
	$intervalBytesRecv = ($row['BYTES_RECV'] === null) ? 0 : $row['BYTES_RECV'];
	$intervalBytesSent = ($row['BYTES_SENT'] === null) ? 0 : $row['BYTES_SENT'];
	$intervalDuration  = ($row['DURATION']   === null) ? 0 : $row['DURATION'];
	if (($intervalBytesRecv == 0) && ($intervalBytesSent == 0) && ($intervalDuration == 0)) {
		// не отображать нулевые строки
		continue;
	}
	$totalBytesRecv += $intervalBytesRecv;
	$totalBytesSent += $intervalBytesSent;
	$totalDuration  += $intervalDuration;
	echo '<tr>';
	echo '<td><a href="log.php?dateTime=' . $intervals[$i][0] . '">' .  date('d.m.Y', $intervals[$i][0]) . '</a></td>';
	echo '<td>' . printFormatInt($intervalBytesRecv) . '</td>';
	echo '<td>' . printFormatInt($intervalBytesSent) . '</td>';
	echo '<td>' . gmdate('H:i:s', $intervalDuration) . '</td>';
	echo '</tr>';
}
echo '</tbody>';
echo '<tfoot>';
echo '<tr>';
echo '<td>Всего</td>';
echo '<td>' . printFormatInt($totalBytesRecv) . '</td>';
echo '<td>' . printFormatInt($totalBytesSent) . '</td>';
echo '<td>' . gmdate('H:i:s', $totalDuration) . '</td>';
echo '</tr>';
echo '</tfoot>';
echo '</table>';
echo '</td>';
echo '</tr>';
echo '</table>';
printFooterTable();
echo '</body>';
echo '</html>';

?>
