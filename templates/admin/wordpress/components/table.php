<table class="wp-list-table widefat fixed striped posts">
    <thead>
        <tr>
            <?php foreach ($columns as $slug => $label): ?>
                <th scope="col" id="<?php echo $slug; ?>" class="manage-column column-<?php echo $slug; ?>">
                    <?php echo $label; ?>
                </th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody id="the-list">
        <?php foreach ($items as $item): ?>
            <tr>
                <?php foreach (array_keys($columns) as $slug): ?>
                    <td class="<?php echo $slug; ?> column-<?php echo $slug; ?>">
                        <?php echo $item[$slug] ?? ''; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
