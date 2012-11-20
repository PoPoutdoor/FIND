<?php
/**
*
* @author PoPoutdoor
*
* @package RSS_input
* @version $Id:$
* @copyright (c) 2008-2011 PoPoutdoor
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package rss_parser
*
*	Note: xmlns:attribute is not supported.
*
*	RSS 2.0 specifications: http://validator.w3.org/feed/docs/rss2.html
*	Atom 1.0 specifications: http://www.atomenabled.org/developers/syndication/atom-format-spec.php
*
*	Differences between RSS 2.0 and Atom 1.0: http://www.intertwingly.net/wiki/pie/Rss20AndAtom10Compared
*
*/
class rss_parser
{
	// private
	var $_xml_parser		= null;

	var $_insideitem		= false;
	var $_insidechannel	= false;
	var $_insideimage		= false;
	var $_tag				= '';
	//		Item attributes.
	var $_item = array(
		'title'			=> '',
		'link'			=> '',
		'description'	=> '',
		'author'			=> '',
		'category'		=> '',
		'comments'		=> '',
		'pubDate'		=> '',
		'update'			=> '',	// Atom
	);
	//		Channel attributes.
	var $_img_url	= '';
	var $_img_link	= '';
	var $_channel 	= array(
		'title'	=> '',
		'link'	=> '',
		'desc'	=> '',
		'rights'	=> '',
		'date'	=> '',
		'update'	=> '',
		'ttl'		=> '',
	);

	// public
	var $channel	= array(); // Channel attributes.
	var $img_url	= '';
	var $img_link	= '';
	var $items		= array(); // Items attributes.

	var $parser_error	= array(); // xml parser error message

	/**
	* our class constructor
	*/
	function rss_parser()
	{
	}

	// Our class destructor (kind of)
	function destroy()
	{
		// destroy our xml parser.
		if ($this->_xml_parser != null)
		{
			xml_parser_free($this->_xml_parser);
			$this->_xml_parser = null;
		}
	}

	/**
	* Convert date string to epoch format
	*
	* @param $rss_date, the input string
	* @return string, in epoch format. Null if not supported
	*/
	function _date2unix($rss_date)
	{
		$ts = trim($rss_date);

		if (preg_replace('/[0-9]/s', '', $ts))
		{
			// convert atom style timestamp (format: '2006-12-27T22:00:00-04:00')
			// otherwise, assume in format 'Sun, 04 Apr 2010 10:53:22 GMT'
			$ts = preg_replace('/([0-9]{4}-[0-9]{2}-[0-9]{2})T(.*)([+|-][0-9]{2}):/is', "$1 $2 $3", $ts);

			$epoch = strtotime($ts);
			// previous to PHP 5.1.0, strtotime() error is -1
			$ts = ($epoch === -1 || $epoch === false) ? '' : $epoch;
		}

		return $ts;
	}

	/**
	* Convert CR to LF, strip extra LFs and spaces, multiple newlines to 2
	* Filter unsupported html tags
	*
	* @param $text
	* @param $is_html (booleans), option to retains supported html tags
	* @return string
	*/
	function _filter($text, $is_html = false)
	{
		$text = str_replace(array("\r", '&nbsp;', '&#32;'), array("\n", ' ', ' '), $text);
		$text = html_entity_decode($text, ENT_QUOTES, "UTF-8");

		if (!$is_html)
		{
			$text = strip_tags($text);
		}

		$text = preg_replace("# +?\n +?#", "\n", $text);
		$text = preg_replace("#\n{3,}#", "\n\n", $text);
		$text = preg_replace('# +#', ' ', $text);

		return trim($text);
	}


	/**
	* parse the xml
	*
	* @return booleans, false if error
	*/
	function parse(&$xml)
	{
		// Make sure we have not already allocated a parser.
		if ($this->_xml_parser != null)
		{
			return false;
		}

		// init parser
		$prev_error_reporting = error_reporting();
		error_reporting(E_ERROR);

		// Instantiate an xml parser and setup the reserved events.
		$this->_xml_parser = xml_parser_create("UTF-8");
			xml_set_object($this->_xml_parser, $this);
			xml_set_element_handler($this->_xml_parser, 'startElement', 'endElement');
			xml_set_character_data_handler($this->_xml_parser, 'characterData');
			xml_parser_set_option($this->_xml_parser, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($this->_xml_parser, XML_OPTION_CASE_FOLDING, 1);
			xml_parser_set_option($this->_xml_parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		$ret = xml_parse($this->_xml_parser, $xml, true);

		error_reporting($prev_error_reporting);

		if (!$ret)
		{
			$this->parser_error = array(
				xml_error_string(xml_get_error_code($this->_xml_parser)),
				xml_get_current_line_number($this->_xml_parser),
				xml_get_current_column_number($this->_xml_parser) + 1,
				xml_get_current_byte_index($this->_xml_parser)
			);

			return false;
		}

		// Destroy our xml parser.
		xml_parser_free($this->_xml_parser);
		$this->_xml_parser = null;

		return true;
	}

	/**
	* Normalize RDF tags
	*/
	function _stripNameSpace($string)
	{
		$pos = utf8_strpos($string, ":");
		if ($pos === true)
		{
			$string = utf8_substr($string, $pos + 1);
		}

		return $string;
	}

	/**
	* this event is fired when the xml parser comes across and opening element in the xml
	*/
	function startElement($parser, $tagName)
	{
		// take a copy of the tag name
		$this->_tag = $this->_stripNameSpace($tagName);

		// if we are on a CHANNEL tag
		if ($tagName =='CHANNEL' || $tagName == 'FEED')
		{
			$this->_insidechannel	= true;
			$this->_insideimage		= false;
			$this->_insideitem		= false;
		}
		// if we are on an IMAGE tag
		elseif ($tagName == 'IMAGE')	// 'LOGO' in Atom
		{
			$this->_insidechannel	= false;
			$this->_insideimage		= true;
			$this->_insideitem		= false;
		}
		// if we are on an ITEM tag
		elseif ($tagName == 'ITEM' || $tagName == 'ENTRY')
		{
			$this->_insidechannel	= false;
			$this->_insideimage		= false;
			$this->_insideitem		= true;
		}
	}

	/**
	* this event is fired when the xml parser comes across a closing element in the xml
	*/
	function endElement($parser, $tagName)
	{
		// if it's an item then we want to add the item details to our item details arrays
		if ($tagName == 'ITEM' || $tagName == 'ENTRY')
		{
			$this->_item['title']			= $this->_filter($this->_item['title']);
			$this->_item['link']				= $this->_item['link'];
			$this->_item['description']	= $this->_filter($this->_item['description'], true);
			$this->_item['author']			= $this->_filter($this->_item['author']);
			$this->_item['category']		= $this->_filter($this->_item['category']);
			$this->_item['comments']		= $this->_item['comments'];
			$this->_item['pubDate']			= $this->_date2unix($this->_item['pubDate']);
			$this->_item['update']			= $this->_date2unix($this->_item['update']);

			array_push($this->items, $this->_item);

			// reset our item detail variables
			$this->_item['title']			= '';
			$this->_item['link']				= '';
			$this->_item['description']	= '';
			$this->_item['author']			= '';
			$this->_item['category']		= '';
			$this->_item['comments']		= '';
			$this->_item['pubDate']			= '';
			$this->_item['update']			= '';

			$this->_insideitem = false;
		}
		// if it's an channel image
		elseif ($tagName == 'IMAGE')
		{
			$this->img_url		= $this->_img_url;
			$this->img_link	= $this->_img_link;

			// reset our image detail variables
			$this->_img_url	= '';
			$this->_img_link	= '';

			$this->_insideimage	= false;
		}
		// if it's a channel then we want to add the channel details to our channel details fields
		elseif ($tagName == 'CHANNEL' || $tagName == 'FEED')
		{
			$this->channel['title']		= $this->_filter($this->_channel['title']);
			$this->channel['link']		= $this->_channel['link'];
			$this->channel['desc']		= $this->_filter($this->_channel['desc']);
			$this->channel['rights']	= $this->_filter(str_replace("©", "&#169;", $this->_channel['rights']));

			$this->channel['date']		= $this->_date2unix($this->_channel['date']);
			$this->channel['update']	= $this->_date2unix($this->_channel['update']);
			$this->channel['ttl']		= $this->_date2unix($this->_channel['ttl']);

			// reset our channel detail variables
			$this->_channel['title']	= '';
			$this->_channel['link']		= '';
			$this->_channel['desc']		= '';
			$this->_channel['rights']	= '';
			$this->_channel['date']		= '';
			$this->_channel['update']	= '';
			$this->_channel['ttl']		= '';

			$this->_insidechannel = false;
		}
	}

	/**
	* this event is fired for each character of data which is read from the xml
	* use it to read the bits of information we are interested in
	*/
	function characterData($parser, $data)
	{
		// if we are inside an item
		if ($this->_insideitem)
		{
			/* RSS 2.0
				- Not going to support tags: 'GUID', 'SOURCE', 'ENCLOSURE'
			*/
			switch ($this->_tag)
			{
				// everything is optional, but title or description must be present.
				case 'TITLE':	// same in Atom
					$this->_item['title'] .= $data;
				break;

				case 'LINK':	// same in Atom
					$this->_item['link'] .= $data;
				break;

				case 'SUMMARY':	// Atom, preferred
				case 'CONTENT':	// Atom, full content
				case 'DESCRIPTION':
					$this->_item['description'] .= $data;
				break;

				case 'NAME':		// Atom?
				case 'CONTRIBUTOR':	// Atom
				case 'AUTHOR':		// same in Atom
					$this->_item['author'] .= $data;
				break;
				// not support tag with optional 'domain' attribute
				case 'CATEGORY':	// same in Atom
					$this->_item['category'] .= $data;
				break;

				case 'COMMENTS':
					$this->_item['comments'] .= $data;
				break;

				case 'DATE':		// Atom?
				case 'PUBLISHED':	// Atom
				case 'PUBDATE':
					$this->_item['pubDate'] .= $data;
				break;

				case 'UPDATED':	// Atom
					$this->_item['update'] .= $data;
				break;
			}
		}
		// if was inside an channel image
		elseif ($this->_insideimage)
		{
			/* RSS 2.0
				- current not support tags(no BBCode support): 'TITLE', 'WIDTH', 'HEIGHT'
			*/
			switch ($this->_tag)
			{
				case 'URL':
					$this->_img_url .= $data;
				break;

				case 'LINK':
					$this->_img_link .= $data;
				break;
			}
		}
		// if we are inside a channel
		elseif ($this->_insidechannel)
		{
			/* RSS 2.0
				- Not going to support tags:
					'CLOUD', 'TEXTINPUT', 'RATING', 'DOCS', 'GENERATOR', 'MANAGINGEDITOR', 'WEBMASTER',
					'CATEGORY'
				- Current not support tags(seems not useful):
					'LANGUAGE', 'SKIPHOURTS', 'SKIPDAYS'
			*/
			switch ($this->_tag)
			{
			// Required
				case 'TITLE':	// same in Atom
					$this->_channel['title'] .= $data;
				break;

				//case 'URI':
				case 'ID':			// Atom
				case 'LINK':		// same in Atom
					$this->_channel['link'] .= $data;
				break;

				case 'SUBTITLE':	// Atom
				case 'DESCRIPTION':
					$this->_channel['desc'] .= $data;
				break;

			// below tags are optional
				case 'RIGHTS':		// Atom
				case 'COPYRIGHT':
					$this->_channel['rights'] .= $data;
				break;

				case 'DATE':		// Atom
				case 'PUBLISHED':	// Atom
				case 'PUBDATE':
					$this->_channel['date'] .= $data;
				break;

				case 'UPDATED':	// Atom
				case 'LASTBUILDDATE':
					$this->_channel['update'] .= $data;
				break;

				case 'TTL':		// in minutes
					$this->_channel['ttl'] .= $data;
				break;
			}
		}
	}
}

?>
