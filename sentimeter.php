<?php
/*
Plugin Name: Sentimeter
Plugin URI: http://wordpress.org/extend/plugins/sentimeter/
Description: Allows sentiments (a la getsatisfaction.com, e.g. happy, sad, neutral etc.) to be attached to comments, measurement of overall sentiment is available as a pie chart in the admin UI (X% happy, Y% sad, Z% neutral). 
Author: Steve Winton | NixonMcInnes
Version: 1.0
Author URI: http://www.nixonmcinnes.co.uk/people/steve/
*/

if ( defined('WP_ADMIN') ) {
	/* The cssjs file sets up and enqueues all CSS and JS files used by this plugin */
	require( WP_PLUGIN_DIR . '/sentimeter/sentimeter_admin_cssjs.php' );
}

class Sentimeter {
    
    /**
     * Array of pre-defined topics that commenter can select from
     */
    static $default_topics = array(
        array(
            'name' => 'Choose an option...',
            'value' => '',
            'default' => true
        ),
        array(
            'name' => 'Ideas',
            'value' => 'ideas'
        ),
        array(
            'name' => 'Improvements',
            'value' => 'improvements'
        ),
        array(
            'name' => 'Happiness',
            'value' => 'happiness'
        ),
        array(
            'name' => 'Rewards',
            'value' => 'rewards'
        ),
        array(
            'name' => 'Something else',
            'value' => 'other'
        )
    );

    /**
     * Array of pre-defined sentiments that commenter can select from
     */
    static $default_sentiments = array(
        array(
            'name' => 'Happy',
            'value' => 'happy'
        ),
        array(
            'name' => 'Content',
            'value' => 'content'
        ),
        array(
            'name' => 'Neutral',
            'value' => 'neutral'
        ),
        array(
            'name' => 'Unhappy',
            'value' => 'unhappy'
        ),
        array(
            'name' => 'Outraged',
            'value' => 'outraged'
        )
    );

    // For looping through topics...
	var $current_topic = -1;
	var $topic_count;
	var $topics;
	var $topic;
	
    // For looping through sentiments...
	var $current_sentiment = -1;
	var $sentiment_count;
	var $sentiments;
	var $sentiment;
	
    // For tracking whether we are in the loop or not...
	var $in_the_loop;
    
    // For tracking required fields when posting a comment...
    var $required = array();
	
	// For holding validated data...
	var $data = array();

    /**
	 * PHP4 type constructor.
	 *
	 * Sets up hooks and filters used by the plugin
     */
    function Sentimeter() {
		// Activation...
		register_activation_hook(__FILE__, array(&$this, 'activate'));

        // Filters and hooks...
        add_filter('admin_menu', array(&$this, 'admin_menu'));
		add_action('admin_init', array(&$this, 'maybe_render_csv'));
        add_action('pre_comment_on_post', array(&$this, 'validate_comment_meta'));
        add_action('wp_insert_comment', array(&$this, 'save_comment_meta'), 10, 2);
        
        // Load topics...
        $this->topics = $this->get_topics();
        $this->topic_count = count($this->topics);
		
		// Create an associative array of topics, keyed by value, to ease lookup
		// of topic name by value
        $this->topics_by_value = array_combine(
			array_map(array(&$this, '_map_values'), $this->topics),
			array_map(array(&$this, '_map_names'), $this->topics)
		);
		
        // Mark topic as a required field...
        if ($this->topics) $this->required[] = 'topic';
        
        // Load sentiments...
        $this->sentiments = $this->get_sentiments();
        $this->sentiment_count = count($this->sentiments);

		// Create an associative array of sentiments, keyed by value, to ease lookup
		// of sentiment name by value
        $this->sentiments_by_value = array_combine(
			array_map(array(&$this, '_map_values'), $this->sentiments),
			array_map(array(&$this, '_map_names'), $this->sentiments)
		);
        
        // Mark sentiment as a required field...
        if ($this->sentiments) $this->required[] = 'sentiment';
    }
    
    /**
     * Activation hook, sets up options in wp_options
     */
    function activate() {
        // Add 'smtr_topics' option to wp_options, if they don't exist already
        if ( ( $topic = get_option('smtr_topics') ) === false ) {
            add_option('smtr_topics', Sentimeter::$default_topics);
        }
        // Add 'smtr_sentiments' option to wp_options, if they don't exist already
        if ( ( $topic = get_option('smtr_sentiments') ) === false ) {
            add_option('smtr_sentiments', Sentimeter::$default_sentiments);
        }
    }
    
	/**
	 * Used to create an associative array of sentiments/topics, keyed by value, to
	 * ease lookup of sentiment/topic name by value
	 */
	function _map_values($element) {
		return $element['value'];
	}
	
	/**
	 * Used to create an associative array of sentiments/topics, keyed by value, to
	 * ease lookup of sentiment/topic name by value
	 */
	function _map_names($element) {
		return $element['name'];
	}
	
    /**
     * Returns current set of topics
     *
     * @return Array
     */
    function get_topics() {
        return (array)get_option('smtr_topics');
    }
    
    /**
     * Returns current set of sentiments
     *
     * @return Array
     */
    function get_sentiments() {
        return (array)get_option('smtr_sentiments');
    }
    
	/**
	 * Returns a sanitized date from the query string param specified by $prefix.
	 * Expects the query string params to be:
	 *
	 * _ <prefix>M : the requested month
	 * _ <prefix>j : the requested day
	 * _ <prefix>Y : the requested year
	 */
	function get_date_for_range($prefix = 'start_date_', $default = '7 days ago') {
		$date = false;
		
		if ($_GET[$prefix . 'M'] && $_GET[$prefix . 'j'] && $_GET[$prefix . 'Y']) {
			$date = strtotime($_GET[$prefix . 'j'] . ' ' . $_GET[$prefix . 'M'] . ' ' . $_GET[$prefix . 'Y']);
		}
		
		$date = ( $date === false ? strtotime($default) : $date );
		
		// Store result on $this, as they are needed later by the admin screen
		$formats = array('', 'j', 'M', 'Y');
		foreach ($formats as $format) {
			$var = substr($prefix, 0, -6);
			if (empty($format)) {
				$this->$var = $date;				
			} else {
				$var .= '_' . $format;
				$this->$var = date($format, $date);
			}
		}
		
		return $date;
	}
	
	/**
	 * Returns total count of each topic within specified date range
	 */
	function get_topic_counts_in_date_range() {
		global $wpdb;
		
		$sql = sprintf("
			SELECT meta_value, COUNT(*) AS count
			FROM {$wpdb->commentmeta} 
			INNER JOIN {$wpdb->comments} ON wp_comments.comment_ID = wp_commentmeta.comment_id
			WHERE meta_key = 'smtr_topic'
			AND wp_comments.comment_date BETWEEN '%s' AND '%s'
			GROUP BY meta_value
			ORDER BY COUNT(*) DESC, meta_value
		", date('Y-m-d 00:00:00', $this->start), date('Y-m-d 23:59:59', $this->end));
		
		return $wpdb->get_results($sql);
	}
	
	/**
	 * Returns total count of each sentiment within specified date range
	 */
	function get_sentiment_counts_in_date_range() {
		global $wpdb;
		
		$sql = sprintf("
			SELECT meta_value, COUNT(*) AS count
			FROM {$wpdb->commentmeta} 
			INNER JOIN {$wpdb->comments} ON wp_comments.comment_ID = wp_commentmeta.comment_id
			WHERE meta_key = 'smtr_sentiment'
			AND wp_comments.comment_date BETWEEN '%s' AND '%s'
			GROUP BY meta_value
			ORDER BY COUNT(*) DESC, meta_value
		", date('Y-m-d 00:00:00', $this->start), date('Y-m-d 23:59:59', $this->end));
		
		return $wpdb->get_results($sql);
	}

	/**
	 * Invokes the csv() method when a CSV export has been requested.
	 */
	function maybe_render_csv() {
		if ( $_GET['page'] == 'sentimeter/sentimeter.php' && strtolower($_GET['submit']) == 'export as csv' ) {
			$this->csv();
		}
	}
	
	/**
	 * Renders a CSV export as an inline attachment, containing the comments, and
	 * metadata, within the date range specified
	 */
	function csv() {
		global $wpdb;
		
		// Try and gzip output
		if ( ! ob_start('ob_gzhandler') ) ob_start();
		
		// Make sure we have start and end dates
		if ( ! $this->start )
			$this->get_date_for_range();
		if ( ! $this->end )
			$this->get_date_for_range('end_date_', 'now');

		// Construct filename
		$filename = sprintf('comments-%s-to-%s.csv', $this->start_M . $this->start_Y . $this->start_j, $this->end_M . $this->end_Y . $this->end_j);
		
		header('Content-type: application/vnd.ms-excel');
		header('Content-disposition: inline;filename=' . $filename);
		
		// Get the row headings for the CSV
		$columns = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->comments}");
		$columns[] = 'Topic';
		$columns[] = 'Sentiment';
		
		// Get the actual data
		$sql = sprintf("
			SELECT wp_comments.*, topics.meta_value AS topic, sentiments.meta_value AS sentiment
			FROM {$wpdb->comments} AS wp_comments
			LEFT JOIN (SELECT * FROM {$wpdb->commentmeta} WHERE meta_key = 'smtr_topic') AS topics ON topics.comment_id = wp_comments.comment_ID
			LEFT JOIN (SELECT * FROM {$wpdb->commentmeta} WHERE meta_key = 'smtr_sentiment') AS sentiments ON sentiments.comment_id = wp_comments.comment_ID
			WHERE wp_comments.comment_date BETWEEN '%s' AND '%s';
		", date('Y-m-d 00:00:00', $this->start), date('Y-m-d 23:59:59', $this->end));
		$rows = $wpdb->get_results($sql, ARRAY_N);
		
		// Push headings and rows onto the $data array
		$data = array_merge(array($columns), $rows);
		
		// Get the output stream for writing to (so we can use fputcsv)
		$resource = fopen('php://output', 'w');
		foreach ( $data as $datum ) {
			fputcsv($resource, $datum);
		}
		
		// Flush output buffer and exit
		ob_end_flush();
		die;
	}
	
    /**
     * Sets up admin menu option
     */
    function admin_menu() {
        add_management_page('Sentiment Analysis &lsaquo; Sentimeter', 'Sentimeter', 8, __FILE__, array(&$this, 'admin_screen'));
    }
    
    /**
     * Renders the Sentimeter admin screen
     */
    function admin_screen() {
		include( WP_PLUGIN_DIR . '/sentimeter/sentimeter_admin.php' );
    }
    
    /**
     * Dies, with an appropriate error message, when a required field is missing
     */
    function _die_required() {
        $required = implode(', ', array_merge(array('name', 'email'), $this->required));
        $mesg = apply_filters('smtr_required_field_error_mesg', sprintf(__('Error: please fill the required fields (%s).', 'sentimeter'), $required), $required);
        wp_die($mesg);
    }
    
    /**
     * Dies, with an appropriate error message, when an invalid field is submitted
     */
    function _die_invalid($field) {
        $mesg = apply_filters('smtr_invalid_field_error_mesg', sprintf(__('Error: please select a valid %s.', 'sentimeter'), $field), $field);
        wp_die($mesg);
    }
    
    /**
     * Returns true if a topic is required
     */
    function _topic_required() {
        return in_array('topic', $this->required);
    }
    
    /**
     * Returns true if a sentiment is required
     */
    function _sentiment_required() {
        return in_array('sentiment', $this->required);
    }
    
    /**
     * Validates the submitted topic, dies if topic is invalid for any reason
     */
    function _validate_topic() {
        if ( $this->_topic_required() ) {
            if ( empty($_POST['smtr_topic']) ) {
                // Topic is required
                $this->_die_required();
            } elseif ( ! array_key_exists( $_POST['smtr_topic'], $this->topics_by_value ) ) {
                // Topic is invalid
                $this->_die_invalid('topic');
            }
			// All is well
			$this->data['smtr_topic'] = esc_sql($_POST['smtr_topic']);
        }        
    }
    
    /**
     * Validates the submitted sentiment, dies if sentiment is invalid for any reason
     */
    function _validate_sentiment() {
        if ( $this->_sentiment_required() ) {
            if ( empty($_POST['smtr_sentiment']) ) {
                // Sentiment is required
                $this->_die_required();
            } elseif ( ! array_key_exists( $_POST['smtr_sentiment'], $this->sentiments_by_value ) ) {
                // Sentiment is invalid
                $this->_die_invalid('sentiment');
            }
			// All is well
			$this->data['smtr_sentiment'] = esc_sql($_POST['smtr_sentiment']);
        }
    }
    
    /**
     * Validates the comment meta on a new comment
     */
    function validate_comment_meta() {
        // Validate topic...
        $this->_validate_topic();
        
        // Validate sentiment...
        $this->_validate_sentiment();
    }
    
    /**
     * Saves the comment meta on a new comment
     */
    function save_comment_meta($id, $comment) {
        // Save topics...
		if ( array_key_exists('smtr_topic', $this->data) )
			add_comment_meta($id, 'smtr_topic', $this->data['smtr_topic']);
        
        // Save sentiments...
		if ( array_key_exists('smtr_sentiment', $this->data) )
	        add_comment_meta($id, 'smtr_sentiment', $this->data['smtr_sentiment']);
    }
    
	/**
	 * Utility method for decoding a sentiment value (returns the corresponding sentiment name)
	 */
	function decode_sentiment_value($value) {
		return __($this->sentiments_by_value[$value], 'sentimeter');
	}
	
	/**
	 * Utility method for decoding a topic value (returns the corresponding topic name)
	 */
	function decode_topic_value($value) {
		return __($this->topics_by_value[$value], 'sentimeter');
	}
	
    // WP-Loop style methods for topics...
    
    function has_topics() {
        return $this->current_topic + 1 < $this->topic_count;
    }

	function next_topic() {
		$this->current_topic++;
		$this->topic = $this->topics[$this->current_topic];
		
		return $this->topic;
	}
    
    function rewind_topics() {
		$this->current_topic = -1;
		if ( $this->topic_count > 0 ) {
			$this->topic = $this->topics[0];
		}
    }
    
    function the_topic() {
		$this->in_the_loop = true;
		$this->topic = $this->next_topic();
				
		if ( 0 == $this->current_topic ) // loop has just started
			do_action('topic_loop_start');
    }
    
    function the_topic_name() {
        if ($this->in_the_loop)
            return esc_html__($this->topic['name'], 'sentimeter');
    }
    
    function the_topic_value() {
        if ($this->in_the_loop)
            return esc_html($this->topic['value']);
    }
    
    function is_topic_selected() {
        if ($this->in_the_loop) {
            return array_key_exists('default', $this->topic) && $this->topic['default'];
        }
    }

    // WP-Loop style methods for sentiments...

    function has_sentiments() {
        return $this->current_sentiment + 1 < $this->sentiment_count;
    }

	function next_sentiment() {
		$this->current_sentiment++;
		$this->sentiment = $this->sentiments[$this->current_sentiment];
		
		return $this->sentiment;
	}
    
    function rewind_sentiments() {
		$this->current_sentiment = -1;
		if ( $this->sentiment_count > 0 ) {
			$this->sentiment = $this->sentiments[0];
		}
    }
    
    function the_sentiment() {
		$this->in_the_loop = true;
		$this->sentiment = $this->next_sentiment();
				
		if ( 0 == $this->current_sentiment ) // loop has just started
			do_action('sentiment_loop_start');
    }
    
    function the_sentiment_name() {
        if ($this->in_the_loop)
            return esc_html__($this->sentiment['name'], 'sentimeter');
    }
    
    function the_sentiment_value() {
        if ($this->in_the_loop)
            return esc_html($this->sentiment['value']);
    }
    
}

// Singleton instance
$smtr = new Sentimeter();

// Template tags

function smtr_has_topics() {
    global $smtr;
    
    return $smtr->has_topics();
}

function smtr_the_topic() {
    global $smtr;
    
    return $smtr->the_topic();
}

function smtr_the_topic_name() {
    global $smtr;
    
    echo $smtr->the_topic_name();
}

function smtr_the_topic_value() {
    global $smtr;
    
    echo $smtr->the_topic_value();
}

function smtr_is_topic_selected() {
    global $smtr;
    
    return $smtr->is_topic_selected();
}

function smtr_has_sentiments() {
    global $smtr;
    
    return $smtr->has_sentiments();
}

function smtr_the_sentiment() {
    global $smtr;
    
    return $smtr->the_sentiment();
}

function smtr_the_sentiment_name() {
    global $smtr;
    
    echo $smtr->the_sentiment_name();
}

function smtr_the_sentiment_value() {
    global $smtr;
    
    echo $smtr->the_sentiment_value();
}
?>