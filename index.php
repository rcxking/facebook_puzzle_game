<?php

// Provides access to app specific values such as your app id and app secret.
// Defined in 'AppInfo.php'
require_once('AppInfo.php');

// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}

// This provides access to helper functions defined in 'utils.php'
require_once('utils.php');
require_once('libs/db_manager.php');
//require_once('highscore.php');

//Function to generate a random picture
function randomPicture($photos)
{
  //Generate a random number from 0 .... number of photos
  $random = rand(0, count($photos));
  
  //Get the random picture
  return $photos[$random];
}

//Function to format milliseconds
function format_milliseconds($ms)
{
	$minutes = floor($ms / 60000);
	$seconds = floor(($ms % 60000) / 1000);
	$milliseconds = str_pad(floor($ms % 1000), 3, '0', STR_PAD_LEFT);
	return $minutes . ':' . $seconds . '.' . $milliseconds;
}

require_once('sdk/src/facebook.php');

//Create a new facebook object
$facebook = new Facebook(array(
  'appId'  => AppInfo::appID(),
  'secret' => AppInfo::appSecret(),
));

//Get the User ID (0 if not logged in)
$user_id = $facebook->getUser();
if ($user_id) {
  try {
    // Fetch the viewer's basic information
    $basic = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    // If the call fails we check if we still have a user. The user will be
    // cleared if the error is because of an invalid accesstoken
    if (!$facebook->getUser()) {
      header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']));
      exit();
    }
  }

  // And this returns 16 of your photos.
  $photos = idx($facebook->api('/me/photos?limit=16'), 'data', array());

  $image = randomPicture($photos);
  $src = $image['source'];

}

// Fetch the basic info of the app that they are using
$app_info = $facebook->api('/'. AppInfo::appID());

$app_name = idx($app_info, 'name', '');

?>
<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />

    <title><?php echo he($app_name); ?></title>
	<link href = 'https://fonts.googleapis.com/css?family=Slackey' rel = 'stylesheet' type = 'text/css'>
    <link rel="stylesheet" href="stylesheets/screen.css" media="Screen" type="text/css" />
    <link rel="stylesheet" href="stylesheets/mobile.css"
      media="handheld, only screen and (max-width: 480px), only screen and (max-device-width: 480px)" 
      type="text/css" />

    <!--[if IEMobile]>
    <link rel="stylesheet" href="mobile.css" media="screen" type="text/css"  />
    <![endif]-->

    <meta property="og:title" content="<?php echo he($app_name); ?>" />
    <meta property="og:type" content="game" />
    <meta property="og:url" content="<?php echo AppInfo::getUrl(); ?>" />
    <meta property="og:image" content="<?php echo AppInfo::getUrl('/logo.png'); ?>" />
    <meta property="og:site_name" content="<?php echo he($app_name); ?>" />
    <meta property="og:description" content="Facebook Image Puzzle Game" />
    <meta property="fb:app_id" content="<?php echo AppInfo::appID(); ?>" />

    <script type="text/javascript" src="/javascript/jquery-1.7.1.min.js"></script>


<script type="text/javascript">
function logResponse(response) {
  if (console && console.log) {
    console.log('The response was', response);
  }
}

$(function(){
  // Set up so we handle click on the buttons
  $('#postToWall').click(function() {
    FB.ui({
      method : 'feed',
        link   : $(this).attr('data-url')
    },
    function (response) {
      // If response is null the user canceled the dialog
      if (response != null) {
        logResponse(response);
      }
    });
  });

  $('#sendToFriends').click(function() {
    FB.ui({
      method : 'send',
        link   : $(this).attr('data-url')
    },
    function (response) {
      // If response is null the user canceled the dialog
      if (response != null) {
        logResponse(response);
      }
    }
    );
  });

  $('#sendRequest').click(function() {
    FB.ui(
      {
        method  : 'apprequests',
          message : $(this).attr('data-message')
    },
    function (response) {
      // If response is null the user canceled the dialog
      if (response != null) {
        logResponse(response);
      }
    }
    );
  });
});


</script>

    <!--[if IE]>
<script type="text/javascript">
var tags = ['header', 'section'];
while(tags.length)
  document.createElement(tags.pop());
</script>
    <![endif]-->
  </head>
  <body>
    <div id="fb-root"></div>
<script type="text/javascript">
window.fbAsyncInit = function() {
  FB.init({
    appId      : '<?php echo AppInfo::appID(); ?>', // App ID
      channelUrl : '//<?php echo $_SERVER["HTTP_HOST"]; ?>/channel.html', // Channel File
      status     : true, // check login status
      cookie     : true, // enable cookies to allow the server to access the session
      xfbml      : true // parse XFBML
        });

        // Listen to the auth.login which will be called when the user logs in
        // using the Login button
        FB.Event.subscribe('auth.login', function(response) {
          // We want to reload the page now so PHP can read the cookie that the
          // Javascript SDK sat. But we don't want to use
          // window.location.reload() because if this is in a canvas there was a
          // post made to this page and a reload will trigger a message to the
          // user asking if they want to send data again.
          window.location = window.location;
        });

        FB.Canvas.setAutoGrow();
      };

      // Load the SDK Asynchronously
      (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));
    </script>

    <header class="clearfix">
      <?php if (isset($basic)) { ?>
      <p id="picture" style="background-image: url(https://graph.facebook.com/<?php echo he($user_id); ?>/picture?type=normal)"></p>

      <div>
        <h1>Welcome, <strong><?php echo he(idx($basic, 'name')); ?></strong></h1>
        <p class="tagline">
          This is your app
          <a href="<?php echo he(idx($app_info, 'link'));?>" target="_top"><?php echo he($app_name); ?></a>
        </p>

        <div id="share-app">
          <p>Share your app:</p>
          <ul>
            <li>
              <a href="#" class="facebook-button" id="postToWall" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="plus">Post to Wall</span>
              </a>
            </li>
			<br/>
			
            <li>
              <a href="#" class="facebook-button speech-bubble" id="sendToFriends" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="speech-bubble">Send Message</span>
              </a>
            </li>
			<br/>
            <li>
              <a href="#" class="facebook-button apprequests" id="sendRequest" data-message="Test this awesome app">
                <span class="apprequests">Send Requests</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
	  
      <?php
} else {
      ?>
        <div>
          <h1>Welcome</h1>
          <div class="fb-login-button" data-scope="user_likes,user_photos,publish_actions"></div>
        </div>
      <?php
}
      ?>
    </header>

    <?php
if ($user_id) {
    ?>

	<p class = "musictitle"><b>Music Controls</b></p>
	<div class = "music">
		<!-- Music Commands to Pause and Play Music -->
		<div id = "pause">Click to Pause Music</div>
		<div id = "play">Click to Play Music</div>
	</div>

    <section id="main" class="clearfix">
			<div id = "splash">
				<h1>Image Puzzle</h1>
				<ul id = "level">
					<li data-level = "3">Easy</li>
					<li data-level = "4">Medium</li>
					<li data-level = "5">Hard</li>
				</ul>
			</div>
	
				<canvas id="game" width="600" height="400" style = "display:none;"></canvas>
    </section>

    <?php
}
    ?>

  <script src="javascript/puzzle.js"></script>
  <script src = "https://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
  
  <script>
    var imagePath = '<?php echo $src; ?>';
	
	$('#level li').click(function() {
		'use strict';
		$('#splash').hide();
		
			var level = $(this).attr('data-level');
	
			init('game', imagePath, level);
			
			score.uid = <?php echo $user_id; ?>;
	
			playMusic('sounds', 'slingersong');
			document.getElementById('pause').onclick = (function(event) {
				music.pause();
			});
  
			document.getElementById('play').onclick = (function(event) {
				music.play();
			});
			
			$('#game').show();
			startTimer();
	});
  </script>
  </body>
</html>