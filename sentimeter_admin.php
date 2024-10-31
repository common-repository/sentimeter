<?php
global $smtr;

$months = array(
    'Jan', 'Feb', 'Mar', 'Apr',	'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
);

$start = $smtr->start;
$start_M = $smtr->start_M;
$start_j = $smtr->start_j;
$start_Y = $smtr->start_Y;

$end = $smtr->end;
$end_M = $smtr->end_M;
$end_j = $smtr->end_j;
$end_Y = $smtr->end_Y;

?>
<div class="wrap">
    
    <h2>Sentiment Analysis</h2>

    <form method="get" action="<?php echo admin_url('tools.php'); ?>">
        <h3>Options</h3>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="start_date_M">Start date</label></th>
                    <td>
                        <select name="start_date_M" id="start_date_M">
                            <?php foreach ( $months as $month) : ?>
                            <option value="<?php echo $month; ?>"<?php echo ($start_M == $month ? ' selected="selected"' : ''); ?>><?php echo $month; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" autocomplete="off" maxlength="2" size="2" value="<?php echo $start_j; ?>" name="start_date_j" id="start_date_j"/>
                        ,
                        <input type="text" autocomplete="off" maxlength="4" size="4" value="<?php echo $start_Y; ?>" name="start_date_Y" id="start_date_Y"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="end_date_M">End date</label></th>
                    <td>
                        <select name="end_date_M" id="end_date_M">
                            <?php foreach ( $months as $month) : ?>
                            <option value="<?php echo $month; ?>"<?php echo ($end_M == $month ? ' selected="selected"' : ''); ?>><?php echo $month; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" autocomplete="off" maxlength="2" size="2" value="<?php echo $end_j; ?>" name="end_date_j" id="end_date_j"/>
                        ,
                        <input type="text" autocomplete="off" maxlength="4" size="4" value="<?php echo $end_Y; ?>" name="end_date_Y" id="end_date_Y"/>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input class="button" type="submit" value="Go" name="submit" />
            <input class="button" type="submit" value="Export as CSV" name="submit" />
            <input type="hidden" name="show_within_range" value="true" />
            <input type="hidden" name="page" value="sentimeter/sentimeter.php" />
        </p>
    </form>
    
    <div id="comments-by-topic">
        <h3 class="title">Comments by Topic</h3>
        <p><?php echo date('jS F Y', $start); ?> to <?php echo date('jS F Y', $end); ?></p>
        <div id="smtr_topic_container"></div>
    </div>
    
    <div id="comments-by-sentiment">
        <h3 class="title">Comments by Sentiment</h3>
        <p><?php echo date('jS F Y', $start); ?> to <?php echo date('jS F Y', $end); ?></p>
        <div id="smtr_sentiment_container"></div>
    </div>
    
    <div class="clear"></div>
    
</div>