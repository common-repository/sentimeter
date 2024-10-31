<?php

function smtr_admin_head() {
    global $smtr, $wpdb;
    
    // Only add the JS if we are on the sentimeter.php page
    if ( ! $_GET['page'] == 'sentimeter/sentimeter.php' ) {
        return;
    }
    
    // Get topics counts
    $topics_results = $smtr->get_topic_counts_in_date_range($smtr->get_date_for_range(), $smtr->get_date_for_range('end_date_', 'now'));
    foreach( $topics_results as $result ) {
        $topic_data[] = (float)$result->count;
        $topic_labels[] = '"%% ' . str_replace('"', '\"', $smtr->decode_topic_value($result->meta_value)) . '"';
    }
    
    // Get sentiments counts
    $sentiments_results = $smtr->get_sentiment_counts_in_date_range($smtr->start, $smtr->end);
    foreach( $sentiments_results as $result ) {
        $sentiment_data[] = (float)$result->count;
        $sentiment_labels[] = '"%% ' . str_replace('"', '\"', $smtr->decode_sentiment_value($result->meta_value)) . '"';
    }
    
?>
    <style type="text/css" media="screen">
        #comments-by-topic, #comments-by-sentiment {
            float: left;
            width: 360px;
        }
    </style>
    
    <script type="text/javascript">
        
        jQuery(document).ready(function ($) {
            var hover_callback = function() {
                this.sector.stop();
                this.sector.scale(1.1, 1.1, this.cx, this.cy);
                if (this.label) {
                    this.label[0].stop();
                    this.label[0].scale(1.5);
                    this.label[1].attr({"font-weight": 800});
                }            
            };
            
            var bounce_callback = function() {
                this.sector.animate({scale: [1, 1, this.cx, this.cy]}, 500, "bounce");
                if (this.label) {
                    this.label[0].animate({scale: 1}, 500, "bounce");
                    this.label[1].attr({"font-weight": 400});
                }
            };
            
            try {
                var r1 = Raphael("smtr_topic_container");
                var smtr_topics_pie = r1.g.piechart(250, 110, 100, [<?php echo implode(', ', $topic_data); ?>], {legend: [<?php echo implode(', ', $topic_labels); ?>], legendpos: "west"});
                smtr_topics_pie.hover(hover_callback, bounce_callback);
            } catch (e) {
                $("#smtr_topic_container").html("<p>Unable to draw pie chart, not enough data?</p>");
            }
            
            try {
                var r2 = Raphael("smtr_sentiment_container");
                var smtr_sentiments_pie = r2.g.piechart(250, 110, 100, [<?php echo implode(', ', $sentiment_data); ?>], {legend: [<?php echo implode(', ', $sentiment_labels); ?>], legendpos: "west"});
                smtr_sentiments_pie.hover(hover_callback, bounce_callback);
            } catch (e) {
                $("#smtr_sentiment_container").html("<p>Unable to draw pie chart, not enough data?</p>");
            }
        });
        
    </script>
<?php
}
add_action('admin_head', 'smtr_admin_head');

if ( $_GET['page'] == 'sentimeter/sentimeter.php' ) {
    wp_enqueue_script('raphael', plugins_url('/sentimeter/raphael-min.js'), array('jquery'));
    wp_enqueue_script('raphael-g', plugins_url('/sentimeter/g.raphael-min.js'), array('raphael'));
    wp_enqueue_script('raphael-g-pie', plugins_url('/sentimeter/g.pie-min.js'), array('raphael-g'));
}
?>