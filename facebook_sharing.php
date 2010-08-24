<?php
  /**
   * <summary>
   * The FacebookSharing kit is a set of methods which enable publishers to insert the meta tags
   * required by all videos being uploaded to Facebook.
   * The Develop Kit contains a primary class FacebookSharing, and one primary method: header().
   * </summary>
   */
  /**
   * <summary>
   * Maximum width allowed for facebook videos. If the video being uploaded
   * has a width greater than this, it will be reduced to make the width
   * the same as FACEBOOK_MAX_WIDTH while maintaining the same aspect ratio
   * between height and width.s
   * </summary>
   */
define("FACEBOOK_MAX_WIDTH","420");

class FacebookSharing
{
   private $_PARTNER_CODE;
   private $_SECRET_CODE;
   private $_EMBED_CODE;

   public function __construct($PARTNERCODE,$SECRETCODE)
   {
      $this->_PARTNER_CODE = $PARTNERCODE;
      $this->_SECRET_CODE  = $SECRETCODE;
   }

  /**
   * <summary>
   * Returns the url string for the specified embed code, secret code and partner code.
   * </summary>
   *
   * <param name="params"> key value pair for embed code,expiry time.</param>
   */
   public function getURL($params)
   {
      if (!array_key_exists('expires', $params))
         {
            $params['expires'] = time() + 1200;
         }
         else
         {
            $params['expires'] = time() + $params['expires'];
         }

          $string_to_sign = $this->_SECRET_CODE;
          $url  					= "http://api.ooyala.com/partner/query?pcode=".$this->_PARTNER_CODE;
          $keys 					= array_keys($params);
          sort($keys);
          foreach ($keys as $key) {
			$string_to_sign .= $key.'='.$params[$key];
			$url .= '&'.rawurlencode($key).'='.rawurlencode($params[$key]);
          }
          $digest = hash('sha256', $string_to_sign, true);
          $signature = preg_replace('{=+$}', '', trim(base64_encode($digest)));
          $url .= '&signature='.rawurlencode($signature);

          return $url;
   }
	/**
	 * return channel information after get it from ooyala api
	 *
	 * @return object
	 * @author MILAN Thbialt
	 **/
	private function get_channel($embed_code)
	{
		// Get the url for the passed in partner_code, secret_code and embed_code.
	       $url				  = $this -> getURL(array("embedCode" => $embed_code.""));
	       // Get the xml data for the url.
	       $responseStr = file_get_contents($url);
	       // Parse the xml document to get the values for creating meta tags
	  	   $data 				= simplexml_load_string($responseStr);

	  /**
	   * <summary>
	   *  Fill the hash map with the key value pairs for the required meta tags
	   *  by getting the values from the parsed xml
	   *   </summary>
	   */

	   // Creating instance of channel object 
	     $channel = new Channel();
	       foreach($data->item as $key => $value)
	       {
	          $channel->embedCode = $value->embedCode;
		        if( $channel->embedCode == null || empty( $channel->embedCode )) {
		        	    // channel embed code is not found.
			    		error_log("Value not found for: embedCode",3,"FacebookSharingLog.log");
			    	}
	          $channel->title = $value->title."";
	          if( $channel->title == null || empty( $channel->title )) {
	                // channel title value is not found.
		    			error_log("Value not found for: channelTitle",3,"FacebookSharingLog.log");
		    		}
	          $channel->description = $value->description."";
	          if( $channel->description == null || empty( $channel->description )) {
	                // channel description value is not found.
		    			error_log("Value not found for: channelDescription",3,"FacebookSharingLog.log");
		    		}
	          $channel->length = $value->length;
	          if( $channel->length == null || empty( $channel->length )) {
	                // channel length value is not found.
		    			error_log("Value not found for: channelLength",3,"FacebookSharingLog.log");
		    		}
	          $channel->uploadedAt =  $value->uploadedAt;
	          if( $channel->uploadedAt == null || empty( $channel->uploadedAt )) {
	                // channel uploadedAt value is not found.
		    			error_log("Value not found for: channelUploadedAt",3,"FacebookSharingLog.log");
		    		}
	          $channel->thumbnail = $value->thumbnail."";
	          if( $channel->thumbnail == null || empty( $channel->thumbnail )) {
	                // channel thumbnail value is not found.
		    			error_log("Value not found for: channelThumbnail",3,"FacebookSharingLog.log");
		    		}
	          $channel->height = $value->height."";
	          if( $channel->height == null || empty( $channel->height)) {
	                // channel height value is not found.
		    			error_log("Value not found for: channelHeight",3,"FacebookSharingLog.log");
		    		}
	          $channel->width = $value->width."";
	          if( $channel->width == null || empty( $channel->width)) {
	            	    // channel width value is not found.
			    		error_log("Value not found for: channelWidth",3,"FacebookSharingLog.log");
			    	}
	      }
	      // Adjust video width to max allowed by Facebook, if required.
	      if ( $channel->width > FACEBOOK_MAX_WIDTH ) {
	        $percentWidthReduction = 100 * FACEBOOK_MAX_WIDTH/$channel->width;
	        $newHeight 			 			 = ($channel->height * $percentWidthReduction / 100);
	        $channel->width   	   = FACEBOOK_MAX_WIDTH;
	        $channel->height 			 = $newHeight;
	      }
		//return channel object
		return $channel;
	}

  /**
   * <summary>
   * Returns the meta tags string for the given embed code, partner code and secret code video.
   * If the width of the video is greater than FACEBOOK_MAX_WIDTH, it reduces the height and
   * width by maintaining the aspect ratio to fit the video in Facebook.
   * </summary>
   * <returns>Return the meta tags string.</returns>
   */
    public function header($embed_code)
     {
       $channel = get_channel($enbed_code);
      // Construct the meta tags string by substituting the values from the metadata hashmap.
     	$metaTags  = "<meta name=\"medium\" content=\"video\" /> \n";
      $metaTags .= "<meta name=\"title\" content=\"" . $channel->title."\" /> \n";
      $metaTags .= "<meta name=\"description\" content=\"" .$channel->description."\" /> \n";
      $metaTags .= "<link rel=\"image_src\" href=\"".$channel->thumbnail. "\" /> \n";
      $metaTags .= "<link rel=\"video_src\" href=\"http://player.ooyala.com/player.swf?embedCode=".$channel->embedCode."&keepEmbedCode=true"."\" />\n";
      $metaTags .= "<meta name=\"video_height\" content=\"" .$channel->height."\" /> \n";
         $metaTags .= "<meta name=\"video_width\" content=\"" .$channel->width."\" /> \n";
         $metaTags .= "<meta name=\"video_type\" content=\"application/x-shockwave-flash\" /> \n";

         // return the meta tags string with the values retrieved for the embedCode
         return $metaTags;
     }
   /**
    * return the embed html code for embedcode id provide. Can select html embed, with or
    * without fallback & pure flash object code.
    *
    * @return string htmlcode
    * @author MILAN Thibault
    **/
  	public function embed($embed_code)
	{
		$bgcolor="#000000";
		$allow_fullscreen="true";
		
		$channel = get_channel($embed_code);
		$html ="<script src=\"http://player.ooyala.com/player.js?width=";
		$html.=$channel->width."&height=".$channel->height;
		$html.="&embedCode=".$embed_code."\"></script><noscript><object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" id=\"ooyalaPlayer_8tjiu_gd36pogy\" width=\"";
		$html.=$channel->width."\" height=\"".$channel->height;
		$html.="\" codebase=\"http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab\"><param name=\"movie\" value=\"http://player.ooyala.com/player.swf?embedCode=";
		$html.=$embed_code."&version=2\" /><param name=\"bgcolor\" value=\"".$bgcolor;
		$html.="\" /><param name=\"allowScriptAccess\" value=\"always\" /><param name=\"allowFullScreen\" value=\"".$allow_fullscreen;
		$html.="<param name=\"flashvars\" value=\"embedType=noscriptObjectTag&embedCode=".$embed_code."\" />";
		$html.="<embed src=\"http://player.ooyala.com/player.swf?embedCode=".$embed_code."&version=2\" bgcolor=\"".$bgcolor."\" width=\"".$channel->width."\" height=\"".$channel->height."\" name=\"ooyalaPlayer_6h6n5_gd7jd7q6\" align=\"middle\" play=\"true\" loop=\"false\" allowscriptaccess=\"always\" allowfullscreen=\"true\" type=\"application/x-shockwave-flash\" flashvars=\"&embedCode=".$embed_code."\" pluginspage=\"http://www.adobe.com/go/getflashplayer\"></embed></object></noscript>";
		
		return $html;
	}

   }
   class Channel
   {
	public $embedCode   = "";
	public $thumbnail   = "";
	public $title 	    = "";
	public $height      = 0.0;
	public $width       = 0.0;
	public $description = "";
	public $length      = 0.0;
	public $uploadedAt  = 0.0;

	public function __construct(){

    	}

    }



?>