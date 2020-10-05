<?php
if( !is_user_logged_in())  {
	wp_redirect( home_url('/') );
}
require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );
// pushing the post value to database to create the recipe
//Check nounce to avoid CSRF
$success='';
if(isset($_POST)&& isset($_POST['_wpnonce']))
{
	//echo '<pre>';print_r($_POST);exit;
	$error = 0;
	$email_required = 0;
	$email_valid = 0;
	$dish_name = 0;
	$image_required = 0;
	$terms_required = 0;	
	
	
	
	if(isset($_POST['preview'])){
		if( isset($_POST['email']) && empty(trim($_POST['email'])) ){
			$email_required = 1;
			$error = 1;
		}
		else if( !empty(trim($_POST['email'])) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ){
			$email_valid = 1;
			$error = 1;
		}
		if( isset($_POST['dish']) && empty(trim($_POST['dish'])) ){
			$dish_name = 1;
			$error = 1;
		}
		if( isset($_POST['cropped_img']) && empty(trim($_POST['cropped_img'])) && empty(trim($_POST['imgrer'])) ){
			$image_required = 1;
			$error = 1;
		}
		if($_POST['post_id']=='')
		{
			if( !isset($_POST['terms']) ){
				$terms_required = 1;
				$error = 1;
			}
		}
		if( ( isset($_POST['lny_brands']) && isset($_POST['lny_products']) ) && ( in_array("", $_POST['lny_brands']) || in_array("", $_POST['lny_products']) ) ){
			$brand_error = 1;
			$error = 1;
		}
		if( isset($_POST['cny_cusine'])  && empty($_POST['cny_cusine']) ){
			$cuisine_error = 1;
			$error = 1;
		}
	}
	
	$nonce = $_REQUEST['_wpnonce']; 
	global $current_user;
   $current_user = wp_get_current_user();
	if ( ! wp_verify_nonce( $nonce, 'submit_entry' ) ) {
	  exit; // Get out of here, the nonce is rotten!
	}
	else if( $error == 0 )
	{
		$success='success';
		if( trim($_POST['post_id']) == '')
		{
			$success='success';
			$create_recipe=new ManageRecipe;
			$generate_recipe=$create_recipe->CreateRecipe($_POST,$_FILES,$current_user->ID);
			//mail function
			if($_POST['cropped_img']!='')
			{
				$url = get_home_url()."/upload_images/".$_POST['cropped_img'];
				//echo $url;
				$tmp = download_url( $url )	;
				//echo $tmp;
				
				if( is_wp_error( $tmp ) ){
    // download failed, handle error
				}
				$desc = "The WordPress Logo";
				$file_array = array();
				
				// Set variables for storage
				// fix file filename for query strings
				preg_match('/[^\?]+\.(txt|doc|xls|pdf|ppt|pps|odt|ods|odp|jpg|jpe|jpeg|gif|png)/i', $url, $matches);
				$file_array['name'] = basename($matches[0]);
				$file_array['tmp_name'] = $tmp;
				//print_r($file_array);
				
				// If error storing temporarily, unlink
				if ( is_wp_error( $tmp ) ) {
					@unlink($file_array['tmp_name']);
					$file_array['tmp_name'] = '';
				}
				
				// do the validation and storage stuff
				$id = media_handle_sideload( $file_array, $generate_recipe, $desc );
				
				
				// If error storing permanently, unlink
				//if ( is_wp_error($id) ) {
				//    @unlink($file_array['tmp_name']);
				 //   return $id;
				//}
				
				
				 $uploadId=update_post_meta($generate_recipe, '_thumbnail_id', $id);
				
				
			}
			
	
	
}

?>
<?php /* Template Name: Submit Entry Page */   ?>
<?php get_header(); ?>

<!-- Top Nav menu ends-->

<!-- Submit form body starts-->
<?php //get_template_part('submit_entry-templates/template-part', 'submitform'); ?>
<!--  Submit form body ends-->


<?php
global $current_user;
$pid='';
get_currentuserinfo();
$author_query = array(
	'posts_per_page' => 1,
	'author' => $current_user->ID,
	'post_type'=>'recipe',
	'post_status' =>  'draft',
);
$author_posts = new WP_Query($author_query);
while($author_posts->have_posts()) : $author_posts->the_post();
	$pid = get_the_ID();
endwhile;wp_reset_query();

?>
<section class="submit-recipe-section">

    <section class="submit-recipe-wrap">
        <div class="container">
            <div class="row">

                <div class="col-md-6">
                </div>
                <div class="col-md-6 no-padding">
                    <?php if(isset($_POST) && isset($_POST['_wpnonce']) && $error == 0) { ?>
                    <div class="alert alert-success text-center"><strong>Success!</strong> Your recipe has been saved as
                        draft. You can preview your recipe and Submit Now.</div>
                    <?php } ?>
                    <div class="col-md-7 no-padding mob100">
                        <div class="submit_entry mob-center">Submit your entry</div>
                    </div>
                    <div class="col-md-5 no-padding mob100">
                        <div class="c_user_name mob-center">Hello, <?php echo $current_user->display_name ?></div>
                    </div>
                    <div class="clearfix"></div>
                    <form method="post" enctype="multipart/form-data" class="cus-form cus-form-recipe"
                        novalidate="novalidate">
                        <div class="submit_form">
                            <div class="form-group">
                                <input type="email" class="form-control" required="required" name="email"
                                    aria-describedby="emailHelp" placeholder="Your Mail ID"
                                    value="<?php if( $pid ) echo get_post_meta($pid, 'email_id', true); else if($error == 1) echo $_POST['email']; else echo $current_user->user_email; ?>">
                                <span2 class="tooltip1" data-toggle="tooltip"
                                    title="Enter a valid email address for any communications"><i class="fa fa-question"
                                        aria-hidden="true"></i></span2>
                            </div>
                            <?php if($error && $email_required){
									echo '<div class="red recipe_error">Email id is required.</div>';	
								} else if( $error && $email_valid){
									echo '<div class="red recipe_error">Email id is not valid.</div>';	
								} ?>


                            <div class="form-group">
                                <input maxlength="60" id="dish12" data-uid="<?php echo $current_user->ID; ?>"
                                    type="text" class="form-control" required="required" name="dish" autocomplete="off"
                                    placeholder="Name of the Dish"
                                    value="<?php if(!empty($pid)){  echo get_the_title($pid); } else if($error == 1) { echo $_POST['dish'];} ?>">
                                <span2 class="tooltip1" data-toggle="tooltip"
                                    title="Give a short, creative name for your Asian recipe"><i class="fa fa-question"
                                        aria-hidden="true"></i></span2>
                            </div>
                            <?php if($error && $dish_name){
									echo '<div class="red recipe_error">Name of the dish is required.</div>';	
								} ?>


                            <div class="form-group file" id="crop-avatar">
                                <div class="avatar-view">
                                    <input type="hidden" name="cropped_img" id="cropped_img" />
                                    <?php 
                                    if(!empty($pid))
                                    {
                                        $attachment_id = get_post_thumbnail_id($pid);
                                        $size = "galley-img"; // (thumbnail, people-img, large, full or custom size)
                                        $image = wp_get_attachment_image_src( $attachment_id, $size );
										
										if(empty($image)){?>
                                    <img src="<?php bloginfo('template_directory') ?>/images/upload.png" alt=""
                                        id="image-upload" class="img-responsive upload_icon cursor-pointer"
                                        title="Upload an image of your dish. Preferably high resolution in a landscape mode to garner more votes!">
                                    <?php }else{?>
                                    <img src="<?php echo $image[0]; ?>" alt="" id="image-upload"
                                        class="img-responsive cursor-pointer"
                                        title="Upload an image of your dish. Preferably high resolution in a landscape mode to garner more votes!">
                                    <?php } 
									} ?>
                                    <?php if(empty($pid))
                                    {
                                    ?>
                                    <img src="<?php bloginfo('template_directory') ?>/images/upload.png" alt=""
                                        id="image-upload" class="img-responsive upload_icon cursor-pointer"
                                        title="Upload an image of your dish. Preferably high resolution in a landscape mode to garner more votes!">
                                    <?php } ?>

                                    <!--	 <input type="file" class="form-control-file" <?php if(empty($pid)) { ?>required="required" <?php } ?> name="photo" id="photo" aria-describedby="fileHelp" onchange="readURL(this);" accept="image/*">-->
                                </div>
                                <span2 class="tooltip1" data-toggle="tooltip"
                                    title="Recommended Image specs: Landscape Image, Preferred Dimension: 720x320, Image Ratio: 16:9">
                                    <i class="fa fa-question" aria-hidden="true"></i></span2>
                            </div>
                            <?php if($error && $image_required){
									echo '<div class="red recipe_error">Image is required.</div>';	
								} ?>

                        </div>
                        <div class="height20"></div>
                        <div class="buttons text-right">
                            <button title="Save as Draft" type="submit" name="draft" class="shadow_button blue_shadow"
                                id="save_as_draft">Save as Draft</button>
                        </div>
                        <?php wp_nonce_field( 'submit_entry' ); ?>
                        <input type="hidden" name="action_type" id="action_type" value="save_as_draft" />
                        <input type="hidden" name="post_id" id="post_id" value="<?php echo $pid; ?>" />
                    </form>
                </div>
                <div class="height20 clearfix"></div>
                <div class="height20 clearfix"></div>
                <div class="clearfix"></div>

            </div>
        </div>
    </section>
</section>

</script>
</body>

</html>