<?php
// Prevent file from being loaded directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class ET_Social_Share {

	public $name;

	public $slug;

	public $share_url = '#';

	private static $_networks = array();

	function __construct() {
		$this->init();

		self::$_networks[$this->slug] = $this;
	}

	static function get_networks() {
		return self::$_networks;
	}

	function create_share_url( $permalink, $title ) {
		$permalink = esc_url( $permalink );
		$title = esc_attr( $title );
		$title = rawurlencode( $title );

		if ( false !== strpos( $this->share_url, '%1$s' ) && false !== strpos( $this->share_url, '%2$s' ) ) {
			$url = sprintf( $this->share_url, $permalink, $title );
		} else if ( false !== strpos( $this->share_url, '%1$s' ) ) {
			$url = sprintf( $this->share_url, $permalink );
		} else {
			return '#';
		}

		$url = esc_url( $url );

		return $url;
	}

}

class ET_Facebook_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'Facebook', 'extra' );
		$this->slug = 'facebook';
		$this->share_url = 'http://www.facebook.com/sharer.php?u=%1$s&t=%2$s';
	}

}
new ET_Facebook_Social_Share;

class ET_Twitter_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'Twitter', 'extra' );
		$this->slug = 'twitter';
		$this->share_url = 'http://twitter.com/home?status=%2$s%%20%1$s';
	}

}
new ET_Twitter_Social_Share;

class ET_Google_Plus_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'Google +', 'extra' );
		$this->slug = 'googleplus';
		$this->share_url = 'https://plus.google.com/share?url=%1$s&t=%2$s';
	}

}
new ET_Google_Plus_Social_Share;

class ET_Tumblr_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'Tumblr', 'extra' );
		$this->slug = 'tumblr';
		$this->share_url = 'https://www.tumblr.com/share?v=3&u=%1$s&t=%2$s';
	}

}
new ET_Tumblr_Social_Share;

class ET_Pinterest_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'Pinterest', 'extra' );
		$this->slug = 'pinterest';
		$this->share_url = 'http://www.pinterest.com/pin/create/button/?url=%1$s&description=%2$s';
	}

}
new ET_Pinterest_Social_Share;

class ET_LinkedIn_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'LinkedIn', 'extra' );
		$this->slug = 'linkedin';
		$this->share_url = 'http://www.linkedin.com/shareArticle?mini=true&url=%1$s&title=%2$s';
	}

}
new ET_LinkedIn_Social_Share;

class ET_Buffer_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'Buffer', 'extra' );
		$this->slug = 'buffer';
		$this->share_url = 'https://bufferapp.com/add?url=%1$s&title=%2$s';
	}

}
new ET_Buffer_Social_Share;

class ET_Stumbleupon_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'Stumbleupon', 'extra' );
		$this->slug = 'stumbleupon';
		$this->share_url = 'http://www.stumbleupon.com/badge?url=%1$s&title=%2$s';
	}

}
new ET_Stumbleupon_Social_Share;

class ET_Basic_Email_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'Email', 'extra' );
		$this->slug = 'basic_email';
	}

}
new ET_Basic_Email_Social_Share;

class ET_Basic_Print_Social_Share extends ET_Social_Share {

	function init() {
		$this->name = __( 'Print', 'extra' );
		$this->slug = 'basic_print';
	}

}
new ET_Basic_Print_Social_Share;