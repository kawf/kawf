<html>
<head>
<title>{DOMAIN} Forums: Edit Account</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva }
-->
</style>
</head>

<body bgcolor="#ffffff" text="#000000" link="#0000cc" vlink="#0000cc" alink="#0000cc" style="text-decoration: none">

{HEADER}

<h1>Account - Edit</h1><p>

<!-- BEGIN error -->
<font color="red">{ERROR}</font><p>
<!-- END error -->

<!-- BEGIN name -->
<font face="verdana, arial, geneva" size="-1">Your screen name has been changed to {NAME}</font><p>
<!-- END name -->

<!-- BEGIN email -->
<font face="verdana, arial, geneva" size="-1">An email has been sent to your new email address of {NEWEMAIL} to confirm the change. Please follow the directions in the email to finish changing your email address. Your tracking number is {TID}. You can also bookmark the <a href="pending.phtml?tracking={TID}">page</a></font><p>
<!-- END email -->

<!-- BEGIN password -->
<font face="verdana, arial, geneva" size="-1">Your password has been changed</font><p>
<!-- END password -->
<font face="verdana, arial, geneva" size="-1">
<form action="edit.phtml" method="post">
New Screen Name: <input type="text" name="name" length="40"><br>
New Email Address: <input type="text" name="email" length="40"><br>
New Password: <input type="password" name="password1" length="20"><br>
Re-enter Password: <input type="password" name="password2" length="20"><br>
<!--
Timezone:
<select name="timezone">
<option value="ndt"{TZ_ndt}>Newfoundland Daylight (NDT)
<option value="adt"{TZ_adt}>Atlantic Daylight (ADT)
<option value="edt"{TZ_edt}>Eastern Daylight (EDT)
<option value="cdt"{TZ_cdt}>Central Daylight (CDT)
<option value="mdt"{TZ_mdt}>Mountain Daylight (MDT)
<option value="pdt"{TZ_pdt}>Pacific Daylight (PDT)
<option value="ydt"{TZ_ydt}>Yukon Daylight (YDT)
<option value="hdt"{TZ_hdt}>Hawaii Daylight (HDT)
<option value="bst"{TZ_bst}>British Summer (BST)
<option value="mes"{TZ_mes}>Middle European Summer (MES)
<option value="sst"{TZ_sst}>Swedish Summer (SST)
<option value="fst"{TZ_fst}>French Summer (FST)
<option value="wad"{TZ_wad}>West Australian Daylight (WAD)
<option value="cad"{TZ_cad}>Central Australian Daylight (CAD)
<option value="ead"{TZ_ead}>Eastern Australian Daylight (EAD)
<option value="nzd"{TZ_nzd}>New Zealand Daylight (NZD)
<option value="gmt"{TZ_gmt}>Greenwich Mean (GMT)
<option value="utc"{TZ_utc}>Universal (Coordinated) (UTC)
<option value="wet"{TZ_wet}>Western European (WET)
<option value="wat"{TZ_wat}>West Africa (WAT)
<option value="at"{TZ_at}>Azores (AT)
<option value="gst"{TZ_gst}>Greenland Standard (GST)
<option value="nft"{TZ_nft}>Newfoundland (NFT)
<option value="nst"{TZ_nst}>Newfoundland Standard (NST)
<option value="ast"{TZ_ast}>Atlantic Standard (AST)
<option value="est"{TZ_est}>Eastern Standard (EST)
<option value="cst"{TZ_cst}>Central Standard (CST)
<option value="mst"{TZ_mst}>Mountain Standard (MST)
<option value="pst"{TZ_pst}>Pacific Standard (PST)
<option value="yst"{TZ_yst}>Yukon Standard (YST)
<option value="hst"{TZ_hst}>Hawaii Standard (HST)
<option value="cat"{TZ_cat}>Central Alaska (CAT)
<option value="ahs"{TZ_ahs}>Alaska-Hawaii Standard (AHS)
<option value="nt"{TZ_nt}>Nome (NT)
<option value="idl"{TZ_idl}>International Date Line West (IDL)
<option value="cet"{TZ_cet}>Central European (CET)
<option value="met"{TZ_met}>Middle European (MET)
<option value="mew"{TZ_mew}>Middle European Winter (MEW)
<option value="swt"{TZ_swt}>Swedish Winter (SWT)
<option value="fwt"{TZ_fwt}>French Winter (FWT)
<option value="eet"{TZ_eet}>Eastern Europe, USSR Zone 1 (EET)
<option value="bt"{TZ_bt}>Baghdad, USSR Zone 2 (BT)
<option value="it"{TZ_it}>Iran (IT)
<option value="zp4"{TZ_zp4}>USSR Zone 3 (ZP4)
<option value="zp5"{TZ_zp5}>USSR Zone 4 (ZP5)
<option value="ist"{TZ_ist}>Indian Standard (IST)
<option value="zp6"{TZ_zp6}>USSR Zone 5 (ZP6)
<option value="was"{TZ_was}>West Australian Standard (WAS)
<option value="jt"{TZ_jt}>Java (3pm in Cronusland!) (JT)
<option value="cct"{TZ_cct}>China Coast, USSR Zone 7 (CCT)
<option value="jst"{TZ_jst}>Japan Standard, USSR Zone 8 (JST)
<option value="cas"{TZ_cas}>Central Australian Standard (CAS)
<option value="eas"{TZ_eas}>Eastern Australian Standard (EAS)
<option value="nzt"{TZ_nzt}>New Zealand (NZT)
<option value="nzs"{TZ_nzs}>New Zealand Standard (NZS)
<option value="id2"{TZ_id2}>International Date Line East (ID2)
<option value="idt"{TZ_idt}>Israel Daylight (IDT)
<option value="iss"{TZ_iss}>Israel Standard (ISS)
</select><br>
-->

<input type="submit" name="submit" value="Update">
</form>
</font>

{FOOTER}

</body>
</html>

