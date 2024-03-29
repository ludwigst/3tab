<?php
// auto-generated by sfPropelCrud
// date: 2008/04/20 19:00:01

/**
 * round actions.
 *
 * @package    3tab
 * @subpackage round
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 3335 2007-01-23 16:19:56Z fabien $
 */
class roundActions extends sfActions
{
  public function executeIndex()
  {
    return $this->forward('round', 'list');
  }
  
  public function executeStartTournament()
  {
	if(RoundPeer::doSelect(new Criteria()) == null)
	{
		$round =Round::createRound("Round 1", 1, null);
		$round->generateRandomDebates();
		//run the institution clash checks
		$round->oneUpOneDown();		
		$this->getRequest()->setParameter('round', $round->getId());
		return $this->forward('round', 'listDebates');
	}
	else
	{
		throw new Exception('The first round has already been generated');
	}
	
  }
  
   public function executeGenerateRound()
  {
	//check if round one, run start tournament
	if(RoundPeer::doSelect(new Criteria()) == null)
	{
		return $this->redirect('round/startTournament');
	}	
	//check if all rounds have been scored
	if(Round::roundCompleted()) 
	{
		$rounds = RoundPeer::doSelect(new Criteria());
		$round=Round::createRound('Round '.(count($rounds)+1), 3, $rounds[count($rounds)-1]->getId());
		$round->generateDebates();
		$round->oneUpOneDown();
		$this->getRequest()->setParameter('round', $round->getId());
		return $this->forward('round', 'listDebates');
	}
	else
	{
		throw new Exception('The previous round has not been completed');
	}	
  }
  
  public function executeViewDebates()
  {
	if ($this->getRequest()->getMethod() != sfRequest::POST)
	{
		$this->rounds = RoundPeer::doSelect(new Criteria());
		return sfView::SUCCESS;
	}
	else
	{
		// Handle the form submission
		$this->forward('round', 'listDebates');
	}	
  }
  
  public function executeViewDebatesFull()
  {
	if ($this->getRequest()->getMethod() != sfRequest::POST)
	{
		$this->rounds = RoundPeer::doSelect(new Criteria());
		return sfView::SUCCESS;
	}
	else
	{
		// Handle the form submission
		$this->forward('round', 'listDebatesFull');
	}	
  }
  
  public function executeListDebates()
  {
	$round = RoundPeer::retrieveByPk($this->getRequestParameter('round'));
	$this->debates = $round->getDebates();
	$this->round = $round;
  }
  
  public function executeListDebatesFull()
  {
	$round = RoundPeer::retrieveByPk($this->getRequestParameter('round'));
	$this->debates = $round->getDebates();
	$this->round = $round;
  }
  
  public function executeEnterScores()
  {
	if ($this->getRequest()->getMethod() != sfRequest::POST)
	{
		//get the latest round
		$rounds = RoundPeer::doSelect(new Criteria());
		$this->round = $rounds[count($rounds)-1];
		return sfView::SUCCESS;
	}
	else
	{
		// Handle the form submission
		$this->debate = $this->getRequestParameter('debate');	
		$this->forward('round', 'enterScores2');
	}	
  }
  
  public function executeEnterScores2()
  {
		$this->debate = DebatePeer::retrieveByPk($this->getRequestParameter('debate'));
  }
  
  public function executeEnterScores3()
  {
	$this->debate = DebatePeer::retrieveByPk($this->getRequestParameter('id'));
	$this->allocation = AdjudicatorAllocationPeer::retrieveByPk($this->getRequestParameter('allocation'));
  }
  
  public function executeCommitScores()
  {
	$govXref = $this->getRequestParameter('govXref');
	$oppXref = $this->getRequestParameter('oppXref');
	$allocation = $this->getRequestParameter('allocation');
	$gov1Id = $this->getRequestParameter('gov1');
	$gov1Score = $this->getRequestParameter('gov1score');
	$gov2Id=$this->getRequestParameter('gov2');
	$gov2Score = $this->getRequestParameter('gov2score');
	$gov3Id =$this->getRequestParameter('gov3');
	$gov3Score = $this->getRequestParameter('gov3score');
	$gov4Id = $this->getRequestParameter('gov4');
	$gov4Score = $this->getRequestParameter('gov4score');
	$opp1Id = $this->getRequestParameter('opp1'); 
	$opp1Score = $this->getRequestParameter('opp1score');
	$opp2Id=$this->getRequestParameter('opp2');
	$opp2Score = $this->getRequestParameter('opp2score');
	$opp3Id = $this->getRequestParameter('opp3');
	$opp3Score = $this->getRequestParameter('opp3score');
	$opp4Id =$this->getRequestParameter('opp4');
	$opp4Score = $this->getRequestParameter('opp4score');
	$govtotal = $gov1Score+$gov2Score+$gov3Score+$gov4Score;
	$opptotal = $opp1Score+$opp2Score+$opp3Score+$opp4Score;
	//commit the team scores
	if($govtotal > $opptotal)
	{
		TeamScoreSheet::createTeamScoreSheet($allocation, $govXref, 1);
		TeamScoreSheet::createTeamScoreSheet($allocation, $oppXref, 0);
	}
	else
	{
		TeamScoreSheet::createTeamScoreSheet($allocation, $govXref, 0);
		TeamScoreSheet::createTeamScoreSheet($allocation, $oppXref, 1);
	}
	//commit the debaters scores
	SpeakerScoreSheet::createSpeakerScoreSheet($allocation,$govXref,$gov1Id,$gov1Score,1);
	SpeakerScoreSheet::createSpeakerScoreSheet($allocation,$govXref,$gov2Id,$gov2Score,2);
	SpeakerScoreSheet::createSpeakerScoreSheet($allocation,$govXref,$gov3Id,$gov3Score,3);
	SpeakerScoreSheet::createSpeakerScoreSheet($allocation,$govXref,$gov4Id,$gov4Score,4);
	SpeakerScoreSheet::createSpeakerScoreSheet($allocation,$oppXref,$opp1Id,$opp1Score,1);
	SpeakerScoreSheet::createSpeakerScoreSheet($allocation,$oppXref,$opp2Id,$opp2Score,2);
	SpeakerScoreSheet::createSpeakerScoreSheet($allocation,$oppXref,$opp3Id,$opp3Score,3);
	SpeakerScoreSheet::createSpeakerScoreSheet($allocation,$oppXref,$opp4Id,$opp4Score,4);
	
	return $this->redirect('round/enterScores');
	
  }
  
 public function validateCommitScores()
 {
	return true;
 }
 
   public function handleErrorCommitScores()
  {
  }


	public function executeAllocateTest()
	{
		$rounds = RoundPeer::doSelect(new Criteria());
		$round = $rounds[1];
		$debates = $round->getDebates();
		$debateIds = array();
		foreach($debates as $debate)
		{
			$debateIds[] = $debate->getId();
		}
		$adjudicatorIds = array();
		$adjudicators = Adjudicator::getAdjudicatorsByTestScore();
		foreach($adjudicators as $adjudicator)
		{
			$adjudicatorIds[] = $adjudicator->getId();
		}
		$allocator = new AdjudicatorAllocator();
		$allocator->setup($debateIds, $adjudicatorIds);
		//$allocator->allocate();
		//$allocator->printPopulation();
	}
	   
  //temporary allocation script while the real one is being worked out
  public function executeAllocateAdjudicatorsTemp()
  {	
	//get debates for the latest round
	$rounds = RoundPeer::doSelect(new Criteria());
	$round = $rounds[count($rounds)-1];
	$debates = $round->getDebates();
	
	$adjudicators = Adjudicator::getAdjudicatorsByTestScore();
	if($adjudicators >= $debates)
	{
		AdjudicatorAllocation::allocateAdjudicators($debates, $adjudicators);
	}
	else
	{
		throw new Exception('There are not enough adjudicators to be allocated into all the debates');
	}	
	$this->round = $round->getId();
	$this->redirect('round/viewDebates');
  }
  
  public function executeList()
  {
    $this->rounds = RoundPeer::getRoundsInSequence();
  }

  public function executeShow()
  {
    $this->round = RoundPeer::retrieveByPk($this->getRequestParameter('id'));
    $this->forward404Unless($this->round);
  }

  public function executeCreate()
  {
    $this->round = new Round();

    $this->setTemplate('edit');
  }

  public function executeEdit()
  {
    $this->round = RoundPeer::retrieveByPk($this->getRequestParameter('id'));
    $this->forward404Unless($this->round);
  }

  public function handleErrorUpdate()
  {
    if (!$this->getRequestParameter('id'))
    {
      $this->forward('round', 'create');
    }
    else
    {
      $this->forward('round', 'edit');
    }
  }

  public function executeUpdate()
  {
    if (!$this->getRequestParameter('id'))
    {
      $round = new Round();
    }
    else
    {
      $round = RoundPeer::retrieveByPk($this->getRequestParameter('id'));
      $this->forward404Unless($round);
    }

    $round->setId($this->getRequestParameter('id'));
    $round->setName($this->getRequestParameter('name'));
    $round->setFeedbackWeightage($this->getRequestParameter('feedback_weightage'));
    $round->setType($this->getRequestParameter('type'));
    $round->setPrecededByRoundId($this->getRequestParameter('preceded_by_round_id') ? $this->getRequestParameter('preceded_by_round_id') : null);

    $round->save();

    $this->setFlash("success", "Round was successfully saved.");

    return $this->redirect('round/show?id='.$round->getId());
  }

  public function executeDelete()
  {
    $round = RoundPeer::retrieveByPk($this->getRequestParameter('id'));

    $this->forward404Unless($round);

    $round->delete();

    $this->setFlash("success", "Round ".$round->getName()." was deleted.");

    return $this->redirect('round/list');
  }
}
