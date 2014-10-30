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
$sortItem = getSortItem();
$startTime  = getDateTime();
$finishTime = getNextTime($startTime) - 1;
$totalBytesRecv = 0;
$totalBytesSent = 0;
$totalDuration  = 0;
mssql_connect(MSSQLServer, MSSQLLogin, MSSQLPassword);
mssql_select_db(MSSQLDatabase);
echo '<p class="Header">Лог за день (' . date('d.m.Y', $startTime) . ')</p>';
echo '<table class="StatisticsTable" align="center">';
echo '<thead>';
echo '<tr>';
echo '<td><a href="?dateTime=' . $startTime . '&sortItem=TIME_BEGIN">Время</td>';
echo '<td>Адрес</td>';
echo '<td><a href="?dateTime=' . $startTime . '&sortItem=BYTES_RECV">Получено</td>';
echo '<td><a href="?dateTime=' . $startTime . '&sortItem=BYTES_SENT">Отправлено</td>';
echo '<td><a href="?dateTime=' . $startTime . '&sortItem=DURATION">Длительность</td>';
echo '</tr>';
echo '</thead>';
$resourceQuery = '
	SELECT [RESOURCE_ID], [RESOURCE_NAME] FROM [RESOURCE] WHERE (
		[RESOURCE_ID] IN (
			SELECT [RESOURCE_ID] FROM [CONNECTIONS] WHERE (
				([TIME_BEGIN] BETWEEN ' . $startTime . ' AND ' . $finishTime . ') AND ([SRC_IP] = ' . $ip . ')
			)
		)
	)
';
$connectionsQuery = '
	SELECT [TIME_BEGIN], [RESOURCE_ID], [BYTES_RECV], [BYTES_SENT], ([TIME_END] - [TIME_BEGIN]) AS [DURATION] FROM [CONNECTIONS] WHERE (
		([TIME_BEGIN] BETWEEN ' . $startTime . ' AND ' . $finishTime . ') AND ([SRC_IP] = ' . $ip . ')
	)
	ORDER BY [' . $sortItem . ']
';
$resourceResult    = mssql_query($resourceQuery);
$connectionsResult = mssql_query($connectionsQuery);
echo '<tbody>';
while ($row = mssql_fetch_array($connectionsResult)) {
	$totalBytesRecv += $row['BYTES_RECV'];
	$totalBytesSent += $row['BYTES_SENT'];
	$totalDuration  += $row['DURATION'];
	echo '<tr>';
	echo '<td>' . gmdate('H:i:s', $row['TIME_BEGIN']) . '</td>';
	echo '<td style="text-align: left;">' . getResourceNameByResourceID($row['RESOURCE_ID'], $resourceResult) . '</td>';
	echo '<td>' . printFormatInt($row['BYTES_RECV']) . '</td>';
	echo '<td>' . printFormatInt($row['BYTES_SENT']) . '</td>';
	echo '<td>' . gmdate('H:i:s', $row['DURATION'])   . '</td>';
	echo '</tr>';
}
echo '</tbody>';
echo '<tfoot>';
echo '<tr>';
echo '<td>Всего</td>';
echo '<td>&nbsp;</td>';
echo '<td>' . printFormatInt($totalBytesRecv) . '</td>';
echo '<td>' . printFormatInt($totalBytesSent) . '</td>';
echo '<td>' . gmdate('H:i:s', $totalDuration)  . '</td>';
echo '</tr>';
echo '</tfoot>';
echo '</table>';
printFooterTable();
echo '</body>';
echo '</html>';

?>
