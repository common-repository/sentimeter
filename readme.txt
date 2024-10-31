=== Plugin Name ===
Contributors: swinton
Tags: sentiments, comments, measurement, metadata, community, feedback, csv, export
Requires at least: 2.9
Tested up to: 2.9
Stable tag: 1.0

Allows sentiments (e.g. happy, neutral, outraged etc.) to be attached to comments, aggregated sentiment is available as a pie chart in the admin UI.

== Description ==

This plugin allows users to express sentiments (a la getsatisfaction.com, e.g. happy, neutral, outraged etc.) through the WordPress commenting system. Users can also associate their comment with a pre-defined 'topic'. Overall sentiment, and comment topics, can be viewed as pie charts in the admin system, within a user-defined date range. Additionally, comments (along with their sentiment, and topic, if specified) can be exported as a CSV for further offline analysis.

Note that, in order to fully incorporate this plugin, you will need to modify your theme. This is so that theme developers have full control over the markup that is generated. The following [Loop](http://codex.wordpress.org/The_Loop)-inspired template tags are provided in order to render the defined sentiments and topics:

* `smtr_has_sentiments` - determines whether their are any sentiments to display
* `smtr_the_sentiment` - initializes the current sentiment for display
* `smtr_the_sentiment_name` - echoes the current sentiment name ("Happy", "Neutral", "Outraged" etc.), first escaping any HTML
* `smtr_the_sentiment_value` - echoes the current sentiment value ("happy", "neutral", "outraged" etc.), first escaping any HTML
* `smtr_has_topics` - determines whether their are any topics to display
* `smtr_the_topic` - initializes the current topic for display
* `smtr_the_topic_name` - echoes the current topic name ("Help", "Ideas", "Suggestions" etc.), first escaping any HTML
* `smtr_the_topic_value` - echoes the current topic value ("help", "ideas", "suggestions" etc.), first escaping any HTML
* `smtr_is_topic_selected` - returns TRUE if the current topic should be selected (intended for use when rendering a select formm element with multiple topic options)

One suggested arrangement of these template tags is:

`
<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">
<ul>
    
    <?php if ( function_exists('smtr_has_topics') ) : // ensure 'Sentimeter' plugin is installed ?>
        <li>
            <?php if ( smtr_has_topics() ) : ?>
            <label for="smtr_topic">This is about</label>
            <select id="smtr_topic" name="smtr_topic">
            <?php while ( smtr_has_topics() ) : smtr_the_topic(); ?>
                <option value="<?php smtr_the_topic_value() ?>"<?php echo smtr_is_topic_selected() ? ' selected="selected"' : ''; ?>"><?php smtr_the_topic_name() ?></option>
            <?php endwhile; ?>
            </select>
            <?php endif; ?>
        </li>
        
        <li>
            <?php if ( smtr_has_sentiments() ) : ?>
            <fieldset class="radio-group">
                <legend>I&#039;m feeling</legend>
                <ul class="formatRadio">
                    <?php $i = 0; while ( smtr_has_sentiments() ) : smtr_the_sentiment(); ?>
                    <li>
                        <label for="smtr_sentiment_<?php smtr_the_sentiment_value() ?>"><?php smtr_the_sentiment_name() ?></label>
                        <input type="radio" id="smtr_sentiment_<?php smtr_the_sentiment_value() ?>" name="smtr_sentiment" value="<?php smtr_the_sentiment_value() ?>"/>
                    </li>
                    <?php $i++; endwhile; ?>
                </ul>
            </fieldset>
            <?php endif; ?>
        </li>
    <?php endif; ?>
        <li>
            <?php  // Other existing comment fields here (name, email, comment etc.) ... ?>
`

== Installation ==

1. Upload the `sentimeter` folder to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Modify your existing comments form to include options for sentiment and/or topic using the installed template tags
1. Monitor sentiment through the admin UI, by navigating to the Sentimeter page, located under the Tools menu

== Frequently Asked Questions ==

= How do I specify my own sentiments and/or topics? =

Currently, there is no admin UI to override the default sentiments or topics, this is planned for a future release.

However, the default sentiments and topics are just options in the `wp_option` database table. The following PHP script, when placed in the root of your WordPress install, can be used to override the default sentiments and topics after the plugin has been installed:

`
<?php
// Load up WordPress
include 'wp-load.php';

// Define sentiments/topics (edit as required, but maintaining the data structure)
$sentiments = array(
    array(
        'name' => 'Happy',        // name is show to the end user
        'value' => 'happy'        // value is stored in the database
    ),
    array(
        'name' => 'Neutral',
        'value' => 'neutral'
    ),
    array(
        'name' => 'Outraged',
        'value' => 'outraged'
    )
);
$topics = array(
    array(
        'name' => 'Help',
        'value' => 'help'
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
        'name' => 'Something else',
        'value' => 'other',
        'default' => 'true'       // this will cause Something else to be pre-selected in a drop-down
    )
);

// Load sentiments/topics
update_option('smtr_sentiments', $sentiments);
update_option('smtr_topics', $topics);
?>
`

= How can I use my own emoticons/icons for the sentiments, a la getsatisfaction.com? =

Excellent question. We've had success with [Ryan Fait's](http://ryanfait.com/) [custom form elements script](http://ryanfait.com/resources/custom-checkboxes-and-radio-buttons/).

= Where is that awesome pie-chart generator from? =

We're using the [gRapha‘l charting JavaScript library](http://g.raphaeljs.com/). Yes, it is awesome, isn't it? :)

= I have another question! =

[Get in touch](http://twitter.com/steveWINton) with [me](http://www.nixonmcinnes.co.uk/people/steve/).

== Screenshots ==

1. This screen shot shows an example sentiment analysis, broken down both by sentiment and topic, within the WordPress admin UI.

== Changelog ==

= 1.0 =
* First release.
