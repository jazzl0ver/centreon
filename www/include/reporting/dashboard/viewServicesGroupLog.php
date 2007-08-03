<?
/**
Oreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
Developped by : Julien Mathis - Romain Le Merlus - Cedrick Facon

The Software is provided to you AS IS and WITH ALL FAULTS.
OREON makes no representation and gives no warranty whatsoever,
whether express or implied, and without limitation, with regard to the quality,
safety, contents, performance, merchantability, non-infringement or suitability for
any particular or intended purpose of the Software found on the OREON web site.
In no event will OREON be liable for any direct, indirect, punitive, special,
incidental or consequential damages however they may arise and even if OREON has
been previously advised of the possibility of such damages.

For information : contact@oreon-project.org
*/

	if (!isset($oreon))
		exit;

	#
	## init
	#
	$totalAlert = 0;
	$day = date("d",time());
	$year = date("Y",time());
	$month = date("m",time());
	$today_start = mktime(0, 0, 0, $month, $day, $year);
	$today_end = time();
	$tt = 0;
	$start_date_select = 0;
	$end_date_select = 0;
	$path = "./include/reporting/dashboard";

	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl, "");
	$tpl->assign('o', $o);
	require_once './class/other.class.php';
	require_once './include/common/common-Func.php';
	require_once('simple-func.php');
	require_once('reporting-func.php');
	include("./include/monitoring/log/choose_log_file.php");

	# LCA
	/*
	$lcaHostByName = getLcaHostByName($pearDB);
	$lcaHGByName = getLcaHostByName($pearDB);
	$lcaHostByID = getLcaHostByID($pearDB);
	$lcaHoststr = getLCAHostStr($lcaHostByID["LcaHost"]);
*/
	#
	## Selectioned ?
	#		
	isset ($_GET["servicegroup"]) ? $mservicegroup = $_GET["servicegroup"] : $mservicegroup = NULL;
	isset ($_POST["servicegroup"]) ? $mservicegroup = $_POST["servicegroup"] : $mservicegroup = $mservicegroup;

	#
	## Select form part 1
	#
	$formservicegroup = new HTML_QuickForm('formHost', 'post', "?p=".$p);

	#
	## period selection
	#
	$period = (isset($_POST["period"])) ? $_POST["period"] : "today"; 
	$period = (isset($_GET["period"])) ? $_GET["period"] : $period;

	if($mservicegroup)	{
		$end_date_select = 0;
		$start_date_select= 0;
		if($period == "customized") {
			$end = (isset($_POST["end"])) ? $_POST["end"] : NULL;
			$end = (isset($_GET["end"])) ? $_GET["end"] : $end;
			$start = (isset($_POST["start"])) ? $_POST["start"] : NULL;
			$start = (isset($_GET["start"])) ? $_GET["start"] : $start;
			getDateSelect_customized($end_date_select, $start_date_select, $start,$end);
			$formservicegroup->addElement('hidden', 'end', $end);
			$formservicegroup->addElement('hidden', 'start', $start);
		}
		else {
			getDateSelect_predefined($end_date_select, $start_date_select, $period);
			$formservicegroup->addElement('hidden', 'period', $period);
		}
		$servicegroup_id = getMyservicegroupID($mservicegroup);
		$sd = $start_date_select;
		$ed = $end_date_select;

		#
		## database log
		#
		$sbase = array();
		$Tup = NULL;
		$Tdown = NULL;
		$Tunreach = NULL;
		$Tnone = NULL;
		getLogInDbForServicesGroup($sbase, $pearDB, $pearDBO, $servicegroup_id, $start_date_select, $end_date_select, $gopt, $today_start, $today_end);
	}

	#
	## Select form part 2
	#
	$res =& $pearDB->query("SELECT sg_name FROM servicegroup where sg_activate = '1' ORDER BY sg_name");

	$servicegroup = array();
	$servicegroup[""] = "";
	while ($res->fetchInto($sg)){
			$servicegroup[$sg["sg_name"]] = $sg["sg_name"];
	}
	$selHost =& $formservicegroup->addElement('select', 'servicegroup', $lang["m_dashboardServiceGroup"], $servicegroup, array("onChange" =>"this.form.submit();"));
	if (isset($_POST["servicegroup"])){
		$formservicegroup->setDefaults(array('servicegroup' => $_POST["servicegroup"]));
	}else if (isset($_GET["servicegroup"])){
		$formservicegroup->setDefaults(array('servicegroup' => $_GET["servicegroup"]));
	}

	#
	## Time select
	#
	$periodList = array();
	$periodList[""] = "";
	$periodList["today"] = $lang["today"];
	$periodList["yesterday"] = $lang["yesterday"];
	$periodList["thisweek"] = $lang["thisweek"];
	$periodList["last7days"] = $lang["last7days"];
	$periodList["thismonth"] = $lang["thismonth"];
	$periodList["last30days"] = $lang["last30days"];
	$periodList["lastmonth"] = $lang["lastmonth"];
	$periodList["thisyear"] = $lang["thisyear"];
	$periodList["lastyear"] = $lang["lastyear"];
	$periodList["customized"] = $lang["m_customizedPeriod"];

	$formPeriod = new HTML_QuickForm('FormPeriod', 'post', "?p=".$p);
	$selHost =& $formPeriod->addElement('select', 'period', $lang["m_predefinedPeriod"], $periodList);

	isset($mservicegroup) ? $formPeriod->addElement('hidden', 'servicegroup', $mservicegroup) : NULL;
	$formPeriod->addElement('hidden', 'timeline', "1");

	$formPeriod->addElement('header', 'title', $lang["m_if_custom"]);
	$formPeriod->addElement('text', 'start', $lang["m_start"]);
	$formPeriod->addElement('button', "startD", $lang['modify'], array("onclick"=>"displayDatePicker('start')"));
	$formPeriod->addElement('text', 'end', $lang["m_end"]);
	$formPeriod->addElement('button', "endD", $lang['modify'], array("onclick"=>"displayDatePicker('end')"));
	$sub =& $formPeriod->addElement('submit', 'submit', $lang["m_view"]);
	$res =& $formPeriod->addElement('reset', 'reset', $lang["reset"]);

	if($period == "customized") {
		$formPeriod->setDefaults(array('start' => date("m/d/Y", $start_date_select)));
		$formPeriod->setDefaults(array('end' => date("m/d/Y", $end_date_select)));
	}


	#
	## ressource selected
	#
	$today_ok = 0;
	$today_warning = 0;
	$today_unknown = 0;
	$today_critical = 0;
	$today_OKnbEvent = 0;
	$today_UNKNOWNnbEvent = 0;
	$today_WARNINGnbEvent = 0;
	$today_CRITICALnbEvent = 0;
	
	if($mservicegroup){
		$tpl->assign('infosTitle', $lang["m_duration"] . Duration::toString($end_date_select - $start_date_select));
		$tpl->assign('servicegroup_name', $mservicegroup);
		#
		## today log for xml timeline
		#
		$today_ok = 0 + $sbase["average"]["today"]["Tok"];
		$today_warning = 0 + $sbase["average"]["today"]["Twarning"];
		$today_unknown = 0 + $sbase["average"]["today"]["Tunknown"];
	
		$today_OKnbEvent = 0 + $sbase["average"]["today"]["Tok"];
		$today_UNKNOWNnbEvent = 0 + $sbase["average"]["today"]["Tunknown"];
		$today_WARNINGnbEvent = 0 + $sbase["average"]["today"]["Twarning"];
		$today_CRITICALnbEvent = 0 + $sbase["average"]["today"]["Tcritical"];

		$tab_log = array();
		$day = date("d",time());
		$year = date("Y",time());
		$month = date("m",time());
		$startTimeOfThisDay = mktime(0, 0, 0, $month, $day, $year);
		$tab_svc_list_average = array();
		$tab_svc_list_average = array();
		$tab_svc_list_average["PTOK"] = 0;
		$tab_svc_list_average["PAOK"] = 0;
		$tab_svc_list_average["PTW"] = 0;
		$tab_svc_list_average["PAW"] = 0;
		$tab_svc_list_average["PTU"] = 0;
		$tab_svc_list_average["PAU"] = 0;
		$tab_svc_list_average["PTC"] = 0;
		$tab_svc_list_average["PAC"] = 0;
		$tab_svc_list_average["PTN"] = 0;
		$tab_svc_list_average["PKTOK"] = 0;
		$tab_svc_list_average["PKTW"] = 0;
		$tab_svc_list_average["PKTU"] = 0;
		$tab_svc_list_average["PKTC"] = 0;
		$tab_svc_list_average["nb_svc"] = 0;

		$tab_hosts = array();
		$day_current_start = 0;
		$day_current_end = time() + 1;
		$time = time();

		#
		## calculate resume
		#
		$tab_resume = array();
		$tab = array();
		$timeTOTAL = $end_date_select - $start_date_select;	
		$Tok = $sbase["average"]["Tok"];
		$Twarning = $sbase["average"]["Twarning"];
		$Tunreach = $sbase["average"]["Tunknown"];
		$Tcritical = $sbase["average"]["Tcritical"];
		$Tnone = $timeTOTAL - ($Tok + $Twarning + $Tunreach + $Tcritical);
		if($Tnone <= 1)
		$Tnone = 0;	
		$tab["state"] = $lang["m_UpTitle"];
		$tab["time"] = Duration::toString($Tok);
		$tab["timestamp"] = $Tok;
		$tab["pourcentTime"] = round($Tok/($timeTOTAL+1)*100,2) ;
		$tab["pourcentkTime"] = round($Tok/($timeTOTAL-$Tnone+1)*100,2). "%";
		$tab["nbAlert"] = $sbase["average"]["OKnbEvent"];
		$tab["style"] = "class='ListColCenter' style='background:" . $oreon->optGen["color_ok"]."'";
		$tab_resume[0] = $tab;
		$tab["state"] = $lang["m_CriticalTitle"];
		$tab["time"] = Duration::toString($Tcritical);
		$tab["timestamp"] = $Tcritical;
		$tab["pourcentTime"] = round($Tcritical/$timeTOTAL*100,2);
		$tab["pourcentkTime"] = null;
		$tab["nbAlert"] = $sbase["average"]["CRITICALnbEvent"];
		$tab["style"] = "class='ListColCenter' style='background:" . $oreon->optGen["color_critical"]."'";
		$tab_resume[1] = $tab;
		$tab["state"] = $lang["m_DownTitle"];
		$tab["time"] = Duration::toString($Twarning);
		$tab["timestamp"] = $Twarning;
		$tab["pourcentTime"] = round($Twarning/$timeTOTAL*100,2);
		$tab["pourcentkTime"] = round($Twarning/($timeTOTAL-$Tnone+1)*100,2)."%";
		$tab["nbAlert"] = $sbase["average"]["WARNINGnbEvent"];
		$tab["style"] = "class='ListColCenter' style='background:" . $oreon->optGen["color_warning"]."'";
		$tab_resume[2] = $tab;
		$tab["state"] = $lang["m_UnreachableTitle"];
		$tab["time"] = Duration::toString($Tunreach);
		$tab["timestamp"] = $Tunreach;
		$tab["pourcentTime"] = round($Tunreach/$timeTOTAL*100,2);
		$tab["pourcentkTime"] = round($Tunreach/($timeTOTAL-$Tnone+1)*100,2)."%";
		$tab["nbAlert"] = $sbase["average"]["UNKNOWNnbEvent"];
		$tab["style"] = "class='ListColCenter' style='background:" . $oreon->optGen["color_unknown"]."'";
		$tab_resume[3] = $tab;
		$tab["state"] = $lang["m_PendingTitle"];
		$tab["time"] = Duration::toString($Tnone);
		$tab["timestamp"] = $Tnone;
		$tab["pourcentTime"] = round($Tnone/$timeTOTAL*100,2);
		$tab["pourcentkTime"] = null;
		$tab["nbAlert"] = "";
		$tab["style"] = "class='ListColCenter' style='background:#cccccc'";
		$tab_resume[4] = $tab;

		#
		## calculate tablist
		#
		$i=0;
		foreach($sbase as $svc_name => $tab)
		{
			if($svc_name != "average"){
				$tab_tmp = array();
				$tab_tmp["hostName"] = getMyHostName($tab["host_id"]);
				$tab_tmp["serviceName"] = getMyServiceName($tab["svc_id"]);
				$tab_tmp["svc_id"] = $tab["svc_id"];
				$tt = $end_date_select - $start_date_select;

				$tab_tmp["PtimeOK"] = round($tab["Tok"] / $tt *100,2);
				$tab_tmp["PtimeWARNING"] = round( $tab["Twarning"]/ $tt *100,2);
				$tab_tmp["PtimeUNKNOWN"] = round( $tab["Tunknown"]/ $tt *100,2);
				$tab_tmp["PtimeCRITICAL"] = round( $tab["Tcritical"]/ $tt *100,2);
				$tab_tmp["PtimeUNDETERMINATED"] = round( ( $tt - ($tab["Tok"] + $tab["Twarning"] + $tab["Tunknown"] + $tab["Tcritical"] ))  / $tt *100,2);

				$tmp_none = $tt - ($tab["Tok"] + $tab["Twarning"] + $tab["Tunknown"]);
				$tab_tmp["OKnbEvent"] = isset($tab["TokNBAlert"]) ? $tab["TokNBAlert"] : 0;
				$tab_tmp["WARNINGnbEvent"] = isset($tab["WARNINGnbEvent"]) ? $tab["WARNINGnbEvent"] : 0;
				$tab_tmp["UNKNOWNnbEvent"] = isset($tab["UNKNOWNnbEvent"]) ? $tab["UNKNOWNnbEvent"] : 0;
				$tab_tmp["CRITICALnbEvent"] = isset($tab["TcriticalNBAlert"]) ? $tab["TcriticalNBAlert"] : 0;

				$tab_tmp["PktimeOK"] = $tab["Tok"] ? round($tab["Tok"] / ($tt - $tmp_none) *100,2): 0;
				$tab_tmp["PktimeWARNING"] = $tab["Twarning"] ? round( $tab["Twarning"]/ ($tt - $tmp_none) *100,2):0;
				$tab_tmp["PktimeUNKNOWN"] =  $tab["Tunknown"] ? round( $tab["Tunknown"]/ ($tt - $tmp_none) *100,2):0;
				$tab_tmp["PktimeCRITICAL"] =  $tab["Tcritical"] ? round( $tab["Tcritical"]/ ($tt - $tmp_none) *100,2):0;

				$tab_tmp["PtimeOK"] = number_format($tab_tmp["PtimeOK"], 1, '.', '');
				$tab_tmp["PtimeWARNING"] = number_format($tab_tmp["PtimeWARNING"], 1, '.', '');
				$tab_tmp["PtimeUNKNOWN"] = number_format($tab_tmp["PtimeUNKNOWN"], 1, '.', '');
				$tab_tmp["PtimeCRITICAL"] = number_format($tab_tmp["PtimeCRITICAL"], 1, '.', '');

				$tab_tmp["PtimeUNDETERMINATED"] = number_format($tab_tmp["PtimeUNDETERMINATED"], 1, '.', '');
				$tab_tmp["PtimeUNDETERMINATED"] = ($tab_tmp["PtimeUNDETERMINATED"] < 0.1) ? 0.0 : $tab_tmp["PtimeUNDETERMINATED"];

				$tab_tmp["PktimeOK"] = number_format($tab_tmp["PktimeOK"], 1, '.', '');
				$tab_tmp["PktimeWARNING"] = number_format($tab_tmp["PktimeWARNING"], 1, '.', '');
				$tab_tmp["PktimeUNKNOWN"] = number_format($tab_tmp["PktimeUNKNOWN"], 1, '.', '');
				$tab_tmp["PktimeCRITICAL"] = number_format($tab_tmp["PktimeCRITICAL"], 1, '.', '');
	
				#
				## fill average svc table
				#
				$tab_svc_list_average["PTOK"] += $tab_tmp["PtimeOK"];
				$tab_svc_list_average["PAOK"]  += $tab_tmp["OKnbEvent"];
				$tab_svc_list_average["PTW"] += $tab_tmp["PtimeWARNING"];
				$tab_svc_list_average["PAW"] += $tab_tmp["WARNINGnbEvent"];
				$tab_svc_list_average["PTU"] += $tab_tmp["PtimeUNKNOWN"];
				$tab_svc_list_average["PAU"] += $tab_tmp["UNKNOWNnbEvent"];
				$tab_svc_list_average["PTC"] += $tab_tmp["PtimeCRITICAL"];
				$tab_svc_list_average["PAC"] += $tab_tmp["CRITICALnbEvent"];
				$tab_svc_list_average["PTN"] += $tab_tmp["PtimeUNDETERMINATED"];
				$tab_svc_list_average["PKTOK"] += $tab_tmp["PktimeOK"];
				$tab_svc_list_average["PKTW"]+= $tab_tmp["PktimeWARNING"];
				$tab_svc_list_average["PKTU"]+= $tab_tmp["PktimeUNKNOWN"];
				$tab_svc_list_average["PKTC"] += $tab_tmp["PktimeCRITICAL"];
				$tab_svc_list_average["nb_svc"]+= 1;


				$tab_svc[$i++] = $tab_tmp;
			}
		}

		#
		## calculate svc average
		#
		# Alert
		if($tab_svc_list_average["PAOK"] > 0)
		$tab_svc_list_average["PAOK"] = number_format($tab_svc_list_average["PAOK"] / $tab_svc_list_average["nb_svc"], 1, '.', '');
		if($tab_svc_list_average["PAW"] > 0)
		$tab_svc_list_average["PAW"] = number_format($tab_svc_list_average["PAW"] / $tab_svc_list_average["nb_svc"], 1, '.', '');
		if($tab_svc_list_average["PAU"] > 0)
		$tab_svc_list_average["PAU"] = number_format($tab_svc_list_average["PAU"] / $tab_svc_list_average["nb_svc"], 1, '.', '');
		if($tab_svc_list_average["PAC"] > 0)
		$tab_svc_list_average["PAC"] = number_format($tab_svc_list_average["PAC"] / $tab_svc_list_average["nb_svc"], 1, '.', '');
		# Time
		if($tab_svc_list_average["PTOK"] > 0)
		$tab_svc_list_average["PTOK"] = number_format($tab_svc_list_average["PTOK"] / $tab_svc_list_average["nb_svc"], 3, '.', '');
		if($tab_svc_list_average["PTW"] > 0)
		$tab_svc_list_average["PTW"] = number_format($tab_svc_list_average["PTW"] / $tab_svc_list_average["nb_svc"], 3, '.', '');
		if($tab_svc_list_average["PTC"] > 0)
		$tab_svc_list_average["PTC"] = number_format($tab_svc_list_average["PTC"] / $tab_svc_list_average["nb_svc"], 3, '.', '');
		if($tab_svc_list_average["PTU"] > 0)
		$tab_svc_list_average["PTU"] = number_format($tab_svc_list_average["PTU"] / $tab_svc_list_average["nb_svc"], 3, '.', '');
		if($tab_svc_list_average["PTN"] > 0)
		$tab_svc_list_average["PTN"] = number_format($tab_svc_list_average["PTN"] / $tab_svc_list_average["nb_svc"], 3, '.', '');

		# %
		if($tab_svc_list_average["PKTOK"] > 0)
		$tab_svc_list_average["PKTOK"] = number_format($tab_svc_list_average["PKTOK"] / $tab_svc_list_average["nb_svc"], 3, '.', '');
		if($tab_svc_list_average["PKTW"] > 0)
		$tab_svc_list_average["PKTW"] = number_format($tab_svc_list_average["PKTW"] / $tab_svc_list_average["nb_svc"], 3, '.', '');
		if($tab_svc_list_average["PKTC"] > 0)
		$tab_svc_list_average["PKTC"] = number_format($tab_svc_list_average["PKTC"] / $tab_svc_list_average["nb_svc"], 3, '.', '');
		if($tab_svc_list_average["PKTU"] > 0)
		$tab_svc_list_average["PKTU"] = number_format($tab_svc_list_average["PKTU"] / $tab_svc_list_average["nb_svc"], 3, '.', '');

		$start_date_select = date("d/m/Y (G:i:s)", $start_date_select);
		$end_date_select_save_timestamp =  $end_date_select;
		$end_date_select =  date("d/m/Y (G:i:s)", $end_date_select);
		$status = "";
		$totalTime = 0;
		$totalpTime = 0;
		$totalpkTime = 0;
	
		foreach ($tab_resume  as $tb){
			if($tb["pourcentTime"] >= 0)
				$status .= "&value[".$tb["state"]."]=".$tb["pourcentTime"];
			$totalTime += $tb["timestamp"];
			$totalpTime += $tb["pourcentTime"];
			$totalpkTime += $tb["pourcentkTime"];
		}
		$totalAlert = $sbase["average"]["UNKNOWNnbEvent"] + $sbase["average"]["WARNINGnbEvent"] + $sbase["average"]["OKnbEvent"] + $sbase["average"]["CRITICALnbEvent"];

	$tpl->assign('totalAlert', $totalAlert);
	$tpl->assign('totalTime', Duration::toString($totalTime));
	$tpl->assign('totalpTime', $totalpTime);
	$tpl->assign('totalpkTime', $totalpkTime);
	$tpl->assign('status', $status);
	$tpl->assign("tab_resume", $tab_resume);
	$tpl->assign("tab_svc_list_average", $tab_svc_list_average);
	$tpl->assign('infosTitle', $lang["m_duration"] . Duration::toString($tt));
	$tpl->assign('date_start_select', $start_date_select);
	$tpl->assign('date_end_select', $end_date_select);
	$tpl->assign('to', $lang["m_to"]);
	}

	if(isset($tab_svc))
	$tpl->assign("tab_svc", $tab_svc);

	$tpl->assign("tab_log", $tab_log);
	$tpl->assign('actualTitle', $lang["actual"]);
	$tpl->assign('period_name', $lang["m_period"]);
	$tpl->assign('style_ok', "class='ListColCenter' style='background:" . $oreon->optGen["color_ok"]."'");
	$tpl->assign('style_ok_alert', "class='ListColCenter' style='width: 25px; background:" . $oreon->optGen["color_ok"]."'");
	$tpl->assign('style_critical', "class='ListColCenter' style='background:" . $oreon->optGen["color_critical"]."'");
	$tpl->assign('style_critical_alert', "class='ListColCenter' style='width: 25px; background:" . $oreon->optGen["color_critical"]."'");
	$tpl->assign('style_warning' , "class='ListColCenter' style='background:" . $oreon->optGen["color_warning"]."'");
	$tpl->assign('style_warning_alert' , "class='ListColCenter' style='width: 25px; background:" . $oreon->optGen["color_warning"]."'");
	$tpl->assign('style_unknown' , "class='ListColCenter' style='background:" . $oreon->optGen["color_unknown"]."'");
	$tpl->assign('style_unknown_alert' , "class='ListColCenter' style='width: 25px; background:" . $oreon->optGen["color_unknown"]."'");
	$tpl->assign('style_undeterminated' , "class='ListColCenter' style='background:#ccccc'");
	$tpl->assign('style_undeterminated_alert' , "class='ListColCenter' style='width: 25px; background:#cccccc'");
	$tpl->assign('hostTitle', $lang["m_hostTitle"]);
	$tpl->assign('serviceTilte', $lang["m_serviceTilte"]);
	$tpl->assign("allTilte",  $lang["m_allTilte"]);
	$tpl->assign("averageTilte",  $lang["m_averageTilte"]);

	$tpl->assign('OKTitle', $lang["m_OKTitle"]);
	$tpl->assign('WarningTitle', $lang["m_WarningTitle"]);
	$tpl->assign('UnknownTitle', $lang["m_UnknownTitle"]);
	$tpl->assign('CriticalTitle', $lang["m_CriticalTitle"]);
	$tpl->assign('UndeterminatedTitle', $lang["m_PendingTitle"]);


	$tpl->assign('StateTitle', $lang["m_StateTitle"]);
	$tpl->assign('TimeTitle', $lang["m_TimeTitle"]);
	$tpl->assign('TimeTotalTitle', $lang["m_TimeTotalTitle"]);
	$tpl->assign('KnownTimeTitle', $lang["m_KnownTimeTitle"]);
	$tpl->assign('AlertTitle', $lang["m_AlertTitle"]);
	$tpl->assign('DateTitle', $lang["m_DateTitle"]);
	$tpl->assign('EventTitle', $lang["m_EventTitle"]);
	$tpl->assign('InformationsTitle', $lang["m_InformationsTitle"]);
	$tpl->assign('periodTitle', $lang["m_selectPeriodTitle"]);
	$tpl->assign('resumeTitle', $lang["m_hostResumeTitle"]);
	$tpl->assign('logTitle', $lang["m_hostLogTitle"]);
	$tpl->assign('svcTitle', $lang["m_hostSvcAssocied"]);

	$formPeriod->setDefaults(array('period' => $period));


	$tpl->assign('hostID', getMyHostID($mservicegroup));
	$color = array();
	$color["CRITICAL"] =  substr($oreon->optGen["color_critical"], 1);
	$color["OK"] =  substr($oreon->optGen["color_ok"], 1);
	$color["WARNING"] =  substr($oreon->optGen["color_warning"], 1);
	$color["UNKNOWN"] =  substr($oreon->optGen["color_unknown"], 1);
	$tpl->assign('color', $color);
	$renderer = new HTML_QuickForm_Renderer_ArraySmarty($tpl);
	$formPeriod->accept($renderer);
	$tpl->assign('formPeriod', $renderer->toArray());

	#Apply a template definition
	$renderer = new HTML_QuickForm_Renderer_ArraySmarty($tpl);
	$formservicegroup->accept($renderer);
	$tpl->assign('formservicegroup', $renderer->toArray());
	$tpl->assign('lang', $lang);
	$tpl->assign("p", $p);

	# For today in timeline
	$tt = 0 + ($today_end - $today_start);
	$today_pending = $tt - ($today_warning + $today_ok + $today_unknown);
	$today_pending = round(($today_pending/$tt *100),2);
	$today_ok = ($today_ok <= 0) ? 0 : round($today_ok / $tt *100,2);
	$today_warning = ($today_warning <= 0) ? 0 : round($today_warning / $tt *100,2);
	$today_unknown = ($today_unknown <= 0) ? 0 : round($today_unknown / $tt *100,2);
	$today_pending = ($today_pending < 0.1) ? "0" : $today_pending;

	if($mservicegroup)	{
		$color = substr($oreon->optGen["color_ok"],1) .':'.
		 		 substr($oreon->optGen["color_warning"],1) .':'.
		 		 substr($oreon->optGen["color_unknown"],1) .':'. 
		 		 substr($oreon->optGen["color_unknown"],1).':CCCCCC';
		$today_var = '&svc_group_id='.$servicegroup_id.'&today_ok='.$today_ok . '&today_critical='.$today_critical . '&today_warning='.$today_warning.'&today_unknown='.$today_unknown. '&today_pending=' . $today_pending;
		$today_var .= '&today_OKnbEvent='.$today_OKnbEvent.'&today_UNKNOWNnbEvent='.$today_UNKNOWNnbEvent.'&today_WARNINGnbEvent='.$today_WARNINGnbEvent.'&today_CRITICALnbEvent='.$today_CRITICALnbEvent;
		$type = 'ServiceGroup';
		$host_id = $servicegroup_id;
		include('ajaxReporting_js.php');
	}
	else {
			?>
			<SCRIPT LANGUAGE="JavaScript">
			function initTimeline() {
				;
			}
			</SCRIPT>
			<?
		}
	$tpl->display("template/viewServicesGroupLog.ihtml");
?>