<?php

/**********************Helper Functions*************************/

//clean up stings keep users from inputting sybols into input forms
function clean($string){

return htmlentities($string);

}

//redirect pages
function redirect($location){

	return header("Location: {$location}");

}


//Session message
function set_message($message) {

		if(!empty($message)){

			$_SESSION['message'] = $message;

		} else{

			$message = "";

		}

}

//display message
function display_message(){

	if(isset($_SESSION['message'])){

		echo $_SESSION['message'];

		unset ($_SESSION['message']);

	}


}


//Displays Errors
function validation_error($error_message){

$error_message = <<<DELIMITER

<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Warning!</strong> $error_message </div>


DELIMITER;

return $error_message;
}


function email_exists($email){

	$sql = "SELECT id FROM users WHERE email = '$email'";

	$result = query($sql);

	if(row_count($result) == 1) {

		return true;

	} else {

		return false;

	}

}


function username_exists($username){

	$sql = "SELECT id FROM users WHERE username = '$username'";

	$result = query($sql);

	if(row_count($result) == 1) {

		return true;

	} else {

		return false;

	}

}


//Send Comfirmation email to user
function send_email($email, $subject, $msg, $headers){

	return mail($email, $subject, $msg, $headers);


}



//encryption strings
function token_generator(){

	$token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));

	return $token;
}


/**********************Validation Functions*************************/


function validate_user_registration(){

	$errors = [];

	$min = 3;
	$max = 20;


	if($_SERVER['REQUEST_METHOD'] == "POST") {

		$first_name =  clean($_POST['first_name']);
		$last_name =  clean($_POST['last_name']);
		$username =  clean($_POST['username']);
		$email =  clean($_POST['email']);
		$password =  clean($_POST['password']);
		$confirm_password =  clean($_POST['confirm_password']);

		if(strlen($first_name) < $min){

			$errors[] = "Your first name cannot be less that {$min} characters";

		}

		if(strlen($first_name) > $max){

			$errors[] = "Your first name cannot be more that {$max} characters";

		}

		if(strlen($first_name) === ""){

			$errors[] = "Your first name cannot be empty";
		
		}


		if(strlen($last_name) > $max){

			$errors[] = "Your first name cannot be more that {$max} characters";

		}

		if(strlen($last_name) < $min){

			$errors[] = "Your last name cannot be less that {$min} characters";

		}

		if(strlen($last_name) === ""){

			$errors[] = "Your last name cannot be empty";
		
		}



		if($password !== $confirm_password){

			$errors[] = "Your passwords do not match";

		}


		if(email_exists($email)){

			$errors[] = "Sorry, that email already registered";

		}


		if(username_exists($username)){

			$errors[] = "Sorry, that username already taken";

		}

		
		if(!empty($errors)){

			foreach ($errors as $error ) {
				
				echo validation_error($error);
			}

				
			} else {

				if(register_user($first_name, $last_name, $username, $email, $password)){

					set_message("<p class='bg-success text-center'>Please check your email or spam folder for activation link</p>");

					redirect("index.php");

				} else {

					set_message("<p class='bg-danger text-center'>Sorry, we could not register the user. Please try again.</p>");

					redirect("index.php");


				}


			}

		}
		

	} 

/**********************Register Users Function*************************/
 

function register_user($first_name, $last_name, $username, $email, $password) {


	$first_name = escape($first_name);
	$last_name = escape($last_name);
	$username = escape($username);
	$email = escape($email);
	$password = escape($password);


	if(email_exists($email)) {

		return false;

	} else if (username_exists($username)){

		return false;

	} else {

		$password = md5($password);

		$validation_code = md5($username . microtime());

		$sql = "INSERT INTO users(first_name, last_name, username, email, password, validation_code, active)";
		$sql.= "VALUES('$first_name','$last_name','$username','$email','$password','$validation_code',0)";
		$result = query($sql);
		confirm($result);

		$subject = "Activate Account";
		$msg = "Please click the link below to active your Account.
		http://localhost/login/active.php?email=$email&code=$validation_code
		"; // <-- Add your website
		$headers = "From: noreply@yourwebsite.com";

		send_email($email, $subject, $msg, $headers);

		return true;


	}

} 


/**********************Activate Users Function*************************/


function activate_user() {

	if($_SERVER['REQUEST_METHOD'] == "GET"){


		if(isset($_GET['email'])) {


			echo $email = clean($_GET['email']);

			echo $validation_code = clean($_GET['code']);

			$sql = "SELECT id FROM users WHERE email = '".escape($_GET['email'])."' AND validation_code = '".escape($_GET['code'])."'";

			$result = query($sql);
			confirm($result);

			if(row_count($result) == 1){

			$sql2 = "UPDATE users SET active = 1, validation_code = 0 WHERE email = '".escape($email)."' AND validation_code = '".escape($validation_code)."'";	
			$result2 = query($sql2);
			confirm($result2);

			set_message ("<p class='bg-success'>Your Account has been activated please login</p>");
			redirect("login.php");

			} else {

			set_message ("<p class='bg-danger'>Sorry, Your Account could not be activated at this time. Please try again.</p>");
			redirect("login.php");	

			}

		}

	}

} //functions

/**********************Validate Users Login Function*************************/

function validate_user_login(){

	$errors = [];

	$min = 3;
	$max = 20;


	if($_SERVER['REQUEST_METHOD'] == "POST") { 

		$email      = clean($_POST['email']);
		$password   = clean($_POST['password']);
		$remember   = isset($_POST['remember']);

		if(empty($email)) {

			$errors[] = "Email field cannot be empty";
		}

		if(empty($password)) {

			$errors[] = "Password field cannot be empty";
		}


		if(!empty($errors)){

			foreach ($errors as $error ) {
				
				echo validation_error($error);
			}

				
			} else {
			
				if(login_user($email, $password, $remember)) {

					redirect("admin.php");

				}else{

					echo validation_error("Your Crendentials are not correct");

				}

			}	

	}
}

/**********************Users Login Function*************************/

	function login_user($email, $password, $remember){

		$sql ="SELECT password, id FROM users WHERE email = '".escape($email)."' AND active = 1";

		$result = query($sql);

		if(row_count($result) == 1) {

			$row = fetch_array($result);

			$db_password = $row['password'];

			if(md5($password) === $db_password) {

				if($remember == "on"){

					setcookie('email', $email, time() + 86400);

				}

				$_SESSION['email'] = $email;


				return true;

			} else {


				return false;

			}

			return true;
		
		} else {

			return false;

		}


	}

	/**********************Logged in Function*************************/

	function logged_in() {

		if(isset($_SESSION['email']) || isset($_COOKIE['email'])){

			return true;

		} else {

			return false;

		}


	}

	/**********************Recover Password function*************************/

	function recover_password(){

		if($_SERVER['REQUEST_METHOD'] == "POST") {

			if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']) {

					$email = clean($_POST['email']);


					if(email_exists($email)){


						$validation_code = md5($email . microtime());

						setcookie('temp_access_code', $validation_code, time()+ 1000);

						$sql = "UPDATE users SET validation_code = '".escape($validation_code)."' WHERE email ='".escape($email)."'";
						$result = query($sql);
					

						$subject = "Please reset your password";
						$message = "Here is your password reset {$validation_code}

						Click here to reset your password http://localhost/code.php?email=$email&code=$validation_code	

						";

						$headers = "From: noreply@yourwebsite.com";

						send_email($email, $subject, $message, $headers);

						set_message("<p class='bg-success text-center'>Please check your email or spam folder for a password reset code.</p>");

						redirect("index.php");


					} else {

						echo validation_error("This email dose not exist");

					}

			} else {

				redirect("index.php");

			} 

			// Token Checks

			if(isset($_POST['cancel_submit'])) {

				redirect("login.php");

			}

   		

		}// Post request


	}

	/**********************Code Validation function*************************/	

	function validate_code() {

		if(isset($_COOKIE['temp_access_code'])) {

				if(!isset($_GET['email']) && !isset($_GET['code'])) {

					redirect("index.php");


				} else if(empty($_GET['email']) || empty($_GET['code'])){

					redirect("index.php");

				} else {


					if(isset($_POST['code'])) {

						$email = clean($_GET['email']);

						$validation_code = clean($_POST['code']);

						$sql ="SELECT id FROM users WHERE validation_code = '".escape($validation_code)."' AND email ='".escape($email)."'";
						$result = query($sql);

						if(row_count($result) == 1) {

							setcookie('temp_access_code', $validation_code, time()+ 1000);

							redirect("reset.php?email=$email&code=$validation_code");

						} else {

							echo validation_error("Sorry wrong validation code");

						}

					}

				}			

		} else {

			set_message("<p class='bg-danger text-center'>Sorry, your validation cookie was expired.</p>");

			redirect("recover.php");

		}

	}
	/**********************Password Reset Function*************************/

	function password_reset(){

		if(isset($_COOKIE['temp_access_code'])) {

			if(isset($_GET['email']) && isset($_GET['code'])){

				if(isset($_SESSION['token']) && isset($_POST['token'])){ 

					if ($_POST['token'] === $_SESSION['token']) {

						if($_POST['password'] === $_POST['confirm_password']) {

							$updated_password = md5($_POST['password']);
							
							$sql = "UPDATE users SET password = '".escape($updated_password)."', validation_code = 0 WHERE email='".escape($_GET['email'])."'";
							query($sql);

							set_message("<p class='bg-danger text-center'>Your password has been updated, please login.</p>");

							redirect("login.php");
						
						} else {

							echo validation_errors("Password fields don't match");
						}
		

				}
	

			}	

		}


	} else {

			set_message("<p class='bg-danger text-center'>Sorry, your validation time was expired.</p>");
			redirect("recover.php");

		} 		

}	

?>



