<?php

class jobQueueStatTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    // $this->addArguments(array(
    //   new sfCommandArgument('my_arg', sfCommandArgument::REQUIRED, 'My argument'),
    // ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'aramisauto'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'cohiba'),
      // add your own options here
    ));

    $this->namespace        = 'jobQueue';
    $this->name             = 'stat';
    $this->briefDescription = 'display statistic about the jobQueue';
    $this->detailedDescription = <<<EOF
Display the different status (waiting, done, has_error, in_progress) and the number of job for each status
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'] ? $options['connection'] : null)->getConnection();

    // add your code here

    $objQuery = Doctrine::getTable("bsJobQueue")
    			->createQuery()
    			->select('COUNT(*) AS total, status')
    			->groupBy('status ASC');
    $arrResult = $objQuery->execute();
    
    foreach($arrResult as $objBsJobQueue) {
    	$this->logSection(str_pad($objBsJobQueue->getStatus(), 20), $objBsJobQueue->getTotal());
    }
    
    $objQuery->free();
  }
}
