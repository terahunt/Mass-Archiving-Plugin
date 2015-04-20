<?php
 
/* 
Plugin Name: Mass Archiving PDF 
Description: This creates a widget that allows the capability to download all posts into a PDF either in bulk or by tag. 
Version: 1.0 
Author: Tera Hunt
*/

/*  2015  Tera Hunt (email : hunttm@miamioh.edu)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*Creating a widget for PDF link */

if (!class_exists ('Pdf_Link_Widget'))
{
	class Pdf_Link_Widget extends WP_Widget {
		
		/**
		 * Register widget.
		 */
		function Pdf_Link_Widget() 
		{
			parent::__construct(
				'pdf_Link_Widget',
				__( 'PDF Post Download', 'text_domain' ),
				array( 'description' => __( 'Widget for downloading all posts into a PDF in bulk or by tag.', 
				'text_domain' ), )
			);
		}

		/**
		 * Front-end display of widget.
		 *
		 * @param array $args   
		 * @param array $instance 
		 */
		public function widget( $args, $instance ) 
		{
		 
			$tags = get_tags( $args );
			echo $args['before_widget'];
			
			if ( ! empty( $instance['title'] ) ) 
			{
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
			}
			
			//Get links based on tags
			$links = call_user_func('marc_create_link');
			
			//Get link for all posts
			array_unshift($links,call_user_func('marc_create_link_all'));
			
			foreach($links as $l)
			{
				echo $l;
			} 
			echo $args['after_widget'];
		}

		/**
		 * Back-end widget form.
		 *
		 * @param array $instance
		 */
		public function form($instance ) 
		{
			$title = isset( $instance['title'] ) ? esc_attr($instance['title']) : '';?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'wp_widget_plugin'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" 
				type="text" value="<?php echo $title; ?>" />
			</p>
			<?php
		}

		/**
		 * Sanitize widget form values as they are saved.
		 *
		 * @param array $new_instance
		 * @param array $old_instance
		 *
		 * @return array $instance
		 */
		public function update( $new_instance, $old_instance ) 
		{
			$instance = array();
			$tags = get_tags();
			$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';			
			
			return $instance;
		}	
	}
}
	
	/**
	* Register widget.
	*/
	if ( ! function_exists( 'marc_register_pdf_widget' ) )
	{
		function marc_register_pdf_widget() 
		{
			register_widget( 'Pdf_Link_Widget' );
		}
		add_action( 'widgets_init', 'marc_register_pdf_widget' );
	}

	/**
	* Retrieves and filters posts.
	*
	* @param string $tag
	* @return array $custom_posts
	*/
	if ( ! function_exists( 'marc_retrieve_posts' ) )
	{	
		function marc_retrieve_posts($tag) 
		{ 
			$custom_posts; 
			if($tag != "")
			{
				$args = array('orderby'=> 'title', 'order' => 'ASC', 'tag'=>$tag);		
				$custom_posts = get_posts($args);
			}
			else
			{
				$args = array('orderby'=> 'title', 'order' => 'ASC');		
				$custom_posts = get_posts($args);
			}
			return $custom_posts;  	
		}
	}

	/**
	* Builds html page for PDF output. 
	*
	* @param string $tag
	* @return string $html
	*/
	if ( ! function_exists( 'marc_build_html' ) )
	{	
		function marc_build_html($tag)
		{
			date_default_timezone_set("America/New_York");
			$current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$current_time = date("Y/m/d") . ' ' . date("h:i:sa");
			
			$html = '<p>Retrieved: ' . $current_time .
			'</p>  <p>URL: ' . $current_url . '</p>';	
			
			$posts = marc_retrieve_posts($tag);
		
			foreach($posts as $p)
			{
				$html .= '<h1>' . $p->post_title . '</h1>'
				. $p->post_content; 
			}
			return $html; 
		}
	}
	
	/**
	* Formats and adds queries.  
	*
	* @param array $vars
	* @return array $vars
	*/
	if ( ! function_exists( 'marc_add_query_vars_filter' ) )
	{
		function marc_add_query_vars_filter( $vars )
		{ 
			$tags = get_tags();
			
			//Add query for each tag
			foreach($tags as $t) 
			{
				$formattedTag = str_replace(' ', '', $t->name);
				array_push($vars, $formattedTag); 
			}
			//Add query for all posts
			array_push($vars, "All");
			return $vars;
		}
		add_filter( 'query_vars', 'marc_add_query_vars_filter' );
	}
	
	/**
	* Outputs PDF using mPDF.  
	*/
	if ( ! function_exists( 'marc_output_pdf' ) )
	{
		function marc_output_pdf()
		{
			$tags = get_tags();
			include("mpdf/mpdf.php");
			$mpdf=new mPDF();
			
			//Generate PDF all posts
			$content = marc_build_html('');
			$newUrl = get_query_var( "All" , "nothing" );
			
			if($newUrl=='pdf')
				{
					$mpdf->WriteHTML($content);
					$mpdf->Output();
				}
			
			//Generate PDF for posts tags
			foreach($tags as $t) 
			{
				$content = marc_build_html(strtolower (str_replace(' ', '-', $t->name)));
				$formattedTag = str_replace(' ', '', $t->name);
				$newUrl = get_query_var( $formattedTag , "nothing" );	 
				
				if($newUrl=='pdf')
				{
					$mpdf->WriteHTML($content);
					$mpdf->Output();
				}
			}
		} 
		add_action( 'wp', 'marc_output_pdf' );
	}

	/**
	* Creates links based on tags. 
	*
	* @return array $links
	*/
	if ( ! function_exists( 'marc_create_link' ) )
	{
		function marc_create_link()
		{	$links = array(); 
			$tags = get_tags();
			foreach($tags as $t) 
			{
				$formattedTag = str_replace(' ', '', $t->name);
				$permalink	= add_query_arg( $formattedTag, 'pdf',  home_url() );
				array_push($links, '<a href="' . $permalink . '" target="_blank" class="selected_links">' . $t->name .'</a> </br>');
			}
			return $links;	
		}
	}
	
	/**
	* Creates a link for all posts PDF.  
	*
	* @return string $link
	*/
	if ( ! function_exists( 'marc_create_link_all' ) )
	{
		function marc_create_link_all()
		{
			$link;
			$permalink	= add_query_arg( "All", 'pdf',  home_url() );
			$link = '<a href="' . $permalink . '" target="_blank" class="selected_links">All Policies</a> </br>';
			return $link;	
		}
	}
?>










