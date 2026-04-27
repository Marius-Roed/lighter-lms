<?php
/** @var WP_Post $post */
$post;

$product = lighter_lms_get_course_product($post->ID);

/**
 * @phpstan-import-type DownloadFile from \LighterLMS\Types
 * @var DownloadFile[] $downloads
 */
$downloads = lighter_lms_get_course_downloads($post);
?>

<?php if (count($downloads)): ?>
<table class="lighter-downloads">
    <thead>
      <tr>
        <td>File name</td>
        <td>File url</td>
        <td align="center">File type</td>
        <td>
          <span class="screen-reader-text">Actions</span>
        </td>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($downloads as $download):
        // TODO:
        $file_type = "pdf"; ?>
    <tr>
        <td<?php
        echo esc_html($download["name"]);
        lighter_icon("pencil");
        ?></td>
        <td>
            <input type="text" value="<?php echo esc_attr(
                $download["file"],
            ); ?>" />
        </td>
        <td align="center"><?php echo esc_html($fileType); ?></td>
        <td>
            <button type="button" class="lighter-btn transparent hide-if-no-js">Choose file</button>
        </td>
      </tr>
    <?php
    endforeach; ?>
    </tbody>
</table>
<button type="button" class="lighter-btn hide-if-no-js" >Add new file</button>
<?php else: ?>
<div class="no-downloads">
    <p>No downloads found for this course</p>
    <div class="hide-if-no-js">
        <button type="button" class="lighter-btn">Add first file</button>
        <?php if ($product === null): ?>
        or
        <button type="button" class="lighter-btn transparent">link to a product</button>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
