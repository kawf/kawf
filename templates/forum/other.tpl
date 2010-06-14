  <td align="left" width="1%">
    <a href="/{FORUM_SHORTNAME}/"><img border="0" alt="{FORUM_NAME}" src="/pics/forum/{FORUM_SHORTNAME}.png"></a>
  </td>
  <td valign="top" align="right" width="99%">{FORUM_NOTICES}</td>
  <script>

var v;
function eugmove() {
	var eug = document.getElementById("eug");

	if (!eug.complete) {
		setTimeout(_eugmove, 100);
		return;
	}

	eug.style.right = (parseInt(eug.style.right) + 20) + "px";
	eug.style.top = (parseInt(eug.style.top) + v) + "px";
	v = v + 1;

	if (parseInt(eug.style.top) > (window.innerHeight + 50))
		document.body.removeChild(eug);
	else
		setTimeout(eugmove, 30);
}

function euginit(e) {
	if (e.type != "keypress")
		return;

	var key = e.which;

	switch (key) {

	case 69: // "E"
		var eug = document.createElement("img");
		eug.id = "eug";
		eug.setAttribute("src", "http://jcs.org/tmp/transfat.png");
		eug.style.position = "fixed";
		eug.style.right = "-156px";
		eug.style.top = "0px";
		v = -1;
		document.body.appendChild(eug);

		setTimeout(eugmove, 1000);
	}
}

window.addEventListener("keypress", euginit, false);


  </script>
