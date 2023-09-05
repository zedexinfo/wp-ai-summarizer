<?php
if (get_option('sm_error_log_option') == 1) {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];
    $error_log_data = file_get_contents($base_dir . '/sm-log.log');

    $lines = explode("\n", $error_log_data);
    array_pop($lines);
    $result = [];
    foreach ($lines as $line) {
        $entries = explode('|', $line);
        $entries = array_map('trim', $entries);
        $result[] = $entries;
    }
    $result = array_reverse($result);
    ?>
    <table class="wp-list-table widefat fixed striped alternate">
        <thead>
        <tr>
            <th style="font-weight: bold" class="manage-column">Time</th>
            <th style="font-weight: bold" class="manage-column">Product ID</th>
            <th style="font-weight: bold" class="manage-column">Product Name</th>
            <th style="font-weight: bold" class="manage-column">Error Message</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($result as $entries): ?>
            <tr>
                <?php foreach ($entries as $entry): ?>
                    <td><?php echo $entry; ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php
}
else {
    echo '<h2>Error log is disabled.</h2>';
}