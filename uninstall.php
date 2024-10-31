<?php
global $wpdb;

delete_option('smtr_topics');
delete_option('smtr_sentiments');

$wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key IN ('smtr_topic','smtr_sentiment')");
?>