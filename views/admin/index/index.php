<?php
$head = array('title' => html_escape('Zotero Import | Index'));
head($head);
?>
<h1><?php echo $head['title']; ?></h1>
<div id="primary">
<?php echo $entry->title; ?><br />
<?php echo $entry->author->name; ?><br />
<?php echo $entry->author->uri; ?><br />
<?php echo $entry->id; ?><br />
<?php echo $entry->published; ?><br />
<?php echo $entry->updated; ?><br />
<?php print_r($entry->link('self')); ?><br />
<?php print_r($entry->link('alternate')); ?><br />
<?php echo $entry->itemID; ?><br />
<?php echo $entry->itemType; ?><br />
<?php echo $entry->creatorSummary; ?><br />
<?php echo $entry->numChildren; ?><br />
<?php echo $entry->numTags; ?><br />
<?php print_r($entry->content->item['itemType']); ?><br />
<?php foreach ($entry->content->item->field as $field): ?>
<?php echo $field['name'] . ': ' . $field; ?><br />
<?php endforeach; ?>
<?php echo $entry->content->item->path; ?><br />
<?php //echo $entry->saveXml(); ?><br />
</div>
<?php foot(); ?>