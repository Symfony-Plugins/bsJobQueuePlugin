<?php

/**
 * PluginbsJobQueueTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class PluginbsJobQueueTable extends Doctrine_Table
{
    /**
     * Returns an instance of this class.
     *
     * @return object PluginbsJobQueueTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('PluginbsJobQueue');
    }
		
	/**
	 * record a new job
	 * @todo add some verification: tableName and FunctionName should exist
	 *
	 * @param unknown_type $tableName
	 * @param unknown_type $functionName
	 * @param unknown_type $params
	 */
	public function createNewJob($tableName, $functionName, $params) {
		$job = new bsJobQueue();
		$job->setTableClassName($tableName);
		$job->setFunctionName($functionName);
		$job->setJobParams(serialize($params));
		$job->setStatus('waiting');
		$job->save();
	}
	
	
	/**
	 * delete the job successfully executed older than 1 week 
	 * and the job which was not executed unsuccesfully older than 1 month.
	 * 
	 * @return int $intNumDeleted the number of records which have been deleted
	 */
	public function purge()
	{
	    $objQuery		= $this->createQuery()
		    			->delete()
		    			->Where("status = 'done' AND executed_at < CURRENT_DATE - INTERVAL 1 WEEK")
		    			->orWhere("status = 'has_error' AND executed_at < CURRENT_DATE - INTERVAL 1 MONTH");
		    			
	    $intNumDeleted	= $objQuery->execute();
	    $objQuery->free();
	    
	    return $intNumDeleted;
	}
}