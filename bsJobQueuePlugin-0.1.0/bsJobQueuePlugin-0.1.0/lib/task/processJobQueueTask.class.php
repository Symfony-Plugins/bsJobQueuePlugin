<?php
/**
 * This class contain the method to update the table vehicle_view
 * tip: you can use #var_dump(number_format(memory_get_peak_usage())); to debug
 */
class processJobQueueTask extends sfBaseTask
{
	protected function configure()
    {
        $this->namespace = 'jobQueue';
        $this->name = 'process';
        $this->briefDescription = 'will process the job in the queue';

        $this->detailedDescription = <<<EOF
will process the job in the queue
EOF;

        $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'aramisauto'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'cohiba'),
      // add your own options here
    ));
        
	}

	protected function execute($arguments = array(), $options = array())
    {

    	
    	//initialize the database connection
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase("cohiba")->getConnection();
        
        $context = sfContext::createInstance($this->configuration);
		$context->getRequest()->setRelativeUrlRoot("");
		

		$host = sfConfig::get('ARAMIS_HOST');
		// set separate prefixes for assets and links	
		$_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', sfConfig::get('sf_web_dir'));	
		$routing = $context->getRouting();
        $options = $routing->getOptions();
        $options['context']['host'] = $host;
        $options['context']['prefix'] = '';
    	$routing->initialize($this->dispatcher, $routing->getCache(), $options);
    	$context->set('routing', $routing); 
		
    	$timer = new sfTimer();
		$timer->startTimer();
		
		/**
		 * when the cron runs, tasks that are with 'in_progress' for more than 1 hour should be updated, and status set to 'waiting'.
		 * This is to rerun tasks that encounter a fatal error (DB failure) when running.
		 */
		$this->logSection('jobQueue', "Updating the job queue which 'in_progress' for more than 1 hour");
        $q = Doctrine::getTable('bsJobQueue')->createQuery()
             ->update()
             ->set('status', "'waiting'")
             ->where('status = ?', 'in_progress')
             ->andWhere('executed_at < (NOW() - INTERVAL 1 HOUR)')
             ->execute();
             ;

		//retrieving the vehicle from the view
		$this->logSection('bsJobQueue', 'Retrieving the job queue...');
        $q = Doctrine::getTable('bsJobQueue')->createQuery('j')
             ->select('j.id')
             
             ->where('j.id = ?', '15842')
             ;


        $jobQueueIds = $q->execute(array(), "list_value");
        $q->free();
		$this->logSection('bsJobQueue', 'Retrieving the job queue... done '.$timer->addTime().' s');

		$this->logSection('bsJobQueue', 'Processing the job queue...');
        foreach ($jobQueueIds as $jobQueueId) {
        	$job = Doctrine::getTable('bsJobQueue')->findOneById($jobQueueId);
        	$status = $job->getStatus();
        	if (true) {
	        	$job->process();
	        	$status = $job->getStatus();
	        	if ($status != "has_error") {
	        		$this->logSection('bsJobQueue', 'Job #'.$job->getId().' processed succesfully in '.$job->getDuration().'s');	
	        	} else {
       		      	$content = "Error occurred while processing job #".$job->getId().".\nPlease check the table job_queue for the details.\n";
       		      	$content .= "\n======================================================================\n";
		        	$content .= "Id: ".$job->getId()."\n";
		        	$content .= "Class: ".$job->getTableClassName()."\n";
		        	$content .= "Function: ".$job->getFunctionName()."\n";
		        	$content .= "Executed at: ".$job->getExecutedAt();
		        	$content .= "\n======================================================================\n";
		        	$content .= "Error: \n";
		        	$content .= $job->getNote();
		        	$content .= "\n======================================================================\n";
		        	$content .= "\n\nEmail sent by the task jobQueue:process";
        			
	   				$objMessage	= $this->getMailer()->compose(
		      					'noreply@bysoft.fr',
		      					sfConfig::get('app_mail_support'),
		      					'Aramis - Error occurred while processing job #'.$job->getId(),
		      					$content
		      		);
		
					$this->getMailer()->send($objMessage);
 
	        		$this->logSection('bsJobQueue', '!!! Job #'.$job->getId().' has error while processing and is not completed.');
	        	}
        	}
        }
		$this->logSection('bsJobQueue', 'Processing the job queue... done '.$timer->addTime().' s');
	}
}