<h2>Detail Report (Exporting Planner data to Mantis) </h2>

<pre>

<?php

//Class : export_planner

//Description: exports planners data to mantis

// Created By : Satish Kumar(satish.prg@gmail.com)

//Date: Jan 6, 2009

require_once(dirname(__FILE__).'/wsdl_config.php');//configuration file

include_once(dirname(__FILE__).'/wsdl_mantis.php');//mantis wsdl api configured functions



class exportPlanner2Mantis extends wsdl_mantis

{

	var $xml;var $completexml;

	var $allocation_xml;

	var $reporterid;

//constructor initiates planner xml content and grabs task

	function __construct($planner_url)

	{

		$this->xml=$this->read_planner($planner_url);// read planner file and store iit

		$this->allocation_xml=	$this->xml;

		$this->checkProjectName();

		//$this->tasks();

	}

//convert planner date to mantis datetime(timestamp)	

	function converttime_planner_mantis($time)

	{



		$year=substr($time,0,4);

		$month=substr($time,4,2);

		$day=substr($time,6,2);

		$time = mktime(0, 0, 0, $month, $day, $year);

		return $time;

		

	}

	function checkProjectName()

	{

		$wsdl_mantis=new wsdl_mantis();	

		$xml=$this->allocation_xml;

		

		//get Project ID From Custom field

		$arr=$xml->properties;

		$projectid=$arr[1]->property['value'];

		

	//	print_r($xml);		

	if(trim($xml['name'])=="")

	{

	echo "Please specify Project Title in the planner";

	exit();

	}

	else 	if(trim($projectid)=="")

	{

	echo "Please specify Project ID in the planner, (In the project property add custom field with title 'projectid' and value set it to the project id)";

	exit();

	}

	else 	if(trim($xml['manager'])=="")

	{

	echo "Invalid Manager Name.";

	exit();

	}

	

	$projectname=$xml['name'];

	$manager=$xml['manager'];		

	$this->tasks($projectid,$projectname,$manager);

	

	}

//grab tasks for the project	

	function tasks($projectid,$projectname,$manager)

	{

	$xml=$this->allocation_xml;

	$wsdl_mantis=new wsdl_mantis();	

	

	//print_r($xml);

	//exit;

	

		foreach ($xml->tasks as $success) {// Loop throughtout the tasks-------------------------------------------------------[C]

		

			//foreach ($success->task as $success1) {// Loop throughtout the tasks-------------------------------------------------------[B]

				echo "Projects Name: ".$projectname."<br>"; //Display Projects Name

				echo "Projects ID: ".$projectid."<br>"; 		//Display Projects ID	

				echo "Managers Name: ".$manager."<br>"; 		//Display Projects ID	

		

				$arr=$success;

				//check if reporterid exits or not

				$reporterid=$wsdl_mantis->checkUserid(MANTIS_USERNAME);

				//if reporter does not exist

				if($reporterid==0)

				{

				echo "<br>Please check mantis username or password in wsdl_config.php. <br>";

				exit;

				}

				$this->reporteridd=$reporterid;	

				



				//check if projectid exits or not

				$status=$wsdl_mantis->checkProjectid($projectid);

				//if project does not exist

				if($status==0)

				exit;



				//SET Project ID

				define('MANTIS_PROJECT_ID',$projectid);

				$projectid=MANTIS_PROJECT_ID;				

				

				echo "<br>";

	

				$subtasks=$arr->task;			

				$task_size=sizeof($arr->task);

				echo "Number of  tasks: ".$task_size."<br><br>"; //number od task

				

//create a node with project description--------



				$handlerid=0;//no handlers for parent task

				$summary=$projectname;

							

				$description=$summary;



				$work=0;

				$priority=0;							

				$work=0;//$subtask['work']/(60*60);

				//echo "Working Hours: ". $work."<br>";



				$priority="0";//trim($subtask['priority']);

				//echo "Priority: ". $priority."<br>";

				$eta=$work;

							

				if($subtask['type']=="milestone")

				$summary="[*] ".$summary;

				else

				$summary=$summary;

							

			$target_idA=$wsdl_mantis->addissue($projectid,$this->reporteridd,$handlerid,$summary,$description,$priority,$eta);

			//exit;	

			$note=$this->completexml;

			//echo "<textarea cols=100 rows=10>". $this->completexml."</textarea>";

			$wsdl_mantis->add_issue_note($target_idA,$this->reporteridd,$note);

			//exit;

//--------------------------

			

				 for($i=0;$i<$task_size;$i++)  // Loop throughtout the tasks-------------------------------------------------------[A]

				  {

						

							$subtask=$subtasks[$i];

							$handlerid=0;//no handlers for parent task

							$summary=$subtask['name'];

							

							if(trim($subtask['note'])!="")//if description is empty update title

							$description=$subtask['note'];

							else

							$description=$summary;



							$work=0;

							$priority=0;							

							$work=$subtask['work']/(60*60);

							//echo "Working Hours: ". $work."<br>";



							$priority=trim($subtask['priority']);

							//echo "Priority: ". $priority."<br>";

							$eta=$work;

							

							if($subtask['type']=="milestone")

							$summary="[*] ".$summary;

							else

							$summary=$summary;

							

							$parent_issueid=$target_idA;

							

							$target_idA=$wsdl_mantis->addissue($projectid,$this->reporteridd,$handlerid,$summary,$description,$priority,$eta);

							$issue_idA="";

							

							//-----add task complete--------------------------------------------

							//---add task custom field to database---------------------



							$bug_id=$target_idA;

							$start_date=$this->converttime_planner_mantis($subtask['start']);

							$finish_date=$this->converttime_planner_mantis($subtask['end']);

							

							$wsdl_mantis->mantis_insert_customfield(startdate,$bug_id,$start_date);

							$wsdl_mantis->mantis_insert_customfield(finishdate,$bug_id,$finish_date);

							

							

							$wsdl_mantis->mantis_insert_customfield(workinghours,$bug_id,$work);

							

							$wsdl_mantis->mantis_insert_customfield(percentcomplete,$bug_id,$subtask['percent-complete']);

							$wsdl_mantis->mantis_insert_customfield(scheduling,$bug_id,$subtask['scheduling']);

							

							//update mantis_bug_table

							if($priority=="")

							$priority="30";

							else

							$priority=trim($subtask['priority']);

							

							$wsdl_mantis->mantis_update_bugfield($bug_id,"eta",$work);

							$wsdl_mantis->mantis_update_bugfield($bug_id,"priority",$priority);

							//----------------------------------------------------------------

							$this->set_relationship($target_idA,$parent_issueid);

							$parent_issueid=$target_idA;

							//-----

							echo "<hr><br><font color=brown><b>Task Name: <b>".$subtask['name']."</b>..........Ticket created with id: ".$target_idA."</b></font>"; //task

							echo "<br>";		

							

							

							

							

								$subtasksA=$subtasks[$i]->task;		

								$sub_task_size=sizeof($subtasks[$i]->task);

								

								//CALL SUBTASKS UNTILL STOP STATUS IS Y

								$stop="n";

								while ($stop=="n")

								{

	

									$stop=$this->subtasks($subtasksA,$target_idA,$parent_issueid,$issue_idA);

	

								}

								

						//print_r($subtask);

						

				   }//---------------------------------------------------------------------------------------------------[A]

			

			

			

			

			

			//} //---------------------------------------------------------------------[B]

		}//--------------------------------------------------------------------------[C]

			

			

				

	}	



	

	function subtasks($subtasks,$target_idA,$parent_issueid,$issue_idA)

	{



		

							$subtasksA=$subtasks;	

							$sub_task_size=sizeof($subtasksA);

							echo "<font color=blue>Number of  subtasks: ".$sub_task_size."</font><br><br>"; //number od subtask



//-----------------------------------IMP // set up parent issueid

									if($issue_idA=="")

									$parent_issueid=$target_idA;

									else

									$parent_issueid=$issue_idA;



	

									for($j=0;$j<$sub_task_size;$j++) // Loop throughtout the subtasks------------------------[B]

									{

																

										echo "SubTask ID: ".$subtasksA[$j]['id']."<br>"; //subtask

										echo "SubTask Name: ".$subtasksA[$j]['name']."<br>"; //subtask

	

			

									//--- add sub task-----------------------------------working here

									$wsdl_mantisA=new wsdl_mantis();

									$projectid=MANTIS_PROJECT_ID;

									$reporterid=$this->reporteridd;

									

									$resid=$this->getResourseID($subtasksA[$j]['id']);

									

									

									$work=0;$priority=0;									

									$work=$subtasksA[$j]['work']/(60*60);

									



									$priority=trim($subtasksA[$j]['priority']);

									

									$eta=$work;

							

							

									//check if resourse id is blank

									if($resid==0)

									{

									$handlerid=0;//no handlers for parent task

									echo "<font color=blue>No user found, Assigned task to Manager</font><br>";

									}

									else

									{

									$handlerid=$resid;

									echo "<font color=green>Resourse with mantis userid :".$resid." ready for allocation</font><br>";

									}

									$summary=$subtasksA[$j]['name'];

									

									if(trim($subtasksA[$j]['note'])!="")//if description is empty update title

									$description=$subtasksA[$j]['note'];

									else

									$description=$summary;

									

									

							if($subtasksA[$j]['type']=="milestone")

							$summary="[*] ".$summary;

							else

							$summary=$summary;

									

									

									

									$issue_idA=$wsdl_mantisA->addissue($projectid,$reporterid,$handlerid,$summary,$description,$priority,$eta);









							//---add task custom field to database---------------------

	

							$bug_id=$issue_idA;

							$start_date=$this->converttime_planner_mantis($subtasksA[$j]['start']);

							$finish_date=$this->converttime_planner_mantis($subtasksA[$j]['end']);

							

							$wsdl_mantisA->mantis_insert_customfield(startdate,$bug_id,$start_date);

							$wsdl_mantisA->mantis_insert_customfield(finishdate,$bug_id,$finish_date);

							

							//$work=$subtasksA[$j]['work']/(60*60);

							$wsdl_mantisA->mantis_insert_customfield(workinghours,$bug_id,$work);

							

							$wsdl_mantisA->mantis_insert_customfield(percentcomplete,$bug_id,$subtasksA[$j]['percent-complete']);

							$wsdl_mantisA->mantis_insert_customfield(scheduling,$bug_id,$subtasksA[$j]['scheduling']);

							

							//update mantis_bug_table

							if($priority=="")

								$priority="30";

							else

								$priority=trim($subtasksA[$j]['priority']);

								

							$wsdl_mantisA->mantis_update_bugfield($bug_id,"eta",$work);

							$wsdl_mantisA->mantis_update_bugfield($bug_id,"priority",$priority);

							//----------------------------------------------------------------

















						

									echo "Sub Task Name: <b>".$subtasksA[$j]['name']."</b>...........Ticket created with id : ".$issue_idA."<br>"; //task

									//-----add sub task complete--------------------------------------------

									//echo "TARGET ID ".$target_idA."<br>";

									//echo "ISSUE ID ".$issue_idA."<br>";

									echo "Resourse allocated to <b>".$subtasksA[$j]['name']."</b>................resourse id ".$resid; //task

									echo "<br>";	

									



									//echo "<hr>";	

									if($issue_idA!="" and $parent_issueid!=$issue_idA)

									{

									//$target_idA=$issue_idA;

									$this->set_relationship($issue_idA,$parent_issueid);

									//$parent_issueid=$issue_idA;

									}



									echo "<br>";							

								

	

									//RECURISE FUNCTION TO CHECK IF AUB TASKS EXISTS

									//CHECK SIZE OF SUB TASKS

									//IF >0 then call function again 

														if(sizeof($subtasksA[$j]->task)>0)

														$this->subtasks($subtasksA[$j]->task,$target_idA,$target_idAA,$issue_idA);

														//else

														//return "y";

	

	

									}//-------------------End FOR Loop---------------------------------------[B]

	

	

	

	}

	

	

	function set_relationship($issue_idA,$target_idA)

	{

			$wsdl_mantisA=new wsdl_mantis();

			//setup relationship

			$relationType="c";

			echo "<b>Configuring relationship : ".$relationType.'-Issue source id: '.$issue_idA.'- Target parent id : '.$target_idA."</b> <br>"; //task		

			//print_r($issue_idA);	

			$wsdl_mantisA->addirelationship($relationType,$issue_idA,$target_idA);



	}



	function read_planner($url){

		 $xmlstr = "";

		//read planner file------------

		  $f = fopen($url, 'r' ); 

		  while( $data = fread( $f, 4096 ) ) { $xmlstr .= $data; }

		  fclose( $f );

		  $this->completexml=$xmlstr;

		//  print_r($this->completexml);

			//read planner files-------------

			$xml = new SimpleXMLElement($xmlstr);

			return $xml;

	}

	

	

	function getResourseID($taskid)

	{

		$xml=$this->allocation_xml;

		

								//-----------allocation starts-----------------------------------

								$allocations=$xml->allocations;

	

		

								$allocation_size=sizeof($allocations->allocation);

								$allocation=$allocations->allocation;

				

								$resid=0;//resouirse id setd efault to 0

								for($k=0;$k<$allocation_size;$k++) // Loop throughtout the  allocation------------------[C]

								{

								$allocationA=$allocation[$k];

	

								//check for allocations

											if(trim($allocationA['task-id'])==trim($taskid)){// if  (search for the task)---[D]

																	echo "AllocationTask ID: ".$allocationA['task-id']."<br>"; //subtask

																	echo "Resourse ID: ".$allocationA['resource-id']."<br>"; //subtask

																	echo "Units: ".$allocationA['units']."<br>"; //subtask

																	$resid=$this->search_resourses($allocationA['resource-id']);

							//echo "RES: ".$resid."<br>"; //subtask

																	

											}//-------------------------------------------------------------------------------end if------------------------------[D]

								

								}//Enb Loop allocations--------------------------------------------------------------------------------------[C]

				



				return $resid;

				

	}



	//get  userid (resourceid)

	function search_resourses($resourceid)

	{

				$wsdl_mantis=new wsdl_mantis();

				

				$xml=$this->allocation_xml;

				$resid=0;

				

				foreach ($xml->resources as $resources) {

				$resourceA=$resources->resource;

				$resources_size=sizeof($resources->resource);

	

				

								for($k=0;$k<$resources_size;$k++) 

								{

								

											

											if(trim($resourceA[$k]['id'])==trim($resourceid)){// if  (search for the task)---[D]							

											//echo "<b>".$resourceA[$k]['id'].$resourceid."</b><br>";

											$username=$resourceA[$k]['name'];

											$resid=$wsdl_mantis->checkUserid($username);

											//$resfound=$wsdl_mantis->checkUserid($resid);

											//echo "<b>".$resourceA[$k]['name']."</b>";

											//print_r($resfound);

											}

											

											

								}

				

				}

				

	if($resid=="")

	$resid=0;

	

	return $resid;

	

				

	}



//GRAB TASKS -N/A

function grab_tasks($xml,$url)

{



foreach ($xml->tasks as $success) {



	foreach ($success->task as $success1) {

	$arr=$success1;



			echo "Projects Name: ".$arr['name']."<br>"; //Projects Name

			$main_projectid=$arr['note'];

			echo "Projects ID: ".$arr['note']."<br>"; //Projects Name

			//initialize project id pulled from planners first tasks notes field

			define('MANTIS_PROJECT_ID',$main_projectid);

			echo "<br>";



	$subtasks=$arr->task;			

	$task_size=sizeof($arr->task);

	echo "Number of  tasks: ".$task_size."<br><br>"; //number od task

		

		

		for($i=0;$i<$task_size;$i++)  // Loop throughtout the tasks-------------------------------------------------------[A]

		{



			$subtask=$subtasks[$i];

			//echo "Task Name: <b>".$subtask['name']."</b>"; //task

			//echo "<br>";

			

			//--- add task-----------------------------------

			$wsdl_mantis=new wsdl_mantis();

			$projectid=MANTIS_PROJECT_ID;

			$reporterid=MANTIS_REPORTER_ID;

			$handlerid=0;//no handlers for parent task

			$summary=$subtask['name'];

			

			if(trim($subtask['note'])!="")//if description is empty update title

			$description=$subtask['note'];

			else

			$description=$summary;

			

			$target_idA=$wsdl_mantis->addissue($projectid,$reporterid,$handlerid,$summary,$description);

			

			//-----add task complete--------------------------------------------

			echo "<font color=red>Task Name: <b>".$subtask['name']."</b>.............................................Ticket created with id: ".$target_idA."</font>"; //task

			echo "<br>";			

			

					$subtasksA=$subtasks[$i]->task;		

					$sub_task_size=sizeof($subtasks[$i]->task);

					echo "Number of  subtasks: ".$sub_task_size."<br><br>"; //number od subtask

							

							for($j=0;$j<$sub_task_size;$j++) // Loop throughtout the subtasks------------------------[B]

							{

								

										echo "SubTask ID: ".$subtasksA[$j]['id']."<br>"; //subtask

										echo "SubTask Name: ".$subtasksA[$j]['name']."<br>"; //subtask

										//echo "Description: ".$subtasksA[$j]['note']."<br>"; //subtask

										//echo "Start Date: ".$subtasksA[$j]['start']."<br>"; //subtask

										//echo "End Date: ".$subtasksA[$j]['end']."<br>"; //subtask

										//echo "Percent-complete: ".$subtasksA[$j]['percent-complete']."<br>"; //subtask

										//echo "Type: ".$subtasksA[$j]['type']."<br>"; //subtask

										//echo "Scheduling: ".$subtasksA[$j]['scheduling']."<br>"; //subtask

																		















							//-----------allocation starts-----------------------------------

							$allocations=$xml->allocations;

							$allocation_size=sizeof($allocations->allocation);

							$allocation=$allocations->allocation;

			

							$resid=0;//resouirse id setd efault to 0

							for($k=0;$k<$allocation_size;$k++) // Loop throughtout the  allocation------------------[C]

							{

							$allocationA=$allocation[$k];



							//check for allocations

										if(trim($allocationA['task-id'])==trim($subtasksA[$j]['id'])){// if  (search for the task)---[D]

																echo "AllocationTask ID: ".$allocationA['task-id']."<br>"; //subtask

																echo "Resourse ID: ".$allocationA['resource-id']."<br>"; //subtask

																echo "Units: ".$allocationA['units']."<br>"; //subtask

																$resid=$this->search_resourses($allocationA['resource-id']);

						//echo "RES: ".$resid."<br>"; //subtask

																

										}//-------------------------------------------------------------------------------end if------------------------------[D]

							

							}//Enb Loop allocations--------------------------------------------------------------------------------------[C]

			

			

		

					//--- add sub task-----------------------------------working here

			$wsdl_mantisA=new wsdl_mantis();

			$projectid=MANTIS_PROJECT_ID;

			$reporterid=MANTIS_REPORTER_ID;

			

			//check if resourse id is blank

			if($resid==0)

			$handlerid=0;//no handlers for parent task

			else

			$handlerid=$resid;

			

			$summary=$subtasksA[$j]['name'];

			

			if(trim($subtasksA[$j]['note'])!="")//if description is empty update title

			$description=$subtasksA[$j]['note'];

			else

			$description=$summary;

			

			$issue_idA=$wsdl_mantisA->addissue($projectid,$reporterid,$handlerid,$summary,$description);





			echo "Sub Task Name: <b>".$subtasksA[$j]['name']."</b>......................................Ticket created with id : ".$issue_idA."<br>"; //task

			//-----add sub task complete--------------------------------------------

	//echo "TARGET ID ".$target_idA."<br>";

	//echo "ISSUE ID ".$issue_idA."<br>";

			echo "Resourse allocated to <b>".$subtasksA[$j]['name']."</b>................resourse id ".$resid; //task

			echo "<br>";	

			

			//setup relationship

			$relationType="c";

			echo "<b>Configuring relationship : ".$relationType.'-Issue source id: '.$issue_idA.'- Target parent id : '.$target_idA."</b> <br>"; //task		

			//print_r($issue_idA);	

			$wsdl_mantisA->addirelationship($relationType,$issue_idA,$target_idA);

					

echo "<hr>";	



								

									//	echo "<br>";

								

							}//Enb Loop sub tasks---------------------------------------------------------------------------------------[B]

			

			

					}//End loop---tasks-----------------------------------------------------------------------------------------------------------------[A}

		



				}

		

		}

		

	

	}//--end function





}//end class





  ?>


