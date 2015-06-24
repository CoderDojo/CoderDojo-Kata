<?php
/**
 * EmbedVideo
 * EmbedVideo VideoService Class
 *
 * @license		MIT
 * @package		EmbedVideo
 * @link		https://www.mediawiki.org/wiki/Extension:EmbedVideo
 *
 **/
namespace EmbedVideo;

class VideoService {
	/**
	 * Available services.
	 *
	 * @var		array
	 */
	static private $services = [
		'archiveorg' => [
			'embed'			=> '<iframe src="//archive.org/embed/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio' => 1.2994923857868, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#archive\.org/(?:details|embed)/([\d\w\-_][^/\?\#]+)#is'
			],
			'id_regex'		=> [
				'#^([\d\w\-_][^/\?\#]+)$#is'
			]
		],
		'bambuser' => [
			'embed'			=> '<iframe src="//embed.bambuser.com/broadcast/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio' => 1.2994923857868, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#bambuser\.com/(?:v|broadcast)/([\d\w\-\+]+)(?:/\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([\d\w\-\+]+)$#is'
			]
		],
		'bambuser_channel' => [
			'embed' 		=> '<iframe src="//embed.bambuser.com/channel/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio' => 1.2994923857868, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#bambuser\.com/channel/([\d\w\-\+]+)(?:/\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([\d\w\-\+]+)$#is'
			]
		],
		'blip' => [
			'default_width'	=> 640,
			'default_ratio' => 1.2994923857868, //(16 / 9)
			'https_enabled'	=> false,
			'url_regex'		=> [
				'#(http://blip\.tv/[\w\d\-]+?/[\w\d\-]+?-[\d]+)#is'
			],
			'oembed'		=> 'http://blip.tv/oembed/?url=%1$s&width=%2$d&maxwidth=%2$d'
		],
		'bing' => [
			'embed'			=> '<iframe src="//hub.video.msn.com/embed/%1$s" width="%2$d" height="%3$d" frameborder="0" scrolling="no" noscroll allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#bing.com/videos/watch/video/[\w\d\-]+?/([a-zA-Z0-9]+)(?:/\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([a-zA-Z0-9]+)$#is'
			]
		],
		'collegehumor' => [
			'embed'			=> '<iframe src="//www.collegehumor.com/e/%1$s" width="%2$d" height="%3$d" frameborder="0" allowFullScreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio' => 1.6260162601626, //(600 / 369)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#collegehumor\.com/(?:video|e)/([\d]+)#is'
			],
			'id_regex'		=> [
				'#^([\d]+)$#is'
			]
		],
		'dailymotion' => [
			'embed'			=> '<iframe src="//www.dailymotion.com/embed/video/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#dailymotion\.com/(?:video|embed/video)/([a-zA-Z0-9]+)(?:_\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([a-zA-Z0-9]+)(?:_\S+?)#is'
			]
		],
		'divshare' => [
			'embed'			=> '<iframe src="//www.divshare.com/flash/video2?myId=%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> true
		],
		'funnyordie' => [
			'embed'			=> '<iframe src="http://www.funnyordie.com/embed/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.64102564102564, //(640 / 390)
			'https_enabled'	=> false,
			'url_regex'		=> [
				'#funnyordie\.com/(?:videos|embed)/([a-zA-Z0-9]+)(?:/\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([a-zA-Z0-9]+)$#is'
			]
		],
		'gfycat' => [
			'embed'			=> '<iframe src="//gfycat.com/ifr/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true" scrolling="no" style="-webkit-backface-visibility: hidden;-webkit-transform: scale(1);" ></iframe>',
			'default_width'	=> 640,
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#gfycat\.com/([a-zA-Z]+)#is'
			],
			'id_regex'		=> [
				'#^([a-zA-Z]+)$#is'
			]
		],
		'kickstarter' => [
			'embed'			=> '<iframe src="//www.kickstarter.com/projects/%1$s/widget/video.html" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#kickstarter\.com/projects/([\d\w-]+/[\d\w-]+)(?:/widget/video.html)?#is'
			],
			'id_regex'		=> [
				'#^([\d\w-]+/[\d\w-]+)$#is'
			]
		],
		'metacafe' => [
			'embed'			=> '<iframe src="http://www.metacafe.com/embed/%1$s/" width="%2$d" height="%3$d" frameborder="0" allowFullScreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> false,
			'url_regex'		=> [
				'#metacafe\.com/(?:watch|embed)/([\d]+)(?:/\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([\d]+)$#is'
			]
		],
		'nico' => [
			'embed'			=> '<script type="text/javascript" src="http://ext.nicovideo.jp/thumb_watch/%1$s?w=%2$d&h=%3$d"></script>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.59609120521173, //(490 / 307)
			'https_enabled'	=> false,
			'url_regex'		=> [
				'#nicovideo\.jp/watch/(sm[\d]+)#is'
			],
			'id_regex'		=> [
				'#^(sm[\d]+)$#is'
			]
		],
		'rutube' => [
			'embed'			=> '<iframe src="//rutube.ru/play/embed/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#rutube\.ru/video/([a-zA-Z0-9]+)(?:/\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([a-zA-Z0-9]+)$#is'
			]
		],
		'teachertube' => [
			'embed'			=> '<iframe src="http://www.teachertube.com/embed/video/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.72972972972973, //(640 / 370)
			'https_enabled'	=> false,
			'url_regex'		=> [
				'#teachertube\.com/video/(?:[\w\d\-]+?-)?([\d]+)$#is',
			],
			'id_regex'		=> [
				'#^([\d]+)$#is'
			]
		],
		'ted' => [
			'embed'			=> '<iframe src="//embed-ssl.ted.com/talks/%1$s.html" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#ted\.com/talks/([\d\w\-]+)(?:/\S+?)?#is',
			],
			'id_regex'		=> [
				'#^([\d\w\-]+)$#is'
			]
		],
		'tudou' => [
			'embed'			=> '<iframe src="http://www.tudou.com/programs/view/html5embed.action?code=%1$s&autoPlay=false&playType=AUTO" allowfullscreen="true" width="%2$d" height="%3$d"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.6,
			'https_enabled'	=> false,
			'url_regex'		=> [
				'#tudou.com/listplay/([\d\w-]+)/([\d\w-]+).html#is',
				'#tudou.com/listplay/([\d\w-]+).html#is'
			],
			'id_regex'		=> [
				'#^([\d\w-]+)$#is'
			]
		],
		'twitch' => [
			'embed'			=> '<iframe src="http://www.twitch.tv/%1$s/embed" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.64021164021164, //(620 / 378)
			'https_enabled'	=> false,
			'url_regex'		=> [
				'#twitch\.tv/([\d\w-]+)(?:/\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([\d\w-]+)$#is'
			]
		],
		'twitchvod' => [
			'embed'			=> '<object id="clip_embed_player_flash" type="application/x-shockwave-flash" width="%2$d" height="%3$d" data="http://www.twitch.tv/widgets/archive_embed_player.swf" bgcolor="#000000">
	<param name="movie" value="http://www.twitch.tv/widgets/archive_embed_player.swf" />
	<param name="allowScriptAccess" value="always" />
	<param name="allowNetworking" value="all" />
	<param name="allowFullScreen" value="true" />
	<param name="flashvars" value="channel=%1$s&amp;auto_play=false&amp;start_volume=100&amp;chapter_id=%4$d" />
</object>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.64021164021164, //(620 / 378)
			'https_enabled'	=> false,
			'url_regex'		=> [
				'#twitch\.tv/([\d\w-]+)/c/([\d]+)(?:/\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([\d\w-]+)/c/([\d]+)$#is'
			]
		],
		'videomaten' => [
			'embed'			=> '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="%2$d" height="%3$d" id="videomat" align="middle"><param name="allowScriptAccess" value="sameDomain" /><param name="movie" value="http://89.160.51.62/recordMe/play.swf?id=%1$s" /><param name="loop" value="false" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" /><embed src="http://89.160.51.62/recordMe/play.swf?id=%1$s" loop="false" quality="high" bgcolor="#ffffff" width="%2$d" height="%3$d" name="videomat" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /></object>',
			'default_ratio'	=> 1.5, //(300 / 200)
			'https_enabled'	=> false
		],
		'vimeo' => [
			'embed'			=> '<iframe src="//player.vimeo.com/video/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio' => 1.2994923857868, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#vimeo\.com/([\d]+)#is',
				'#vimeo\.com/channels/[\d\w-]+/([\d]+)#is'
			],
			'id_regex'		=> [
				'#^([\d]+)$#is'
			],
			'oembed'		=> '%4$s//vimeo.com/api/oembed.json?url=%1$s&width=%2$d&maxwidth=%2$d'
		],
		'vine' => [
			'embed'			=> '<iframe src="//vine.co/v/%1$s/embed/simple" width="%2$d" height="%3$d" frameborder="0"></iframe>',
			'default_width'	=> 640,
			'default_ratio' => 1, //(1 / 1)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#vine\.co/v/([a-zA-Z0-9]+)#is'
			],
			'id_regex'		=> [
				'#^([a-zA-Z0-9]+)$#is'
			]
		],
		'yahoo' => [
			'embed'			=> '<iframe src="//screen.yahoo.com/%1$s.html?format=embed" width="%2$d" height="%3$d" scrolling="no" frameborder="0" allowfullscreen="true" allowtransparency="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#screen\.yahoo\.com/([\w\d\-]+?-\d+).html#is'
			],
			'id_regex'		=> [
				'#^([\w\d\-]+?-\d+)$#is'
			]
		],
		'youtube' => [
			'embed'			=> '<iframe src="//www.youtube.com/embed/%1$s%4$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#v=([\d\w-]+)(?:&\S+?)?#is',
				'#youtu\.be/([\d\w-]+)#is'
			],
			'id_regex'		=> [
				'#^([\d\w-]+)$#is'
			],
			'oembed'		=> [
				'http'	=> 'http://www.youtube.com/oembed?url=%1$s&width=%2$d&maxwidth=%2$d',
				'https'	=> 'http://www.youtube.com/oembed?scheme=https&url=%1$s&width=%2$d&maxwidth=%2$d'
			]
		],
		'youtubeplaylist' => [
			'embed'			=> '<iframe src="//www.youtube.com/embed/videoseries?list=%1$s%4$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.77777777777778, //(16 / 9)
			'https_enabled'	=> true,
			'url_regex'		=> [
				'#list=([\d\w-]+)(?:&\S+?)?#is'
			],
			'id_regex'		=> [
				'#^([\d\w-]+)$#is'
			]
		],
		'youku' => [
			'embed'			=> '<iframe src="http://player.youku.com/embed/%1$s" width="%2$d" height="%3$d" frameborder="0" allowfullscreen="true"></iframe>',
			'default_width'	=> 640,
			'default_ratio'	=> 1.6,
			'https_enabled'	=> false,
			'url_regex'		=> [
				'#id_([\d\w-]+).html#is',
			],
			'id_regex'		=> [
				'#^(?:id_)?([\d\w-]+)$#is'
			]
		]
	];

	/**
	 * This object instance's service information.
	 *
	 * @var		array
	 */
	private $service = [];

	/**
	 * Video ID
	 *
	 * @var		array
	 */
	private $id = false;

	/**
	 * Player Width
	 *
	 * @var		integer
	 */
	private $width = false;

	/**
	 * Player Height
	 *
	 * @var		integer
	 */
	private $height = false;

	/**
	 * Description Text
	 *
	 * @var		string
	 */
	private $description = false;

	/**
	 * Extra IDs that some services require.
	 *
	 * @var		array
	 */
	private $extraIDs = false;

	/**
	 * Extra URL Arguments that may be utilized by some services.
	 *
	 * @var		array
	 */
	private $urlArgs = false;

	/**
	 * Main Constructor
	 *
	 * @access	private
	 * @param	string	Service Name
	 * @return	void
	 */
	private function __construct($service) {
		$this->service = self::$services[$service];
	}

	/**
	 * Create a new object from a service name.
	 *
	 * @access	public
	 * @param	string	Service Name
	 * @return	mixed	New VideoService object or false on initialization error.
	 */
	static public function newFromName($service) {
		if (array_key_exists($service, self::$services)) {
			return new self($service);
		} else {
			return false;
		}
	}

	/**
	 * Return built HTML.
	 *
	 * @access	public
	 * @return	mixed	String HTML to output or false on error.
	 */
	public function getHtml() {
		if ($this->getVideoID() === false || $this->getWidth() === false || $this->getHeight() === false) {
			return false;
		}

		$html = false;
		if (array_key_exists('embed', $this->service)) {
			//Embed can be generated locally instead of calling out to the service to get it.
			$data = [
				$this->service['embed'],
				htmlentities($this->getVideoID(), ENT_QUOTES),
				$this->getWidth(),
				$this->getHeight(),
			];

			if ($this->getExtraIds() !== false) {
				foreach ($this->getExtraIds() as $extraId) {
					$data[] = htmlentities($extraId, ENT_QUOTES);
				}
			}

			$urlArgs = $this->getUrlArgs();
			if ($urlArgs !== false) {
				$data[] = '?'.$urlArgs;
			}

			$html = call_user_func_array('sprintf', $data);
		} elseif (array_key_exists('oembed', $this->service)) {
			//Call out to the service to get the embed HTML.
			if ($this->service['https_enabled']) {
				if (stristr($this->getVideoID(), 'https:') !== false) {
					$protocol = 'https:';
				} else {
					$protocol = 'http:';
				}
			}
			$url = sprintf(
				$this->service['oembed'],
				$this->getVideoID(),
				$this->getWidth(),
				$this->getHeight(),
				$protocol
			);
			$oEmbed = OEmbed::newFromRequest($url);
			if ($oEmbed !== false) {
				$html = $oEmbed->getHtml();
			}
		}

		return $html;
	}

	/**
	 * Return Video ID
	 *
	 * @access	public
	 * @return	mixed	Parsed Video ID or false for one that is not set.
	 */
	public function getVideoID() {
		return $this->id;
	}

	/**
	 * Set the Video ID for this video.
	 *
	 * @access	public
	 * @param	string	Video ID/URL
	 * @return	boolean	Success
	 */
	public function setVideoID($id) {
		$id = $this->parseVideoID($id);
		if ($id !== false) {
			$this->id = $id;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Parse the video ID/URL provided.
	 *
	 * @access	private
	 * @param	string	Video ID/URL
	 * @return	mixed	Parsed Video ID or false on failure.
	 */
	private function parseVideoID($id) {
		$id = trim($id);
		//URL regexes are put into the array first to prevent cases where the ID regexes might accidentally match an incorrect portion of the URL.
		$regexes = array_merge((array) $this->service['url_regex'], (array) $this->service['id_regex']);
		if (is_array($regexes) && count($regexes)) {
			foreach ($regexes as $regex) {
				if (preg_match($regex, $id, $matches)) {
					//Get rid of the full text match.
					array_shift($matches);

					$id = array_shift($matches);

					if (count($matches)) {
						$this->extraIDs = $matches;
					}

					return $id;
				}
			}
			//If nothing matches and matches are specified then return false for an invalid ID/URL.
			return false;
		} else {
			//Service definition has not specified a sanitization/validation regex.
			return $id;
		}
	}

	/**
	 * Return extra IDs.
	 *
	 * @access	public
	 * @return	boolean	Array of extra information or false if not set.
	 */
	public function getExtraIDs() {
		return $this->extraIDs;
	}

	/**
	 * Return the width.
	 *
	 * @access	public
	 * @return	mixed	Integer value or false for not set.
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * Set the width of the player.  This also will set the height automatically.
	 * Width will be automatically constrained to the minimum and maximum widths.
	 *
	 * @access	public
	 * @param	integer	Width
	 * @return	void
	 */
	public function setWidth($width = null) {
		global $wgEmbedVideoMinWidth, $wgEmbedVideoMaxWidth, $wgEmbedVideoDefaultWidth;

		if (!is_numeric($width)) {
			if ($width === null && $this->getDefaultWidth() !== false && $wgEmbedVideoDefaultWidth < 1) {
				$width = $this->getDefaultWidth();
			} else {
				$width = ($wgEmbedVideoDefaultWidth > 0 ? $wgEmbedVideoDefaultWidth : 640);
			}
		} else {
			$width = intval($width);
		}

		if ($wgEmbedVideoMaxWidth > 0 && $width > $wgEmbedVideoMaxWidth) {
			$width = $wgEmbedVideoMaxWidth;
		}

		if ($wgEmbedVideoMinWidth > 0 && $width < $wgEmbedVideoMinWidth) {
			$width = $wgEmbedVideoMinWidth;
		}
		$this->width = $width;

		if ($this->getHeight() === false) {
			$this->setHeight();
		}
	}

	/**
	 * Return the height.
	 *
	 * @access	public
	 * @return	mixed	Integer value or false for not set.
	 */
	public function getHeight() {
		return $this->height;
	}

	/**
	 * Set the height automatically by a ratio of the width or use the provided value.
	 *
	 * @access	public
	 * @param	mixed	[Optional] Height Value
	 * @return	void
	 */
	public function setHeight($height = null) {
		if ($height !== null && $height > 0) {
			$this->height = intval($height);
			return;
		}

		$ratio = 16 / 9;
		if ($this->getDefaultRatio() !== false) {
			$ratio = $this->getDefaultRatio();
		}
		$this->height = round($this->getWidth() / $ratio);
	}

	/**
	 * Return the optional URL arguments.
	 *
	 * @access	public
	 * @return	mixed	Integer value or false for not set.
	 */
	public function getUrlArgs() {
		if ($this->urlArgs !== false) {
			return http_build_query($this->urlArgs);
		}
	}

	/**
	 * Set URL Arguments to optionally add to the embed URL.
	 *
	 * @access	public
	 * @param	string	Raw Arguments
	 * @return	boolean	Success
	 */
	public function setUrlArgs($urlArgs) {
		if (!$urlArgs) {
			return true;
		}

		$urlArgs = urldecode($urlArgs);
		$_args = explode('&', $urlArgs);

		if (is_array($_args)) {
			foreach ($_args as $rawPair) {
				list($key, $value) = explode("=", $rawPair, 2);
				if (empty($key) || ($value === null || $value === '')) {
					return false;
				}
				$arguments[$key] = htmlentities($value, ENT_QUOTES);
			}
		} else {
			return false;
		}
		$this->urlArgs = $arguments;
		return true;
	}

	/**
	 * Is HTTPS enabled?
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function isHttpsEnabled() {
		return (bool) $this->service['https_enabled'];
	}

	/**
	 * Return default width if set.
	 *
	 * @access	public
	 * @return	mixed	Integer width or false if not set.
	 */
	public function getDefaultWidth() {
		return ($this->service['default_width'] > 0 ? $this->service['default_width'] : false);
	}

	/**
	 * Return default ratio if set.
	 *
	 * @access	public
	 * @return	mixed	Integer ratio or false if not set.
	 */
	public function getDefaultRatio() {
		return ($this->service['default_ratio'] > 0 ? $this->service['default_ratio'] : false);
	}
}
?>