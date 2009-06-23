<?php
$head = array('body_class' => 'zotero-import primary',
              'title'      => 'Zotero Import');
head($head);
?>
<h1><?php echo $head['title'];?></h1>

<div id="primary">

<?php echo flash(); ?>

<table>
    <thead>
        <tr>
            <th>Export Directory</th>
            <th></th>
        </tr>
    </thead>
<?php foreach ($this->exportDirs as $exportDir): ?>
    <tr>
        <td><?php echo basename($exportDir); ?></td>
        <td><form method="post" action="<?php echo uri('zotero-import/index/import');?>">
            <?php echo $this->formHidden('export_directory', $exportDir); ?>
            <?php echo $this->formSubmit('submit_import', 'Import'); ?>
        </form></td>
    </tr>
<?php endforeach; ?>
</table>

</div>

<?php
foot();