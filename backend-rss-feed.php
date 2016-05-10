<?php 
/*
Plugin Name: Backend RSS Feed
Description: Plugin that builds an Interface on the Dashboard of WordPress that displays recent posts from 4 different sites and allows for ordering and submitting posts to a table automatically created in the database. 
Version: 1.0
Author: Daniel Martinez
License: GPLv2
*/

ob_start();

function backend_rss_feed_enqueue_scripts() {
	wp_enqueue_script( 'jquery');
	wp_register_script( 'backend_rss_feed_js',plugins_url( '/backend-rss-feed/backend-rss-feed.js') );	
	wp_enqueue_script( 'backend_rss_feed_js',plugins_url( '/backend-rss-feed/backend-rss-feed.js'),array('jquery'));	
	wp_enqueue_style( 'bootstrap', plugins_url( '/backend-rss-feed/bootstrap.min.css') );
}
add_action( 'init', 'backend_rss_feed_enqueue_scripts' );

$sites = ['http://www.wpsquared.com','http://wplift.com','https://www.wpeka.com','https://www.wpmayor.com'];

add_option( 'post_num', '10', '', 'yes' );
add_option( 'sites', $sites, '', 'yes' );
add_option( 'display_site', 'All', '', 'yes' );

global $wpdb;


$table_name = $wpdb->prefix . "approved_posts";

$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  title text NOT NULL,
  link text NOT NULL,
  username text NOT NULL,
  datepub int NOT NULL,
  created_at TIMESTAMP NOT NULL,
  UNIQUE KEY id (id)
) $charset_collate;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

if(isset($_POST['datepub'])){
		require_once( ABSPATH . 'wp-config.php' );

		$title = $_POST['title'];
		$link = $_POST['link'];
		$datepub = $_POST['datepub'];
		$username = $_POST['loggedIn'];
		
		global $wpdb;
		$table_name = $wpdb->prefix . "approved_posts";

		
		$wpdb->insert( 
			$table_name, 
			array( 
				'title' => $title,
				'link' => $link,
				'datepub' => $datepub,
				'username' => $username
				
			) 
		);
		
	}


function backend_rss_feed_add_dashboard_widgets() {

	wp_add_dashboard_widget(
                 'backend_rss_feed_dashboard_widget',         
                 'Backend RSS Feed Dashboard Widget',         
                 'backend_rss_feed_dashboard_widget_display' 
        );	
}
add_action( 'wp_dashboard_setup', 'backend_rss_feed_add_dashboard_widgets' );


function backend_rss_feed_dashboard_widget_display() {

	if(isset($_POST['post_num'])){
		update_option( 'post_num', $_POST['post_num'] );
	}

	if(isset($_POST['display_site'])){
		update_option( 'display_site', $_POST['display_site'] );
	}
	

	$post_num = get_option('post_num');
	$sites = get_option('sites');
	$display_site = get_option('display_site');
	global $current_user;
	$loggedIn =  $current_user->user_login;

	
?>
	<form action="" method="post">
	  Num Posts: <input type="text" name="post_num" value="<?= $post_num ?>"> <input type="submit" value="Submit" class="btn btn-primary">
	</form>
	</br>


	<form action="" method="post">
	  Display Sites:
	  <input type="hidden" value="<?= $display_site ?>" id="selectedElement"> 
	  <select name="display_site" id="selectHide">
	  	<option value="All">All</option>
	  	<?php foreach($sites as $site): ?>
	  	<option value="<?= $site ?>"><?= $site ?></option>
	  <?php endforeach ?>
	  </select>

	  <input type="submit" value="Submit" class="btn btn-primary">
	</form>
	</br></br>

<?php
	include_once(ABSPATH.WPINC.'/rss.php');
	global $wpdb;
	$table_name = $wpdb->prefix . "approved_posts";

	$datepubs = $wpdb->get_results("
		SELECT datepub
		FROM $table_name
		"
	);
	

	if($display_site=="All"){

		$posts = [];
		$flag=0;

		
		foreach($sites as $site){


			$feed = fetch_rss($site.'/feed/');

			
			$items = array_slice($feed->items, 0, $post_num);

			if (!empty($items)){
				foreach ($items as $item){
					foreach($datepubs as $datepub){

						if($datepub->datepub==strtotime($item['pubdate'])){
							$flag=1;
						}
					}
					if($flag==0){
						$time = strtotime($item['pubdate']);
			 			$posts[$time] = $item;	
					}
					$flag=0;
			 		
			 	} 
			}
			
			 	
		}
		
		krsort($posts);

		$displayPosts = array_slice($posts, 0, $post_num);

		if (!empty($displayPosts)){
			 foreach ($displayPosts as $item){
			 	
			 ?>
			 <form action="" method="post">
				<input type="hidden" value="<?=  $item['title']; ?>" name="title">			
				<input type="hidden" value="<?=  $item['link']; ?>" name="link">
				<input type="hidden" value="<?=  $loggedIn; ?>" name="loggedIn">
				<input type="hidden" value="<?=  strtotime($item['pubdate']); ?>" name="datepub">
				<input type="submit" value="Approve" class="btn btn-success">
			</form>
			 <?php
		
			 echo $item['pubdate'];
			 ?>

				<h2><a href="<?php echo $item['link']; ?>"><?php echo $item['title']; ?></a></h2>
				<p><?php echo $item['description']; ?></p>
				<?php 
			}
		}
		
		

	}else{
		$feed = fetch_rss($display_site.'/feed/');
		$flag=0;

		foreach($feed->items as $item){

			foreach($datepubs as $datepub){
				

				if($datepub->datepub==strtotime($item['pubdate'])){
					$flag=1;
				}
			}

			if($flag==0){
				$time = strtotime($item['pubdate']);
	 			$posts[$time] = $item;	
			}
			$flag=0;

		}

		$posts = array_slice($posts, 0, $post_num);

		?>

		<?php if (!empty($posts)) : ?>
		
	  
	
		<?php foreach ($posts as $item) : ?>

		<form action="" method="post">
			<input type="hidden" value="<?=  $item['title']; ?>" name="title">			
			<input type="hidden" value="<?=  $item['link']; ?>" name="link">
			<input type="hidden" value="<?=  $loggedIn; ?>" name="loggedIn">
			<input type="hidden" value="<?=  strtotime($item['pubdate']); ?>" name="datepub">
			<input type="submit" value="Approve" class="btn btn-success">
		</form>
		
		<?php echo $item['pubdate'];  ?>


		<h2><a href="<?php echo $item['link']; ?>"><?php echo $item['title']; ?></a></h2>
		<p><?php echo $item['description']; ?></p>


		<?php 

			  endforeach; 
		 	  endif; 
		 	  

	}
	
	
}
?>