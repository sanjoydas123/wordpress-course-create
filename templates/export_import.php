<div class="wrap">
    <h1>Create New Course</h1>
    <form method="post">
        <?php wp_nonce_field('wpc_create_course'); ?>
        <table class="form-table">
            <tr>
                <th><label for="product_name">Course Name</label></th>
                <td><input type="text" name="product_name" id="product_name" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="product_slug">Course Slug</label></th>
                <td><input type="text" name="product_slug" id="product_slug" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="acf_field_group">ACF Field Group</label></th>
                <td>
                    <select name="acf_field_group" id="acf_field_group" required>
                        <option value="">Select a Field Group</option>
                        <?php foreach ($field_groups as $group) : ?>
                            <option value="<?php echo esc_attr($group['key']); ?>">
                                <?php echo esc_html($group['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button('Create Course'); ?>
    </form>
</div>