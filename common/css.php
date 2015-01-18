<?php

function css() 
{
//	Get the colours that the user has chosen
$c = theme('colours');

$css = "@charset \"UTF-8\";

@font-face {
	font-family: 'icons';
	src: url('".BASE_URL."fonts/dabr-icons.eot');
	src: url('".BASE_URL."fonts/dabr-icons.eot#iefix') format('embedded-opentype'),
	     url('".BASE_URL."fonts/dabr-icons.woff2') format('woff2'),
	     url('".BASE_URL."fonts/dabr-icons.woff') format('woff'),
	     url('".BASE_URL."fonts/dabr-icons.ttf') format('truetype'),
	     url('".BASE_URL."fonts/dabr-icons.svg#dabr') format('svg');
	font-weight: normal;
	font-style: normal;
}

body{
	margin:0;
	font-family:sans-serif;
	background:#{$c->body_background};
	color:#{$c->body_text};
}

fieldset {
	border-radius:1em;
	max-width:30em;
}
	
	#twitterbird {
		color: #00acee;
	}
	textarea {
		width: 100%;
		border-radius: 0.5em;
		max-width: 39.5em;
	}
	.fileinputs {
		float: right;
		direction: rtl;
		margin-right: 1em;
	}
	#geo {
		float:right;
	}

.profile,.bottom {
	padding: 0.5em;
}

.bottom {
	text-align: center;
}

.actionicons {
	display: block;
	margin: 0.3em; 
	clear: both;
}
.actionicons a{
	font-family:icons,sans-serif;
	font-size: 1.2em;
	text-decoration: none;
}
.actionicons .action-text {
	font-size:0.9em;
}

.icons {
	font-family:icons,sans-serif;
	font-size: 1em;
}

.button {
	text-decoration: none;
	background-color: #EEE;
	color: #333;
	padding: 0.1em 1em;
	border: 0.15em solid black;
}

.action {
	font-family:icons,sans-serif;
	text-decoration: none;
}

form{margin:.3em;}

a{color:#{$c->links}}

small,small a{
	color:#{$c->small}
}
.odd{
	background:#{$c->odd};
}
.even{
	background:#{$c->even};
}
.reply{
	background:#{$c->replyodd};
}
.reply.even{
	background:#{$c->replyeven};
}

.menu{
	color:#{$c->menu_text};
	background:#{$c->menu_background};
	padding: 2px;
	font-family:icons,sans-serif;
	font-size: 1.75em;
}

.menu-text{
	
	background:#{$c->menu_background};
	font-family:sans-serif;
}

.menu a{
	color:#{$c->menu_link};
	text-decoration: none;
}

.menu-text a{
	color:#{$c->menu_link};
	text-decoration: none;
}

.tweet{
	padding: 0.5em;
}

.timeline a img{
	/*padding:2px;*/
}

.avatar{
	height: auto;
	width: auto;
	float: left;
	margin-right: 10px;
}

.shift{
}

.status {
	word-wrap:break-word;
	min-height:50px;
}

.embed{
	left: 0px;
	display: block;
	overflow-x: auto;
	clear: both;
}
.embedded {
	/*max-width:80%;*/
	max-width:30em;
	height:auto;
	margin-top: 0.25em;
}

.date{
	padding:5px;
	font-size:0.8em;
	font-weight:bold;
	color:#{$c->small}
}

.about{
	color:#{$c->small}
}

.time{
	font-size:1em;
	color:#{$c->small}
}

.from{
	font-size:0.75em;
	color:#{$c->small};
	font-family:serif;
}
.from a{
	color:#{$c->small};
}
.table {
	display: table;
	max-width:95%;
}
.table-row {
	display: table-row;
	text-align: right;
	word-wrap:break-word;
}
.table-cell {
	display: table-cell;
}
.table-cell-middle,.table-cell-end {
	display: table-cell;
	padding-left: 1em;
	padding-right:1em;
}
";

header('Content-Type: text/css; charset=utf-8');
echo $css;
}