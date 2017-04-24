<?php include("includes/header.php") ?>
<?php include("includes/navigation.php") ?>
	
	<div class="jumbotron">
	<?php echo display_message(); ?>
		<h1 class="text-center">Home</h1>
	</div>

	<?php

		$sql ="SELECT * FROM users";
		$result = query($sql);

		confirm($result);

		$row = fetch_array($result);

	?>

<?php include("includes/footer.php") ?>