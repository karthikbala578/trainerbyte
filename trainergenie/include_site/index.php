<? 	$headmenu = 'awareness';
	include("header_index.php");
	include("../header_menu.php");
	include("../include_site/sitecommon.php");
?>
<section id="mu-features"><div class="container">&nbsp;</div></section>
  <!-- Start Slider -->
  <section id="mu-slider">
	<?
    //==== Slide show
	$sqlSlide 		= "SELECT * FROM slideshow WHERE slide_id ='4'";
	$resultSlide 	= $connS->query($sqlSlide);
	while($rowSlide = $resultSlide->fetch_assoc()){
	?>
    	<!-- Start single slider item -->
        <div class="mu-slider-single">
          <div class="mu-slider-img">
            <figure>
              <img src="../slideshow/<?=(!empty($rowSlide['slide_image']))? $rowSlide['slide_image']:'spacer.png';?>" alt="img" border="0" >
			</figure>
          </div>
          <div class="mu-slider-content">
            <h5 style="color:#004080; font-size:40px; font-weight:bold;margin-bottom:0;"><?=$rowSlide['slide_name']?></h5>
            <span style="width:45%;"></span>
            <p style="color:#004080; font-size:25px; font-weight:bold;"><?=$rowSlide['slide_description']?></p>
          </div>
        </div>
        <!-- Start single slider item -->
	<? } ?>
  </section>
  <!-- End Slider -->
  <section id="mu-features"><div class="container">&nbsp;</div></section>
  <?
    //==== home_punchline
	$sqlSlide 		= "SELECT * FROM home_punchline WHERE h_cid ='4'";
	$resultSlide 	= $connS->query($sqlSlide);
	$rowSlide 		= $resultSlide->fetch_assoc();
	?>
 <!--Mobile Start-->	
 <section id="sliderpuc">
		<div id="navbar" class="navbar-collapse punchline" align="center">
			<?=$rowSlide['h_ctitle']?>
		</div>
 </section>
 <!--Mobile End-->
	 
  <section id="mu-menu2">
    <nav class="navbar navbar-default2" role="navigation">  
      <div class="container">
        <div id="navbar" class="navbar-collapse collapse punchline" align="center">
			<?=$rowSlide['h_ctitle']?>
        </div>
      </div>     
    </nav>
  </section>
  
  <section id="mu-features"><div class="container">&nbsp;</div></section>
  
  <!-- Start features section -->
 
  
  <!--Mobile Start--> 
  <!--From Main Site-->
<?
	$sql 		= "SELECT * FROM home_fourcolumn WHERE h_page_id='4' AND h_status IN(1) ORDER BY h_title ASC";
	$result 	= $connS->query($sql);
	$numRowF 	= $result->num_rows;
	if(!empty($numRowF)){
?>  
  <iframe class="iframesh" src="<?=$website?>index_fourcolumncontent.php?pi=4" style="height:320px;width:100%; border:none;"></iframe>
  <!-- End features section -->
  <div class="viewfourmobile" >
				 <?
					while($row = $result->fetch_assoc()) {  
				?>
							  
							  <div class="col-lg-4 col-md-4 col-sm-6" style="float:left;">
								<div class="mu-single-feature" style="height:auto; padding-bottom:50px;  ">
								  <h4>
									<? if(!empty($row['h_image'])){ ?>
									<img src="<?=$website?>homecontent1/<?=$row['h_image']?>" border="0" width="50%" class="columnimage">
									<? } ?>
									<strong><?=$row['h_title']?></strong>
								  </h4>
								  <div style="clear:both; padding-bottom:5px;"></div>
								  <p>
									<?=$row['h_note']?>
									<? if(!empty($row['h_pdf'])){ ?>
									<br>
									<a href="<?=$website?>homecontent1/<?=$row['h_pdf']?>" target="_blank" class="mu-read-more-btn">More</a>
									<? } ?>
								  </p>
								</div>
							  </div>
				<? } ?>
	</div>
	  <section id="mu-features"><div class="container">&nbsp;</div></section>
<? } ?>
  <!--Mobile End-->
	  
  <!-- End about us -->
  <!--<section id="mu-features"><div class="container">&nbsp;</div></section>--> 
  
  <section id="mu-latest-courses">
    <div class="container" id="oln">
      <div class="row">
        <div class="col-lg-12 col-md-12">
          <div class="mu-latest-courses-area">
		  	<table width="100%" border="0" cellspacing="2" cellpadding="2">
            <?
				//==== Display group
				$sqlgp 	= "SELECT
									`displaygroup`.`dgm_id`
									,`displaygroup`.`dgm_name`
									,`displaygroup`.`dgm_description`
									,`displaygroup`.`dgm_colour`
									,`displaygroup`.`dgm_noprice`
								FROM
									`gm_game`
									INNER JOIN `gm_instance` 
										ON (`gm_game`.`ga_id` = `gm_instance`.`in_game_pkid`)
									INNER JOIN `displaygroup` 
									ON (`gm_instance`.`in_displaygroup_pkid` = `displaygroup`.`dgm_id`)
								WHERE (  `gm_game`.`ga_status` ='1' AND `gm_instance`.`in_display` ='1' AND in_status = '1' AND `gm_instance`.`in_play_status` IN(2)) AND displaygroup.dgm_status='1' GROUP BY dgm_id ORDER BY displaygroup.dgm_sequence ASC ";
				$resultgp = $conn->query($sqlgp);
				while($rowgp = $resultgp->fetch_assoc()){
					$dgmid 		= $rowgp['dgm_id'];
					$noprice 	= $rowgp['dgm_noprice'];
			?>
				<tr style="border:<?=$rowgp['dgm_colour']?> solid 2px;">
					<td align="center" valign="top" bgcolor="<?=$rowgp['dgm_colour']?>" width="228">
						<div class="displayGroup"><?=$rowgp['dgm_name']?></div>
						<p class="displayDesc"><?=$rowgp['dgm_description']?></p>
						
						<ul class="displayUL">
							<?=simcomDisplaygroupParameter($conn,$rowgp['dgm_id'])?>
						</ul>
						<? if(!empty($noprice)){ ?>
						 	<a class="noprice"  href="../index.php#pgo" >Subscribe</a>
						 <? } ?>
					</td>
					<td align="center" valign="top" style="padding-top:3px;">
						<!-- Start latest course content -->
						<div style="clear:both"></div>
						<div id="instancetable" >
							<?
								//==== ONline Game Instance
								$sql 	= "SELECT
													`gm_instance`.`in_name`
													,`gm_instance`.`in_id`
													,`gm_instance`.`in_book`
													,`gm_instance`.`in_short_desc`
													,`gm_instance`.`in_type`
													,`gm_instance`.`in_image`
													,`gm_instance`.`in_inr`
													,`gm_instance`.`in_usd`
												FROM
													`gm_game`
													INNER JOIN `gm_instance` 
														ON (`gm_game`.`ga_id` = `gm_instance`.`in_game_pkid`)
												WHERE ( `gm_game`.`ga_status` ='1' AND in_status = '1' AND in_display = '1' AND `gm_instance`.`in_play_status` IN(2)) AND in_displaygroup_pkid='".$dgmid."' ORDER BY gm_instance.in_name ASC";
								$result = $conn->query($sql);
								$si 	= 1;
								while($row = $result->fetch_assoc()){
									$inst_id 	= $row['in_id'];
									$instimage	= $row['in_image'];
									$instName 	= $row['in_name'];
									$insdescrip	= $row['in_short_desc'];
									$instbook	= $row['in_book'];
																
									//=== 1:Offline , 2: Online
									$inst_type 	= $row['in_type'];
									
									$inst_inr	= (empty($row['in_inr']))?0:$row['in_inr'];
									$inst_usd	= (empty($row['in_usd']))?0:$row['in_usd'];
									
									if(!empty($noprice)){
										$bgi 	= 'small';
										$notcl	= 'style="height:58px;"';
										$divcl	= 'np_resourcesdiv';
									}
									else{
										$bgi 	= '1'.$si;
										$notcl	= '';
										$divcl	= 'resourcesdiv_ins';
									}
							?> 
								<div class="<?=$divcl?>" style="background-image: url(../images/resources/<?=$bgi?>.png);" >
									<table width="100%" border="0" cellspacing="0" cellpadding="0">
									  <tr>
										<td align="left"><div class="h_title" <?=$notcl?>><?=$instName?></div></td>
									  </tr>
									  <tr>
										<td align="left"  valign="top"><div class="ins_subtitle"><img src="instance/<?=$instimage?>" border="0" width="185" height="87" class="wideimage"></div></td>
									  </tr>
									  <tr>
										<td align="left" valign="top"><div class="ins_note"><?=$insdescrip?></div></td>
									  </tr>
									  <? if(empty($noprice)){ ?>
									  <tr>
										<td align="center" valign="top" class="inc_price" height="20">
											<? if(empty($inst_inr) and empty($inst_usd)){ ?>
												Free subscription							
											<? } else { 
													
													if(!empty($inst_usd)){ 
														print 'USD '.$inst_usd;
													}
													
													if(!empty($inst_usd) and !empty($inst_inr)){ 
														print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
													}
													
													if(!empty($inst_inr)){ 
														print 'INR '.$inst_inr;
													}  
												} 
											?>
										</td>
									  </tr>
									  <? } ?>
									  <tr>
										<td align="center" valign="bottom" height="20"><a href="instance/<?=$instbook?>" target="_blank" class="h_pdf">More</a></td>
									  </tr>
									  <? if(empty($noprice)){ ?>
									  <tr>
										<td align="center" height="42" valign="bottom"><button class="addtocart" id="ins_<?=$inst_id?>" onClick="addtocart(<?=$inst_id?>,<?=$inst_type?>,<?=$si?>);">Subscribe</button>
											<button class="addmobile" onClick="mobmsg();">Subscribe</button>
											</td>
									  </tr>
									  <? } ?>
									</table>
								</div>
							<? 	
								if($si==5){ 
										$si =0;
								 	} 
									 $si++; 
								} 
							?>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2" height="2"><img src="images/spacer.png" border="0" height="2"></td>
				</tr>	
			<? } ?>	
			</table>
            <!-- End latest course content -->
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- End latest course section -->
  
  

<!--Checkout Start-->
<div id="messagecenterid" class="w3-modal" style="display:none; padding-top:80px;" >
    <div class="w3-modal-content w3-animate-zoom w3-card-4" style="border:#bfbfbf solid 3px; width:1150px; top:-50px;" >
      <header class="w3-container w3-teal"> 
		<span onClick="document.getElementById('messagecenterid').style.display='none'"  class="w3-button w3-display-topright" style="font-size:22px;">&times;</span>
        <h2 class="headerMenu nopad" style="padding:2px;"><strong>Checkout</strong></h2>
      </header>
	  
      <div class="w3-container" style="height:500px; padding:0;" id="messagecenter" >
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr>
				<td width="210" id="getinstancecheckout" valign="top">&nbsp;</td>
				<td align="left" valign="top"><div style="width:100%; height:500px; overflow-y:scroll;">
				 <? if(empty($checklogin)){ ?>  
					  <section id="mu-latest-courses">
						<div class="container containerwidth" id="oln" >
						  <div class="row">
							<div class="col-lg-12 col-md-12">
							  <div class="mu-latest-courses-area">
								<!-- Start Title -->
								<div class="mu-title check_div">
								  <h2 class="popuphead" id="user1" align="left" style="display:none;">User credential <img src="images/uparrows.png" border="0" align="right" width="22"></h2>
								  <h2 class="popuphead" id="user2" align="left" >User credential <img src="images/downarrows.png" border="0" align="right" width="22"></h2>
								</div>
								<!-- End Title -->
								<!-- Start latest course content -->
								<div style="clear:both" id="err"></div>
								<div id="gameuser" class="checktab" style="display:<?=$block?>; padding:5px 10px;" >
									<form name="useregform" id="useregform" >
										<table width="99%" border="0" cellpadding="2" cellspacing="2">
											<tr>
												<td align="left" width="140" class="checkouttext" valign="middle" >Name</td>
												<td align="left" width="120" class="checkouttext" valign="middle" >Display&nbsp;name</td>
												<td align="left" width="170" class="checkouttext" valign="middle" >E-Mail</td>
												<td align="left" width="120" class="checkouttext" valign="middle" >Password</td>
												<td align="left" width="120" class="checkouttext" valign="middle" >Confirm&nbsp;Password</td>
												<td align="left" class="checkouttext" valign="middle" >&nbsp;&nbsp;Not&nbsp;a&nbsp;Robot&nbsp;&nbsp;</td>
												<td>&nbsp;</td>
										  </tr>
										  <tr>
												<td align="left" class="chpl" valign="middle" ><input type="text" placeholder="Enter name"  class="sublogin_checkout" name="sname" id="sname" required></td>
												<td align="left" class="chpl" valign="middle" ><input type="text" placeholder="Enter display name" maxlength="10"  class="sublogin_checkout" name="dname" id="dname" required></td>
												<td align="left" class="chpl" valign="middle" ><input type="email" placeholder="Enter e-mail"  class="sublogin_checkout" name="smail" id="smail" required></td>
												<td align="left" class="chpl" valign="middle" ><input type="password" placeholder="Enter password"  class="sublogin_checkout" name="spassword" id="spassword" required></td>
												<td align="left" class="chpl" valign="middle" ><input type="password" placeholder="Enter confirm password"  class="sublogin_checkout" name="spassword2" id="spassword2" required></td>
												<td align="center" class="chpl" valign="middle" ><input type="checkbox" name="robot" id="robot" required></td>
												<td align="left" valign="middle" ><input type="submit" id="ufloginf" name="ufloginf" class="button_check" value="Next" /></td>
										  </tr>
										  <tr><td colspan="7" height="10" style="font-size:1px;"><img src="images/spacer.png" border="0" height="10"></td></tr>
										  <tr><td colspan="7" align="right" ><div class="errmsg fleft" id="suberrorerr" style="color:#F00;"><?=$logerr?></div></td></tr>
										</table>
									</form>
								</div>			
								<!-- End latest course content -->
							  </div>
							</div>
						  </div>
						</div>
					  </section>
					<? } ?>
			  
						<form action="payteam.php" name="round1form" id="round1form" method="post">  			
							<input type="hidden" id="instanceID" name="instanceID" value="0">
							<input type="hidden" id="checklogin" name="checklogin" value="<?=$checklogin?>">
							<input type="hidden" name="setpayid" id="setpayid"  />
							<input type="hidden" id="inpass" name="inpass" >
							<input type="hidden" id="free" name="free" value="2" />
							<input type="hidden" id="getgamename" name="getgamename"  />
							<input type="hidden" id="getsub" name="getsub"  />
						</form>
					  
					  <form name="gamenamesform" id="gamenamesform" >
							<div id="olnfree2">
							  <section id="mu-latest-courses">
								<div class="container containerwidth" id="oln">
								  <div class="row" id="tem">
									<div class="col-lg-12 col-md-12">
									  <div class="mu-latest-courses-area">
										<!-- Start Title -->
										<div class="mu-title check_div">
										  <h2 class="popuphead" id="pick12" align="left" style="display:none;">Choose a title <img src="images/uparrows.png" border="0" align="right" width="22"></h2>
										  <h2 class="popuphead" id="pick22" align="left" >Choose a title <img src="images/downarrows.png" border="0" align="right" width="22"></h2>
										</div>
										<!-- End Title -->
										<!-- Start latest course content -->
										<div style="clear:both"></div>
										<div id="pickplayer2" class="checktab" style="display:none;  padding:5px 10px;">
											<table width="822" border="0" cellpadding="2" cellspacing="2">
											<tr>
												<td align="left" class="checkouttext_normal" valign="middle" >Enter a title of your choice for your subscription. This will appear as a title to all your users.</td>
										  </tr>
										  <tr>
												<td align="left" class="chpl" valign="middle" ><input type="text" placeholder="Name"  class="sublogin_checkout" name="insname" id="insname" maxlength="100" required style="width:250px;" ></td>
										  </tr>
										  <tr>
										  	<td ><img src="images/spacer.png" border="0" height="10"></td>
										  </tr>
										  <tr>
												<td align="left" class="checkouttext_normal" valign="middle" ><strong>Subscription quantity</strong></td>
										  </tr>
										  <tr>
												<td align="left" class="chpl" valign="middle" ><input type="number" maxlength="4" value="2" name="countsub" id="countsub" class="plusminus4" onchange="plus();" onkeyup="plus();" onblur="plus();" >
													<?php /*?><div id="input_div">
														<table width="50" border="0" cellspacing="0" cellpadding="0">
														  <tr>
															<td align="center" valign="top"><img src="images/red-minus-hi.png" width="20" alt="mins" title="mins" onclick="minus();" style="cursor:pointer;" vspace="5" hspace="10" ></td>
															<td align="center" valign="middle"><input type="text" value="2" name="countsub" id="countsub" readonly="readonly" class="plusminus4"></td>
															<td align="center" valign="bottom"><img src="images/green-plus.png" width="20" vspace="5" border="0" alt="plus" title="plus" onclick="plus();" style="cursor:pointer;" hspace="10"></td>
														  </tr>
														</table>					
													</div><?php */?>
												</td>
										  </tr>
										  <tr>
										  	<td align="right" valign="middle" ><input type="submit" id="gamename" name="ufloginfs" class="button_check" value="Next" /></td>
										  </tr>
										</table>
										</div>			
										<!-- End latest course content -->
									  </div>
									</div>
								  </div>
								</div>
							  </section>
							</div>
			  			</form>
						
					  <section id="mu-latest-courses">
						<div class="container containerwidth" id="oln">
						  <div class="row">
							<div class="col-lg-12 col-md-12">
							  <div class="mu-latest-courses-area">
								<!-- Start Title -->
								<div class="mu-title check_div">
								  <h2 class="popuphead"  id="pay1" align="left" style="display:none;">Payment <img src="images/uparrows.png" border="0" align="right" width="22"></h2>
								  <h2 class="popuphead"  id="pay2" align="left" >Payment <img src="images/downarrows.png" border="0" align="right" width="22"></h2>
								</div>
								<!-- End Title -->
								<!-- Start latest course content -->
								<div style="clear:both"></div>
								<div id="payment" style="display:none;" >
									<table width="100%" border="0" cellpadding="2" cellspacing="2">
										<tr bgcolor="#FFFFFF">
											<td align="center" class="inctablepad" valign="middle" id="setteampayment"><strong>Please choose the simulation on top</strong></td>
									  </tr>
									</table>
								</div>			
								<!-- End latest course content -->
							  </div>
							</div>
						  </div>
						</div>
					  </section></div></td>
			  </tr>
			</table>		  
	  </div>
    </div>
</div>
<!--Checkout End-->

<!--Partner Start-->
<div id="messagecenterid2" class="w3-modal" style="display:none; padding-top:80px;" >
    <div class="w3-modal-content w3-animate-zoom w3-card-4" style="border:#bfbfbf solid 3px; width:1150px; top:-50px;" >
      <header class="w3-container w3-teal"> 
		<span onClick="document.getElementById('messagecenterid2').style.display='none'"  class="w3-button w3-display-topright" style="font-size:22px;">&times;</span>
        <h2 class="headerMenu nopad" style="padding:2px;"><strong>Checkout</strong></h2>
      </header>
	  
      <div class="w3-container" style="height:500px; padding:0;" id="messagecenter2" >
	  </div>
    </div>
</div>
<!--Partner End-->
  
  <!--Mobile Start-->	
	 <section id="sliderpuc">
			<div id="navbar" class="navbar-collapse packoff" align="center">
				We have designed package offer to suit your needs. Economical pricing with annual validity. Please <a href="../index.php#pgo">CLICK</a> to know more.
			</div>
	 </section>
	 <!--Mobile End-->	
 
  <section id="mu-menu2">
    <nav class="navbar navbar-default2" role="navigation">  
      <div class="container">
        <div id="navbar" class="navbar-collapse collapse packoff" align="center">
			We have designed package offer to suit your needs. Economical pricing with annual validity. Please <a href="../index.php#pgo">CLICK</a> to know more.
        </div>
      </div>     
    </nav>
  </section>
  
  <section id="mu-features"><div class="container">&nbsp;</div></section>
  
  <!-- Start about us -->
	 <?
		$sql 	= "SELECT * FROM home_wideband WHERE h_page_id='4' AND h_status IN(1) ORDER BY h_title ASC";
		$result = $connS->query($sql);
		$wi =1;
		while($row = $result->fetch_assoc()) {  
			
			//=== Image
			if($row['h_type']==1){
				$imageContent = '<img src="'.$website.'homecontent2/'.$row['h_image'].'" class="wideimage moblastimg" >'; 
			}
			//=== Video
			else{
				$imageContent = $row['h_url']; 
			}
		
			if($wi%2==1){
	 ?> 
		<section id="mu-about-us">
		  <div class="container">
			<div class="row">
			  <div class="col-md-12">
				<div class="mu-about-us-area">
				  <div class="row">
					<div class="col-lg-7 col-md-6">
					  <div class="mu-about-us-left">
						<!-- Start Title -->
						<div class="mu-title">
						  <h2>
							<?=$row['h_title']?>
						  </h2>
						</div>
						<!-- End Title -->
						<?=$row['h_note']?>
						<? if(!empty($row['h_pdf'])){ ?>
							<P><a href="<?=$website?>homecontent2/<?=$row['h_pdf']?>" target="_blank" class="mu-read-more-btn">More</a></P>
						<? } ?>
					  </div>
					</div>
					<div class="col-lg-4 col-md-6">
					  <div class="mu-about-us-right"><?=$imageContent?></div>
					</div>
				  </div>
				</div>
			  </div>
			</div>
		  </div>
		</section>

	<? } else { ?>	
		<section id="mu-about-us">
		  <div class="container">
			<div class="row">
			  <div class="col-md-12">
				<div class="mu-about-us-area">
				  <div class="row">
					<div class="col-lg-5 col-md-6">
					  <div class="mu-about-us-right"><?=$imageContent?></div>
					</div>
					<div class="col-lg-7 col-md-6">
					  <div class="mu-about-us-left">
						<!-- Start Title -->
						<div class="mu-title">
						  <h2>
							<?=$row['h_title']?>
						  </h2>
						</div>
						<!-- End Title -->
						<?=$row['h_note']?>
						<? if(!empty($row['h_pdf'])){ ?>
							<P><a href="<?=$website?>homecontent2/<?=$row['h_pdf']?>" target="_blank" class="mu-read-more-btn">More</a></P>
						<? } ?>
					  </div>
					</div>
				  </div>
				</div>
			  </div>
			</div>
		  </div>
		</section>
	<? } ?>	
		<section id="mu-features"><div class="container">&nbsp;</div></section>
 <? $wi++; } ?>  
 
  <section id="mu-menu">
    <nav class="navbar navbar-default" role="navigation">  
      <div class="container">
        <div id="navbar" class="navbar-collapse collapse">
          <ul id="top-menu" class="nav navbar-nav main-nav">
            <li onClick="document.getElementById('aboutusg').style.display='block'" ><a>About us</a></li>
            <li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>
            <li onClick="document.getElementById('id02').style.display='block'"><a>Terms of use</a></li>
            <li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>
            <li onClick="document.getElementById('id03').style.display='block'"><a>Pricing&nbsp;&&nbsp;Refund&nbsp;policy</a></li>
            <li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>
            <li onClick="document.getElementById('id04').style.display='block'"><a>Privacy</a></li>
            <li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>
            <li onClick="document.getElementById('id05').style.display='block'"><a>Contact us</a></li>
          </ul>                     
        </div><!--/.nav-collapse -->        
      </div>     
    </nav>
  </section>
  
    
  <!-- Start footer -->
  <footer id="mu-footer">
    <!-- start footer bottom -->
    <div class="mu-footer-bottom">
      <div class="container">
        <div class="mu-footer-bottom-area">
          <div >
		  <a href="https://www.facebook.com/sarasventure/" target="_blank"><img src="<?=$website?>images/facebook.png" width="40"  alt="Facebook" /></a> &nbsp;&nbsp;&nbsp;
		  <a href="https://www.linkedin.com/company/sarasventure/" target="_blank"> <img src="<?=$website?>images/linkedin2.png" alt="LinkedIn" width="40" /></a>&nbsp;&nbsp;&nbsp;
		  <a href="https://twitter.com/ilangovasudevan"  target="_blank"> <img src="<?=$website?>images/twitter.png" alt="Twitter" width="40" /></a> 
		  <p>&nbsp;</p>
		  <p>&copy; All Right Reserved</p>
	  </div>
		  
		  
        </div>
		
      </div>
    </div>
    <!-- end footer bottom -->
  </footer>
  <!-- End footer -->
<? include('../include_site/footer_3.php');?>
<? include('../gamelicense.php');?>
<? include('../contactus.php');?>
<style>
.plusminus4{
	width:80px; 
	text-align:left;
	padding:3px;
	background-color:#FFFFFF;
	border:#e4e0e0 1px solid; 
	color:#666666;
	font-weight:bold;
}
</style>
  
  <!-- jQuery library -->
  <script src="<?=$website?>assets/js/jquery.min.js"></script>  
  <!-- Include all compiled plugins (below), or include individual files as needed -->
  <script src="<?=$website?>assets/js/bootstrap.js"></script>   
  <!-- Slick slider -->
  <script type="text/javascript" src="<?=$website?>assets/js/slick.js"></script>
  <!-- Counter -->
  <script type="text/javascript" src="<?=$website?>assets/js/waypoints.js"></script>
  <script type="text/javascript" src="<?=$website?>assets/js/jquery.counterup.js"></script>  
  <!-- Mixit slider -->
  <script type="text/javascript" src="<?=$website?>assets/js/jquery.mixitup.js"></script>
  <!-- Add fancyBox -->        
  <script type="text/javascript" src="<?=$website?>assets/js/jquery.fancybox.pack.js"></script>
  <!-- Custom js -->
  <script src="<?=$website?>assets/js/custom.js?s=1"></script> 
  <script src="js_site/contactus.js"></script>  

<? if(!isset($_SESSION['login_userid'])){ ?>
<script src="<?=$website?>js_site/validation.js?rand=55" type="text/javascript"></script>
<style>
	/* Full-width input fields */
	.loginText{
		width: 100%;
		padding: 12px 20px;
		margin: 8px 0;
		display: inline-block;
		border: 1px solid #ccc;
		box-sizing: border-box;
		float:left;
	}
	
	/* Set a style for all buttons */
	.button5 {
		background-color: #E46C0A;
		color: white;
		padding: 14px 20px;
		margin: 8px 0;
		border: none;
		cursor: pointer;
		width: 100%;
	}
	
	.button5:hover {
		opacity: 0.8;
	}
	
	/* Extra styles for the cancel button */
	.cancelbtn {
		width: auto;
		padding: 10px 18px;
		background-color: #f44336;
		float:left;
	}
	
	/* Center the image and position the close button */
	.imgcontainer {
		text-align: center;
		margin: 24px 0 12px 0;
		position: relative;
	}
	.containerLog {
		padding: 16px;
	}
	
	span.psw {
		float: right;
		padding-top: 16px;
	}
	
	/* The Modal (background) */
	.modal {
		display: none; /* Hidden by default */
		position: fixed; /* Stay in place */
		z-index: 1; /* Sit on top */
		left: 0;
		top: 0;
		width: 100%; /* Full width */
		height: 100%; /* Full height */
		overflow: auto; /* Enable scroll if needed */
		background-color: rgb(0,0,0); /* Fallback color */
		background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
		padding-top: 60px;
	}
	
	/* Modal Content/Box */
	.modal-content {
		background-color: #fefefe;
		margin: 5% auto 15% auto; /* 5% from the top, 15% from the bottom and centered */
		border: 1px solid #888;
		width: 50%; /* Could be more or less, depending on screen size */
	}
	
	/* The Close Button (x) */
	.close {
		position: absolute;
		right: 25px;
		top: 0;
		color: #000;
		font-size: 35px;
		font-weight: bold;
	}
	
	.close:hover,
	.close:focus {
		color: red;
		cursor: pointer;
	}
	
	/* Add Zoom Animation */
	.animate {
		-webkit-animation: animatezoom 0.6s;
		animation: animatezoom 0.6s
	}
	
	@-webkit-keyframes animatezoom {
		from {-webkit-transform: scale(0)} 
		to {-webkit-transform: scale(1)}
	}
		
	@keyframes animatezoom {
		from {transform: scale(0)} 
		to {transform: scale(1)}
	}
	
	/* Change styles for span and cancel button on extra small screens */
	@media screen and (max-width: 300px) {
		span.psw {
		   display: block;
		   float: none;
		}
		.cancelbtn {
		   width: 100%;
		}
	}
	.textLogin{
		padding:5px 15px;
		font-size:24px;
		font-weight:bold;
		color:#E46C0A;
		float:left;	
	}
	.clearSpan{
		clear:both;
	}
	.textViewLogin{
		font-size:14px;	
		float:left;
	}
	</style>
    
    <div id="id099" class="modal">
      <form class="modal-content animate" id="formtarget" name="formtarget">
        <div class="imgcontainer">
          <span onClick="document.getElementById('id099').style.display='none'" class="close" title="Close Modal">&times;</span>
          <span class="textLogin">Login</span>
          
        </div>
        <div class="clearSpan"></div>
    
        <div class="containerLog">
          <label class="textViewLogin"><b>Login</b></label>
          <input type="text" placeholder="Enter Username" class="loginText" name="uname" id="uname" required><div class="errmsg fleft" id="unameerr"></div>
          <div class="clearSpan"></div>
          <label  class="textViewLogin"><b>Password</b></label>
          <input type="password" placeholder="Enter Password" class="loginText"  name="psw" id="psw" required><div class="errmsg fleft" id="pswerr"></div>
        <div class="clearSpan"></div>
          <!--<button type="button" id="loginf" name="loginf" class="button5">Login</button>-->
          <input type="submit" id="loginf" name="loginf" class="button5" value="Login" />
          <div class="errmsg fleft" id="errorerr" style="color:#F00;"></div>
        </div>
     <div class="clearSpan"></div>
        <div class="containerLog" style="background-color:#f1f1f1">
          <button type="button" onClick="document.getElementById('id099').style.display='none'" class="button5 cancelbtn">Cancel</button>
          <span class="psw"><a href="javascript:void(0);" onClick="forgotPassword();">Forgot password?</a></span>
         <div class="clearSpan"></div>
        </div>
      </form>
    </div>
    
    
    <!--Forgot password-->
    <div id="id077" class="modal">
	  <form class="modal-content animate" action="">
		<div class="imgcontainer">
		  <span onClick="document.getElementById('id077').style.display='none'" class="close" title="Close Modal">&times;</span>
          <span class="textLogin">Forgot password?</span>
          
		</div>
        <div class="clearSpan"></div>
	
		<div class="containerLog">
		  <label class="textViewLogin"><b>Username</b></label>
		  <input type="text" placeholder="Enter Username" class="loginText" name="fort" id="fort" required><br><div class="errmsg fleft" id="forterr" style="color:#ff0000;"></div>
          <div class="clearSpan"></div>
		  <button type="button" id="forgot" class="button5">Forgot password?</button><div class="errmsg fleft" id="error1err" style="color:#ff0000;"></div>
          	<div class="frmsucess fleft" id="error2err" style="color:#1C9012;"></div>
            <div class="clearSpan"></div>
		</div>
	 <div class="clearSpan"></div>
		<div class="containerLog" style="background-color:#f1f1f1">
		  <button type="button" onClick="document.getElementById('id077').style.display='none'" class="button5 cancelbtn">Cancel</button>
		  <span class="psw"><a href="javascript:void(0);" onClick="loginBox();">Login</a></span>
		 <div class="clearSpan"></div>
        </div>
        
	  </form>
	</div>	
        
	<script>
		function forgotPassword(){
			document.getElementById('id099').style.display='none';
			document.getElementById('id077').style.display='block';
		}
		function loginBox(){
            document.getElementById('id077').style.display='none';
            document.getElementById('id099').style.display='block';
        }
        
        // Get the modal
        var modal = document.getElementById('id099');
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        $(document).ready(function () {
            
            //=== Login
            //$('#loginf').click(function() {
            $("#formtarget").submit(function(){
                hideErr('#uname');
                hideErr('#psw');
                hideErr('#error');
                
                if($('#uname').val()=="") {
                    showErr('#uname','Enter the Username');
                    return false;
                }
                if($('#psw').val()=="") {
                    showErr('#psw','Enter the Password');
                    return false;
                }
                
                var psw 	= $('#psw').val();
                var uname 	= $('#uname').val();
                var params	= 'psw='+psw+'&uname='+uname+'&lo=1';
                $.ajax({
                    type	: 'POST',
                    url		: 'getloginforgot.php',
                    data	: params,
                    dataType: 'json',
                    success : function(data){
                        if((data.msg)=='1'){
                            window.location.href = "myaccount.php?f=1";
                        }
                        else{
                            showErr('#error','Invalid username or password');
                            return false;								
                        }
                    }
                });
                return false;
            
            });
			
			//=== Forgot
				$('#forgot').click(function() {
					hideErr('#fort');
					hideErr('#error1');
					hideErr('#error2');
					
					if($('#fort').val()=="") {
						showErr('#fort','Enter the Username');
						return false;
					}
					
					var fort 	= $('#fort').val();
					var params	= 'fort='+fort+'&ft=1';
					$.ajax({
						type	: 'POST',
						url		: 'getloginforgot.php',
						data	: params,
						dataType: 'json',
						success : function(data){
							if((data.msg)=='1'){
								showErr('#error2','Password reset link sent to your Email id. Kindly check it');
								return false;						
							}
							else{
								showErr('#error1','Invalid username');
								return false;								
							}
						}
					});

				});
            
        });
    </script>
<? } ?>
<script src="js_site/indexins.js?rand=55" type="text/javascript"></script>
<script>
	function mobmsg(){
		document.getElementById('mobmsg').style.display='block';	
	}
</script>

  </body>
</html>