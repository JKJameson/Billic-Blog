<?php
class Blog {
	// After you upload this module you will need to go to Settings -> Modules and regenerate the module cache
	public $settings = array(
		'description' => 'Display news and announcements to your users.',
		//'user_menu_name' => 'Blog',
		//'user_menu_icon' => '<i class="icon-chat-bubble-two"></i>',
		'admin_menu_category' => 'Support',
		'admin_menu_name' => 'Blog Manager',
		'admin_menu_icon' => '<i class="icon-chat-bubble-two"></i>',
		'allowed_tags' => '<p><a><strong><u><blockquote><ul><ol><li><h2><h3><s><em><img>',
		'permissions' => array('Blog_Moderator'),
	);
	
	function user_area() {
		global $billic, $db;
		
		// This is the page at /User/Blog/
		
		// Call the user_view_article function...
		if (isset($_GET['Post'])) {
			if (isset($_GET['Action']) && $_GET['Action']=='Edit' && $billic->user_has_permission($user, 'admin')) {
				$this->edit_article();
				return;
			}
			$this->view_article();
			return;
		}
		
		// Show a list
		$this->list_articles();
	}
	
	// Edit Article
	// This is called from user_area... admin check already done
	function edit_articles() {
		global $billic, $db;
		
	}
	
	public $authors = [];
	
	public function api_list_posts($limit = false) {
		global $billic, $db;
		$now = new DateTime();
		$posts = $db->q('SELECT * FROM `blog_posts` WHERE `published` IS NOT NULL ORDER BY (case when window_end > NOW() then 1 else 2 end), `published` DESC '.($limit!==false?' LIMIT '.$limit:''));
		foreach($posts as $k => $post) {
			$author = $this->get_author($post['author_id']);
			$post['author'] = $author['firstname'].' '.$author['lastname'];
			
			$post['inprogress'] = false;
			if ($post['window_start']!=NULL) {
				$window_start = new DateTime($post['window_start']);
				$post['window_start_datetime'] = $window_start;
				$window_end = new DateTime($post['window_end']);
				$post['window_end_datetime'] = $window_end;
				if ($window_start<$now && $window_end>$now)
					$post['inprogress'] = true;
			}
			
			$posts[$k] = $post;
		}
		return $posts;
	}
	
	public function get_author($author_id) {
		global $billic, $db;
		if (!array_key_exists($author_id, $this->authors)) {
			$author = $db->q('SELECT `firstname`, `lastname`, `email` FROM `users` WHERE `id` = ?', $author_id);
			$author = $author[0];
			$this->authors[$author_id] = $author;
		} else {
			$author = $this->authors[$author_id];	
		}
		return $author;
	}
					  
	// List Articles
	function list_articles() {
		global $billic, $db;
		
		/*
			START CONTAINER
		*/
		
		echo '<div class="row">';
		
		$now = new DateTime();

		$posts = $this->api_list_posts();
		foreach($posts as $post) {
			$post['inprogress'] = false;
			if ($post['window_start']!=NULL) {
				$window_start = new DateTime($post['window_start']);
				$post['window_start_datetime'] = $window_start;
				$window_end = new DateTime($post['window_end']);
				$post['window_end_datetime'] = $window_end;
				if ($window_start<$now && $window_end>$now)
					$post['inprogress'] = true;
			}
			
			echo '<!-- Blog Post -->
			<div class="col-xs-12">
						<h1><a href="/User/Blog/Post/'.safe($post['id_text']).'/">'.safe($post['title']).'</a></h1>
						<p>Published on <i class="icon icon-calendar"></i> '.safe($post['published']).' by <i class="icon icon-user"></i> '.safe($post['author']).'.';
			if (isset($post['window_start_datetime'])) {
				$diff = $post['window_start_datetime']->diff($post['window_end_datetime']);
				echo '<br>';
				if ($post['inprogress'] || $now > $post['window_end_datetime'])
					echo 'Started';
				else
					echo 'Starts';
				echo ' at '.safe($post['window_start']).' for '.$diff->format('%h').' hours.';
			}
			echo '</p>

						

						<p>'.nl2br(safe(str_replace("\r", '', $post['content']))).'</p>
						<hr><br><br></div>';
		}
		
		echo '</div>';
		
		/*
			END CONTAINER
		*/
    }

	// View Article
	function view_article() {
		global $billic, $db;
		$post = $db->q('SELECT * FROM `blog_posts` WHERE `id` = unhex(replace(?,\'-\',\'\'))', $_GET['Post']);
		$post = $post[0];
		if (empty($post)) {
			err('This article does not exist');
		}
		
		$author = $this->get_author($post['author_id']);
		
		$now = new DateTime();
		
		$post['inprogress'] = false;
		if ($post['window_start']!=NULL) {
			$window_start = new DateTime($post['window_start']);
			$post['window_start_datetime'] = $window_start;
			$window_end = new DateTime($post['window_end']);
			$post['window_end_datetime'] = $window_end;
			if ($window_start<$now && $window_end>$now)
				$post['inprogress'] = true;
		}
    
        echo '<div class="row">

            <!-- Blog Post Content Column -->
            <div class="col-lg-12">

                <h1>'.safe($post['title']).'</h1>
                <p>Published on <i class="icon icon-calendar"></i> '.safe($post['published']).' by <i class="icon icon-user"></i> '.safe($author['firstname'].' '.$author['lastname']).'';
		
		if (isset($post['window_start_datetime'])) {
			$diff = $post['window_start_datetime']->diff($post['window_end_datetime']);
			echo '<br>';
			if ($post['inprogress'] || $now > $post['window_end_datetime'])
				echo 'Started';
			else
				echo 'Starts';
			echo ' at '.safe($post['window_start']).' for '.$diff->format('%h').' hours.';
		}
		echo '</p>
				<hr>

                <!-- Post Content -->
                <p>'.nl2br(safe(str_replace("\r", '', $post['content']))).'</p>
				
                <hr>
				
				<!-- Tags -->
				';
				if (!empty($post['tags'])) {
				echo '<p><i class="icon icon-tags"></i> Tags: ';
				$tags = explode(',', $post['tags']);
				foreach($tags as $tag) {
					echo "<span class=\"label label-default\">$tag</span>";
				}
				echo '</p>';
				}

		/*
		echo '
                <hr>

                <!-- Blog Comments -->

                <!-- Comments Form -->
                <div class="well">
                    <h4><i class="fa fa-comment"></i> Leave a Comment:</h4>
                    <form role="form">
                        <div class="form-group">
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>

                <hr>

                <!-- Posted Comments -->

                <!-- Comment -->
                <div class="media">
                    <a class="pull-left" href="#">
                        <img class="media-object" src="http://placehold.it/64x64" alt="">
                    </a>
                    <div class="media-body">
                        <h4 class="media-heading">Start Bootstrap
                            <small><i class="fa fa-calendar"></i> August 25, 2014 at <i class="fa fa-clock-o"></i> 9:30 PM</small>
                        </h4>
                        Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
                    </div>
                </div>

                <!-- Comment -->
                <div class="media">
                    <a class="pull-left" href="#">
                        <img class="media-object" src="http://placehold.it/64x64" alt="">
                    </a>
                    <div class="media-body">
                        <h4 class="media-heading">Start Bootstrap
                            <small><i class="fa fa-calendar"></i> August 25, 2014 at <i class="fa fa-clock-o"></i> 9:30 PM</small>
                        </h4>
                        Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
                        <!-- Nested Comment -->
                        <div class="media">
                            <a class="pull-left" href="#">
                                <img class="media-object" src="http://placehold.it/64x64" alt="">
                            </a>
                            <div class="media-body">
                                <h4 class="media-heading">Nested Start Bootstrap
                                    <small><i class="fa fa-calendar"></i> August 25, 2014 at <i class="fa fa-clock-o"></i> 9:30 PM</small>
                                </h4>
                                Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
                            </div>
                        </div>
                        <!-- End Nested Comment -->
                    </div>
                </div>

            </div>
			*/
echo '

        </div>
        <!-- /.row -->';
    }

    // This section will be accessible at http://yourdomain.com/Admin/MyFirstModule
    function admin_area() {
        global $billic, $db;
		
		if (isset($_POST['NewPost'])) {
			$db->q('SET @uuid = UUID()');
			$uuid = $db->q('SELECT @uuid');
			$uuid = $uuid[0]['@uuid'];
			$db->insert('blog_posts', [
				'id' => hex2bin(str_replace('-', '', $uuid)),
				'author_id' => $billic->user['id'],
			]);
			$billic->redirect('/Admin/Blog/EditPost/'.urlencode($uuid).'/');
		}
		
		if (isset($_GET['EditPost'])) {
			if (isset($_POST['update'])) {
				$db->q('UPDATE `blog_posts` SET `title` = ?, `window_start` = ?, `window_end` = ?, `content` = ? WHERE `id` = unhex(replace(?,\'-\',\'\'))', $_POST['title'], $_POST['window_start'], $_POST['window_end'], $_POST['content'], urldecode($_GET['EditPost']));
				if (empty($_POST['window_start']))
					$db->q('UPDATE `blog_posts` SET `window_start` = null WHERE `id` = unhex(replace(?,\'-\',\'\'))', urldecode($_GET['EditPost']));
				if (empty($_POST['window_end']))
					$db->q('UPDATE `blog_posts` SET `window_end` = null WHERE `id` = unhex(replace(?,\'-\',\'\'))', urldecode($_GET['EditPost']));
				if ($_POST['uri']!=$_GET['URI']) {
					// URI was changed
					$billic->redirect('/Admin/Pages/URI/'.urlencode($_POST['uri']).'/');
				}
				$billic->status = 'updated';
			}

			$post = $db->q('SELECT * FROM `blog_posts` WHERE `id` = unhex(replace(?,\'-\',\'\'))', urldecode($_GET['EditPost']));
			$post = $post[0];
			if (empty($post)) {
				err('Post does not exist');
			}
			/*if (!empty($billic->errors)) {
				$page['uri'] = $_POST['uri'];
				$page['menu_show'] = $_POST['menu_show'];
				$page['menu_name'] = $_POST['menu_name'];
				$page['menu_icon'] = $_POST['menu_icon'];
				$page['pagecontent'] = $_POST['pagecontent'];
			}*/

			$billic->set_title('Admin/Blog Post '.safe($_GET['EditPost']));
			$billic->show_errors();

			echo '<form method="POST"><table class="table table-striped">';
			echo '<tr><td width="125">Title</td><td><input type="text" class="form-control" name="title" value="'.safe($post['title']).'"></td></tr>';
			echo '<tr><th colspan="2">Time Window</th></td></tr>';
			echo '<tr><td width="125">Start</td><td><input type="text" class="form-control" name="window_start" value="'.safe($post['window_start']).'"></td></tr>';
			echo '<tr><td width="125">End</td><td><input type="text" class="form-control" name="window_end" value="'.safe($post['window_end']).'"></td></tr>';
			echo '<tr><td colspan="2"><textarea id="postcontent" style="width: 100%; height:500px" name="content">'.safe($post['content']).'</textarea></td></tr>';
			echo '<tr><td colspan="4" align="center"><input type="submit" class="btn btn-success" name="update" value="Update &raquo;"></td></tr>';
			echo '</table></form>';
			echo '
<link rel="stylesheet" href="/Modules/Core/codemirror/codemirror.css">
<script src="/Modules/Core/codemirror/codemirror.js"></script>
<script src="/Modules/Core/codemirror/matchbrackets.js"></script>
<script src="/Modules/Core/codemirror/htmlmixed.js"></script>
<script src="/Modules/Core/codemirror/xml.js"></script>
<script src="/Modules/Core/codemirror/javascript.js"></script>
<script src="/Modules/Core/codemirror/css.js"></script>
<script src="/Modules/Core/codemirror/clike.js"></script>
<script src="/Modules/Core/codemirror/php.js"></script>
			
<script>
var pageChanged = false;
var editor = CodeMirror.fromTextArea(document.getElementById("postcontent"), {
	lineNumbers: true,
	lineWrapping: true,
	mode: "application/x-httpd-php",
	matchBrackets: true,
	indentUnit: 4,
	indentWithTabs: true
});
</script>';
			return;
		}
		
		if (isset($_POST['Publish'])) {
			$db->q('UPDATE `blog_posts` SET `published` = NOW() WHERE `id` = unhex(replace(?,\'-\',\'\'))', urldecode($_POST['postid']));
		}
		if (isset($_POST['Unpublish'])) {
			$db->q('UPDATE `blog_posts` SET `published` = null WHERE `id` = unhex(replace(?,\'-\',\'\'))', urldecode($_POST['postid']));
		}
		if (isset($_POST['DeletePost'])) {
			$db->q('DELETE FROM `blog_posts` WHERE `id` = unhex(replace(?,\'-\',\'\'))', urldecode($_POST['postid']));
		}

        echo '<form method="POST"><input type="submit" name="NewPost" value="Create a new Post" class="btn btn-success"></form>';
		
		$posts = $db->q('SELECT `id_text`, `title`, `published` FROM `blog_posts` ORDER BY (case when window_end > NOW() then 1 else 2 end), `published` DESC');
		echo '<table class="table table-striped"><tr><th>Title</th><th>Published</th><th>Actions</th></tr>';
		if (empty($posts)) {
			echo '<tr><td colspan="20">No Posts matching filter.</td></tr>';
		}
		foreach($posts as $post) {
			echo '<tr><td>'.safe($post['title']).'</td><td>'.safe($post['published']).'</td><td>';
			echo '<form method="POST"><input type="hidden" name="postid" value="'.safe($post['id_text']).'"><a href="/Admin/Blog/EditPost/'.urlencode($post['id_text']).'/" class="btn btn-primary btn-xs"><i class="icon-edit-write"></i> Edit</a> ';
			if ($post['published']===null)
				echo '<button type="submit" name="Publish" class="btn btn-success btn-xs"><i class="icon-like"></i> Publish</a></button> ';
			else
				echo '<button type="submit" name="Unpublish" class="btn btn-danger btn-xs"><i class="icon-dislike"></i> Unpublish</a></button> ';
			echo '<button type="submit" name="DeletePost" class="btn btn-danger btn-xs" onClick="return confirm(\'Are you sure you want to delete?\');"><i class="icon-remove"></i> Delete</a></button>';
			echo '</form></td></tr>';
		}
		echo '</table>';
    }
}
?>
