<?php

require('functions.php');
session_start();
$dbConn = sqlConnect();
pageHeader('New User');

?>
<script src="js/newUser.js"></script>
<div class="row w-75 text-center border-bottom m-3" id="head">
	<h3>Create New User</h3>
</div> 
<?php
if(isset($_SESSION['uid'])) {
	?>
			<div class="row col-sm-8 text-center">
				<h4>You are already logged in</h4>
			</div>
			<div class="row col-sm-8 text-center">
				<h5><a href="index.php" class="d-inline-flex focus-ring py-1 px-2 text-decoration-none border rounded-2">Return to main page</a></h5>
			</div>
		</div>
	</body>
</html>
	<?php
	exit;
}
?>			<form class="col-sm-3" id="newUser">
				<div class="mb-3">
					<label for="emailAddress" class="form-label">E-mail Address</label>
					<input type="email" class="form-control" id="emailAddress">
					<div id="emailReject" class="form-text text-danger d-none">Invalid e-mail address</div>
				</div>
				<div class="mb-3">
					<label for="password" class="form-label">Password</label>
					<input type="password" class="form-control" id="password1">
					<div id="passwordReject" class="form-text text-danger d-none">Password cannot be blank</div>
				</div>
				<div class="mb-3">
					<label for="password2" class="form-label">Confirm Password</label>
					<input type="password" class="form-control" id="password2">
					<div id="password2Reject" class="form-text text-danger d-none">Passwords do not match</div>
				</div>
				<div class="mb-3">
					<label for="name" class="form-label">Name</label>
					<input type="text" class="form-control" id="name">
					<div id="nameReject" class="form-text text-danger d-none">Name cannot be blank</div>
				</div>
				<div class="mb-3">
					<label for="favTeam" class="form-label">Favorite Team</label>
					<select class="form-select" id="favTeam">
						<option selected value="-1"></option>
<?php
$teams = getTeams($dbConn);
foreach($teams as $id => $team) {
	if(is_numeric($id) && $team['isFBS'] == true) {
		?>
		<option value="<?= $id ?>"><?= $team['school'].' '.$team['mascot'] ?></option>
		<?php
	}
}
?>
					</select>
					<div id="teamReject" class="form-text text-danger d-none">Come on, pick a team, please!</div>
				</div>
				<div class="mb-3">
					<button class="btn btn-primary" type="button" id="submit">Create User</button>
				</div>
				<div id="failedServer" class="form-text text-danger d-none"></div>
			</form>
		</div>
	</body>
</html>
