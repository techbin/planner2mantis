<div style="border:1px solid #0066CC; width:700px;background-color:#E6F3FF;padding-left:20px;padding-top:20px; margin-left:50px;">

<?php

require_once(dirname(__FILE__).'/exportPlanner.php');//include exportPlanner file to create instance



if(isset($_POST['MAX_FILE_SIZE']))

{



	$target_path = "planner_files/";

	

	$target_path = $target_path .time().".planner";// basename( $_FILES['uploadedfile']['name']); 

	

	if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {

		echo "The planner file :".  basename( $_FILES['uploadedfile']['name']). 

		" has been uploaded<br><br><br>";

	} else{

		echo "There was an error uploading the file, please try again!";

	}

	

	//create exportPlanner object to run the script

	new exportPlanner2Mantis($target_path);



}

?></div>

