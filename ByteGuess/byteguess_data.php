<?php
class GameRound{
    function getCompanies($conn, $cg_id) {



        if (!$cg_id) {

            return null;

        }



        $stmt = $conn->prepare(

            "SELECT * FROM card_group WHERE cg_id = ?"

        );

        $stmt->bind_param("i", $cg_id);

        $stmt->execute();



        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        return $companies = $row;

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



    /* ----------------------------------

    Get single card group by ID

    -----------------------------------*/

    // function getCardGroupById(mysqli $conn, int $cg_id): array {

    //     $stmt = $conn->prepare(

    //         "SELECT * FROM card_group WHERE cg_id = ?"

    //     );

    //     $stmt->bind_param("i", $cg_id);

    //     $stmt->execute();




    //     $result = $stmt->get_result();

    //     $row = $result->fetch_assoc();



    //     if (!$row) {

    //         die("Game not found");

    //     }



    //     return $row;

    // }

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
}

?>

