	function updateCount() 
	{
		document.getElementById("remaining").innerHTML = 140 - document.getElementById("status").value.length;
		setTimeout(updateCount, 400);
	}

	function confirmShortTweet() 
	{
		var len = document.getElementById("status").value.length;
		if (len < 30) return confirm("That\'s a short tweet.\nContinue?");
		return true;
	}
	updateCount();

