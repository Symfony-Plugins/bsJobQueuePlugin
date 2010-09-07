<?php

class jobQueuePurgeTask extends sfBaseTask
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
    $this->name             = 'purge';
    $this->briefDescription = 'clean the jobQueue table';
    $this->detailedDescription = <<<EOF
delete the job successfully executed older than 1 week and the job which was not executed unsuccesfully older than 1 month
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'] ? $options['connection'] : null)->getConnection();

    // add your code here
    $intNumDeleted  = Doctrine::getTable("bsJobQueue")->purge();
    echo ">> ".$intNumDeleted." record(s) has been deleted.";
  }
}
