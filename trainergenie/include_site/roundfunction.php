<?

class GameRound{

	public $data 		= array();

	public $apiData 	= array();

	public $msgAI  		= array();

	public $total 		= 0;

	public $curRound	= 0;

	public $gcompleted	= 0;

	

	//=== Get Played Details

	function getTeamPlayed($conn,$accID){

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();



		$this->total 		= $row['score'];

		$this->data	 		= json_decode($row['data'],true);

		$this->apiData 		= ($row['api_data']!='')?json_decode($row['api_data'],true):array();

		$this->curRound 	= $row['current_round'];

		$this->gcompleted	= $row['completed'];

	}

		

	//== Instance Game Details

	function insGameDetails($conn,$id,$accID){

		$return = array();

		$insID	= $id['in_id'];

		$gameID = $id['in_game_pkid'];

		

		//=== Get Play Details

		$this->getTeamPlayed($conn,$accID);

		

		//=== Boilerplate

		$this->boilerPlate($conn);

		

		

		//=== Game Details

		$return['game'] 			= $this->gameDetails($conn,$gameID);

		

		//=== Instance Casestudy Group

		$return['ins_casestudy']	= (!empty($id['in_casestudy_group_pkid']))? $this->casestudyGroup($conn,$id['in_casestudy_group_pkid'],'inc_1') : array();

		

		//=== Round List

		$return['ins_round']		= $this->roundDetails($conn,$insID);

		

		$return['current_round']	= $this->curRound;

		$return['completed']		= $this->gcompleted;

		$return['msgAI']			= json_encode($this->msgAI);

		return $return;

	}

	

	//==== Game Details

	function gameDetails($conn,$id){

		$sql 	= "SELECT * FROM gm_game WHERE ga_id='".$id."'";

		$result	= $conn->query($sql);

		$row 	= $result->fetch_assoc();

		return $row;

	}

	

	//==== Boiler Plate

	function boilerPlate($conn){

		$sql 	= "SELECT * FROM mdm_assessment_group";

		$result = $conn->query($sql);

		while($row 	= $result->fetch_assoc()) {

			$this->msgAI['txt'][$row['ag_id']] = $row['ag_description'];

		}

	}

	

	//=== Results & Analysis	

	function resultsDiv(){

		$return = '<div id="myResult" class="showresult disnone">

					<table width="100%" border="0" cellspacing="0" cellpadding="0">

					  <tr>

						<td align="left" class="resulthead">'.$this->msgAI['txt'][6].'</td>

						<td align="left" width="5"><span class="resultclose">&times;</span></td>

					  </tr>

					  <tr>

						<td colspan="2" height="100%" ><div class="resultview" id="setresult" ></div></td>

					  </tr>

					</table>

				</div>';

		return $return;

	}

	

	//==== Casestudy Group

	function casestudyGroup($conn,$id,$arrID){

		$sql 	= "SELECT cg_description FROM sub_casestudy_group WHERE cg_id='".$id."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();		

		$this->msgAI[$arrID]['CS'] = $row['cg_description'];

		

		//==== Display Case Study

		$return = array();

		$sql 	= "SELECT * FROM sub_casestudy WHERE ch_group_pkid='".$id."' AND ch_status=1 ORDER BY ch_sequence ASC";

		$result = $conn->query($sql);

		while($row 	= $result->fetch_assoc()) {

			$return[] = $row;

		}

		return $return;

	}

	

	//==== Card Riddles Group

	function cardRiddlesGroup($conn,$id){

		$return = array();

		

		//==== Group

		$sql 	= "SELECT * FROM card_group WHERE cg_id='".$id."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		$return['cardgroup'] = $row;

				

		//==== 1:Yes,2:NO

		$order = ($row['cg_random']==1)? 'RAND()' : 'cu_sequence ASC';

		

		//=== Card

		$card = array();

		$sql 	= "SELECT * FROM card_unit WHERE cu_card_group_pkid='".$id."' AND cu_status=1 ORDER BY ".$order;

		$result = $conn->query($sql);

		while($row 	= $result->fetch_assoc()) {

			$card[] = $row;

		}

		

		$return['cardunit'] = $card;

		

		return $return;

	}

	

	//==== Survival Quest Group

	function survivalQuestGroup($conn,$id){

		//=== Card

		$card = array();

		$sql 	= "SELECT * FROM survival_unit WHERE su_survival_group_pkid='".$id."' AND su_status=1 ORDER BY RAND()";

		$result = $conn->query($sql);

		while($row 	= $result->fetch_assoc()) {

			$card[] = $row;

		}

		

		return $card;

	}

	

	//==== ByteBargain Group

	function byteBargainGroup($conn,$id){

		$card = array();

		$sql 	= "SELECT * FROM api_bargain WHERE api_id='".$id."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		return $row;

	}

		

	//==== Treasure Hunt Group

	function treasureHuntGroup($conn,$id){

		$return = array();

		$sql 	= "SELECT * FROM mdm_learning_unit WHERE lu_group_pkid='".$id."' AND lu_status=1 ORDER BY lu_sequence ASC";

		$result = $conn->query($sql);

		while($row 	= $result->fetch_assoc()) {

			$return[] = $row;

		}

		return $return;

	}

	

	//==== 	Animation message

	function animationMessage($conn,$id,$roName){

		$sql 	= "SELECT * FROM animation_msg WHERE id='".$id."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();



		$this->msgAI[$roName]['start_msg'] 	= $row['start_msg'];

		$this->msgAI[$roName]['return_msg'] = $row['return_msg'];

		$this->msgAI[$roName]['end_msg'] 	= $row['end_msg'];



		return $row;

	}

	

	//==== 	Stage message

	function stageMessage($conn,$id){

		$sql 	= "SELECT * FROM stage_msg WHERE id='".$id."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		return $row;

	}

	

	//=== Balance Days

	function dateDiffInDays($date1, $date2){ 

		// Calulating the difference in timestamps 

		$diff = strtotime($date2) - strtotime($date1); 

		// 1 day = 24 hours 

		// 24 * 60 * 60 = 86400 seconds 

		return abs(round($diff / 86400)); 

	}

	

	//==== Round Details

	function roundDetails($conn,$insID){
    global $rowCount;

		$return = array();

	  $sql 	= "SELECT
					gm_round.* , gm_instance_round.ir_order

					FROM

						gm_instance_round

						INNER JOIN gm_round 

							ON (gm_instance_round.ir_round_pkid = gm_round.ro_id)

					WHERE (gm_instance_round.ir_instance_pkid ='".$insID."'

						AND gm_round.ro_status ='1')

					ORDER BY gm_instance_round.ir_order ASC";

		$result = $conn->query($sql);
    	$rowCount = $result->num_rows; // Getting count of Rounds Maria
        //$return	= $numRows;
		while($row 	= $result->fetch_assoc()) {

			$roName							= 'round_'.$row['ir_order'];

			$return[$roName] 				= $row;

			

			//=== Round Casestudy Group

			$return[$roName]['casestudy'] 	 = (!empty($row['ro_casestudy_group_pkid']))? $this->casestudyGroup($conn,$row['ro_casestudy_group_pkid'],$roName) : array();

			

			//=== Round Treasure Hunt Group

			if($row['ro_type']==1){

				$return[$roName]['treasure'] = (!empty($row['ro_treasure_group_pkid']))? $this->treasureHuntGroup($conn,$row['ro_treasure_group_pkid']) : array();

			}			

			//=== Round Card riddles

			else if($row['ro_type']==2){

				$return[$roName]['treasure'] = (!empty($row['ro_treasure_group_pkid']))? $this->cardRiddlesGroup($conn,$row['ro_treasure_group_pkid']) : array();

			}

			//=== Survival Quest

			else if($row['ro_type']==3){

				$return[$roName]['treasure'] = (!empty($row['ro_treasure_group_pkid']))? $this->survivalQuestGroup($conn,$row['ro_treasure_group_pkid']) : array();

			}

			//=== ByteBargain

			else if($row['ro_type']==4){

				$return[$roName]['treasure'] = (!empty($row['ro_treasure_group_pkid']))? $this->byteBargainGroup($conn,$row['ro_treasure_group_pkid']) : array();

			}			

			//=== DigiSim

			else if($row['ro_type']==5){

				$return[$roName]['treasure'] = (!empty($row['ro_treasure_group_pkid']))? $this->digiSimGroup($conn,$row['ro_treasure_group_pkid']) : array();

			}

			

			//=== Animation message

			$return[$roName]['animation'] 	= (!empty($row['ro_animation_msg_pkid']))? $this->animationMessage($conn,$row['ro_animation_msg_pkid'],$roName) : array();

			

			//=== Stage start message

			$return[$roName]['stage_start'] = (!empty($row['ro_stage_start_pkid']))? $this->stageMessage($conn,$row['ro_stage_start_pkid']) : array();

			

			//=== Stage end message

			$return[$roName]['stage_end'] 	= (!empty($row['ro_stage_end_pkid']))? $this->stageMessage($conn,$row['ro_stage_end_pkid']) : array();

			

		}

		return $return;

	}

	

	//=== Side Menu

	function sideMenu($round,$insDeta){

		$resView 	= ($this->curRound!=0)?'':'disnone';

		$mList 		= '';

		$roundLi 	= '';

		$ri 		= 1;

		foreach($round as $roundName){			

			$cuRound = $this->curRound;

			$neRound = ($this->curRound+1);

			

			if($ri <= $cuRound){

				$mList.='<div class="sidebar-content"><div class="innermenu noMenu chand" id="mm'.$ri.'" ><div class="menunamein">'.$roundName["ro_name"].'</div><div class="menuimag" id="im'.$ri.'"><img src="images/c_completed.png" border="0" height="22" /></div></div></div>';

				$roundLi.='<div class="sideround" id="cm'.$ri.'"  style="display:none;">R'.$ri.'</div>';

			}

			else if($neRound==$ri){

				$mList.='<div class="sidebar-content"><div class="innermenu selectMenu chand" id="mm'.$ri.'"><div class="menunamein">'.$roundName["ro_name"].'</div><div class="menuimag" id="im'.$ri.'"><img src="images/c_unlock.png" border="0" height="22" /></div></div></div>';

				$roundLi.='<div class="sideround mhove"  id="cm'.$ri.'" style="display:none;">R'.$ri.'</div>';

			}

			else{

				$mList.='<div class="sidebar-content"><div class="innermenu noMenu cblock" id="mm'.$ri.'"><div class="menunamein">'.$roundName["ro_name"].'</div><div class="menuimag" id="im'.$ri.'"><img src="images/c_lock.png" border="0" height="22" /></div></div></div>';

				$roundLi.='<div class="sideround scm" id="cm'.$ri.'" style="display:none;">R'.$ri.'</div>';

			}

						

			$ri++;

		}

		

		//=== For SkilDa

		if(isset($_SESSION['login_out'])){

			$smallIcon = '';

			$bigIcon   = '<div class="biglogout" ><a href="#" onclick="window.close();"><img src="images/logout_2.svg" align="absmiddle" border="0" hspace="10" height="30" width="30">Logout</a></div>';

		}

		else{

			$smallIcon = '<div class="smallaccount"><img src="images/person_2.svg" hspace="5" vspace="5" border="0" height="30" width="30"></div>';

			$bigIcon   = '<div class="bigaccount"><a href="../myaccount.php"><img src="images/person_2.svg" align="absmiddle" border="0" hspace="10" height="30" width="30">My account</a></div>

							<div class="biglogout" ><a href="../logout.php"><img src="images/logout_2.svg" align="absmiddle" border="0" hspace="10" height="30" width="30">Logout</a></div>';

		}

		  

 

		if(!empty($insDeta['in_displaytext'])){

			$menuTitle 	= $insDeta['in_displaytext'];

			$muid 		= 'id="inscas"';

			$mth		= ' chand';

		}

		else{

			$menuTitle = $this->msgAI['txt'][4];

			$muid 		= '';

			$mth		= '';

		}



		//$return= '<div id="mySidenav" class="sidenav" >

		$return= '<div id="mySidenav" class="sidenav"  style="display:block;">	
		  				
							<div class="sidebar-brand">
                                <img src="images/logo_2.png" class="brand-logo-img" alt="Logo">
                            </div>	

					<div class="menufirst">

					  <div class="padb15">

						  <span class="closebtn" onclick="closeNav()">&times;</span>
						  <img src="images/home.svg" style="width:20px; height:auto; padding-left: 50px;">
						  <span class="menuhead'.$mth.'" '.$muid.'> '.$menuTitle.'</span>

					  </div>

					  '.$mList.'

					</div>



					<div class="viewresult bigresult '.$resView.'" id="bigresult" ><a href="javascript:void(0);"><img src="images/result.svg" align="absmiddle" border="0" hspace="14" height="30" width="25">'.$this->msgAI['txt'][5].'</a></div>

					'.$bigIcon.'

 				    

				</div>

				

				<div id="mysmallmenubar" class="smallmenubar">

						<span class="smalllist" onclick="openNav()" >&#9776;</span>

						'.$roundLi.'

						<div class="viewresult smallresult '.$resView.'" id="smallresult"><img src="images/result.svg" hspace="9" vspace="5" border="0" height="30" width="25"></div>						

						'.$smallIcon.'

						<div class="smalllogout" ><img src="images/logout_2.svg" hspace="5" vspace="5" border="0" height="30" width="30"></div>

				</div>';

		return $return;

	}

		

	//=== Body Load

	function bodyLoad($insGameDetails){

		$return = '';

		

		//=== Casestudy Instance

		if(count($insGameDetails['ins_casestudy']) > 0){			

			//=== Check Next

			$next 	= $this->findNext($insGameDetails,1,0,0);

			$return.= $this->casestudy($insGameDetails['ins_casestudy'],'inc','',$next);

		}

				

		//=== Round Details

		$return.= $this->getRoundDetails($insGameDetails);		

		$return.= '<div class="scorediv disnone" id="scorebox">

						<div class="totalscore" id="viewtotal">'.$this->total.'</div>

						<div class="scoretxt">'.$this->msgAI['txt'][7].'</div>

				  </div>

				  <div class="maxdiv disnone" id="maxbox">

					<div class="stat-block">
						<div class="maxcards"> 
						   <i class="fa-regular fa-eye"></i> Cards Revealed </div>
    					<div class="maxcount" id="maxcard">3 / 8</div>
					</div>

					<div class="divider"></div>

					<div class="stat-block">
				  		<div class="balancecards" id="balancecard"> 
							<i class="fa-regular fa-hourglass-half"></i> Remaining Chances </div>
						<div class="balancecount" id="maxtotal"></div>
					</div>

				  </div>

				  <div class="maxdiv disnone" id="showthread">


						<div class="maxtxt">'.$this->msgAI['txt'][22].'</div>

				  </div>';//						<div class="maxscore" id="threadtotal"></div>


		return $return;

	}

	

	//=== Round Details

	function getRoundDetails($insGameDetails){

		$return = '';

		$si 	= 1;

		foreach($insGameDetails['ins_round'] as $roundKey=>$roundVal){

			//==== For Next rount

			$fround = $roundVal['ir_order'];

			

			//=== For Card riddles

			$rID 	= ($roundVal['ro_type']==2)? $roundVal['ro_id'] : 0;

					

			//==== Stage Start

			if(count($roundVal['stage_start']) > 0){

				$next 	= $this->findNext($insGameDetails,2,$si,$rID,0);

				$pre 	= $this->findPre($insGameDetails,2,$si,$rID);

				$return.=$this->stageMsg($roundVal['stage_start'],$roundKey.'ST',$pre,$next);

			}

			

			//==== Round Casestudy

			if(count($roundVal['casestudy']) > 0){

				$next 	= $this->findNext($insGameDetails,3,$si,$rID,0);

				$pre 	= $this->findPre($insGameDetails,3,$si,$rID);

				$return.=$this->casestudy($roundVal['casestudy'],$roundKey,$pre,$next);

			}			

			

			//==== Stage End

			if(count($roundVal['stage_end']) > 0){

				$next 	= $this->findNext($insGameDetails,5,$si,$rID,0);

				$pre 	= $this->findPre($insGameDetails,5,$si,$rID);

				$return.=$this->stageMsg($roundVal['stage_end'],$roundKey.'ED',$pre,$next);

			}

			

			//==== Treasure

			if(count($roundVal['treasure']) > 0){

				$next 	= $this->findNext($insGameDetails,4,$si,$rID,$fround);

				$pre 	= $this->findPre($insGameDetails,4,$si,$rID);

				

				//=== Round Treasure Hunt Group

				if($roundVal['ro_type']==1){				

					$return.=$this->treasureDiv($roundVal['treasure'],$roundKey.'TR',$pre,$next,$roundVal['ro_id']);

				}

				

				//=== Round Card riddles

				else if($roundVal['ro_type']==2){

					$return.=$this->cardDiv($roundVal['treasure'],$roundKey.'TR',$pre,$next,$rID);

				}

				

				//=== Survival Quest

				else if($roundVal['ro_type']==3){

					$return.=$this->survivalDiv($roundVal['treasure'],$roundKey.'TR',$pre,$next,$rID,$roundVal['ro_id']);

				}

				

				//=== Survival Quest

				else if($roundVal['ro_type']==4){

					$return.=$this->byteBargainDiv($roundVal['treasure'],$roundKey.'TR',$pre,$next,$rID,$roundVal['ro_id']);

				}

				

				//=== Survival Quest

				else if($roundVal['ro_type']==5){

					$return.=$this->digiSimDiv($roundVal['treasure'],$roundKey.'TR',$pre,$next,$rID,$roundVal['ro_id']);

				}

			}

			

			$si++;

		}

		return $return;

	}

	

	//==== Show Round Bargain Type

	function findRoundBargain($insGameDetails,$round){

		$return = ($insGameDetails['ins_round']['round_'.$round]['ro_type']==4)?$insGameDetails['ins_round']['round_'.$round]['ro_id']:0;

		return $return;

	}

	

	//==== Find Next 

	function findNext($insGameDetails,$type,$round=0,$rID=0,$fround=0){	

		$nround 	= ($round+1);



		//=== Next Round ID for cardgroup Only

		$nxRoundid 	= 0;

		if(isset($insGameDetails['ins_round']['round_'.$nround]['treasure']['cardgroup'])){

			$nxRoundid 	= $insGameDetails['ins_round']['round_'.$nround]['ro_id'];

		}



		//=== Current Round ID

		$gcuRoundid = 0;

		if(isset($insGameDetails['ins_round']['round_'.$round])){

			$gcuRoundid 	= $insGameDetails['ins_round']['round_'.$round]['ro_id'];

		}



		//=== Current Round ID

		$gnxRoundid = 0;

		if(isset($insGameDetails['ins_round']['round_'.$nround])){

			$gnxRoundid = $insGameDetails['ins_round']['round_'.$nround]['ro_id'];

		}	

		

		$return = '';

		//==== Instance Casestudy

		if($type==1){

			$baType = $this->findRoundBargain($insGameDetails,1);

			if(count($insGameDetails['ins_round']['round_1']['stage_start']) > 0){

				$return = 'onclick="casestudy(\'round_1ST\',1,1,0,0,0,'.$baType.',0);"';

			}

			else if(count($insGameDetails['ins_round']['round_1']['casestudy']) > 0){

				$return = 'onclick="casestudy(\'round_1\',1,1,0,0,0,'.$baType.',0);"';

			}

			else if(count($insGameDetails['ins_round']['round_1']['treasure']) > 0){

				$return = 'onclick="casestudy(\'round_1TR\',1,2,'.$rID.',0,0,'.$baType.','.$gcuRoundid.');"';

			}

		}

		

		//==== Round Stages Start

		elseif($type==2){

			$baType = $this->findRoundBargain($insGameDetails,$round);

			if(count($insGameDetails['ins_round']['round_'.$round]['casestudy']) > 0){

				$return = 'onclick="casestudy(\'round_'.$round.'\',1,1,0,0,0,'.$baType.',0);"';

			}

			else if(count($insGameDetails['ins_round']['round_'.$round]['treasure']) > 0){

				$return = 'onclick="casestudy(\'round_'.$round.'TR\',1,2,'.$rID.',0,0,'.$baType.','.$gcuRoundid.');"';

			}

		}

		

		//==== Round Casestudy

		elseif($type==3){

			if(count($insGameDetails['ins_round']['round_'.$round]['treasure']) > 0){

				$baType = $this->findRoundBargain($insGameDetails,$round);

				$return = 'onclick="casestudy(\'round_'.$round.'TR\',1,2,'.$rID.',0,0,'.$baType.','.$gcuRoundid.');"';

			}

		}

		

		//==== Round Treasure

		elseif($type==4){

			if(count($insGameDetails['ins_round']['round_'.$round]['stage_end']) > 0){

				$baType = $this->findRoundBargain($insGameDetails,$round);

				$return = 'onclick="casestudy(\'round_'.$round.'ED\',1,1,0,1,'.$fround.','.$baType.',0);"';

			}

			elseif(isset($insGameDetails['ins_round']['round_'.$nround])){

				$baType = $this->findRoundBargain($insGameDetails,$nround);

				if(count($insGameDetails['ins_round']['round_'.$nround]['stage_start']) > 0){

					$return = 'onclick="casestudy(\'round_'.$nround.'ST\',1,1,0,1,'.$fround.','.$baType.',0);"';

				}

				else if(count($insGameDetails['ins_round']['round_1']['casestudy']) > 0){

					$return = 'onclick="casestudy(\'round_'.$nround.'\',1,1,0,1,'.$fround.','.$baType.','.$gcuRoundid.');"';

				}

				else if(count($insGameDetails['ins_round']['round_1']['treasure']) > 0){

					$return = 'onclick="casestudy(\'round_'.$nround.'TR\',1,2,'.$nxRoundid.',1,'.$fround.','.$baType.','.$gnxRoundid.');"';

				}			

			}

		}

		

		//==== Round Stages End

		elseif($type==5){

			if(isset($insGameDetails['ins_round']['round_'.$nround])){

				$baType = $this->findRoundBargain($insGameDetails,$nround);

				

				if(count($insGameDetails['ins_round']['round_'.$nround]['stage_start']) > 0){

					$return = 'onclick="casestudy(\'round_'.$nround.'ST\',1,1,0,0,0,'.$baType.',0);"';

				}

				else if(count($insGameDetails['ins_round']['round_1']['casestudy']) > 0){

					$return = 'onclick="casestudy(\'round_'.$nround.'\',1,1,0,0,0,'.$baType.',0);"';

				}

				else if(count($insGameDetails['ins_round']['round_1']['treasure']) > 0){

					$return = 'onclick="casestudy(\'round_'.$nround.'TR\',1,2,'.$nxRoundid.',0,0,'.$baType.','.$gnxRoundid.');"';

				}			

			}

		}		

		

		return $return;

	}

	

	//==== Find Pre 

	function findPre($insGameDetails,$type,$round='',$rID=0){

		$pRound = ($round-1);

		

		//=== Previous Round ID for cardgroup Only

		$prRoundid 	= 0;

		if(isset($insGameDetails['ins_round']['round_'.$pRound]['treasure']['cardgroup'])){

			$prRoundid 	= $insGameDetails['ins_round']['round_'.$pRound]['ro_id'];

		}



		//=== Current Round ID

		$gcuRoundid = 0;

		if(isset($insGameDetails['ins_round']['round_'.$round])){

			$gcuRoundid 	= $insGameDetails['ins_round']['round_'.$round]['ro_id'];

		}



		//=== Previous Round ID

		$gprRoundid = 0;

		if(isset($insGameDetails['ins_round']['round_'.$pRound])){

			$gprRoundid 	= $insGameDetails['ins_round']['round_'.$pRound]['ro_id'];

		}

		

		$return = '';

		

		//==== Round Stages Start

		if($type==2){

			if($round==1){

				if(count($insGameDetails['ins_casestudy']) > 0){

					$count 	= count($insGameDetails['ins_casestudy']);

					$return = 'onclick="casestudy(\'inc\','.$count.',1,0,0,0,0,0);"';

				}			

			}

			else{

				if(count($insGameDetails['ins_round']['round_'.$pRound]['stage_end']) > 0){

					$return = 'onclick="casestudy(\'round_'.$pRound.'ED\',1,1,0,0,0,0,0);"';

				}

				elseif(count($insGameDetails['ins_round']['round_'.$pRound]['treasure']) > 0){

					$return = 'onclick="casestudy(\'round_'.$pRound.'TR\',1,2,'.$prRoundid.',0,0,0,'.$gprRoundid.');"';

				}

			}

		}

		

		//==== Round Casestudy

		elseif($type==3){

			if(count($insGameDetails['ins_round']['round_'.$round]['stage_start']) > 0){

					$return = 'onclick="casestudy(\'round_'.$round.'ST\',1,1,0,0,0,0,0);"';

			}

			elseif($round==1){

				if(count($insGameDetails['ins_casestudy']) > 0){

					$count 	= count($insGameDetails['ins_casestudy']);

					$return = 'onclick="casestudy(\'inc\','.$count.',1,0,0,0,0,0);"';

				}			

			}

			elseif(count($insGameDetails['ins_round']['round_'.$pRound]['stage_end']) > 0){

				$return = 'onclick="casestudy(\'round_'.$pRound.'ED\',1,1,0,0,0,0,0);"';

			}

			elseif(count($insGameDetails['ins_round']['round_'.$pRound]['treasure']) > 0){

				$return = 'onclick="casestudy(\'round_'.$pRound.'TR\',1,2,'.$prRoundid.',0,0,0,'.$gprRoundid.');"';

			}

		}

		

		//==== Round Treasure

		elseif($type==4){

			if(count($insGameDetails['ins_round']['round_'.$round]['casestudy']) > 0){

				$count 	= count($insGameDetails['ins_round']['round_'.$round]['casestudy']);

				$return = 'onclick="casestudy(\'round_'.$round.'\','.$count.',1,0,0,0,0,0);"';

			}

			elseif(count($insGameDetails['ins_round']['round_'.$round]['stage_start']) > 0){

					$return = 'onclick="casestudy(\'round_'.$round.'ST\',1,1,0,0,0,0,0);"';

			}

			elseif($round==1){

				if(count($insGameDetails['ins_casestudy']) > 0){

					$count 	= count($insGameDetails['ins_casestudy']);

					$return = 'onclick="casestudy(\'inc\','.$count.',1,0,0,0,0,0);"';

				}			

			}

			elseif(count($insGameDetails['ins_round']['round_'.$pRound]['stage_end']) > 0){

				$return = 'onclick="casestudy(\'round_'.$pRound.'ED\',1,1,0,0,0,0,0);"';

			}

			elseif(count($insGameDetails['ins_round']['round_'.$pRound]['treasure']) > 0){

				$return = 'onclick="casestudy(\'round_'.$pRound.'TR\',1,2,'.$prRoundid.',0,0,0,'.$gprRoundid.');"';

			}			

		}

		

		//==== Round Stages End

		elseif($type==5){

			if(count($insGameDetails['ins_round']['round_'.$round]['treasure']) > 0){

				$return = 'onclick="casestudy(\'round_'.$round.'TR\',1,2,'.$rID.',0,0,0,'.$gcuRoundid.');"';

			}

			elseif(count($insGameDetails['ins_round']['round_'.$round]['casestudy']) > 0){

				$count 	= count($insGameDetails['ins_round']['round_'.$round]['casestudy']);

				$return = 'onclick="casestudy(\'round_'.$round.'\','.$count.',1,0,0,0,0,0);"';

			}

			elseif(count($insGameDetails['ins_round']['round_'.$round]['stage_start']) > 0){

					$return = 'onclick="casestudy(\'round_'.$round.'ST\',1,1,0,0,0,0,0);"';

			}

			elseif($round==1){

				if(count($insGameDetails['ins_casestudy']) > 0){

					$count 	= count($insGameDetails['ins_casestudy']);

					$return = 'onclick="casestudy(\'inc\','.$count.',1,0,0,0,0,0);"';

				}			

			}

			elseif(count($insGameDetails['ins_round']['round_'.$pRound]['stage_end']) > 0){

				$return = 'onclick="casestudy(\'round_'.$pRound.'ED\',1,1,0,0,0,0,0);"';

			}

			elseif(count($insGameDetails['ins_round']['round_'.$pRound]['treasure']) > 0){

				$return = 'onclick="casestudy(\'round_'.$pRound.'TR\',1,2,'.$prRoundid.',0,0,0,'.$gprRoundid.');"';

			}

		}		

		

		return $return;

	}


	//=== Stage Msg 

	function stageMsg($msgVal,$div,$pr='',$nx=''){

		//=== Previous

		if($pr==''){

			$class 	= 'disblk';

			$hand 	= 'cblock';

		}

		else{

			$class 	= 'disnone';

			$hand 	= 'chand';

		}

				

		//=== Next

		$nHand = ($nx=='') ? 'cblock':'chand';		

		

		$return ='<div class="allcase '.$class.'" id="'.$div.'_1">

					<div class="casestudy" ><table cellpadding="0" cellspacing="0" border="0" width="70%" align="center" height="100%">

						<tr><td valign="middle" class="castxt" align="center" height="100%">'.$msgVal['description'].'</td></tr>

					</table></div>

					<div class="casestudy_bt" style="margin-">

						<div class="casestudy_art">

							<div class="cas_right '.$nHand.'" title="Next" '.$nx.'></div>

							<div class="cas_left '.$hand.'" title="Previous" '.$pr.'></div>

						</div>

					</div>

				</div>';

		return $return;	

	}



	//=== Treasure Div

	function treasureDiv($treasure,$div,$pr='',$nx='',$rID=0){

		//=== Previous

		if($pr==''){

			$class 	= 'disblk';

			$hand 	= 'cblock';

		}

		else{

			$class 	= 'disnone';

			$hand 	= 'chand';

		}

				

		//=== Next

		$nHand = ($nx=='') ? 'cblock':'chand';		

		

		//=== Title Box

		$box 	= '';

		$hunt 	= '';

		$tCount = count($treasure);

		$ti 	= 1;

		foreach($treasure as $treasureName){

			$hid 		= $treasureName['lu_id'];			

			$getClues 	= $this->setClues($hid,$treasureName['lu_clue'],$rID);

			

			$lock		= (isset($this->data['open']['treasure-'.$hid]))? '&#128275;' : '&#128274;';



			//==== Title Image

			$box.='<div class="topindexdiv" >

						<div class="checkhuntgame sec3Button55 handpoint" id="'.$hid.'-'.$div.'-'.$rID.'"  style="background-image:url(learning/'.$treasureName['lu_image'].');" title="'.$treasureName['lu_name'].'" >							

							<div class="lock" id="lock_t_'.$hid.'">'.$lock.'</div>							

							<div class="tabsubBoxNO">&nbsp;</div>

							<div class="tabsubBoxText indexcat">'.$treasureName['lu_name'].'</div>

						</div>

						

						<div class="huntopendiv" id="hc_'.$hid.'-'.$div.'-'.$rID.'"></div>

					</div>';

		

			//=== Check Image and Text

			$midDiv = '';

			$toggle = '';

			if(!empty($treasureName['lu_treasure_image']) && !empty($treasureName['lu_description'])){

				$midDiv = '<div id="htog21_'.$hid.'" class="huntimage"><img src="learning/treasure/'.$treasureName['lu_treasure_image'].'" border="0" class="imgclass"  /></div>

							<div id="htog22_'.$hid.'" class="huntimage disnone"><table cellpadding="0" cellspacing="0" border="0" width="95%" align="center" height="100%">

								<tr><td valign="middle" class="castxt" align="left" height="100%">'.$treasureName['lu_description'].'</td></tr>

							</table></div>';

							

				$toggle = '<div class="toggle">

							<div class="togglediv">

								<span class="toggletxt" id="htog11_'.$hid.'" onclick="huntTooggle(2,'.$hid.',\'h\');">View Text<br><span class="togglesys">&#8652;</span></span>

								<span class="toggletxt disnone" id="htog12_'.$hid.'" onclick="huntTooggle(1,'.$hid.',\'h\');">View Image<br><span class="togglesys">&#8652;</span></span>

							</div>

						  </div>';

			}

			else if(empty($treasureName['lu_treasure_image']) && !empty($treasureName['lu_description'])){

				$midDiv = '<div class="huntimage"><table cellpadding="0" cellspacing="0" border="0" width="95%" align="center" height="100%">

						<tr><td valign="middle" class="castxt" align="left" height="100%">'.$treasureName['lu_description'].'</td></tr>

					</table></div>';

			}

			else{

				$midDiv = '<div class="huntimage">
								<button class="card-close" aria-label="Close card" onclick="huntView('.$hid.',\''.$div.'_1\',2,0);">
        							✕
    							</button>
								<img src="learning/treasure/'.$treasureName['lu_treasure_image'].'" border="0" class="imgclass"  />
							</div>';
			}

			

			$checkBut = '';

			if($tCount!=$ti && (!isset($this->data['code']['treasure-'.$hid]))){

				$checkBut='<div class="checkDiv">'.$this->msgAI['txt'][8].'</div>

								<div class="checkDiv"><input type="text" id="checkcode_'.$hid.'-'.$rID.'" name="checkcode_'.$hid.'-'.$rID.'" class="checkinput" ></div>

								<div class="checkDiv"><button type="button" class="submitbut checkbutton" id="'.$hid.'-'.$rID.'" >CHECK</button></div>';

			}

								

			//=== Induvial Image

			$hunt.='<div class="allcase disnone" id="hunt_'.$hid.'">

						<div class="rightsidebar">

							<div class="mgnt80">&nbsp;</div>

							'.$toggle.$getClues.'

						</div>

						

						'.$midDiv.'

						<div class="casestudy_bt">

							<div class="checkside" id="checkdiv_'.$hid.'">'.$checkBut.'</div>

							<div class="casestudy_hunt">

								<div class="cas_empty" ></div>

								<div class="cas_left chand" title="Previous" onclick="huntView('.$hid.',\''.$div.'_1\',2,0);"></div>

							</div>

						</div>						

					</div>';					

			$ti++;

		}

		//===

		

		$return ='<div class="allcase '.$class.'" id="'.$div.'_1">

					<div class="rightsidebar">

						

					</div>

					<div class="hunttitle" >'.$box.'</div>					

					<div class="casestudy_bt">

						<div class="casestudy_hunt">

							<div class="cas_right '.$nHand.'" title="Next" '.$nx.'></div>

							<div class="cas_left '.$hand.'" title="Previous" '.$pr.'></div>

						</div>

					</div>

				</div>';

		return $return.$hunt;	

	}

	

	//=== Set Clues

	function setClues($hid,$clues,$rID){

		$free = '';

		$pay  = '';

		$getClue 	= ($clues!='')? json_decode($clues,true) : array();

		foreach($getClue as $clueVal){		

			$legend = $clueVal['legend'];

			$score 	= $clueVal['score'];

			$clue 	= $clueVal['clue'];

			$order 	= $clueVal['order'];



			$class = (isset($this->data['clue']['treasure-order-'.$hid.'-'.$order]))? 'cluein' : 'clueout';



			if(empty($score)){

				if(empty($free)){

					$free.='<div class="cluetitle">'.$this->msgAI['txt'][1].'</div>';

				}

				$free.='<div class="showclue '.$class.'" id="'.$hid.'-'.$order.'-'.$rID.'"></div>';

			}

			else{

				if(empty($pay)){

					$pay.='<div class="cluetitle">'.$this->msgAI['txt'][2].'</div>';

				}

				$pay.='<div class="showclue '.$class.'" id="'.$hid.'-'.$order.'-'.$rID.'">'.$score.'</div>';

			}

		}

		return $free.$pay;

	}

	

	//=== Set Card Clues

	function setCardClues($hid,$clues,$rID){

		$free = '';

		$pay  = '';

		$getClue 	= ($clues!='')? json_decode($clues,true) : array();

		foreach($getClue as $clueVal){		

			$legend = $clueVal['legend'];

			$score 	= $clueVal['score'];

			$clue 	= $clueVal['clue'];

			$order 	= $clueVal['order'];

			

			$class = (isset($this->data['clue']['card-order-'.$hid.'-'.$order]))? 'cluein' : 'clueout';

			

			if(empty($score)){

				if(empty($free)){

					$free.='<div class="cluetitle">'.$this->msgAI['txt'][14].'</div>';

				}

				$free.='<div class="cardclue '.$class.'" id="'.$hid.'-'.$order.'-c-'.$rID.'"></div>';

			}

			else{

				if(empty($pay)){

					$pay.='<div class="cluetitle">'.$this->msgAI['txt'][15].'</div>';

				}

				$pay.='<div class="cardclue '.$class.'" id="'.$hid.'-'.$order.'-c-'.$rID.'">'.$score.'</div>';

			}

		}

		return $free.$pay;

	}

	

	//=== ByteBargain Div

	function byteBargainDiv($treasure,$div,$pr='',$nx='',$rID=0,$roundID=0){

		//==== Already Data

		$apiQuestion = '';

		if(isset($this->data['API']['round_'.$roundID])){

			$fe =1;

			foreach($this->apiData['API_'.$roundID] as $apiVal){

				if(!isset($apiVal['dp'])){

					if($apiVal['user'] == 'You'){

						if($fe!=1){

							$apiQuestion.='<div class="msg right-msg">

										<div class="msg-img">You</div>

										<div class="msg-bubble">

											<div class="msg-text">'.$apiVal['data'].'</div>

										</div>

									</div>';

						}

					}

					else{

						$apiQuestion.='<div class="msg left-msg">

										<div class="msg-img" >'.$treasure['api_ai_name'].'</div>							

										<div class="msg-bubble">

											<div class="msg-text">'.$apiVal['data'].'</div>

										</div>

									</div>';

					}				

					$fe++;

				}

			}

		}

		

		//=== Button

		$apiButton = '';

		$forceBut  = '';

		if(isset($this->data['API']['round_'.$roundID.'_completed'])){

			$apiButton='<div class="msger-inputarea" id="loading'.$roundID.'">

							<div class="custom-loader">'.$this->msgAI['txt'][26].'</div>

						</div>

						<div class="disnone" id="subai'.$roundID.'"></div>';

		}

		else{

			$apiButton='<div class="msger-inputarea" id="loading'.$roundID.'">

							<div class="custom-loader">'.$this->msgAI['txt'][28].'</div>

						</div>	

						<div class="msger-inputarea" id="subai'.$roundID.'">

							<input type="text" id="aitext'.$roundID.'" class="msger-input" placeholder="'.$this->msgAI['txt'][24].'">

							<button type="submit"  id="'.$roundID.'" class="msger-send-btn aisend">Send</button>

						</div>';

			$forceBut ='<div class="checkside" id="loading2'.$roundID.'"><div class="checkDiv padl20">'.$this->msgAI['txt'][23].' <span class="endclick" id="'.$roundID.'">CLICK HERE</span> </div></div>';

		}

		

		//=== Previous

		if($pr==''){

			$class 	= 'disblk';

			$hand 	= 'cblock';

		}

		else{

			$class 	= 'disnone';

			$hand 	= 'chand';

		}

				

		//=== Next

		$nHand = ($nx=='') ? 'cblock':'chand';			

		

		$return ='<div class="allcase '.$class.'" id="'.$div.'_1">

					<div class="rightsidebar">

						<div class="mgnt70">&nbsp;</div>

						

					</div>

					<div class="hunttitle" >

							

						<section class="msger">

						  <header class="msger-header">

							<div class="msger-header-title">

							  <i class="fas fa-comment-alt"></i>'.$treasure['api_name'].' 

							</div>

							<div class="msger-header-options">

							  <span><i class="fas fa-cog"></i></span>

							</div>

						  </header>

						

						  <main class="msger-chat" id="bar_'.$roundID.'">'.$apiQuestion.'</main>

						   '.$apiButton.'						 

						</section>

					

					</div>					

					<div class="casestudy_bt">

						'.$forceBut.'

						<div class="casestudy_hunt">

							<div class="cas_right '.$nHand.'" title="Next" '.$nx.'></div>

							<div class="cas_left '.$hand.'" title="Previous" '.$pr.'></div>

						</div>						

					</div>

				</div>';

		return $return;

	}

	

	//=== Survival Div

	function survivalDiv($treasure,$div,$pr='',$nx='',$rID=0,$roundID=0){

		//=== Previous

		if($pr==''){

			$class 	= 'disblk';

			$hand 	= 'cblock';

		}

		else{

			$class 	= 'disnone';

			$hand 	= 'chand';

		}

				

		//=== Next

		$nHand = ($nx=='') ? 'cblock':'chand';		

		

		$getClues ='<div class="cluetitle">'.$this->msgAI['txt'][19].'</div>';

		

		//=== Title Box

		$box 	= '';

		$hunt 	= '';

		$tCount = count($treasure);

		$ti 	= 1;

		$pri	= array();

		foreach($treasure as $treasureName){

			$hid 		= $treasureName['su_id'];			

			

			$prioList ='';

			if(isset($this->data['survival']['round_'.$roundID]['L5_'.$hid.'_'.$roundID.'_1'])){

				//$prioList = '<div class="survivalclue" draggable="true" ondragstart="drag(event)" id="drg_'.$treasureName['su_sequence'].'" >'.$treasureName['su_sequence'].'</div>';

				$prioList = '<div class="survivalclue" draggable="true" ondragstart="drag(event)" id="drg'.$roundID.'_'.$treasureName['su_sequence'].'" >'.$this->data['survival']['round_'.$roundID]['L5_'.$hid.'_'.$roundID.'_1'].'</div>';

			}

			else{

				$pri[$treasureName['su_sequence']] = '<div class="survivalclue" draggable="true" ondragstart="drag(event)" id="drg'.$roundID.'_'.$treasureName['su_sequence'].'" >'.$treasureName['su_sequence'].'</div>';

			}

			

			//==== Title Image

			$box.='<div class="surindexdiv">

						<!--<div class="sec3ButtonCC i_su_'.$roundID.'" style="background-image:url(survival/'.$treasureName['su_image'].');" title="'.$treasureName['su_name'].'"  id="L2_'.$hid.'_'.$roundID.'_1" >

								<div class="tabsubBoxNO" id="L3_'.$hid.'_'.$roundID.'_1">&nbsp;</div>

								<div class="tabsubBoxText indexcat" id="L4_'.$hid.'_'.$roundID.'_1">'.$treasureName['su_name'].'</div>						    

						</div>-->

						

						<div class="sec3ButtonTT t_su_'.$roundID.'" id="L6_'.$hid.'_'.$roundID.'_1">

							<strong>'.$treasureName['su_name'].'</strong><br>'.$treasureName['su_description'].'

						</div>



						<div class="dragplace sur'.$roundID.'" ondrop="drop(event)" ondragover="allowDrop(event)" id="L5_'.$hid.'_'.$roundID.'_1">

							'.$prioList.'

						</div>

					</div>';							

			$ti++;

		}		

		ksort($pri);

		$getClues.=implode('',$pri);

		//===

		

		$submitBut = '';

		$viewPrior = '';

		if(!isset($this->data['survival']['round_'.$roundID])){

			$submitBut = '<div class="checkDiv padl20">'.$this->msgAI['txt'][18].'</div>

							<div class="checkDiv padl35"><button type="button" class="submitbut checkSurvival" id="'.$roundID.'" >SUBMIT</button></div>';

							

			$viewPrior = '<div ondrop="drop(event)" ondragover="allowDrop(event)" class="surlist" id="priority_L_'.$roundID.'_2" >'.$getClues.'</div>';

		}

		

		//=== Toggle Image and Text

		$toggle ='';
		

		$scroll = ($ti > 17)?' cardScroll':''; 

		$return ='<div class="allcase '.$class.'" id="'.$div.'_1">

					<div class="rightsidebar">

						<div class="mgnt70">&nbsp;</div>

						'.$toggle.$viewPrior.'

					</div>

					<div class="hunttitle'.$scroll.'" >'.$box.'</div>					

					<div class="casestudy_bt">

						<div class="checkside" id="survivalbutton'.$roundID.'">'.$submitBut.'</div>

						<div class="casestudy_hunt">

							<div class="cas_right '.$nHand.'" title="Next" '.$nx.'></div>

							<div class="cas_left '.$hand.'" title="Previous" '.$pr.'></div>

						</div>

					</div>

				</div>';

		return $return;

	}

	

	//=== Card Div

	function cardDiv($treasure,$div,$pr='',$nx='',$rID=0){

		//=== Previous

		if($pr==''){

			$class 	= 'disblk';

			$hand 	= 'cblock';

		}

		else{

			$class 	= 'disnone';

			$hand 	= 'chand';

		}

				

		//=== Next

		$nHand  = ($nx=='') ? 'cblock':'chand';

		$nexBut = (isset($this->data['answer']['round_'.$rID])) ? ' disblk':' disnone';	

		$style = (isset($this->data['answer']['round_'.$rID])) ? '':' marglfg';	

			

		

		$getClues 	= $this->setCardClues($treasure['cardgroup']['cg_id'],$treasure['cardgroup']['cg_clue'],$rID);

		

		//=== Title Box

		$box 	= '';

		$hunt 	= '';

		$tCount = count($treasure);

		$ti 	= 1;

		foreach($treasure['cardunit'] as $treasureName){

			$hid 		= $treasureName['cu_id'];			

			

			$lock		= (isset($this->data['open']['card-'.$hid]))? '&#128275;' : '&#128274;';
							if ($lock === '&#128275;') {
								$status = 'REVEALED';
							}else {
								$status = '';
							}

			$box.='<div class="cardindexdiv" id="'.$hid.'-'.$div.'-'.$rID.'" >

						<div class="sec3ButtonCC handpoint" style="background-image:url(card/'.$treasureName['cu_image'].');" title="'.$treasureName['cu_name'].'" >							

							<div class="lock" id="lock_c_'.$hid.'">'.$lock.'</div>							

							<div class="tabsubBoxNO2">&nbsp;</div>

							<div class="tabsubBoxText indexcat">'.$treasureName['cu_name'].'</div>

							<div class="status-revealed">'.$status.'</div>

						</div>

					</div>';

			//=== Check Image and Text

			$midDiv = '';

			$toggle = '';

			if(!empty($treasureName['cu_treasure_image']) && !empty($treasureName['cu_description'])){

				$midDiv = '<div id="ctog21_'.$hid.'" class="huntimage"><img src="card/unit/'.$treasureName['cu_treasure_image'].'" border="0" class="imgclass" /></div>

							<div id="ctog22_'.$hid.'" class="huntimage disnone"><table cellpadding="0" cellspacing="0" border="0" width="95%" align="center" height="100%">

								<tr><td valign="middle" class="castxt" align="left" height="100%">'.$treasureName['cu_description'].'</td></tr>

							</table></div>';

							

				$toggle = '<div class="toggle">

							<div class="togglediv">

								<span class="toggletxt" id="ctog11_'.$hid.'" onclick="huntTooggle(2,'.$hid.',\'c\');">View Text<br><span class="togglesys">&#8652;</span></span>

								<span class="toggletxt disnone" id="ctog12_'.$hid.'" onclick="huntTooggle(1,'.$hid.',\'c\');">View Image<br><span class="togglesys">&#8652;</span></span>

							</div>

						  </div>';

			}

			else if(empty($treasureName['cu_treasure_image']) && !empty($treasureName['cu_description'])){

				$midDiv = '<div class="huntimage"><table cellpadding="0" cellspacing="0" border="0" width="95%" align="center" height="100%">

						<tr><td valign="middle" class="castxt" align="left" height="100%">'.$treasureName['cu_description'].'</td></tr>

					</table></div>';

			}

			else{

				$midDiv = '<div class="huntimage" ><img src="card/unit/'.$treasureName['cu_treasure_image'].'" border="0" class="imgclass" /></div>';

			}

			

			//=== Induvial Image

			$hunt.='<div class="allcase disnone" id="cardrp_'.$hid.'">

						<div class="rightsidebar">

							<div class="mgnt158">&nbsp;</div>

							'.$toggle.$getClues.'

						</div>						

						'.$midDiv.'

						<div class="casestudy_bt">

							<div class="checkside"></div>

							<div class="casestudy_hunt">

								<div class="cas_empty"></div>

								<div class="cas_left chand" title="Previous" onclick="huntView('.$hid.',\''.$div.'_1\',2,0);"></div>

							</div>

						</div>						

					</div>';					

			$ti++;

		}

		//===

		$scroll = ($ti > 17)?' cardScroll':''; 

$return ='<div class="allcase '.$class.'" id="'.$div.'_1">

					<div class="rightsidebar">

						

					</div>

					<div class="hunttitle'.$scroll.'" >'.$box.'</div>					

					<div class="casestudy_bt">

						<div class="checkside">							

							<div class="checkDiv padl20 mainrel">

								<img src="images/handa.gif" width="40" height="40" border="0" id="'.$rID.'" class="handpos cardclick2 hand'.$rID.'" />

								<button type="button" class="submitbut cardclick2" id="'.$rID.'">Finish Review</button>&nbsp;
								
							</div>

						</div>

						

							

							<div class="casestudy_hunt">

								<div class="cas_right '.$nHand.$nexBut.'" id="next_ro_'.$rID.'" title="Next" '.$nx.'></div>

								<div class="cas_left '.$hand.$style.'" id="next_ro2_'.$rID.'" title="Previous" '.$pr.'></div>

							</div>

					</div>

				</div>';
		

		$answer = $this->cardAnswer($rID,$div,$treasure['cardgroup'],$getClues);

		return $return.$hunt.$answer;

	}

	

	//=== Card Answer

	function cardAnswer($rID,$div,$treasure,$getClues){

		

		//=== Check Answer Submit Or Not

		if(isset($this->data['answer']['round_'.$rID])){

			$submitBut = '';

			$disabled  = ' disabled="disabled" ';			

		}

		// else{

		// 	$submitBut = '<div class="checkDiv padl35"><button type="button" class="submitbut checkanswer" id="'.$rID.'" >SUBMIT</button></div>';

		// 	$disabled  = '';

		// }
		else{

			$submitBut = '<div class="checkDiv padl35"><button type="button" class="submitbut checkanswer finalbtn" id="'.$rID.'" >SUBMIT</button></div>';

			$disabled  = '';

		}

		

		$cardGrid = $treasure['cg_id'];

		

		$ansList  = '';

		$getClue  = ($treasure['cg_answer']!='')? json_decode($treasure['cg_answer'],true) : array();

		foreach($getClue as $clueVal){		

			$score 	= $clueVal['score'];

			$answer	= $clueVal['answer'];

			$title	= (isset($clueVal['title']))?$clueVal['title']:'';

			$order 	= $clueVal['order'];

			

			$checked  = (isset($this->data['answer']['round_'.$rID]['order_'.$order]))? ' checked="checked" ' : '';

			/*$ansList.='<label class="contradio">'.$answer.'

						  <input type="radio" name="answer'.$rID.'" class="answer'.$rID.'" value="'.$order.'" '.$disabled.$checked.'>

						  <span class="checkmark"></span>

						</label>';*/



			$ansList.='<label class="contradio cardanswerbox">

							<div class="CardHead">

								<div class="CardHead1">

									<input type="radio" name="answer'.$rID.'" class="answer'.$rID.'" value="'.$order.'" '.$disabled.$checked.'><span class="checkmark"></span>

								</div>

								<div class="CardHead2">

									'.$title.'

								</div>

							</div>

							<div class="cleard"></div>

							<div class="CardBottom">'.$answer.'</div>

					  </label>';

		}

	$return = '<div class="allcase disnone" id="cardbox_'.$rID.'">

						<div class="rightsidebar">

							<div class="mgnt158">&nbsp;</div>

						</div>						

						

						<div class="huntimage">		
						
							<div class="option-intro">
								<h1>Make Your Selection</h1>
        						<p>
        						    Based on the card analysis, select the option below that best reflects the objective of the exercise.
        						</p>
    						</div>

							<form id="form'.$rID.'">'.$ansList.'</form>

						</div>

					

						<div class="casestudy_bt">

							<div class="checkside">'.$submitBut.'</div>

							<div class="casestudy_hunt finalsubmit">

								<div class="cas_empty"></div>

								<div class="cas_right chand" title="Next" onclick="huntView('.$rID.',\''.$div.'_1\',2,0);"></div>

							</div>

						</div>						

					</div>';
					

		return $return;	

	}

	

	//=== Played Details

	function checkTeamPlayed($conn,$accID){

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$numRow = $result->num_rows;

		if($numRow==0){

			//=== Insert Learnin group

			$score 	= 0;

			$cuDate	= date('Y-m-d H:i:s');

			$data	= json_encode(array('IN'=>$cuDate));

			$stmt 	= $conn->prepare("INSERT INTO team_play_details (team_play_pkid,score,data) VALUES (?,?,?)");

			$stmt->bind_param("iis", $accID,$score,$data);

			$stmt->execute();

			$stmt->close();

		}

	}

	

	//=== Update Play details Clue

	function updatePlaydetailsClue($conn,$accID,$score,$thId,$order,$ty,$rid){

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();

		

		$data  = json_decode($row['data'],true); 

		$total = (isset($data['score'][$rid])) ? $data['score'][$rid] : 0;

		

		$tyName = ($ty==1)?'treasure':'card';

		

		//=== Check Already Exist

		if(!isset($data['code'][$tyName.'-'.$thId])){

			if(!isset($data['clue'][$tyName.'-order-'.$thId.'-'.$order])){

				$total = ($total - intval($score));

				$data['score'][$rid] 							  = $total;

				$data['clue'][$tyName.'-order-'.$thId.'-'.$order] = $score;

				$setData = json_encode($data);			

				

				//=== Update Team play Details

				$stmt 	= $conn->prepare("UPDATE team_play_details SET score=?,data=? WHERE team_play_pkid=?");

				$stmt->bind_param("isi", $total,$setData,$accID);

				$stmt->execute();

				$stmt->close();

			}

		}

		

		return $total;		

	}

		

	//==== Check Treasure Hunt Clue

	function checkTreasureHuntClue($conn,$id,$accID){

		$ex 	= explode('-',$id);

		$thId 	= $ex[0];

		$order 	= $ex[1];

		$rid 	= $ex[2];

		

		$sql 	= "SELECT lu_clue FROM mdm_learning_unit WHERE lu_id='".$thId."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		

		$score 	= 0;

		$clue 	= '';				

		$gClue = json_decode($row['lu_clue'],true);

		foreach($gClue as $clueVal){

			if($clueVal['order'] == $order){

				$score 	= (!empty($clueVal['score']))?$clueVal['score']:0;

				$clue 	= $clueVal['clue'];

				break;

			}			

		}

		

		//=== Update Play details

		$total = $this->updatePlaydetailsClue($conn,$accID,$score,$thId,$order,1,$rid);

		

		$return = array('total'=>$total,'clue'=>$clue);	

		return $return;

	}

	

	//==== Check Card Clue

	function checkCardClue($conn,$id,$accID){

		$ex 	= explode('-',$id);

		$thId 	= $ex[0];

		$order 	= $ex[1];

		$let 	= $ex[2];

		$rid 	= $ex[3];

		

		$sql 	= "SELECT cg_clue FROM card_group WHERE cg_id='".$thId."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		

		$score 	= 0;

		$clue 	= '';				

		$gClue = json_decode($row['cg_clue'],true);

		foreach($gClue as $clueVal){

			if($clueVal['order'] == $order){

				$score 	= (!empty($clueVal['score']))?$clueVal['score']:0;

				$clue 	= $clueVal['clue'];

				break;

			}			

		}

		

		//=== Update Play details

		$total = $this->updatePlaydetailsClue($conn,$accID,$score,$thId,$order,2,$rid);

		

		$return = array('total'=>$total,'clue'=>$clue);	

		return $return;

	}

	

	//==== Check Treasure Hunt Code

	function checkTreasureHuntCode($conn,$id,$code,$accID,$rid,$insID){

		$smsg 	 = '';

		$nextID  = '';

		$cround	 = '';

		

		//==== Check Current Treasure Hunt

		$sql 	= "SELECT lu_group_pkid,lu_sequence,lu_hunt_score FROM mdm_learning_unit WHERE lu_id='".$id."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		

		$gID 	= $row['lu_group_pkid'];

		$gOD 	= $row['lu_sequence'];

		$score 	= (!empty($row['lu_hunt_score']))?$row['lu_hunt_score']:0;

		

		//==== Check Next Treasure Hunt

		$sql 	= "SELECT lu_hunt_code,lu_id FROM mdm_learning_unit WHERE lu_group_pkid='".$gID."' AND lu_sequence > '".$gOD."' ORDER BY lu_sequence ASC LIMIT 2";

		$result = $conn->query($sql);

		$numRow = $result->num_rows;



		$total	= 0;

		$check  = 'NO';

		$complete = 0;

		if($numRow > 0){

			$row 	= $result->fetch_assoc();			

			$code 	= strtolower($code);

			$dbCode = array_map('trim',explode(',',strtolower($row['lu_hunt_code'])));

			//=== Check Code

			if(in_array($code,$dbCode)){

				$check  = 'YES';

				$nextID = $row['lu_id'];

				//=== Update Play details

				$upreturn = $this->updatePlaydetailsCode($conn,$accID,$score,$id,$nextID,$rid,$insID,$numRow,1);

				$total 	= $upreturn['total'];

				$cround = $upreturn['cround'];

				$complete = $upreturn['completed'];

				

				//=== Skillsda LMS

				if($complete==1){

					$this->apiLMA();

				}

								

				//=== Get Round Stage

				$aniMsg = $this->animationMsg($conn,$rid);

				$smsg 	= $aniMsg['stage_msg'];				

			}

		}

		

		$return = array('total'=>$total,'check'=>$check,'smsg'=>$smsg,'nextid'=>$nextID,'curid'=>$id,'cround'=>$cround,'completed'=>$complete);	

		return $return;

	}

	

	//=== Update Play details Code

	function updatePlaydetailsCode($conn,$accID,$score,$id,$nextID,$rid,$insID,$numRow,$type){

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();

		

		$cround = $row['current_round'];

		$data  	= json_decode($row['data'],true);

		$total 	= (isset($data['score'][$rid])) ? $data['score'][$rid] : 0;

		$complete = $row['completed'];

		

		//=== Check Already Exist

		if(!isset($data['code']['treasure-'.$id])){

			$total = ($total + intval($score));



			$data['score'][$rid] 				= $total;

			$data['code']['treasure-'.$id] 		= $score;

			$data['open']['treasure-'.$nextID] 	= 'IN';

			$setData = json_encode($data);

			

			//=== Update Team play Details

			$stmt 	= $conn->prepare("UPDATE team_play_details SET score=?,data=? WHERE team_play_pkid=?");

			$stmt->bind_param("isi", $total,$setData,$accID);

			$stmt->execute();

			$stmt->close();

			

			//=== Check and Allow Next Round

			if(($numRow==1 && $type==1) || ($numRow==0 && $type==2) ){

				$getRetu = $this->allowNextRound($conn,$accID,$rid,$insID,$cround);

				$cround	 = $getRetu['round'];

				$complete= $getRetu['complete'];

				

				//=== Skillsda LMS

				if($complete==1){

					$this->apiLMA();

				}

			}

		}

		

		$return = array('total'=>$total,'cround'=>$cround,'completed'=>$complete);

		

		return $return;		

	}

	

	//=== Check and Allow Next Round

	function allowNextRound($conn,$accID,$rid,$insID,$cround){

		$return 	= $cround;

		$nround 	= 0;

		$complete 	= 0;



		$sql 	= "SELECT *  FROM gm_instance_round WHERE ir_instance_pkid ='".$insID."' ORDER BY ir_order ASC";

		$result	= $conn->query($sql);

		while($row = $result->fetch_assoc()){

			if($complete ==1){

				$complete = 0;

			}

			if($row['ir_round_pkid'] == $rid && $row['ir_order'] > $cround){

				$nround 	= $row['ir_order'];

				$complete 	= 1;

			}		

		}

		

		//==== Set Next Round

		if($nround!=0){

			$stmt 	= $conn->prepare("UPDATE team_play_details SET current_round=?,completed=? WHERE team_play_pkid=?");

			$stmt->bind_param("iii", $nround,$complete,$accID);

			$stmt->execute();

			$stmt->close();

			$return = $nround;

		}

		$set = array('round'=>$return,'complete'=>$complete);

		return $set;		

	}

	

	//=== Check Treasure Hunt Open

	function checkTreasureHuntOpen($conn,$hid,$accID,$rid){

		$return		  	= array();

		$return['ch'] 	= 'OUT';

		$return['or'] 	= 0;

		$return['smsg'] = '';

				

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();		

		$data  	= json_decode($row['data'],true);

		

		//$total  = $row['score'];

		$total   = (isset($data['score'][$rid])) ? $data['score'][$rid] : 0;



		//=== Check Already Exist	

		if(isset($data['open']['treasure-'.$hid])){

			$return['ch'] = 'IN';

		}

		else{	

			//=== Check Hunt Code

			$sql 	= "SELECT lu_hunt_code,lu_sequence,lu_hunt_score FROM mdm_learning_unit WHERE lu_id='".$hid."'";

			$result = $conn->query($sql);

			$row 	= $result->fetch_assoc();

			if(empty($row['lu_hunt_code'])){

				$return['ch'] = 'IN';

				$return['or'] = $row['lu_sequence'];			

				$score 	= (!empty($row['lu_hunt_score']))?$row['lu_hunt_score']:0;

				

				$total  = ($score+$total);

				

				//=== Updat If IN

				$data['score'][$rid] 			= $total;

				$data['open']['treasure-'.$hid] = 'IN';

				$setData = json_encode($data);				

				

				//=== Update Team play Details

				$stmt 	= $conn->prepare("UPDATE team_play_details SET data=?,score=? WHERE team_play_pkid=?");

				$stmt->bind_param("sii", $setData,$total,$accID);

				$stmt->execute();

				$stmt->close();	

				

				//=== Get Round Stage

				$aniMsg = $this->animationMsg($conn,$rid);

				$return['smsg'] = $aniMsg['stage_msg'];

			}

		}	

		$return['total'] = $total;

					

		return $return;

	}

	

	//=== Animation Msg

	function animationMsg($conn,$rid){

		$sql 	= "SELECT

							animation_msg.*

						FROM

							gm_round

							INNER JOIN animation_msg 

								ON (gm_round.ro_animation_msg_pkid = animation_msg.id)

						WHERE (gm_round.ro_id ='".$rid."')";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		return $row;

	}

	

	//=== Open Stage

	function openStage($conn,$hid,$code,$accID,$rid,$insID){

		$return		   = array();

		$return['ch']  = 'OUT';

		$return['or']  = 0;

		$cround	 	   = '';

		$total		   = 0;

		$smsg		   = '';

		

		//=== Check Hunt Code

		$sql 	= "SELECT lu_hunt_code,lu_sequence,lu_hunt_score,lu_group_pkid FROM mdm_learning_unit WHERE lu_id='".$hid."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		$complete = 0;

		

		$code 	= strtolower($code);

		$dbCode = array_map('trim',explode(',',strtolower($row['lu_hunt_code'])));

		//=== Check Code



		if(in_array($code,$dbCode)){

			$score 		  = (!empty($row['lu_hunt_score']))?$row['lu_hunt_score']:0;

			$return['ch'] = 'IN';

			$gID 		  = $row['lu_group_pkid'];

			$gOD 		  = $row['lu_sequence'];

			

			//==== Check Next Treasure Hunt

			$sql 	= "SELECT lu_hunt_code,lu_id FROM mdm_learning_unit WHERE lu_group_pkid='".$gID."' AND lu_sequence > '".$gOD."' ORDER BY lu_sequence ASC LIMIT 1";

			$result = $conn->query($sql);

			$numRow = $result->num_rows;

			

			//=== Update Play details

			$upreturn = $this->updatePlaydetailsCode($conn,$accID,$score,$hid,$hid,$rid,$insID,$numRow,2);

			

			$total 	= $upreturn['total'];

			$cround = $upreturn['cround'];

			$complete= $upreturn['completed'];

			

			//=== Skillsda LMS

			if($complete==1){

				$this->apiLMA();

			}

			

			//=== Get Round Stage

			$aniMsg = $this->animationMsg($conn,$rid);

			$smsg 	= $aniMsg['stage_msg'];	


		}

		

		$return['total'] 	= $total;

		$return['cround']	= $cround;

		$return['smsg']		= $smsg;

		$return['completed']= $complete;		

		return $return;

	}

	

	//=== Find Round Max limit

	function cardRoundMax($conn,$rid,$accID,$ins){

		

		//=== Check card Max

		$sql 	= "SELECT

						card_group.cg_max as maxlimit

					FROM

						gm_round

						INNER JOIN card_group 

							ON (gm_round.ro_treasure_group_pkid = card_group.cg_id)

						INNER JOIN gm_instance_round 

							ON (gm_instance_round.ir_round_pkid = gm_round.ro_id)

					WHERE (gm_instance_round.ir_instance_pkid ='".$ins."'

						AND gm_instance_round.ir_round_pkid ='".$rid."')";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		$max 	= $row['maxlimit'];

		

		//=== Check card Used	

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();		

		$data  	= json_decode($row['data'],true);		

		$return = (isset($data['round'][$rid]['cardused'])) ? ($max-$data['round'][$rid]['cardused']) : $max;

		

		return $return;

	}

	

	//=== Open Card

	function openCard($conn,$hid,$rid,$accID,$ins){

		$return		  = array();

		$return['ch'] = 'OUT';

		$return['max'] = 0;

		$return['msg'] = '';

				

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();		

		$data  	= json_decode($row['data'],true);		

		//=== Check Already Exist	

		if(isset($data['open']['card-'.$hid])){

			$return['ch'] = 'AIN';

		}

		else{

			//=== Check card Max

			$sql 	= "SELECT

							card_group.cg_max as maxlimit,card_group.cg_max_msg as maxmsg

						FROM

							gm_round

							INNER JOIN card_group 

								ON (gm_round.ro_treasure_group_pkid = card_group.cg_id)

							INNER JOIN gm_instance_round 

								ON (gm_instance_round.ir_round_pkid = gm_round.ro_id)

						WHERE (gm_instance_round.ir_instance_pkid ='".$ins."'

							AND gm_instance_round.ir_round_pkid ='".$rid."')";

			$result = $conn->query($sql);

			$row 	= $result->fetch_assoc();

			$max 	= $row['maxlimit'];

			

			$balance = (isset($data['round'][$rid]['cardused'])) ? ($max-$data['round'][$rid]['cardused']) : $max;



			if($balance > 0){

				$bal = ($balance-1);

				//=== Updat If IN

				$data['round'][$rid]['cardused'] 	= ($max-$bal);

				$data['open']['card-'.$hid] 		= 'IN';

				$setData = json_encode($data);	

				

				//=== Update Team play Details

				$stmt 	= $conn->prepare("UPDATE team_play_details SET data=? WHERE team_play_pkid=?");

				$stmt->bind_param("si", $setData,$accID);

				$stmt->execute();

				$stmt->close();	

				

				$return['ch'] = 'IN';

				$return['max'] = $bal;

			}

			else{

				$return['msg'] = $row['maxmsg'];

			}

		}				

		return $return;

	}

	

	//=== Submit Card Answer

	function submitAnswer($conn,$order,$rid,$accID,$ins){

		$sql 	= "SELECT

						card_group.cg_answer as cganswer,ir_order as roundorder

					FROM

						gm_round

						INNER JOIN card_group 

							ON (gm_round.ro_treasure_group_pkid = card_group.cg_id)

						INNER JOIN gm_instance_round 

							ON (gm_instance_round.ir_round_pkid = gm_round.ro_id)

					WHERE (gm_instance_round.ir_instance_pkid ='".$ins."'

						AND gm_instance_round.ir_round_pkid ='".$rid."')";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		

		$score 	= 0;

		$gClue = json_decode($row['cganswer'],true);

		foreach($gClue as $clueVal){

			if($clueVal['order'] == $order){

				$score 	= (!empty($clueVal['score']))?$clueVal['score']:0;					

				break;

			}			

		}

		$cround = $row['roundorder'];

		

		//=== Check game Completed

		$sqlC 		= "SELECT * FROM gm_instance_round WHERE ir_instance_pkid='".$ins."' AND ir_order > '".$cround."'";

		$resultC 	= $conn->query($sqlC);

		$numRowC 	= $resultC->num_rows;		



		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();	

			

		$data   = json_decode($row['data'],true);

		$total  = (isset($data['score'][$rid])) ? $data['score'][$rid] : 0;



		$complete = $row['completed'];

		if(empty($numRowC)){

			$complete = 1;

		}



		if(!isset($data['answer']['round_'.$rid]['order_'.$order])){

			$total  = ($total+$score);

			$data['score'][$rid] = $total;



			$data['answer']['round_'.$rid]['order_'.$order] = $score;

			$setData = json_encode($data);	

				

			//=== Update Team play Details

			$stmt 	= $conn->prepare("UPDATE team_play_details SET score=?,data=?,current_round=?,completed=? WHERE team_play_pkid=?");

			$stmt->bind_param("isiii", $total,$setData,$cround,$complete,$accID);

			$stmt->execute();

			$stmt->close();

		}

		

		//=== Skillsda LMS

		if($complete==1){

			$this->apiLMA();

		}

			

		$return	= array('total'=>$total,'cround'=>$cround,'completed'=>$complete);

		return $return;

	}

	

	//=== Submit Survival Quest

	function submitSurvivalQuest($conn,$proArray,$accID,$ins){

		$setScore = 0;

		

		$suArray = array();

		foreach($proArray as $key=>$val){

			$exp 	= explode('_',$key);

			$suArray[$exp[1]] = $val;

		}

		$rid 	= $exp[2];



		$sql 	= "SELECT

						ir_order as roundorder,su_id,su_sequence,su_score,su_secondary_sequence,su_secondary_score

					FROM

						gm_round

						INNER JOIN survival_group 

							ON (gm_round.ro_treasure_group_pkid = survival_group.sg_id)

						INNER JOIN survival_unit 

							ON (survival_group.sg_id = survival_unit.su_survival_group_pkid)

						INNER JOIN gm_instance_round 

							ON (gm_instance_round.ir_round_pkid = gm_round.ro_id)

					WHERE (gm_instance_round.ir_instance_pkid ='".$ins."'

						AND gm_instance_round.ir_round_pkid ='".$rid."') 

					ORDER BY su_sequence ASC";

		$result = $conn->query($sql);

		while($row 	= $result->fetch_assoc()) {

			$suid 		= $row['su_id'];

			$cround 	= $row['roundorder'];

			$sequence 	= $row['su_sequence'];

			$score 		= $row['su_score'];

			$secscore 	= $row['su_secondary_score'];

			

			if(isset($suArray[$suid]) && $suArray[$suid] == $sequence){

				$setScore = ($setScore+$score);

			}

			elseif(!empty($row['su_secondary_sequence'])){

				$secPr = array_map('trim', explode(',', $row['su_secondary_sequence']));

				if(in_array($suArray[$suid],$secPr)){

					$setScore = ($setScore+$score);

				}

			}			

		}



		//=== Check game Completed

		$sqlC 		= "SELECT * FROM gm_instance_round WHERE ir_instance_pkid='".$ins."' AND ir_order > '".$cround."'";

		$resultC 	= $conn->query($sqlC);

		$numRowC 	= $resultC->num_rows;

		

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();	

			

		$complete = $row['completed'];

		if(empty($numRowC)){

			$complete = 1;

		}



		$data   = json_decode($row['data'],true);

		$total  = (isset($data['score'][$rid])) ? $data['score'][$rid] : 0;

		$total  = ($total+$setScore);

		$data['score'][$rid] = $total;



		$data['survival']['round_'.$rid] 			= $proArray;

		$data['survival']['round_'.$rid]['score'] 	= $setScore;

		$setData = json_encode($data);	

			

		//=== Update Team play Details

		$stmt 	= $conn->prepare("UPDATE team_play_details SET score=?,data=?,current_round=?,completed=? WHERE team_play_pkid=?");

		$stmt->bind_param("isiii", $total,$setData,$cround,$complete,$accID);

		$stmt->execute();

		$stmt->close();

		

		//=== Skillsda LMS

		if($complete==1){

			$this->apiLMA();

		}

		

		$return	= array('total'=>$total,'cround'=>$cround,'completed'=>$complete);

		return $return;

	}	

	

	//=== View Survival positive negative Msg

	function viewSurvivalMsg($conn,$suID,$prID){

		$sql 	= "SELECT su_positive,su_negative,su_sequence FROM survival_unit WHERE su_id ='".$suID."'";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		$return = ($row['su_sequence']==$prID) ? $row['su_positive'] : $row['su_negative'];

		return $return;

	}

	

	//=== Check Bargain

	function checkBargain($conn,$rid,$accID){

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();		

		$data   = json_decode($row['data'],true);

		

		//=== Check Already Exist

		if(!isset($data['API']['round_'.$rid])){

			$sql 	= "SELECT

						api_bargain.*

					FROM

						gm_round

						INNER JOIN api_bargain 

							ON (gm_round.ro_treasure_group_pkid = api_bargain.api_id)

					WHERE (gm_round.ro_id ='".$rid."')";

			$result = $conn->query($sql);

			$row 	= $result->fetch_assoc();

			

			$aiArray = array();		

			if(!empty($row['api_introduction'])){

				$aiArray[] = $row['api_introduction'];

			}

			if(!empty($row['api_scenario'])){

				$aiArray[] = $row['api_scenario'];

			}

			if(!empty($row['api_thread'])){

				$aiArray[] = $row['api_thread'];

			}

			if(!empty($row['api_response'])){

				$aiArray[] = $row['api_response'];

			}


			$apiQuestion = implode(' ',$aiArray);

			

			$cuDate		= date('Y-m-d H:i:s');

			$aiDetails  = array('date'=>$cuDate,'user'=>'You','data'=>$apiQuestion);

			$apidata 	= array();

			$apidata['API_'.$rid][] = $aiDetails;

			

			//============== API Return			

			$mss = array( array(

						'role' => 'user',

						'content' => $apiQuestion

					));

			$apiReturn  = $this->generateAiResult($mss);

			//===			

			

			$cuDate		= date('Y-m-d H:i:s');

			$aiDetails  = array('date'=>$cuDate,'user'=>'AI','data'=>$apiReturn);

			$apidata['API_'.$rid][] = $aiDetails;			

			$setApiData = json_encode($apidata);

						

			$data['API']['round_'.$rid] 		 			= 'Started';

			$data['API']['round_'.$rid.'_balance_count'] 	= $row['api_total_discussion'];

			$setData = json_encode($data);

						

			//=== Update Team play Details

			$stmt 	= $conn->prepare("UPDATE team_play_details SET data=?,api_data=? WHERE team_play_pkid=?");

			$stmt->bind_param("ssi", $setData,$setApiData,$accID);

			$stmt->execute();

			$stmt->close();

			

			$return 			= array();

			$return['sty'] 		= 1;

			$return['apitotal'] = $row['api_total_discussion'];

			$return['youdata']  = $apiQuestion;

			$return['aidata']   = $apiReturn;

			$return['apicomp']  = 'NO';

			$return['ainame']  	= $row['api_ai_name'];



			return $return;

		}

		else{

			$return 			= array();

			$return['sty'] 		= 2;

			$return['apicomp']  = (isset($data['API']['round_'.$rid.'_completed'])) ? 'YES':'NO';

			$return['apitotal'] = $data['API']['round_'.$rid.'_balance_count'];

			return $return;

		}

	}

	

	//=== API Request Bargain

	function apiRequestBargain($conn,$rid,$aitxt,$accID){

		$sql 	= "SELECT

					api_bargain.api_ai_name as ainame

				FROM

					gm_round

					INNER JOIN api_bargain 

						ON (gm_round.ro_treasure_group_pkid = api_bargain.api_id)

				WHERE (gm_round.ro_id ='".$rid."')";

		$result = $conn->query($sql);

		$row 	= $result->fetch_assoc();

		$aiName = $row['ainame'];



		//=== Select Team play Details

		$sql 	 = "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	 = $conn->query($sql);

		$row	 = $result->fetch_assoc();		

		$data    = json_decode($row['data'],true);

		$apidata = json_decode($row['api_data'],true);

		

		//=== Data		

		$balanceCount 								 = ($data['API']['round_'.$rid.'_balance_count']-1);

		$data['API']['round_'.$rid.'_balance_count'] = $balanceCount;

		

		//=== Api Data

		$cuDate		= date('Y-m-d H:i:s');

		$aiDetails  = array('date'=>$cuDate,'user'=>'You','data'=>$aitxt);

		$apidata['API_'.$rid][] = $aiDetails;

		

		//============== API Return

		$mss = array();

		foreach($apidata['API_'.$rid] as $apiVal){

			if($apiVal['user'] == 'You'){

				$msgTxt = array('role' => 'user','content' => $apiVal['data']);

			}

			else{

				$msgTxt = array('role' => 'system','content' => $apiVal['data']);

			}			

			$mss[] = $msgTxt;

		}

		$apiReturn  = $this->generateAiResult($mss);

			

		$cuDate		= date('Y-m-d H:i:s');

		$aiDetails  = array('date'=>$cuDate,'user'=>'AI','data'=>$apiReturn);

		$apidata['API_'.$rid][] = $aiDetails;

		//===

		

		//=== Update Team play Details

		$setApiData = json_encode($apidata);

		$setData 	= json_encode($data);

		

		$stmt 	= $conn->prepare("UPDATE team_play_details SET data=?,api_data=? WHERE team_play_pkid=?");

		$stmt->bind_param("ssi", $setData,$setApiData,$accID);

		$stmt->execute();

		$stmt->close();

		

		$return 			= array();

		$return['apitotal'] = $balanceCount;

		$return['aidata']   = $apiReturn;

		$return['ainame']   = $aiName;

		return $return;		

	}

	

	//=== API Completion Bargain

	function apiCompletionBargain($conn,$rid,$accID,$insID){

		//=== Select Team play Details

		$sql 	 = "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	 = $conn->query($sql);

		$row	 = $result->fetch_assoc();		

		$data    = json_decode($row['data'],true);

		$apidata = json_decode($row['api_data'],true);

		

		//=== Data		

		$sqlB 	= "SELECT

						api_bargain.*

				FROM

					gm_round

					INNER JOIN api_bargain 

						ON (gm_round.ro_treasure_group_pkid = api_bargain.api_id)

				WHERE (gm_round.ro_id ='".$rid."')";

		$resultB = $conn->query($sqlB);

		$rowB 	= $resultB->fetch_assoc();

		$apiScore 	 = $rowB['api_score'];

		$apiQuestion = $rowB['api_completion'];

		$aiName		 = $rowB['api_ai_name'];

		

		//=== API Score

		$cuDate		= date('Y-m-d H:i:s');

		$aiDetails  = array('date'=>$cuDate,'user'=>'You','data'=>$apiScore,'dp'=>'no');

		$apidata['API_'.$rid][] = $aiDetails;

		

		$mss = array();

		foreach($apidata['API_'.$rid] as $apiVal){

			if($apiVal['user'] == 'You'){

				$msgTxt = array('role' => 'user','content' => $apiVal['data']);

			}

			else{

				$msgTxt = array('role' => 'system','content' => $apiVal['data']);

			}			

			$mss[] = $msgTxt;

		}

		$apiScoreReturn  = $this->generateAiResult($mss);

		$intScore 		 = (int)$apiScoreReturn;

		$intScore		 = (!empty($intScore))? $intScore : rand(70,85);

		

		$cuDate			 = date('Y-m-d H:i:s');

		$aiDetails  	 = array('date'=>$cuDate,'user'=>'AI','data'=>$apiScoreReturn,'dp'=>'no');

		$apidata['API_'.$rid][] = $aiDetails;		

		//=====	

		

		$cuDate		= date('Y-m-d H:i:s');

		$aiDetails  = array('date'=>$cuDate,'user'=>'You','data'=>$apiQuestion,'dp'=>'no');

		$apidata['API_'.$rid][] = $aiDetails;

		

		//============== API Return

		$mss = array();

		foreach($apidata['API_'.$rid] as $apiVal){

			if($apiVal['user'] == 'You'){

				$msgTxt = array('role' => 'user','content' => $apiVal['data']);

			}

			else{

				$msgTxt = array('role' => 'system','content' => $apiVal['data']);

			}			

			$mss[] = $msgTxt;

		}

		$apiReturn  = $this->generateAiResult($mss);

		//===			

		

		$cuDate		= date('Y-m-d H:i:s');

		$aiDetails  = array('date'=>$cuDate,'user'=>'AI','data'=>$apiReturn);

		$apidata['API_'.$rid][] = $aiDetails;			

		$setApiData = json_encode($apidata);

					

		$data['API']['round_'.$rid.'_completed'] = 'Yes';

		$data['score'][$rid] = $intScore;

		$setData = json_encode($data);

		

		//=== Find current round and completion

		$roundDetails 	= $this->findCurrentRoundCompletion($conn,$insID,$rid);

		$nround 		= $roundDetails['nround'];

		$completed 		= $roundDetails['completed'];

					

		//=== Update Team play Details

		$stmt 	= $conn->prepare("UPDATE team_play_details SET data=?,api_data=?,current_round=?,completed=? WHERE team_play_pkid=?");

		$stmt->bind_param("ssiii", $setData,$setApiData,$nround,$completed,$accID);

		$stmt->execute();

		$stmt->close();

		

		//=== Skillsda LMS

		if($completed==1){

			$this->apiLMA();

		}

		

		$return 				  = array();

		$return['youdata_score']  = $apiScore;

		$return['aidata_score']   = $intScore;

		$return['ainame']   	  = $aiName;

		$return['youdata']  	  = $apiQuestion;

		$return['aidata']   	  = $apiReturn;

		$return['cround']   	  = $nround;

		$return['completed']   	  = $completed;

		return $return;		

	}

	

	//=== Find current round and completion

	function findCurrentRoundCompletion($conn,$insID,$rid){

		$sql 	= "SELECT *  FROM gm_instance_round WHERE ir_instance_pkid ='".$insID."' ORDER BY ir_order ASC";

		$result	= $conn->query($sql);

		$nround 	= '';

		$completed  = 1;

		while($row = $result->fetch_assoc()){

			if(!empty($nround)){

				$completed  = 0;

			}

			if($row['ir_round_pkid'] == $rid){

				$nround = $row['ir_order'];

			}			

		}

		$return = array('nround'=>$nround,'completed'=>$completed);

		return $return;

	}

	

	//=== Generate Ai Result

	function generateAiResult($mss){

		$apiKey = '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';

		$model 	= 'gpt-3.5-turbo';

		

		// Prepare the data to be sent to the OpenAI API

		$data = array(

			'messages' 	=> $mss,

			'model' 	=> $model

		);

	

		// Encode the data for sending

		$postData = json_encode($data);



		// Set up the API request

		$curl = curl_init();

		curl_setopt_array($curl, array(

			CURLOPT_URL => "https://api.openai.com/v1/chat/completions",

			CURLOPT_RETURNTRANSFER => true,

			CURLOPT_ENCODING => "",

			CURLOPT_MAXREDIRS => 10,

			CURLOPT_TIMEOUT => 30,

			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

			CURLOPT_CUSTOMREQUEST => "POST",

			CURLOPT_POSTFIELDS => $postData,

			CURLOPT_HTTPHEADER => array(

				"Authorization: Bearer ".$apiKey,

				"Content-Type: application/json"

			),

		));

			

		$response 	= curl_exec($curl);

		$err 		= curl_error($curl);		

		curl_close($curl);

		

		if ($err) {

			$output	= 'Error #:' . $err;

		} else {

			$decoded_response = json_decode($response, true);

			$output	= nl2br($decoded_response['choices'][0]['message']['content']);

		}

		return $output;			

	}



	//=== Get Round Score

	function getRoundScore($conn,$rid,$accID){

		$sql 	 = "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	 = $conn->query($sql);

		$row	 = $result->fetch_assoc();		

		$data    = json_decode($row['data'],true);	

		$score   = (isset($data['score'][$rid])) ? $data['score'][$rid] : 0;



		$return = array('score'=>$score);

		return $return;

	}	

	

	//===== API LMA Status

	function apiLMA(){

		if(isset($_SESSION['login_out'])){

			$userLms 	= explode('_',$_SESSION['login_all']['team_email']);

			$userid 	= $userLms[0];

			$couseIds	= $_SESSION['com_instanceID']."_1";

			

			$postRequest = array("wsfunction"=>"local_saras_completion_track",

							  "wstoken"=>"f5cca75ac03c41a1420fa5dffa38884f",

							  "userid"=>$userid,

							  "coursecode"=>$couseIds,

							  "score"=>"100",

							  "status"=>"Completed");

			

			$cURLConnection = curl_init('https://skillsdalms.com/webservice/rest/server.php?moodlewsrestformat=json');

			curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $postRequest);

			curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

			

			$apiResponse = curl_exec($cURLConnection);

			curl_close($cURLConnection);

		}

	}

	

	function trim_value(&$value){ 

	  $value = trim($value);

	}

	

	//==== DigiSim Group

	function digiSimGroup($conn,$id){

		//=== Digisim

		$sqlD 	 = "SELECT * FROM digisim WHERE di_id='".$id."'";

		$resultD = $conn->query($sqlD);

		$rowD 	 = $resultD->fetch_assoc();

		

		//=== 1:Random, 2:Sequence 

		$orderby = ($rowD['di_display']==1)? 'RAND()':'digisim_response.dr_order ASC'; 

	

		//=== Message
		$sql 	= "SELECT DISTINCT

						sub_channels.ch_level

						, sub_channels.ch_desc

						, sub_channels.ch_id

						, mdm_injectes.lg_name

						, sub_channels.ch_image

						, mdm_injectes.lg_description

						, digisim.di_scoring_basis

						, digisim.di_min_select

						, digisim.di_result_type

						, digisim.di_max_score
						
					FROM

						digisim

						INNER JOIN mdm_injectes 

							ON (digisim.di_injects_id = mdm_injectes.lg_id)

						INNER JOIN sub_channels 

							ON (mdm_injectes.lg_id = sub_channels.in_group_pkid)

					WHERE digisim.di_id = '".$id."'
    				AND sub_channels.ch_status = '1' 
					AND EXISTS (SELECT 1 FROM digisim_message WHERE dm_injectes_pkid = sub_channels.ch_id)
					ORDER BY sub_channels.ch_sequence ASC  ";



		$result = $conn->query($sql);

		$msg 	= array();

		$selTy	= '';

		$resTy	= '';

		while($row 	= $result->fetch_assoc()) {

			$msg[] = $row;

			$selTy = $row['di_scoring_basis'].'_'.$row['di_min_select'];

			$resTy = $row['di_result_type'].'_'.$row['di_max_score'];

		}



		//=== Card

		$card = array();

		$sql 	= "SELECT

					digisim_response.dr_order

					, digisim_response.dr_id

					, digisim_response.dr_tasks

					, digisim_response.dr_score_pkid

				FROM

					digisim_response

					INNER JOIN sub_index 

						ON (digisim_response.dr_response_pkid = sub_index.ln_id)

					INNER JOIN mdm_response 

						ON (sub_index.ix_group_pkid = mdm_response.lg_id)

				WHERE (digisim_response.dr_digisim_pkid ='".$id."'

					AND sub_index.ln_status ='1'

					AND mdm_response.lg_status ='1')

				ORDER BY ".$orderby;

		$result = $conn->query($sql);

		while($row 	= $result->fetch_assoc()) {

			$card[] = $row;

		}

		

		//=== Score Type

		$score = array();

		$sql 	= "SELECT

						scoretype.st_display_name

						, scoretype.st_desc

						, scoretype_value.stv_sname

						, scoretype_value.stv_name

						, scoretype_value.stv_color

						, scoretype_value.stv_value

						, scoretype_value.stv_id

					FROM

						digisim

						INNER JOIN scoretype 

							ON (digisim.di_scoretype_id = scoretype.st_id)

						INNER JOIN scoretype_value 

							ON (scoretype.st_id = scoretype_value.stv_scoretype_pkid)

					WHERE (digisim.di_id ='".$id."'

						AND scoretype.st_status ='1')

					ORDER BY scoretype_value.stv_id ASC";

		$result = $conn->query($sql);

		while($row 	= $result->fetch_assoc()) {

			$score[] = $row;

		}		

		

		$return = array();

		$return['msg'] 		= $msg;

		$return['card'] 	= $card;

		$return['score'] 	= $score;

		$return['simid'] 	= $id;

		$return['selty']	= $selTy;

		$return['resTy']	= $resTy;

		return $return;

	}

	

	//=== DigiSim Main
// By Maria
	function digiSimDiv($treasure,$div,$pr='',$nx='',$rID=0,$roundID=0){
		$simID = $treasure['simid'];
		//=== Previous
		if($pr==''){
			$class 	= 'disblk';
			$hand 	= 'cblock';
		}
		else{
			$class 	= 'disnone';
			$hand 	= 'chand';
		}
		//=== Next
		$nHand = ($nx=='') ? 'cblock':'chand';		
		//=== Message
		$msgTd	= '';
		$msgT	= '';	
		$msgM	= '';
	 foreach($treasure['msg'] as $msgVal){
			$msgT	= $msgVal['lg_name'];
			$msgM	= $msgVal['lg_description'];
			$msgTd.='<tr id="'.$roundID.'_'.$msgVal['ch_id'].'" class="simimagtd">
				<td align="left" valign="middle" class="porel" width="20">
				<img src="images/channels/'.$msgVal['ch_image'].'" alt="'.$msgVal['ch_level'].'" title="'.$msgVal['ch_level'].'" align="middle" border="0" class="simimag" >
				</td>
				<td class="msgtxtss" valign="middle" height="30">'.$msgVal['ch_level'].'</td>
				<td width="80" valign="middle"><div class="msgdiv_count" id="view_'.$roundID.'_'.$msgVal['ch_id'].'" ></div>            
				</td></tr>';
		}

		$msgTable='<table width="95%" border="0" cellspacing="0" cellpadding="0" class="padl20 padt20">
					  <tr>
						<td align="left" colspan="2" valign="top" class="simtitle2"><span class="simtitle">'.$msgT.'</span><p>'.$msgM.'</p></td>
					  </tr>
					  <tr>
						<td width="320" align="left" valign="top">
							<div class="msgl_div">
								<table width="100%" border="0" cellspacing="0" cellpadding="3" class="padt5">
									'.$msgTd.'	
								</table>
							</div>
						</td>
						<td  align="left" valign="top">
						<div id="msg2_'.$roundID.'" class="padl20 msgr_div" >			
							</div>
						</td>
					  </tr></table>';
		//=== Score Type
		$pri		= array();
		$selScore	= array();
		foreach($treasure['score'] as $scoreVal){
			$drag = '';
			if(!isset($this->data['bcm'][$roundID]['submit'])){
				$drag = ' draggable="true" ondragstart="drag(event)" ';
			}			
			$colore = ' style="background: radial-gradient(circle, '.$scoreVal['stv_color'].', #ffffff); border:'.$scoreVal['stv_color'].' solid 2px;" ';
			$pri[$scoreVal['stv_id']] = '<div class="survivalclue dgiclmgnl" '.$colore.' '.$drag.' id="drg'.$roundID.'_'.$scoreVal['stv_id'].'" >'.$scoreVal['stv_sname'].'<div class="scorenameview" style="color:'.$scoreVal['stv_color'].';">'.$scoreVal['stv_name'].'</div></div>
			
										<div class="disnone" id="drg'.$roundID.'_'.$scoreVal['stv_id'].'_1" >
											<div class="survivalclue" '.$colore.' id="drg'.$roundID.'_'.$scoreVal['stv_id'].'_2" >'.$scoreVal['stv_sname'].'</div>
										</div>';
			$selScore[$scoreVal['stv_id']] = '<div class="survivalclue" '.$colore.' id="drg'.$roundID.'_'.$scoreVal['stv_id'].'_2" >'.$scoreVal['stv_sname'].'</div>';
		}
		$getClues ='<div class="bellmsgtxt mgnt5 mgnb10">'.$scoreVal['st_display_name'].'</div>';
		$getClues.=implode('',$pri);		
		//=== Title Box
		$box 	= '';
		$hunt 	= '';
		$ti 	= 1;		
		foreach($treasure['card'] as $treasureName){
			$hid 		= $treasureName['dr_id'];			
			$prioList ='';			
			if(isset($this->data['bcm'][$roundID]['details'][$hid])){
				$prioList = $selScore[$this->data['bcm'][$roundID]['details'][$hid]];			
			}
			$droup 	= '';
			$csscli = '';
			if(!isset($this->data['bcm'][$roundID]['submit'])){
				$droup 	= ' ondrop="dropBcm(event,'.$simID.')" ondragover="allowDrop(event)" ';
				$csscli = ' clickbcm ';
			}
	
			//==== Title Image
			$box.='<div class="surindexdiv padtb">
						<div class="sec3ButtonDig t_su_'.$roundID.'" id="L6_'.$hid.'_'.$roundID.'_1">
							'.$treasureName['dr_tasks'].'
						</div>
				<div class="dragplacebcm '.$csscli.' sur'.$roundID.'" '.$droup.' id="L5_'.$hid.'_'.$roundID.'_1">
							'.$prioList.'
						</div>
						<div id="fa_'.$roundID.'_'.$hid.'" class="viewfactor"></div>
					</div>';
			$ti++;
		}		

		//===
		$submitBut = '';
		if(!isset($this->data['bcm'][$roundID]['submit'])){
			$submitBut = '<div class="checkDiv padl35"><button type="button" class="submitbut savebcm" id="'.$roundID.'" >SAVE</button></div>
							<div class="checkDiv padl35"><button type="button" class="submitbut submitbcm" id="'.$roundID.'" >SUBMIT</button></div>
							<input type="hidden" id="subdetails_'.$roundID.'" value="'.$treasure['selty'].'" />							
							<input type="hidden" id="simid_'.$roundID.'" value="'.$simID.'" />';
		}					
		$scroll = ($ti > 10)?' cardScroll':''; 
		//==== Message
		$return ='<div class="allcase '.$class.'" id="'.$div.'_1">
					 <input type="hidden" id="ch-'.$roundID.'-5" value="" />
					 <input type="hidden" id="result5_'.$roundID.'" value="'.$treasure['resTy'].'" />
					<div class="rightsidebar" id="'.$roundID.'-5">
						<div class="mgnt70">&nbsp;</div>
					</div>
					<div class="hunttitle" >'.$msgTable.'</div>

					<div class="casestudy_bt">
						<div class="casestudy_hunt">
							<div class="cas_right chand resetmsg" id="getr1_'.$roundID.'" title="Next" onclick="simGameView(\''.$div.'\',2,'.$roundID.');" ></div>
							<div class="cas_left resetmsg '.$hand.'" id="getr2_'.$roundID.'" title="Previous" '.$pr.'></div>
						</div>
					</div>
				</div>';
           //==== Game
		$return.='<div class="allcase disnone" id="'.$div.'_2">
					<div class="rightsidebar">
						<div class="mgnt70">&nbsp;</div>';
		$return.='<div style="width:100%;font-size: 13px;color: blueviolet;font-weight: 600;margin-bottom: 10px;text-align: center;">Drag & drop to set priority. You can change them before submitting.</div>';
		$return.='<div class="surlist" id="priority_L_'.$roundID.'_2" >
							<div class="setboderclu">'.$getClues.'</div>
				<div align="center" class="bellicon" onclick="simGameView(\''.$div.'\',1,'.$roundID.')">								
								<div class="bellview" id="bell_score_'.$roundID.'" >0</div>
								<img src="images/bell.png" width="50%" border="0" />
							</div>							
							<div class="bellmsgtxt chand" onclick="simGameView(\''.$div.'\',1,'.$roundID.');">'.$msgT.'</div>
		
						</div>
					</div>
					<div class="hunttitle'.$scroll.'" >'.$box.'</div>
					<div class="casestudy_bt">
						<div class="checkside" id="survivalbutton'.$roundID.'">'.$submitBut.'</div>
						<div class="casestudy_hunt">
							<div class="cas_right '.$nHand.'" title="Next" '.$nx.'></div>
							<div class="cas_left chand" title="Previous" onclick="simGameView(\''.$div.'\',1,'.$roundID.');"></div>
						</div>
					</div>
				</div>';
	    return $return;

	}

// End by Maria	

	//=== View Survival positive negative Msg

	function findResponseMsg($conn,$respID,$roundID,$accID,$simID){

		//=== Select Team play Details

		$return = 0;

		$textre = '';

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();

		

		$data  = json_decode($row['data'],true); 

		//=== Check Already Exist



		if(!isset($data['bcm'])){

			$data['bcm'] =[];

		}

		if(!isset($data['bcm'][$roundID])){

			$data['bcm'][$roundID] =[];

		}

		if(!isset($data['bcm'][$roundID]['respmsg'])){

			$data['bcm'][$roundID]['respmsg'] =[];

		}			

		

		if(!in_array($respID,$data['bcm'][$roundID]['respmsg'])){

			$data['bcm'][$roundID]['respmsg'][] = $respID;				

			

			//=== Progressive Message

			if(!isset($data['bcm'][$roundID]['msg'])){

				$data['bcm'][$roundID]['msg'] =[];

			}

			

			$injName	= '';

			$txtMsg 	= array();

			$getCount 	= count($data['bcm'][$roundID]['respmsg']);

			$sql 		= "SELECT

							digisim_message.dm_id,

							mdm_injectes.lg_name,

							sub_channels.ch_level

						FROM

							digisim

							INNER JOIN mdm_injectes 

								ON (digisim.di_injects_id = mdm_injectes.lg_id)

							INNER JOIN sub_channels 

								ON (mdm_injectes.lg_id = sub_channels.in_group_pkid)

							INNER JOIN digisim_message 

								ON (digisim_message.dm_injectes_pkid = sub_channels.ch_id)								

						WHERE (digisim.di_id ='".$simID."'

								AND sub_channels.ch_status ='1'

								AND digisim_message.dm_trigger ='3'

								AND digisim_message.dm_event ='".$getCount."'

							)";

			$result = $conn->query($sql);

			while($row 	= $result->fetch_assoc()) {

				$injName = $row['lg_name'];

				$txtMsg[$row['ch_level']] = (isset($txtMsg[$row['ch_level']])) ? $txtMsg[$row['ch_level']]+1 : 1;

				$data['bcm'][$roundID]['msg'][] = $row['dm_id'];

				$return++;

			}

			

			//=== Task Message

			$sql 	= "SELECT dr_order FROM digisim_response WHERE dr_id ='".$respID."'";

			$result = $conn->query($sql);

			$row 	= $result->fetch_assoc();

			$getOrder = $row['dr_order'];

			

			$sql 		= "SELECT

							digisim_message.dm_id,

							mdm_injectes.lg_name,

							sub_channels.ch_level

						FROM

							digisim

							INNER JOIN mdm_injectes 

								ON (digisim.di_injects_id = mdm_injectes.lg_id)

							INNER JOIN sub_channels 

								ON (mdm_injectes.lg_id = sub_channels.in_group_pkid)

							INNER JOIN digisim_message 

								ON (digisim_message.dm_injectes_pkid = sub_channels.ch_id)								

						WHERE (digisim.di_id ='".$simID."'

								AND sub_channels.ch_status ='1'

								AND digisim_message.dm_trigger ='2'

								AND digisim_message.dm_event ='".$getOrder."'

							)";

			$result = $conn->query($sql);

			while($row 	= $result->fetch_assoc()) {

				$injName = $row['lg_name'];

				$txtMsg[$row['ch_level']] = (isset($txtMsg[$row['ch_level']])) ? $txtMsg[$row['ch_level']]+1 : 1;

				$data['bcm'][$roundID]['msg'][] = $row['dm_id'];

				$return++;

			}

			

			$getText = array();;

			foreach($txtMsg as $txtKey => $txtVal){

				$getText[] = $txtVal.' New '.$txtKey.' Notification';

			}			

			$textre = 'You got '.implode(', ',$getText).'. To view, click '.$injName.' above';

			

			//=== Update Team play Details

			$setData = json_encode($data);

			$stmt 	 = $conn->prepare("UPDATE team_play_details SET data=? WHERE team_play_pkid=?");

			$stmt->bind_param("si",$setData,$accID);

			$stmt->execute();

			$stmt->close();

		}

		

		$ret	= array('msgtotal'=>$return,'msgtxt'=>$textre);

		return $ret;

	}

	

	//=== Submit Digi Bcm

	function submitDigiBcm($conn,$proArray,$accID,$ins,$simid){

		$setScore = 0;

		

		//=== Edit Digisim Changed By Maria
		$sql = "SELECT di_scoretype_id,di_max_score,di_result_type,di_scoring_logic,di_total_basis,di_analysis_id,di_digisim_category_pkid        FROM digisim WHERE di_id='".$simid."'";

		$result = $conn->query($sql);
		$rsty   = $result->fetch_assoc();

		$digisim_category = $rsty['di_digisim_category_pkid']; //  CHANNEL ID


		$scorepkid = $rsty['di_scoretype_id'];

		

		$scorLogic 	= $rsty['di_scoring_logic']; //=== 1) Atleast, 2)Actual, 3)Absolute

		$totLogic 	= $rsty['di_total_basis']; //=== 1) All, 2)Response

		$analyID	= $rsty['di_analysis_id']; //=== Analysis ID



		$maxScore	= $rsty['di_max_score']; //===  (let us say Max score is 20, and the % score calculated overall is 70%, then the score is (70/100)*20 = 14. 

		$resultType	= $rsty['di_result_type']; //=== 1:NA,2:Percentage,3:Score,4:Band

		

	

		$suArray = array();

		foreach($proArray as $key=>$val){

			$exp 	= explode('_',$key);

			$exp2 	= explode('_',$val);

			$suArray[$exp[1]] = $exp2[1];

		}

		

		$rid 	= $exp[2];		

		$getscoreIds = implode(',',array_unique($suArray));

		

		//=== Get Score Types

		$setScoreVal = array();

		$sqlS 	= "SELECT stv_id,stv_value

					FROM scoretype_value

					WHERE stv_scoretype_pkid = '".$scorepkid."'";

		$resultS = $conn->query($sqlS);

		while($rowS	= $resultS->fetch_assoc()){	

			$setScoreVal[$rowS['stv_id']] = $rowS['stv_value'];

		}

		

		//==== Get response

		$serUserScore = array();

		$sql 	= "SELECT

						gm_instance_round.ir_order AS roundorder

						, digisim_response.dr_score_pkid

						, digisim_response.dr_id

					FROM

						gm_round

						INNER JOIN gm_instance_round 

							ON (gm_round.ro_id = gm_instance_round.ir_round_pkid)

						INNER JOIN digisim 

							ON (gm_round.ro_treasure_group_pkid = digisim.di_id)

						INNER JOIN sub_index

						ON (digisim.di_response_id = sub_index.ix_group_pkid)

						INNER JOIN digisim_response 

							ON (sub_index.ln_id = digisim_response.dr_response_pkid)

					WHERE (sub_index.ln_status ='1'

						AND gm_instance_round.ir_instance_pkid ='".$ins."'

						AND gm_instance_round.ir_round_pkid ='".$rid."')						

						ORDER BY digisim_response.dr_order ASC";

		$result = $conn->query($sql);

		$tRow 	= $result->num_rows;

		$iRow	= 0;

		while($row 	= $result->fetch_assoc()) {

			$drid 		= $row['dr_id'];

			$cround 	= $row['roundorder'];

			$scoreId 	= $row['dr_score_pkid'];

						

			if(isset($suArray[$drid])){

				//=== 1) Atleast (the user should be given 100 if their score is equal or exceeds expert score)

				if($scorLogic==1){

					$expeS = $setScoreVal[$scoreId];

					$userS = $setScoreVal[$suArray[$drid]];

					if($userS >= $expeS){

						$setScore = ($setScore+100);

						$serUserScore[$drid] = 100;

					}

					else{

						$serUserScore[$drid] = 0;

					}

				}

				

				//=== 2) Actual (the user score should match expert score to get 100 else 0)

				else if($scorLogic==2){

					if($suArray[$drid] == $scoreId){

						$setScore = ($setScore+100);

						$serUserScore[$drid] = 100;

					}

					else{

						$serUserScore[$drid] = 0;

					}

				

				}

				else if($scorLogic==3){

					$expeS = $setScoreVal[$scoreId];

					$userS = $setScoreVal[$suArray[$drid]];

					

					if($expeS == $userS){

						$sc = 100;

					}

					else if($expeS > $userS){

						$sc = round(($userS/$expeS)*100);

					}

					else{

						$sc = round(($expeS/$userS)*100);

					}

					

					$setScore = ($setScore+$sc);

					$serUserScore[$drid] = $sc;

				}

				$iRow++;

			}

			else{

				$serUserScore[$drid] = 0;				

			}			

		}

		

		//=== 1) All, 2)Response

		if($setScore > 0){

			if($totLogic==1){

				$setScore = round(($setScore/$tRow));	

			}

			else{

				$setScore = round(($setScore/$iRow));	

			}

		}



		//=== Check game Completed

		$sqlC 		= "SELECT * FROM gm_instance_round WHERE ir_instance_pkid='".$ins."' AND ir_order > '".$cround."'";

		$resultC 	= $conn->query($sqlC);

		$numRowC 	= $resultC->num_rows;

		

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();	

			

		$complete = $row['completed'];

		if(empty($numRowC)){

			$complete = 1;

		}



		$data   = json_decode($row['data'],true);

		$total  = $setScore;

		$data['score'][$rid] = $total;



		$data['bcm'][$rid]['details']	= $suArray;

		$data['bcm'][$rid]['score'] 	= $setScore;

		$data['bcm'][$rid]['userscore'] = $serUserScore;

		$data['bcm'][$rid]['submit'] 	= 1;

		//$data['bcm'][$rid]['digisim']	= $simid; Changes by Maria
		$data['bcm'][$rid]['digisim']          = $simid;
		$data['bcm'][$rid]['digisim_category'] = $digisim_category; //  SAVE CHANNEL


		$data['bcm'][$rid]['analysis']	= $analyID;	

		

		$setData = json_encode($data);	

			

		//=== Update Team play Details

		$stmt 	= $conn->prepare("UPDATE team_play_details SET score=?,data=?,current_round=?,completed=? WHERE team_play_pkid=?");

		$stmt->bind_param("isiii", $total,$setData,$cround,$complete,$accID);

		$stmt->execute();

		$stmt->close();

		

		//=== Skillsda LMS

		if($complete==1){

			$this->apiLMA();

		}

		if($resultType==3){

			$total = round(($total/100)*$maxScore);

		}

		else{

			$total = 0;

		}

		

		$return	= array('total'=>$total,'cround'=>$cround,'completed'=>$complete);

		return $return;

	}

	

	//=== Save Digi Bcm

	function saveDigiBcm($conn,$proArray,$accID,$ins){

		$setScore = 0;

		

		$suArray = array();

		foreach($proArray as $key=>$val){

			$exp 	= explode('_',$key);

			$exp2 	= explode('_',$val);

			$suArray[$exp[1]] = $exp2[1];

		}

		$rid 	= $exp[2];		

		

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();



		$data   = json_decode($row['data'],true);

		$data['bcm'][$rid]['details']	= $suArray;		

		$setData = json_encode($data);	

			

		//=== Update Team play Details

		$stmt 	= $conn->prepare("UPDATE team_play_details SET data=? WHERE team_play_pkid=?");

		$stmt->bind_param("si", $setData,$accID);

		$stmt->execute();

		$stmt->close();

		

		$return	= array('total'=>0);

		return $return;

	}

	

	//=== View Digi Bcm Msg

	function viewBcmMsg($conn,$accID,$ins,$rid,$channID){		

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();

		$data   = json_decode($row['data'],true);

		

		//=== Already View MSG		

		$viewIDs = array(0);

		if(isset($data['bcm'][$rid]['viewmsg'])){

			$viewIDs = $data['bcm'][$rid]['viewmsg'];

		}

		

		if(!isset($data['bcm'])){

			$data['bcm'] =[];

		}

		if(!isset($data['bcm'][$rid])){

			$data['bcm'][$rid] =[];

		}

		if(!isset($data['bcm'][$rid]['viewmsg'])){

			$data['bcm'][$rid]['viewmsg'] = array();

		}

		

		//=== Select Channels Name 

		$sqlc 	= "SELECT ch_level FROM sub_channels WHERE ch_id ='".$channID."'";
		

		$resultc= $conn->query($sqlc);

		$rowc	= $resultc->fetch_assoc();

		//=== Get Noramal Msg
		// by Maria

		$full	= array();
		$getMsh = array();
		// === Get channel ID safely
		if (!empty($data['bcm'][$rid]['digisim_category'])) {
			// Already saved → use it
			$channelID = $data['bcm'][$rid]['digisim_category'];
		} else {
			// Not saved → find it from DB using instance + round
			 $sqlCh = "SELECT d.di_digisim_category_pkid FROM gm_instance_round gir INNER JOIN gm_round gr ON gr.ro_id = gir.ir_round_pkid INNER JOIN digisim d ON d.di_id = gr.ro_treasure_group_pkid WHERE gir.ir_instance_pkid = '".$ins."' AND gir.ir_round_pkid = '".$rid."'
			LIMIT 1 ";
			$resCh = $conn->query($sqlCh);
			$rowCh = $resCh->fetch_assoc();

			$channelID = $rowCh['di_digisim_category_pkid'];

			// Save it so next time it is available
			$data['bcm'][$rid]['digisim_category'] = $channelID;
		}
						error_log('VIEW BCM | RID='.$rid.' | CHANNEL_ID='.$channelID);
		$sql = "SELECT * FROM digisim_message WHERE dm_injectes_pkid = '".$channID."' AND dm_digisim_pkid = '".$channelID."' AND dm_trigger = 1";
		$result	= $conn->query($sql);
		while($row = $result->fetch_assoc()) {
			error_log(
			'SAVED MSG | ID='.$row['dm_id'].
			' | MSG_CHANNEL='.$row['dm_digisim_pkid'].
			' | EXPECTED='.$channelID
		);
		$getMsh[] = $row;
		$data['bcm'][$rid]['viewmsg'][]	  = $row['dm_id'];
		}
		//=== saved Msg
		if(isset($data['bcm'][$rid]['msg'])){
			$msgIDs = $data['bcm'][$rid]['msg'];
			if(count($msgIDs) > 0){
				$sql = "SELECT * FROM digisim_message WHERE dm_injectes_pkid = '".$channID."' AND dm_digisim_pkid = '".$channelID."' AND dm_id IN (".implode(',', $msgIDs).")";
				$result	= $conn->query($sql);
				while($row = $result->fetch_assoc()) {
					error_log(
					'SAVED MSG | ID='.$row['dm_id'].
					' | MSG_CHANNEL='.$row['dm_digisim_pkid'].
					' | EXPECTED='.$channelID
					);
					$getMsh[] = $row;
					$data['bcm'][$rid]['viewmsg'][]	  = $row['dm_id'];
				}
			}
		}
		//=== Set Design
		$sm 	= 1;
		$num	= array();
		$newMSG = '';
		$oldMSG = '';
		foreach($getMsh as $getVal){
			if(in_array($getVal['dm_id'],$viewIDs)){
				$status = 'Older';
				$bgc 	= 'msgdiv';
				$txc 	=  'msgdiv_now';
			}
			else{
				$status = 'Now';
				$bgc 	= 'msgdiv2';
				$txc 	=  'msgdiv_now2';
			}
			$img = ($getVal['dm_attachment']=='')? '' : '<a href="images/message/'.$getVal['dm_attachment'].'" target="_blank"><img src="images/attach.png" width="20" ></a>';
			$setDiv ='<div class="'.$bgc.'">
							  <table width="100%" border="0" cellpadding="2" cellspacing="2">
								  <tr>
									<td class="msgdiv_tital">'.$getVal['dm_subject'].'</td>
									<td class="'.$txc.'">'.$status.'</td>
								  </tr>
								  <tr>
									<td class="msgdiv_msg">'.$getVal['dm_message'].'</td>
									<td align="center" width="50" valign="top">'.$img.'</td>
								  </tr>
								</table>
						</div>';
			// Code By Maria
			if(in_array($getVal['dm_id'],$viewIDs)){
				$oldMSG.= $setDiv;
			}
			else{
				$newMSG.= $setDiv;
			}
			$sm++;
		}
		$allMsg = $newMSG . $oldMSG;
		if (empty(trim($allMsg))) {
			$allMsg = '<div style="width: 80%;text-align: center;margin-top: 100px;color: darkblue;">Waiting for Your Message...</div>';
		}
		$return = '
		<div id="viewallbcmmsg">
			<div class="msg_title">'.$rowc['ch_level'].'</div>
			<div class="msg_main">
				'.$allMsg.'
				<div class="hgt120"></div>
			</div>
		</div>';
			// End Code By Maria
		$setData = json_encode($data);
		$stmt 	= $conn->prepare("UPDATE team_play_details SET data=? WHERE team_play_pkid=?");
		$stmt->bind_param("si", $setData,$accID);
		$stmt->execute();
		$stmt->close();
		return $return;
	}


//End code By Maria	
	//=== Get Factor

	function getFactor($getvalue,$factor){

		$return = '';

		foreach($factor as $factorValue){

			if($getvalue >= $factorValue['rf_from'] and $getvalue <= $factorValue['rf_to']){

				$return = '<div class="factor" style="color:#FFFFFF;background-color:'.$factorValue['rf_colour'].';">'.$factorValue['rf_name'].'</div>';

				break;

			}

		}

		if(empty($return)){

			$return = '<strong>'.$getvalue.'%</strong>';

		}

		

		return $return;		

	}	

	

	//==== Result Factor

	function resultFactor($conn,$id){

		$sqlT2 		= "SELECT * FROM sub_result_factor WHERE  rf_status ='1' AND an_group_pkid = '".$id."' ORDER BY rf_from DESC";

		$resultt2 	= $conn->query($sqlT2);

		$return	= array();

		while($rowt2 = $resultt2->fetch_assoc()) {

			$return[]	= $rowt2;

		}

		return $return;

	}

	

	//=== View Bcm Result

	function viewBcmResult($conn,$accID,$roundID){

		//=== Digisim

		$sqlD 	 = "SELECT ro_treasure_group_pkid FROM gm_round WHERE ro_id='".$roundID."'";

		$resultD = $conn->query($sqlD);

		$rowD 	 = $resultD->fetch_assoc();

		$diID 	 = $rowD['ro_treasure_group_pkid'];

		$getRTY  = $this->resultTypeDigiSim($conn,$diID);

		$setRty  = $getRTY['rty']; //=== 1:NA,2:Percentage,3:Score,4:Band

		

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();

		$data   = json_decode($row['data'],true);

		

		$userScore 	= $data['bcm'][$roundID]['userscore'];

		$analyID 	= $data['bcm'][$roundID]['analysis'];		

		

		$factor 	= $this->resultFactor($conn,$analyID);		

		$return 	= array();

		foreach($userScore as $repID => $score){

			if($setRty==4){

				$getFactor  = $this->getFactor($score,$factor);

			}

			else if($setRty==2){

				$getFactor  = '<div class="vieresper" >'.$score.'%</div>';

			}

			else{

				$getFactor = '';

			}

			

			$return[$repID] = $getFactor;

		}

		return $return;

	}

	

	//=== View Round Msg

	function viewRoundMsg($conn,$accID,$roundID){

		//=== Select Team play Details

		$sql 	= "SELECT * FROM team_play_details WHERE team_play_pkid ='".$accID."'";

		$result	= $conn->query($sql);

		$row	= $result->fetch_assoc();

		$data   = json_decode($row['data'],true);

		

		//=== Digisim

		$sqlD 	 = "SELECT ro_treasure_group_pkid FROM gm_round WHERE ro_id='".$roundID."'";

		$resultD = $conn->query($sqlD);

		$rowD 	 = $resultD->fetch_assoc();

		$diID 	 = $rowD['ro_treasure_group_pkid'];

		

		//=== Message

		$sql 	= "SELECT

						 sub_channels.ch_id

					FROM

						digisim

						INNER JOIN mdm_injectes 

							ON (digisim.di_injects_id = mdm_injectes.lg_id)

						INNER JOIN sub_channels 

							ON (mdm_injectes.lg_id = sub_channels.in_group_pkid)

					WHERE (digisim.di_id ='".$diID."'

						AND sub_channels.ch_status ='1')

					ORDER BY sub_channels.ch_sequence ASC";

		$result = $conn->query($sql);

		$msg 	= array();

		$new	= array();

		$selTy	= '';

		while($row 	= $result->fetch_assoc()) {

			$channID = $row['ch_id'];

			$msg[] 	 = $row['ch_id'];

			

			//=== Get Noramal Msg

			$getMsh = array();

			$sql1 	= "SELECT dm_id FROM digisim_message WHERE dm_injectes_pkid ='".$channID."' AND dm_trigger = 1";

			$result1	= $conn->query($sql1);

			while($row1 = $result1->fetch_assoc()) {

				$getMsh[] = $row1['dm_id'];

			}

			

			//=== saved Msg

			if(isset($data['bcm'][$roundID]['msg'])){

				$msgIDs = $data['bcm'][$roundID]['msg'];

				if(count($msgIDs) > 0){

					$sql2 	= "SELECT dm_id FROM digisim_message WHERE dm_injectes_pkid ='".$channID."' AND dm_id IN(".implode(',',$msgIDs).")";

					$result2	= $conn->query($sql2);

					while($row2 = $result2->fetch_assoc()) {

						$getMsh[] = $row2['dm_id'];

					}

				}

			}

			

			//=== Check View MSG

			$numRow = 0;

			if(isset($data['bcm'][$roundID]['viewmsg'])){

				$viewIDs = $data['bcm'][$roundID]['viewmsg'];

				if(count($viewIDs) > 0){

					$sql3 	 = "SELECT dm_id FROM digisim_message WHERE dm_injectes_pkid ='".$channID."' AND dm_id IN(".implode(',',$viewIDs).")";

					$result3 = $conn->query($sql3);

					$numRow  = $result3->num_rows;

				}

			}			

			

			//=== Update View Masg for C

			$new[$channID] = (count($getMsh)-$numRow);

			

		}

		

		$return 				= array();

		$return['first_msg']  	= $msg[0];

		$return['new']  		= $new;

		

		return $return;

	}

	

	//=== Find Result Type for DIgiSim

	function resultTypeDigiSim($conn,$id){

		$sql 	= "SELECT di_result_type,di_max_score FROM digisim WHERE di_id='".$id."'";

		$result = $conn->query($sql);

		$rsty 	= $result->fetch_assoc();

		

		$return = array();

		$return['mxsc']= $rsty['di_max_score']; //===  (let us say Max score is 20, and the % score calculated overall is 70%, then the score is (70/100)*20 = 14. 

		$return['rty'] = $rsty['di_result_type']; //=== 1:NA,2:Percentage,3:Score,4:Band

		return $return;	

	}

	// To set rountcount in Footer by Anand

	//=== Casestudy (Dynamic Round Tracker + Fixed Buttons)
	function casestudy($casestudy,$div,$pr='',$nx=''){
		 global $rowCount;
		 $cuRound = $this->curRound;


		$si 			= 1;
		$return 		= '';
		$insCaseCount 	= count($casestudy);
		$totalRounds = $rowCount; // change by maria
        if(isset($this->ins_round) && is_array($this->ins_round)){
            $totalRounds = count($this->ins_round);
        }
	      foreach($casestudy as $casVal){
			if($pr==''){
				$class 	= ($si==1)?'disblk':'disnone';
				$backBtn = ($si==1) ? '<div class="cas_empty"></div>' : '<div class="cas_left chand" title="Previous" onclick="casestudy(\''.$div.'\','.($si-1).');"></div>';
			}
			else{
				$class 	= 'disnone';
				$backBtn = '<div class="cas_left" title="Previous" '.$pr.'></div>';
			}
			
			// --- Next Arrow (Right) ---
			$nextAction	= ($insCaseCount==$si)? $nx : 'onclick="casestudy(\''.$div.'\','.($si+1).');"';
			$nHand = ($insCaseCount==$si && $nx == '') ? 'cblock' : 'chand';
			$nextBtn = '<div class="cas_right" title="Next" '.$nextAction.'></div>';

			
            $footerContent = '';
            $footerClass = ''; 

            // -- SLIDE 1: LANDING PAGE (Round Tracker) --
            if($si == 1 && $div == 'inc') {
                $footerClass = 'landing-footer'; 

				$trackerNodes = '
                    <div class="tracker-item active" '.$nextAction.' title="Start Game">
                        <div class="tracker-circle">START</div>
                    </div>';
                $ri = 1;
                for($i=1; $i<=$totalRounds; $i++){
					if($ri <= $cuRound){
                     $trackerNodes .= '<div class="tracker-line"></div>
                     <div class="tracker-item future"><div class="tracker-circle" style="background-color:lightskyblue;">'.$i.'</div></div>';
					}else{
					 $trackerNodes .= '<div class="tracker-line"></div>
                     <div class="tracker-item future"><div class="tracker-circle">'.$i.'</div></div>';	
					}
                }
				if($ri == $cuRound){
                $trackerNodes .= '<div class="tracker-line"></div>
                    <div class="tracker-item future">
                        <div class="tracker-circle end-btn-fix" style="background-color:red;">END</div>
                    </div>';
				}else{
					 $trackerNodes .= '<div class="tracker-line"></div>
                    <div class="tracker-item future">
                        <div class="tracker-circle end-btn-fix">END</div>
                    </div>';
				}
                $footerContent = '<div class="tracker-container">'.$trackerNodes.'</div>';
            } 
            
           
			$return.='<div class="allcase '.$class.' cspos" id="'.$div.'_'.$si.'">
						<div class="caseaps" style="background-image:url(images/pageloader.gif);"></div>
						
						<div class="casestudy" style="background-image:url(images/casestudy/'.$casVal['ch_image'].');"></div>
						
						<div class="casestudy_bt">
							'.$footerContent.'
							<div class="casestudy_art">
								'.$backBtn.'
								'.$nextBtn.'							
							</div>
						</div>
					</div>';
			$si++;
		}
		return $return;	
	}
}


	

?>