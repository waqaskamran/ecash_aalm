<?php
/**
 * @author John Hargrove
 */
class Queue_Config
{
	/**
	 * @var Server
	 */
	private $server;

	/**
	 * @var stdClass
	 */
	private $request;

	/**
	 * @param Server $server
	 * @param stdClass $request
	 */
	public function __construct(Server $server, $request)
	{
        $this->server = $server;
        $this->request = $request;

        ECash::getTransport()->Set_Data($request);

	}

	public function New_Queue()
	{
		$data = new stdClass;
		$data->view = 'new_queue';
		$data->completed = '';

		$factory = ECash::getFactory();
		$queue_manager = $factory->getQueueManager();
		
		//if it has been submitted
		if(isset($this->request->go))
		{
			
			//instantiate the queue
			/* @var $queue ECash_Models_Queue */
			if($this->request->queue_function == 'add')
			{
				$queue = $factory->getModel('Queue');
				$queue->date_created = date('Y-m-d H:i:s');
		
				if($queue_manager->hasQueue($this->request->queue_name_short))
				{
					$data->completed = 'Multiple queues cannot have the same name short.';
				}
				
				//A multi-company layer is not implemented in the QueueManager, which was causing errors and general
				//usability problems for Agean CFE.  This was repaired by restricting the adding of queues
				//to the current company. - Nita - [#14246]
				if($this->request->queue_company_id != -1 && $this->request->queue_company_id != $this->server->company_id)
				{
					$data->completed = 'You cannot add a queue for the selected company.';
				}
				
			} 
			else 
			{
				$queue_greater = $queue_manager->getQueue(trim($this->request->queue_select));
				$queue = $queue_greater->getModel();
			}

			$q_display = $factory->getModel('QueueDisplay');

			//check to see if it's a valid request
			if(!empty($this->request->queue_name)
			&& !empty($this->request->queue_name_short)
			&& !empty($this->request->queue_name_display)
			&& empty($data->completed))
			{
				if($this->request->queue_function == 'delete')
				{
					$q_display->deleteByQueueId($queue->queue_id);
					$queue->delete();
					$queue_manager->renewQueues();
					$data->completed = 'Queue successfully deleted.';
					ECash::getLog()->Write("Queue " . $queue->name_short . " successfully deleted");
				} 
				else 
				{

					//die(print_r($this->request,TRUE));
					if ($this->request->queue_company_id == -1)
					{
						$queue->company_id = NULL;
					} 
					else 
					{
						$queue->company_id = $this->request->queue_company_id;
					}
		
					$queue->queue_group_id = (int)$this->request->queue_group_id;
					$queue->section_id = (int)$this->request->section_id;
					//we should also instantiate the queue_select field
					$queue->name_short = $this->request->queue_select = $this->request->queue_name_short;
					$queue->name = $this->request->queue_name;
					$queue->display_name = $this->request->queue_name_display;
					$queue->control_class = $this->request->queue_type;
					$queue->is_system_queue = $this->request->is_system_queue;
					$queue->save();
					//delete and re-save queue display info
					$q_display->deleteByQueueId($queue->queue_id);

					$new_queue_displays = new DB_Models_ModelList_1($factory->getModelClass('QueueDisplay'), ECash::getMasterDb());	

					$default_display = $factory->getModel('QueueDisplay');
					$default_display->section_id = $this->request->section_id;
					$default_display->queue_id = $queue->queue_id;
					$new_queue_displays[] = $default_display;

					$alt_display = $factory->getModel('QueueDisplay');
					$alt_display->section_id = $this->request->display_section_id_1;
					$alt_display->queue_id = $queue->queue_id;
					$new_queue_displays[] = $alt_display;

					$new_queue_displays->save();

					$queue_manager->renewQueues();
	
					//now deal with the recycling values
					$this->Save_Timeout_And_Recycle();
		
					if($this->request->queue_function == 'add')
					{
						$data->completed = 'Queue successfully added.';
						ECash::getLog()->Write("Queue " . $queue->name_short . " successfully added with id " . $queue->queue_id);
					} else {
						$data->completed = 'Queue modified.';
						ECash::getLog()->Write("Queue " . $queue->name_short . " successfully modified.");
					}
				}			
			} elseif($this->request->queue_function == 'edit' && ($queue->is_system_queue || $queue_greater->count()) ) {
				$this->Save_Timeout_And_Recycle();
			} elseif($this->request->queue_function == 'delete') {
				$data->completed = 'Cannot delete system queues or queues with applications in them.';
				$this->request = array('go' => true, 'completed' => false);
			} elseif(empty($data->completed)) {
				$data->completed = false;
			}
		}
		
		//if the queue was selected to be edited and has either an application in it or it is a system queue, 
		//deal with the timeout and limit then leave it alone
		if($this->request->queue_function == 'edit' && ($queue_manager->getQueue($this->request->queue_select)->count() || $queue_manager->getQueue($this->request->queue_select)->getModel()->is_system_queue))
		{
			$this->Set_Recycle_Limit_Rule($this->request->queue_select, $this->request->queue_recycle_limit);
			$this->Set_Timeout_Rule($this->request->queue_select, $this->request->queue_timeout_limit);
			$this->request = array();
		}
		
		$db = ECash::getMasterDb();
		$data->module_mode_list = $db->query("
		(
			select
				s2.section_id section_id,
				concat_ws(' -> ', s1.description, s2.description) description,
				1 enabled,
				s2.active_status active_status
			from section s0
			join section s1 on (s1.section_parent_id = s0.section_id)
			join section s2 on (s2.section_parent_id = s1.section_id)
			where
				s0.name = 'ecash3_0'
				AND 
				(
					s2.can_have_queues > 0
					OR 
					(
						s2.can_have_queues IS NULL
						AND s1.can_have_queues > 0
					)
				)
				AND s2.active_status = 'active'
		) union (
			select
				s1.section_id section_id,
				s1.description,
				0 enabled,
				s1.active_status active_status
			from section s0
			join section s1 on (s1.section_parent_id = s0.section_id)
			where
				s0.name = 'ecash3_0'
				AND s1.can_have_queues > 0
				AND s1.active_status = 'active'
		)
		order by description")->fetchAll(PDO::FETCH_NUM);

		$data->req = $this->request;
		$data->queue_group_list = array();
		$group_list = $queue_manager->getQueueGroups();
		foreach($group_list as $group)
		{
			if($group->getModel()->company_id == ECash::getCompany()->company_id || ECash::getConfig()->MULTI_COMPANY_ENABLED)
				$data->queue_group_list[] = $group;
		}
				
		//A multi-company layer is not implemented in the QueueManager, which was causing errors and general
		//usability problems for Agean CFE.  This was repaired by restricting the adding of queues
		//to the current company. - Nita - [#14246]
		$data->queue_company_list = $factory->getReferenceList('Company', null, array('active_status'=>'active', 'company_id' => $this->server->company_id));

		ECash::getTransport()->Set_Data($data);

	}
	
	private function Save_Timeout_And_Recycle()
	{
		//compatibility with old admin timeout config
		if($this->request->queue_timeout_rule == null) 
		{
			$qmap = $this->request->queue_select;
		}
		else
		{
			$qmap = $this->request->queue_timeout_rule;
		}
		$this->Set_Recycle_Limit_Rule($qmap, $this->request->queue_recycle_limit);
		$this->Set_Timeout_Rule($qmap, $this->request->queue_timeout_limit);
	}

	public function Set_Timeout()
	{
                //compatibility with old admin timeout config
                if($this->request->queue_timeout_rule == null)
                {
                        $qmap = $this->request->queue_select;
                }
                else
                {
                        $qmap = $this->request->queue_timeout_rule;
                }
		$data = new StdClass;

		if(isset($this->request->queue_timeout_save) && $this->request->queue_timeout_save == 'yes') {
			$this->Set_Timeout_Rule($qmap, $this->request->queue_timeout_value);
			$this->request->queue_timeout_value = NULL;
			$data->view = (!empty($data->view)) ? $data->view : $data->view = 'reset_queue_timeouts';
		}

		if(isset($this->request->queue_timeout_backapply) && $this->request->queue_timeout_backapply == 'yes') {
			$this->Expire_Affiliations($qmap);
			$data->view = (!empty($data->view)) ? $data->view : $data->view = 'recycle_queues';
		}

		ECash::getTransport()->Set_Data($data);

	}
	
	public function Recycle_Queues()
	{
		$data = new StdClass;
		$this->Expire_Affiliations($this->request->queue_timeout_rule);
		$data->view = 'recycle_queues';		
		ECash::getTransport()->Set_Data($data);
	}

	public function Set_Recycle_Limit()
	{
		/**
		 * Retarded transport crap.
		 */
			$data = new StdClass;
			$data->view = "reset_queue_cycle_limits";
			ECash::getTransport()->Set_Data($data);

		$queue_name = $this->request->queue_recycle_limit_rule;
		$count = $this->request->queue_recycle_limit_value;

		$this->Set_Recycle_Limit_Rule($queue_name, $count);

	}
	
	public function Set_Recycle_Limit_Rule($queue_name_short, $count)
	{
		$queue_manager = ECash::getFactory()->getQueueManager();
		$queue = $queue_manager->getQueue(trim($queue_name_short));
		$queue_config = $queue->getConfig();
		$limit_model = ECash::getFactory()->getModel('QueueConfig');
		$limit_loaded = $limit_model->loadBy(array("queue_id" => 0, 'config_key' => 'recycle_limit'));
		//[#40090] I gave this a value of one if not set
		$limit_company_default = $limit_loaded ? $limit_model->config_value : 1;

		if ($count == "COMPANY")
		{
			$queue_config->setValue('recycle_limit', $limit_company_default);
		}
		else
		{
			$queue_config->setValue('recycle_limit', $count);
		}		
	}

	protected function Expire_Affiliations($queues)
	{
		$queue_manager = ECash::getFactory()->getQueueManager();

		if (is_array($queues))
		{
			foreach ($queues as $queue_name_short)
			{
				$queue_manager->getQueue(trim($queue_name_short))->flushUnavailableItems();
			}
		}
		else
		{
			$queue_manager->getQueue(trim($queues))->flushUnavailableItems();
		}

	}

	protected function Set_Timeout_Rule($queue_name_shorts, $timeout_value)
	{
		$qm = ECash::getFactory()->getQueueManager();
		$timeout_model = ECash::getFactory()->getModel('QueueConfig');
		$timeout_loaded = $timeout_model->loadBy(array("queue_id" => 0, 'config_key' => 'recycle_time'));
		//[#40090] I gave this a value of one if not set		
		$timeout_company_default = $timeout_loaded ? $timeout_model->config_value / 60 : 1;
		//hack because this is being called in two different ways
		if(!is_array($queue_name_shorts))
		{
			$queue_name_shorts = array($queue_name_shorts);
		}
		foreach($queue_name_shorts as $queue_name_short)
		{
			$q = $qm->getQueue(trim($queue_name_short));
			if ($timeout_value == "COMPANY")
			{
				$q->Config->setValue('recycle_time', $timeout_company_default);
			}
			else
			{
				$q->Config->setValue('recycle_time', $timeout_value*60);
			}
		}
	}
	
}

