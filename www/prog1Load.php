<!doctype html public "-//W3C//DTD HTML 4.0 //EN"> 
<html>
<head>
	<link rel="stylesheet" href="main.css" />
	<script language="JavaScript" src="js/jquery-3.3.1.min.js" type="text/javascript"></script>
	<style>
		#loader {
			border: 12px solid var(--main-hrborder-color);
			border-radius: 50%;
			border-top: 12px solid var(--main-tablesortable-color);
			width: 70px;
			height: 70px;
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
