<!doctype html public "-//W3C//DTD HTML 4.0 //EN">
<html>
<head>
<?php include "head.php"; ?>
	<script
	src="https://code.jquery.com/jquery-3.7.1.min.js"
	integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
	crossorigin="anonymous"></script>

	<style>
		#loader {
			border: 0.75rem solid var(--main-hrborder-color);
			border-radius: 50%;
			border-top: 0.75rem solid var(--main-boxbg-color);
			width: 4.4rem;
			height: 4.4rem;
			animation: spin 1s linear infinite;
		}
		
		@keyframes spin {
			100% {
				transform: rotate(360deg);
			}
		}
		
		.center {
			position: absolute;
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
			margin: auto;
		}
	</style>
</head>
<body>
	<img src="img/updown.png" />
	<div id="loader" class="center"></div>
	<script>
		document.onreadystatechange = function () {
		var state = document.readyState
			if (state == 'interactive') {
				document.getElementById('loader').style.visibility="visible";
			}
		}
		var loc = window.location.href;
		window.location.href = loc.replace("Load.php", ".php");
	</script>
</body>
</html>
