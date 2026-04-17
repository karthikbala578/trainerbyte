<?php
class GameRound{
	public function getModuleContent($conn, $modId)
	{
		$stmt = $conn->prepare("
			SELECT 
				em.mod_type,
				cg.cg_id,
				cg.cg_name,
				cg.cg_description,
				cg.cg_clue,
				cg.cg_guidelines,
				cg.cg_answer,
				cg.cg_max
			FROM tb_events_module em
			JOIN card_group cg
				ON cg.cg_id = em.mod_game_id
			WHERE em.mod_id = ?
			AND em.mod_status = 1
			AND em.mod_type = 2
			LIMIT 1
		");
		$stmt->bind_param("i", $modId);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}
}

?>