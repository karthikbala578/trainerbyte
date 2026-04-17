<?php
class GameRound{
   public  $ro_type=5;
    function getCasestudy($conn, $ro_id) {

        $ro_status = 1;
        $ro_type = 5;



        if (!$ro_id) {

            return null;

        }

        $stmt = $conn->prepare(

            "SELECT ro_casestudy_group_pkid FROM gm_round WHERE ro_id = ? and ro_status = ? AND ro_type = ?"

        );

        $stmt->bind_param("iii", $ro_id, $ro_status, $ro_type);

        $stmt->execute();



        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        $pkid = $row['ro_casestudy_group_pkid'];

        $ch_status = 1;

        $cst = $conn->prepare(

            "SELECT * FROM sub_casestudy WHERE ch_group_pkid = ? AND ch_status=? ORDER BY ch_id DESC limit 1"

        );

        $cst->bind_param("ii", $pkid, $ch_status);

        $cst->execute();



        $results = $cst->get_result();

        $rows = $results->fetch_assoc();

        return $casestudy = $rows;

    }

    function getGuideline($conn, $ro_id) {

        $ro_status = 1;
        $ro_type = 5;



        if (!$ro_id) {

            return null;

        }

        $stmt = $conn->prepare(

            "SELECT * FROM gm_round WHERE ro_id = ? and ro_status = ? AND ro_type = ?"

        );

        $stmt->bind_param("iii", $ro_id, $ro_status, $ro_type);

        $stmt->execute();



        $result = $stmt->get_result();

        $row = $result->fetch_assoc();


        return $guideline = $row;

    }

    function getCards($conn) {

        $companies = [];



        $stmt = $conn->prepare(

            "SELECT * FROM card_group WHERE cg_id = ?"

        );

        $stmt->bind_param("i", $cg_id);

        $stmt->execute();



        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        return $companies 		= $row;

    }

    /* ----------------------------------

    Get card group ID from request

    -----------------------------------*/

    function getGameIdFromRequest(): int {

        $cg_id = intval($_GET['game_id'] ?? 0);



        if ($cg_id <= 0) {

            die("Invalid game");

        }



        return $cg_id;

    }


    function getCardGroupById(mysqli $conn, int $cg_id): array {
		$return = array();
		//==== Group
		$sql 	= "SELECT * FROM card_group WHERE cg_id='".$cg_id."'";
		$result = $conn->query($sql);
		$row 	= $result->fetch_assoc();
		$return['cardgroup'] = $row;
		//==== 1:Yes,2:NO
		$order = ($row['cg_random']==1)? 'RAND()' : 'cu_sequence ASC';

		//=== Card
		$card = array();
		$sql 	= "SELECT * FROM card_unit WHERE cu_card_group_pkid='".$cg_id."' AND cu_status=1 ORDER BY ".$order;
		$result = $conn->query($sql);
		while($row 	= $result->fetch_assoc()) {
			$card[] = $row;
		}
		$return['cardunit'] = $card;
		return $return;
	}



    /* ----------------------------------

    Optional: Get all card groups

    -----------------------------------*/

    function getAllCardGroups(mysqli $conn): array {

        $data = [];



        $stmt = $conn->prepare("SELECT * FROM card_group");

        $stmt->execute();

        $result = $stmt->get_result();



        while ($row = $result->fetch_assoc()) {

            $data[] = $row;

        }



        return $data;

    }



    function getLatestUserScore(mysqli $conn, int $game_id, string $game_code): int

    {

        if ($game_id <= 0 || empty($game_code)) {

            return 0;

        }



        $stmt = $conn->prepare("

            SELECT user_conclution_score

            FROM tb_event_user_score

            WHERE game_id = ?

            AND game_code = ?

            ORDER BY id DESC

            LIMIT 1

        ");



        $stmt->bind_param("is", $game_id, $game_code);

        $stmt->execute();



        $row = $stmt->get_result()->fetch_assoc();

        $stmt->close();



        return (int)($row['user_conclution_score'] ?? 0);

    }

    function getLatestGameStatus(mysqli $conn, int $game_id): int

    {

        if ($game_id <= 0 || empty($game_code)) {

            return 0; // not completed

        }



        $stmt = $conn->prepare("

            SELECT game_status

            FROM tb_event_user_score

            WHERE game_id = ?

            AND event_id = ?

            ORDER BY id DESC

            LIMIT 1

        ");

        $stmt->bind_param("ii", $game_id, $_SESSION['event_id']);

        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        $stmt->close();



        // assume game_status = 1 means completed

        return $row['game_status'];

    }

    function getLatestGameResult(mysqli $conn, int $game_id, string $game_code): array

    {

        $stmt = $conn->prepare("

            SELECT 

                open_card_id,

                user_conclusion,

                user_conclution_score

            FROM tb_event_user_score

            WHERE game_id = ?

            AND game_code = ?

            ORDER BY id DESC

            LIMIT 1

        ");

        $stmt->bind_param("is", $game_id, $game_code);

        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        $stmt->close();



        if (!$row) {

            return [];

        }



        return [

            'opened_cards' => json_decode($row['open_card_id'], true),

            'selection'    => json_decode($row['user_conclusion'], true),

            'score'        => (int)$row['user_conclution_score']

        ];


           }

           	//==== DigiSim Group

	function digiSimGroup($conn,$ro_type){

		//=== Digisim

		$sqlD 	 = "SELECT * FROM digisim WHERE di_id='".$ro_type."'";

		$resultD = $conn->query($sqlD);

		$rowD 	 = $resultD->fetch_assoc();
        //print_r($rowD);
        $bucketScore = $rowD['di_max_score']; // total score (36)

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

					WHERE digisim.di_id = '".$ro_type."'
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

				WHERE (digisim_response.dr_digisim_pkid ='".$ro_type."'

					AND sub_index.ln_status ='1'

					AND mdm_response.lg_status ='1')

				ORDER BY ".$orderby;

		$result = $conn->query($sql);

		while($row 	= $result->fetch_assoc()) {

			$card[] = $row;

		}

		

		//=== Score Type

        $sqlTotal = "SELECT SUM(scoretype_value.stv_value) AS totalScore
            FROM digisim_response
            INNER JOIN scoretype_value 
            ON digisim_response.dr_score_pkid = scoretype_value.stv_id
            WHERE digisim_response.dr_digisim_pkid = '".$ro_type."'";

            $resultTotal = $conn->query($sqlTotal);
            $rowTotal = $resultTotal->fetch_assoc();
            $totalScore = $rowTotal['totalScore'] ?? 0;
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

					WHERE (digisim.di_id ='".$ro_type."'

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

		$return['simid'] 	= $ro_type;

		$return['selty']	= $selTy;

		$return['resTy']	= $resTy;
        $return['bucketScore'] = $totalScore;
		return $return;

	}

    function digiSimGroup1($conn, $sim_id){

    /* ================= DIGISIM MAIN ================= */

    $sqlD = "SELECT * FROM mg5_digisim WHERE di_id = '".$sim_id."'";
    $resultD = $conn->query($sqlD);
    $rowD = $resultD->fetch_assoc();

    if(!$rowD){
        return false;
    }

    $bucketScore = $rowD['di_max_score'];

    /* ================= DISPLAY ORDER ================= */

    $orderby = ($rowD['di_display'] == 1) 
        ? 'RAND()' 
        : 'mg5_digisim_response.dr_order ASC';


    /* ================= MESSAGE CHANNELS ================= */

    $sql = "SELECT DISTINCT
                mg5_sub_channels.ch_level,
                mg5_sub_channels.ch_desc,
                mg5_sub_channels.ch_id,
                mg5_mdm_injectes.lg_name,
                mg5_sub_channels.ch_image,
                mg5_mdm_injectes.lg_description,
                mg5_digisim.di_scoring_basis,
                mg5_digisim.di_min_select,
                mg5_digisim.di_result_type,
                mg5_digisim.di_max_score
            FROM
                mg5_digisim
            INNER JOIN mg5_mdm_injectes 
                ON (mg5_digisim.di_injects_id = mg5_mdm_injectes.lg_id)
            INNER JOIN mg5_sub_channels 
                ON (mg5_mdm_injectes.lg_id = mg5_sub_channels.in_group_pkid)
            WHERE mg5_digisim.di_id = '".$sim_id."'
              AND mg5_sub_channels.ch_status = '1'
              AND EXISTS (
                    SELECT 1 FROM mg5_digisim_message 
                    WHERE dm_injectes_pkid = mg5_sub_channels.ch_id
              )
            ORDER BY mg5_sub_channels.ch_sequence ASC";

    $result = $conn->query($sql);

    $msg = [];
    $selTy = '';
    $resTy = '';

    while($row = $result->fetch_assoc()) {
        $msg[] = $row;
        $selTy = $row['di_scoring_basis'].'_'.$row['di_min_select'];
        $resTy = $row['di_result_type'].'_'.$row['di_max_score'];
    }


    /* ================= CARDS ================= */

    $card = [];

    $sql = "SELECT
                mg5_digisim_response.dr_order,
                mg5_digisim_response.dr_id,
                mg5_digisim_response.dr_tasks,
                mg5_digisim_response.dr_score_pkid
            FROM
                mg5_digisim_response
            INNER JOIN mg5_sub_index 
                ON (mg5_digisim_response.dr_response_pkid = mg5_sub_index.ln_id)
            INNER JOIN mg5_mdm_response 
                ON (mg5_sub_index.ix_group_pkid = mg5_mdm_response.lg_id)
            WHERE (
                mg5_digisim_response.dr_digisim_pkid ='".$sim_id."'
                AND mg5_sub_index.ln_status ='1'
                AND mg5_mdm_response.lg_status ='1'
            )
            ORDER BY ".$orderby;

    $result = $conn->query($sql);

    while($row = $result->fetch_assoc()) {
        $card[] = $row;
    }


    /* ================= TOTAL SCORE ================= */

    $sqlTotal = "SELECT SUM(mg5_scoretype_value.stv_value) AS totalScore
        FROM mg5_digisim_response
        INNER JOIN mg5_scoretype_value 
        ON mg5_digisim_response.dr_score_pkid = mg5_scoretype_value.stv_id
        WHERE mg5_digisim_response.dr_digisim_pkid = '".$sim_id."'";

    $resultTotal = $conn->query($sqlTotal);
    $rowTotal = $resultTotal->fetch_assoc();
    $totalScore = $rowTotal['totalScore'] ?? 0;


    /* ================= SCORE TYPES ================= */

    $score = [];

    $sql = "SELECT
                mg5_scoretype.st_display_name,
                mg5_scoretype.st_desc,
                mg5_scoretype_value.stv_sname,
                mg5_scoretype_value.stv_name,
                mg5_scoretype_value.stv_color,
                mg5_scoretype_value.stv_value,
                mg5_scoretype_value.stv_id
            FROM
                mg5_digisim
            INNER JOIN mg5_scoretype 
                ON (mg5_digisim.di_scoretype_id = mg5_scoretype.st_id)
            INNER JOIN mg5_scoretype_value 
                ON (mg5_scoretype.st_id = mg5_scoretype_value.stv_scoretype_pkid)
            WHERE (
                mg5_digisim.di_id ='".$sim_id."'
                AND mg5_scoretype.st_status ='1'
            )
            ORDER BY mg5_scoretype_value.stv_id ASC";

    $result = $conn->query($sql);

    while($row = $result->fetch_assoc()) {
        $score[] = $row;
    }


    /* ================= RETURN ================= */

    return [
        'msg' => $msg,
        'card' => $card,
        'score' => $score,
        'simid' => $sim_id,
        'selty' => $selTy,
        'resTy' => $resTy,
        'bucketScore' => $totalScore
    ];
}
    	
}
?>

