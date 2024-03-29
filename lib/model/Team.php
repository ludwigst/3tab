<?php

/**
 * Subclass for representing a row from the 'teams' table.
 *
 * 
 *
 * @package lib.model
 */ 
class Team extends BaseTeam
{
    public static function getTeamSize()
    {
        return 3;
    }
	
	public function getWinLossText($roundId, $con = null)
	{
        $teamResult = $this->getDebate($roundId, $con)->getDebateTeamXrefForTeam($this->getId(), $con)->getTeamResult($con);
		if($teamResult->getTeamVoteCount() == 0)
		{
			return 'Loss';
		}
		else if($teamResult->getOpponentTeamVoteCount() == 0)
		{
			return 'Win';
		}
		else if($teamResult->getTeamVoteCount() > $teamResult->getOpponentTeamVoteCount())
		{
			return 'Split Win';
		}
		else if($teamResult->getOpponentTeamVoteCount() > $teamResult->getTeamVoteCount())
		{
			return 'Split Loss';
		}
		return 'Error';
	}
	
	public function getDebate($roundId, $con = null)
	{
        $criteria = new Criteria();
        $criteria->addJoin(RoundPeer::ID, DebatePeer::ROUND_ID);
        $criteria->addJoin(DebatePeer::ID, DebateTeamXrefPeer::DEBATE_ID);
        $criteria->add(RoundPeer::ID, $roundId);
		$criteria->add(DebateTeamXrefPeer::TEAM_ID, $this->getId());
        
        return DebatePeer::doSelectOne($criteria, $con);
	}
	
	public function getTeamScoreSheets($roundId, $con = null)
	{
		$criteria = new Criteria();
		$criteria->addJoin(RoundPeer::ID, DebatePeer::ROUND_ID);
		$criteria->addJoin(DebatePeer::ID, DebateTeamXrefPeer::DEBATE_ID);
		$criteria->addJoin(DebateTeamXrefPeer::ID, TeamScoreSheetPeer::DEBATE_TEAM_XREF_ID);
		$criteria->add(RoundPeer::ID, $roundId);
		$criteria->add(DebateTeamXrefPeer::TEAM_ID, $this->getId());

		return TeamScoreSheetPeer::doSelect($criteria, $con);
	}
	
	public function save($con = null)
	{
		parent::save($con);
		if(!$this->getTeamScores(null, $con))
		{
			$teamScore = new TeamScore();
			$teamScore->setTeam($this);
			$teamScore->save($con);
			$this->addTeamScore($teamScore);
		}		
		
		$adjudicators = $this->getInstitution($con)->getAdjudicators(null, $con);		
		foreach($adjudicators as $anAdjudicator)
		{
			$conflict = $anAdjudicator->createConflict($this, $con);
			if($conflict != null)
			{
				$conflict->save($con);				
				$this->addAdjudicatorConflict($conflict);
			}			
		}		
		parent::save($con);
		
	}
	
	public function getTeamsDebated($conn = null)
	{
		$xrefs = $this->getDebateTeamXrefs(new Criteria(), $conn);
		$teamsMet = array();
		foreach($xrefs as $anXref)
		{
			$debate = $anXref->getDebate($conn);
			if($anXref->getPosition($conn) == 1)
			{
				$teamsMet[] = $debate->getDebateTeamXref(2, $conn)->getTeam();
			}
			else if($anXref->getPosition($conn) == 2)
			{
				$teamsMet[] = $debate->getDebateTeamXref(1, $conn)->getTeam();
			}
		}
		
		return $teamsMet;
	}
	
	public function hasMetTeam($team, $conn = null)
	{
		if(in_array($team, $this->getTeamsDebated($conn)))
		{
			return true;
		}
		
		return false;
	}
	
	public function delete($con = null)
	{
		$debaters = $this->getDebaters();
		foreach($debaters as $aDebater)
		{
			//$this->forward404Unless($aDebater);
			$speakerScore = $aDebater->getSpeakerScores();
			//$this->forward404Unless($speakerScore);
			$speakerScore[0]->delete();
			$aDebater->delete();
		}
			
		$teamScore = $this->getTeamScores();
		//$this->forward404Unless($teamScore);
		$teamScore[0]->delete();
		
		$conflicts = $this->getAdjudicatorConflicts();
		foreach($conflicts as $aConflict)
		{
			$aConflict->delete();
		}
		
		parent::delete();
	}
	
	public function getTeamScore($debate, $con = null)
    {
        $c = new Criteria();
        $c->addJoin(DebateTeamXrefPeer::ID, TeamScoreSheetPeer::DEBATE_TEAM_XREF_ID);
        $c->add(DebateTeamXrefPeer::DEBATE_ID, $debate->getId());

        $adjudicatorAllocationCriteria = new Criteria();
        $adjudicatorAllocationCriteria->add(AdjudicatorAllocationPeer::TYPE, AdjudicatorAllocation::ADJUDICATOR_TYPE_TRAINEE, Criteria::NOT_EQUAL);
        
        if ($debate->countAdjudicatorAllocations($adjudicatorAllocationCriteria, false, $con) * 2 != TeamScoreSheetPeer::doCount($c, false, $con))
        {
            return null;
        }
        return $debate->getDebateTeamXrefForTeam($this->getId(), $con)->getTeamResult($con)->getMajorityTeamScore();
	}
	
    public function getTotalTeamScoreAtRound($round, $con = null)
    {
        if (!($con instanceof Connection)) {
            $con = Propel::getConnection();
        }

        $rounds = array_merge(array($round), $round->getAllPrecedingRounds($con));
        $roundIds = array();
        foreach ($rounds as $round) {
            $roundIds[] = $round->getId();
        }

        $sql = <<<EOD
SELECT SUM(COALESCE(debates_teams_xrefs.majority_team_score, 0)) AS total_team_score
FROM teams
LEFT JOIN debates_teams_xrefs ON debates_teams_xrefs.team_id = teams.id
LEFT JOIN debates ON debates.id = debates_teams_xrefs.debate_id
WHERE debates.round_id = ANY(?) AND teams.id = ?
EOD;

        $stmt = $con->prepareStatement($sql);
        $stmt->setArray(1, $roundIds);
        $stmt->setInt(2, $this->getId());
        $rs = $stmt->executeQuery();
        $rs->next();

        return $rs->getInt('total_team_score');
    }

	public function getTotalTeamScore($propelConn = null)
	{
		$score = $this->getTeamScores();
		return $score[0]->getTotalTeamScore();
	}
	
	public function getTotalSpeakerScore($propelConn = null)
	{
		$criteria = new Criteria();
		$criteria->add(TeamPeer::ID, $this->getId());
		$score = TeamScorePeer::doSelect($criteria);
		return $score[0]->getTotalSpeakerScore();
	}
	
	public function getTotalMargin($propelConn = null)
	{
		$criteria = new Criteria();
		$criteria->add(TeamPeer::ID, $this->getId());
		$score = TeamScorePeer::doSelect($criteria);
		return $score[0]->getTotalMargin();
	}
	
	public function deriveTotalTeamScore($con = null)
    {
        if (!($con instanceof Connection)) {
            $con = Propel::getConnection();
        }

        $sql = "SELECT SUM(COALESCE(team_results.majority_team_score, 0)) AS total_team_score " .
        "FROM teams " .
        "LEFT JOIN debates_teams_xrefs ON debates_teams_xrefs.team_id = teams.id " .
        "LEFT JOIN team_results ON team_results.debate_team_xref_id = debates_teams_xrefs.id " .
        "WHERE teams.id = ?";
        $stmt = $con->prepareStatement($sql);
        $stmt->setInt(1, $this->getId());
        $rs = $stmt->executeQuery();
        $rs->next();

        return $rs->getInt('total_team_score');
    }
	
	public function deriveTotalSpeakerScore($con = null)
	{
        if (!($con instanceof Connection)) {
            $con = Propel::getConnection();
        }
        $sql = "SELECT SUM(COALESCE(debater_results.averaged_score, 0.00)) AS total_team_speaker_score " .
        "FROM teams " .
        "LEFT JOIN debates_teams_xrefs ON debates_teams_xrefs.team_id = teams.id " .
        "LEFT JOIN debater_results ON debater_results.debate_team_xref_id = debates_teams_xrefs.id " .
        "WHERE teams.id = ?";
        $stmt = $con->prepareStatement($sql);
        $stmt->setInt(1, $this->getId());
        $rs = $stmt->executeQuery();
        $rs->next();

        return $rs->getFloat('total_team_speaker_score');
	}
    
    public function deriveTotalMargin($con = null)
    {   
        if (!($con instanceof Connection)) {
            $con = Propel::getConnection();
        }
        $sql = "SELECT SUM(COALESCE(team_margins.margin, 0.00)) AS total_margin " .
        "FROM teams " .
        "LEFT JOIN debates_teams_xrefs ON debates_teams_xrefs.team_id = teams.id " .
        "LEFT JOIN team_margins ON team_margins.debate_team_xref_id = debates_teams_xrefs.id " .
        "WHERE teams.id = ?";
        $stmt = $con->prepareStatement($sql);
        $stmt->setInt(1, $this->getId());
        $rs = $stmt->executeQuery();
        $rs->next();

        return $rs->getFloat('total_margin');
    }
	
	public function getTotalAffs($conn=null)
	{
		$total = 0;
		foreach($this->getDebateTeamXrefs(new Criteria(), $conn) as $xref)
		{
			if($xref->getPosition() == 1)
			{
				$total++;
			}
		}
		return $total;
	}
}
