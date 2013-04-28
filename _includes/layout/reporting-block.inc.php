<?php
// /_includes/layout/reporting-block.inc.php
// 
// Domain Manager - A web-based application written in PHP & MySQL used to manage a collection of domain names.
// Copyright (C) 2010 Greg Chetcuti
// 
// Domain Manager is free software; you can redistribute it and/or modify it under the terms of the GNU General
// Public License as published by the Free Software Foundation; either version 2 of the License, or (at your
// option) any later version.
// 
// Domain Manager is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
// implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
// for more details.
// 
// You should have received a copy of the GNU General Public License along with Domain Manager. If not, please 
// see http://www.gnu.org/licenses/
?>
<script type="text/javascript">
<!--
function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}
//-->
</script>
Before running any reports you should <a href="<?=$web_root?>//system/update-conversion-rates.php">update the conversion rates</a>.<BR><BR><BR>
<div class="reporting-block-outer">
	<div class="reporting-block-left">
        <font class="subheadline">Domain Reports</font><BR><BR>
        <form name="reporting_menu_domain_form" method="post" action="<?=$PHP_SELF?>">
            <select name="reporting_menu_domains" onChange="MM_jumpMenu('parent',this,0)">
            <option value="<?=$web_root?>/reporting/"<?php if ($report_name == "") echo " selected" ?>>Click to select a Domain Report</option>
            <option value="<?=$web_root?>/reporting/domains/cost-breakdown-by-month.php"<?php if ($report_name == "domains-cost-breakdown-by-month") echo " selected" ?>>Cost Breakdown by Month</option>
            <option value="<?=$web_root?>/reporting/domains/registrar-fee-breakdown.php"<?php if ($report_name == "registrar-fee-breakdown") echo " selected" ?>>Registrar Fee Breakdown</option>
            <option value="<?=$web_root?>/reporting/domains/renewals.php"<?php if ($report_name == "domains-renewals") echo " selected" ?>>Renewal Report</option>
            </select>
		</form>
	</div>
	<div class="reporting-block-right">
        <font class="subheadline">SSL Certificate Reports</font><BR><BR>
        <form name="reporting_menu_domain_form" method="post" action="<?=$PHP_SELF?>">
            <select name="reporting_menu_ssl" onChange="MM_jumpMenu('parent',this,0)">
            <option value="<?=$web_root?>/reporting/"<?php if ($report_name == "") echo " selected" ?>>Click to select an SSL Report</option>
            <option value="<?=$web_root?>/reporting/ssl/cost-breakdown-by-month.php"<?php if ($report_name == "ssl-cost-breakdown-by-month") echo " selected" ?>>Cost Breakdown by Month</option>
            <option value="<?=$web_root?>/reporting/ssl/ssl-provider-fee-breakdown.php"<?php if ($report_name == "ssl-provider-fee-breakdown") echo " selected" ?>>SSL Provider Fee Breakdown</option>
            <option value="<?=$web_root?>/reporting/ssl/renewals.php"<?php if ($report_name == "ssl-renewals") echo " selected" ?>>Renewal Report</option>
            </select>
		</form>
	</div>
</div>
<div style="clear: both;"></div>
<?php if ($report_name != "") echo "<BR>"; ?>