<?php
// auto-generated by sfPropelCrud
// date: 2008/06/03 03:45:14
?>
<?php

/**
 * venue actions.
 *
 * @package    3tab
 * @subpackage venue
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 3335 2007-01-23 16:19:56Z fabien $
 */
class venueActions extends sfActions
{
  public function executeIndex()
  {
    return $this->forward('venue', 'list');
  }

  public function executeList()
  {
    $this->venues = VenuePeer::doSelect(new Criteria());
  }

  public function executeShow()
  {
    $this->venue = VenuePeer::retrieveByPk($this->getRequestParameter('id'));
    $this->forward404Unless($this->venue);
  }

  public function executeCreate()
  {
    $this->venue = new Venue();

    $this->setTemplate('edit');
  }

  public function executeEdit()
  {
    $this->venue = VenuePeer::retrieveByPk($this->getRequestParameter('id'));
    $this->forward404Unless($this->venue);
  }

  public function executeUpdate()
  {
    if (!$this->getRequestParameter('id'))
    {
      $venue = new Venue();
    }
    else
    {
      $venue = VenuePeer::retrieveByPk($this->getRequestParameter('id'));
      $this->forward404Unless($venue);
    }

    $venue->setId($this->getRequestParameter('id'));
    $venue->setName($this->getRequestParameter('name'));
    $venue->setActive($this->getRequestParameter('active', 0));
    $venue->setPriority($this->getRequestparameter('priority'));

    $venue->save();

    return $this->redirect('venue/show?id='.$venue->getId());
  }

  public function executeDelete()
  {
    $venue = VenuePeer::retrieveByPk($this->getRequestParameter('id'));

    $this->forward404Unless($venue);

    $venue->delete();

    return $this->redirect('venue/list');
  }
}
