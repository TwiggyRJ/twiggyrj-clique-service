<?php

// Table Of Contents
// 1.0: Config and none class functions
// 1.1: User Class for logining, registering and editing users
// 1.2: Base Functionality


// 1.0: Config and none class functions

// includes the password library for encrypting the password for storage on the SQL Database

date_default_timezone_set('Europe/London');

function connect_db(){

	return new PDO("mysql:host=localhost;dbname=", "", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

}


// 1.1: User Class for logining, registering and editing users

class User{
	
	public function reg_user($username, $password, $name){
		
		// Checks to see if the username variable only contains alpha numeric characters
		if (!ctype_alnum($username)){
			
			// error message
			$username = "";
			$password = "";
			
			header("HTTP/1.1 400 Bad Request");
			
		}
			
		// changes the session ID to prevent cross site scripting
		session_regenerate_id(); 
			
		// connect to the PDO database
		$conn = connect_db();
		
		$encrypt = $password;
		
		$encrypt = password_hash($password, PASSWORD_BCRYPT);
			
		$admin = 0;
		
		// attempts the query and catches any errors
		try{
			
			// a prepared statement that should help prevent SQL Injections
			$query = $conn->prepare("INSERT INTO users (username, password, name, joined, isadmin) VALUES (:username, :password, :name, Now(), :admin)");	
			$query->bindParam(":username", $username, PDO::PARAM_STR);
			// not encrypted $query->bindParam(":password", $password, PDO::PARAM_STR);
			$query->bindParam(":password", $encrypt, PDO::PARAM_STR);
			$query->bindParam(":name", $name, PDO::PARAM_STR);
			$query->bindParam(":admin", $admin, PDO::PARAM_STR);
			$query->execute();
			
			header("HTTP/1.1 200 OK");
		
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
			header("HTTP/1.1 400 Bad Request");
		}
		
	}
	
	public function login_user($username, $password){
		
		
		// changes the session ID to prevent cross site scripting
		session_regenerate_id(); 
		
		// Checks to see if the username variable only contains alpha numeric characters
		if (!ctype_alnum($username)){
			
			// error message
			//echo "One does not simply enter an invalid set of characters...";
			$username = "";
			$password = "";
			
			header("HTTP/1.1 400 Bad Request");
			
		}
		
		$conn = connect_db();
		$arr = array();
			
		$query = $conn->prepare("SELECT * from users WHERE username = :username");
		
			$query->bindParam(":username", $username, PDO::PARAM_STR);
			$query->execute();
		
		while($row = $query->fetch(PDO::FETCH_ASSOC)){
			if (password_verify($password, $row["password"])){
				// password was correct
				header("HTTP/1.1 200 OK");
				header ("Content-type: application/json");
				
				
				// collects user data ready
				
				$user_id = $row['ID'];
				$user_nm = $row['username'];
				$user_rnm = $row['name'];
				$user_jd = $row['joined'];
				$user_pw = $row['password'];
				$user_ad = $row['isadmin'];
				$user_av = $row['avatar'];
				$user_fd = $row['fontdark'];
				
				$querycount = $conn->prepare("SELECT COUNT(*) as nofreviews FROM reviews WHERE username = :username");
				$querycount->bindParam(":username", $user_nm, PDO::PARAM_STR);
				$querycount->execute();
				
				$user_rv = "";
				
				while($row2 = $querycount->fetch(PDO::FETCH_ASSOC)){
					$user_rv = $row2['nofreviews'];
				}
				
				//Puts the collected data into an array
				$user_array = array("username" => $user_nm, "password" => $user_pw, "name" => $user_rnm, "joined" => $user_jd, "avatar" => $user_av, "reviews" => $user_rv, "admin" => $user_ad, "ID" => $user_id, "fontdark" => $user_fd);
				
				//Connects the user array to the array that will be pushed out to the client
				array_push($arr, $user_array);
				
				//Pushes out the array as a JSON object for the clients to consume
				echo json_encode($arr);
			
			}else{
				header("HTTP/1.1 401 Unauthorized");
			}
			
		}
	}
}

// 1.2: Base Functionality

class NTD_Base{

	public function admin_approve(){
		// the script that creates the content of the administrator area where admins can approve reviews

		$conn = connect_db();
		
		// Selects all reviews that are not approved

		$results = $conn->query("SELECT * from reviews where approved = 0 ORDER BY ID");
		
			while($row = $results->fetch()){
				
				// all the variables necessary to provide the data to the admins that are echoed in a loop
				
				$id = $row['ID'];
				$author = $row['username'];
				$review = $row['review'];
				$reviewdate = $row['reviewdate'];
					
					// echoes out the forms, buttons and information about each individual review allowing admins to approve them, this loops until there's no more values in $row
					
					// the <script> is the javascript/ jquery method of sending data via AJAX to another PHP script that handles the receiving of data ready to be sent to the function that approves the review
					
				echo"
					
					<form id = 'form_apr$id' method='post' action='includes/admin_approve.php?id=$id'>
						<input type='hidden' name = 'approve$id' id = 'approve$id' value = '$id'/> 
					</form>
					
					<p><b>Review ID: $id, $author:</b><br />The Review: $review <br /> Created: <b>";echo date("D, d M Y H:i:s", strtotime($reviewdate)); echo"</b><br /> <br />
					
					<button type='submit' id='submit_approval$id' name='submit_approval$id' value='Approve' class='button pri_accent' form='form_apr$id'>
						<i class='fa fa-thumbs-up'></i> Approve
					</button>
					</p>
					<br />
					
					<div id = 'div$id'></div>
				
						<br />
							
						<script>
							
							$(document).ready(function(){
											
									$('#form_apr$id').submit(function(e){
												
										var postData = $(this).serializeArray();
										var formURL = $(this).attr('action');
										$.ajax({
										
											url : formURL,
											type: 'POST',
											data : postData,
											success:function(data, textStatus, jqXHR){
												$('#div$id').html('<p>'+data+'</p>');
											},
											error: function(jqXHR, textStatus, errorThrown){
												$('#div$id').html('<p>AJAX Request Failed<br/> textStatus='+textStatus+', errorThrown='+errorThrown+'</p>');
											}
											
										});
										e.preventDefault();	//STOP default action
										e.unbind();
									});
								
							});	
								
						</script>";
			}
	}
	
	public function admin_approved(){
		// the script that creates the content of the administrator area where admins can disapprove reviews
	
		$conn = connect_db();

		// Selects all reviews that are approved
		
		$results = $conn->query("SELECT * from reviews where approved = 1 ORDER BY ID");
		
			// all the variables necessary to provide the data to the admins that are echoed in a loop
		
			while($row = $results->fetch()){
				// echoes out the forms, buttons and information about each individual review allowing admins to disapprove them, this loops until there's no more values in $row
			
				// the <script> is the javascript/ jquery method of sending data via AJAX to another PHP script that handles the receiving of data ready to be sent to the function that approves the review
				
				$id = $row['ID'];
				$author = $row['username'];
				$review = $row['review'];
				$reviewdate = $row['reviewdate'];
					
				echo"
					
					<form id = 'form_dapr$id' method='post' action='includes/admin_disapprove.php?id=$id'>
						<input type='hidden' name = 'dapprove$id' id = 'dapprove$id' value = '$id'/> 
					</form>
					
					<p><b>Review ID: $id, $author:</b><br />The Review: $review <br /> Created: <b>";echo date("D, d M Y H:i:s", strtotime($reviewdate)); echo"</b><br /> <br />
					
					<button type='submit' id='submit_disapproval$id' name='submit_disapproval$id' value='Disapprove' class='button pri_accent' form='form_dapr$id'>
						<i class='fa fa-thumbs-down'></i> Disapprove
					</button>
					</p>
					<br />
					
					<div id = 'div$id'></div>
				
						<br />
							
						<script>
							
							$(document).ready(function(){
											
									$('#form_dapr$id').submit(function(e){
												
										var postData = $(this).serializeArray();
										var formURL = $(this).attr('action');
										$.ajax({
										
											url : formURL,
											type: 'POST',
											data : postData,
											success:function(data, textStatus, jqXHR){
												$('#div$id').html('<p>'+data+'</p>');
											},
											error: function(jqXHR, textStatus, errorThrown){
												$('#div$id').html('<p>AJAX Request Failed<br/> textStatus='+textStatus+', errorThrown='+errorThrown+'</p>');
											}
											
										});
										e.preventDefault();	//STOP default action
										e.unbind();
									});
								
							});	
								
						</script>";
			}
	}
	
	public function admin_approve_review($id, $approved, $message){
		// approves and disapproves the review that was approved or disapproved
		
		$conn = connect_db();
		
		try{
		// UPDATE approved with $approved where $id matches the id in the table
		$query = "UPDATE reviews SET approved = :approved WHERE ID = :id";

		$query_prep = $conn->prepare($query);
		$query_prep->bindValue(":approved", $approved, PDO::PARAM_STR);
		$query_prep->bindValue(":id", $id, PDO::PARAM_STR);
		$query_prep->execute();
		
		echo "This review has been $message";
		
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
		}
	}
		
	public function new_venue($name, $type, $desc){
		// adds a new venue from the add venue form which the variables $name, $type and $desc originated from
		
		session_start();
		
		// detects if special characters that can be used in a SQL Injection is in the variable and if there are, removes them
		$regexp="^[A-Za-z\s\.\!\,0-9]+$";
		if (!preg_match("/$regexp/",$desc)){
			$desc = strip_tags($desc);
		}
		if (!preg_match("/$regexp/",$name)){
			$name = strip_tags($name);
		}
		
		$conn = connect_db();
		
		// sets the username value to the value of the logged in user
		$owner = $_SESSION['userdata'][0];
		try{
		// inserts the venue into the table so venue name, type, description, the user who entered it into the appropriate tables
		$query = "INSERT INTO venues (name, type, description, username) VALUES (:name, :type, :desc, :owner)";

		$query_prep = $conn->prepare($query);
		$query_prep->bindValue(":name", $name, PDO::PARAM_STR);
		$query_prep->bindValue(":type", $type, PDO::PARAM_STR);
		$query_prep->bindValue(":desc", $desc, PDO::PARAM_STR);
		$query_prep->bindValue(":owner", $owner, PDO::PARAM_STR);
		$query_prep->execute();
		
		echo "You have added the venue: $name";
		
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
		}
		
	}
	
	public function add_review($id, $review){
		// adds a review from the data collected from the add review function which is the venue id and the review data
		
		session_start();
		
		// detects if special characters that can be used in a SQL Injection is in the variable and if there are, removes them
		$regexp="^[A-Za-z\s\.\!\,0-9]+$";
		if (!preg_match("/$regexp/",$review)){
			$review = strip_tags($review);
			// adds a warning to the admins that this comment could possibly be an attempted attack on the site
			$review = $review . " SQL injection attack may have been attempted";
		}
		
		$conn = connect_db();
		
		$owner = $_SESSION['userdata'][0];
		try{
		// inserts the reveiw data of the venue id, the review creator, the review, sets approved to 0 and sets the review date to the current server date and time.
		$query = "INSERT INTO reviews (venueID, username, review, approved, reviewdate) VALUES (:venue, :owner, :review, :approved, Now())";

		$query_prep = $conn->prepare($query);
		$query_prep->bindValue(":venue", $id, PDO::PARAM_STR);
		$query_prep->bindValue(":owner", $owner, PDO::PARAM_STR);
		$query_prep->bindValue(":review", $review, PDO::PARAM_STR);
		$query_prep->bindValue(":approved", FALSE, PDO::PARAM_STR);
		$query_prep->execute();
		
		echo "Your review has been submitted but you will need to wait for an Administrator to approve the review";
		
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
		}
		
	}
	
	public function add_rec($rec, $id){
		
		session_start();
		
		$conn = connect_db();
		
		try{
		$query = "UPDATE venues SET recommended = :recommended WHERE ID = :id";

		$query_prep = $conn->prepare($query);
		$query_prep->bindValue(":recommended", $rec, PDO::PARAM_STR);
		$query_prep->bindValue(":id", $id, PDO::PARAM_STR);
		$query_prep->execute();
		
		echo "You have recommended this venue!";
		
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
		}
		
	}
	
	public function search_feed($search_term, $method){
		session_start();
		$conn = connect_db();
		
		if($method=="name"){
			$results = $conn->query("SELECT * from venues where name like '$search_term' ORDER BY ID");
		}elseif ($method=="type"){
			if ($search_term =="cafe"){
				$search_term = "café";
			} elseif ($search_term =="Cafe"){
				$search_term = "Café";
			}
			$results = $conn->query("SELECT * from venues where type = '$search_term' ORDER BY ID");
		
		}elseif ($method=="id"){
			$results = $conn->query("SELECT * from venues where ID ='$search_term'");
		}elseif ($method=="all"){
			$results = $conn->query("SELECT * from venues ORDER BY ID");
		}
		
		if ($results->rowCount() == 0) {
			header("HTTP/1.1 404 Not Found");
		}
		
		$rev_count = 0;
		
		if ($results->rowCount() > 0) {
			
			header ("Content-type: application/json");
			header("HTTP/1.1 200 OK");
			
			$arr = array();

			while($row = $results->fetch()){
				
				$name = $row['name'];
				$desc = $row['description'];
				$type = $row['type'];
				$rec = $row['recommended'];
				$owner = $row['username'];
				$id = $row['ID'];
				$img = $row['ImagePath'];
				
				$result_review = $conn->query("SELECT * from reviews where venueID = '$id' AND approved = 1 ORDER BY ID");
		
				$reviews = array();
		
				while($row = $result_review->fetch()){
					
					$author = $row['username'];
					$review = $row['review'];
					$reviewdate = $row['reviewdate'];
					$reviewid = $row['ID'];
					$reviewvid = $row['venueID'];
					$avatar = "";
					
					$result_avatar = $conn->query("SELECT * from users where username = '$author'");
					
					while($row = $result_avatar->fetch()){
						$avatar = $row['avatar'];
					}
					
					$reviews = array("reviewer" => $author, "ureview" => $review, "reviewed" => date("D, d M Y H:i:s", strtotime($reviewdate)), "avatar" => $avatar, "rid" => $reviewid, "vid" => $reviewvid);
				
				}

				$search_row = array("name" => $name, "description" => $desc, "type" => $type, "recommended" => $rec, "owner" => $owner, "id" => $id, "ImagePath" => $img, "Reviews" => array());
				
				array_push($search_row['Reviews'], $reviews);
				
				array_push($arr, $search_row);
			}
			
			echo json_encode($arr);
			
		}elseif($search_term == "There is no Cake!" || $search_term == "There is no Cake" || $search_term == "There is no Cake!?" || $search_term == "there is no cake!" || $search_term == "There is no cake!"){
			/*
			$arr = array();
			
			$joke = array("name" => "There is no Cake!?", "description" => 'https://www.youtube.com/embed/Y6ljFaKRTrI Please assume the party position!', "type" => "Comedy", "recommended" => "0", "owner" => "GLaDOS", "id" => "999", "ImagePath" => "http://apps.kshatriya.co.uk/test_case/img/test.jpg", "Reviews" => array());
			
			$acheivement = array("reviewer" => "GLaDOS", "ureview" => "Achievement Unlocked!: There is no Cake!? Easter Egg Bonus!", "reviewed" => "", "avatar" => "http://apps.kshatriya.co.uk/test_case/img/test.jpg", "rid" => "999", "vid" => "666");
			
			array_push($joke['Reviews'], $acheivement);
			
			array_push($arr, $joke);
			
			echo json_encode($arr);
			*/
		}elseif($search_term == "One Does Not Simply" || $search_term == "One does not simply" || $search_term == "Boromir"){
			echo"
					<div class = 'home-blog-feed'>
						<h1>One Does not Simply!</h1>
						<p>One does not simply find an easter egg</p>
						<img src = 'img/onds.png' width='335px' height='670px'/>
						<br />
						<p><b>Achievement Unlocked!:</b> One does not Simply! Easter Egg Bonus!</p>
						<br />
					</div>
			";
		}else{
			
			/*
			echo"
					<div class = 'home-blog-feed'>
						<h1>Awww, come on Man! No search results!</h1>
						<p>How you uh, how you comin' on that novel you're working on? Huh? Gotta a big, uh, big stack of papers there? Gotta, gotta nice little story you're working on there? Your big novel you've been working on for 3 years? Huh? Gotta, gotta compelling protagonist? Yeah? Gotta obstacle for him to overcome? Huh? Gotta story brewing there? Working on, working on that for quite some time? Huh? (voice getting higher pitched) Yea, talking about that 3 years ago. Been working on that the whole time? Nice little narrative? Beginning, middle, and end? Some friends become enemies, some enemies become friends? At the end your main character is richer from the experience? Yeah? Yeah? No, no, you deserve some time off.</p>
					</div>
			";
			*/
			
			header("HTTP/1.0 404 Not Found");
			
		}
		
	}
	
	
	public function search_feed_reviews($search_term, $method){
		session_start();
		$conn = connect_db();
		
		if ($method=="id"){
			$results = $conn->query("SELECT * from reviews where venueID = '$search_term' AND approved = 1 ORDER BY ID");
		}
		
		if ($results->rowCount() == 0) {
			header("HTTP/1.1 404 Not Found");
		}
		
		$rev_count = 0;
		
		if ($results->rowCount() > 0) {
			
			header ("Content-type: application/json");
			header("HTTP/1.1 200 OK");
		
			$arr = array();

			while($row = $results->fetch()){
				
				$author = $row['username'];
				$review = $row['review'];
				$reviewdate = $row['reviewdate'];
				$reviewid = $row['ID'];
				$reviewvid = $row['venueID'];
				$avatar = "";
				
				$result_avatar = $conn->query("SELECT * from users where username = '$author'");
				
				while($row = $result_avatar->fetch()){
					$avatar = $row['avatar'];
				}
				
				$reviews = array("reviewer" => $author, "ureview" => $review, "reviewed" => date("D, d M Y H:i:s", strtotime($reviewdate)), "avatar" => $avatar, "rid" => $reviewid, "vid" => $reviewvid);
				
				array_push($arr, $reviews);

			}
			
			echo json_encode($arr);
			
		}

	}
}

?>