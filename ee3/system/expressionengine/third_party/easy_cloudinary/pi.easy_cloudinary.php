<?php
/*
=====================================================
 Easy Cloudinary - by Aaron Gustafson
-----------------------------------------------------
 https://www.aaron-gustafson.com/
=====================================================
 This extension was created by Aaron Gustafson
 (aaron@easy-designs.net)
 This work is licensed under the MIT License.
=====================================================
 File: pi.easy_cloudinary.php
-----------------------------------------------------
 Purpose: Automates swapping of Cloudinary’s image 
 paths for local ones.
=====================================================
*/

class EasyCloudinary {

	var $return_data;
	var $site_domain;
	var $cloudinary_config = '';

	/**
	 * EasyCloudinary constructor
	 * stores the config & triggers the processing
	 */
	function __construct()
	{
		# grab the config
		$this->cloudinary_config = ee()->config->item('easy_cloudinary');
		if ( $this->cloudinary_config !== NULL )
		{
			# get the host name
			$this->site_domain = ee()->config->site_url();
			$this->return_data = $this->convert( ee()->TMPL->tagdata );
		}
		else
		{
			$this->return_data = ee()->TMPL->tagdata;
		}
	} # end EasyCloudinary constructor
  
	/**
	 * EasyCloudinary::convert()
	 * processes the supplied content based on the configuration
	 * 
	 * @param str $str - the content to be parsed
	 */
	function convert( $str='' )
	{
		$cloud_name = $this->cloudinary_config['cloud_name'];
		$template = $this->cloudinary_config['template'];
		
		# in case we have relative image paths
		$current_path = ee()->uri->uri_string();
		if ( substr( $current_path, -1, 1 ) != '/' )
		{
			$current_path = explode( '/', $current_path );
			$last = count( $current_path ) - 1;
			# if the path is a file…
			if ( strpos( $current_path[$last], '.' ) !== FALSE )
			{
				# remove the file name
				$current_path[$last] = '';
			}
			# otherwise it’s probably a folder
			else
			{
				$current_path[] = '';
			}
			$current_path = implode( '/', $current_path );
		}
		
		# trim
		$str = trim( $str );

		$img_lookup = '/(<img([^>]*)\/?>)/';
		if ( preg_match_all( $img_lookup, $str, $found, PREG_SET_ORDER ) )
		{
			# loop the matches
			foreach ( $found as $instance )
			{
				$original_img = $instance[1];
				$src = '';
				$srcset = [];
				$other_attributes = [];
				
				# remove the /
				if ( substr( $instance[2], -1, 1 ) == '/' )
				{
					$instance[2] = substr( $instance[2], 0, -1 );
				} 

				# Get all attributes
				# Reference: http://stackoverflow.com/questions/138313/how-to-extract-img-src-title-and-alt-from-html-using-php#answer-2937682
				$doc = new DOMDocument();
				@$doc->loadHTML( $original_img );
				$tags = $doc->getElementsByTagName('img');
				
				foreach ( $tags as $tag )
				{
					foreach ( $tag->attributes as $attribute )
					{
						$name = $attribute->name;
						$value = $attribute->value;
						
						if ( $name == 'src' )
						{
							$src = $value;
						}
						if ( $name == 'srcset' )
						{
							// do nothing (Cloudinary dynamically handles or the template can)
						}
						else
						{
							$attributes[$name] = "{$name}=\"{$value}\"";
						}
					}
				}
				
				# enforce an alt attribute
				if ( ! isset( $attributes['alt'] ) )
				{
					$attributes['alt'] = '';
				}

				# Format the src to include this domain is we don’t have it already
				if ( strpos( $src, $this->site_domain ) === FALSE )
				{
					# only modify if not a fully qualified domain
					if ( preg_match( "/^https?:\\/\\//", $src ) === 0 )
					{
						if ( substr( $src, 0, 1 ) == '/' )
						{
							$src = "{$this->site_domain}{$src}";
						}
						else
						{
							$src = "{$this->site_domain}{$current_path}{$src}";
						}
					}
				}
				
				# build the new image
				$swap = array(
					'cloud_name' => $cloud_name,
					'attributes' => implode( ' ', $attributes ),
					'image_url'	 => $src
				);
				$new_img = ee()->functions->var_swap( $template, $swap );
				
				$str = str_replace( $original_img, $new_img, $str );
				
			} # end foreach instance
			
		} # end if match
		
		$this->return_data = $str;
		
		return $this->return_data;
		
	} # end EasyCloudinary::convert()

} # end EasyCloudinary

/* End of file pi.easy_cloudinary.php */ 
/* Location: ./system/expressionengine/third_party/easy_cloudinary/pi.easy_cloudinary.php */