<?php
$head = array('title' => html_escape('Zotero Import | Import ' . ucfirst($this->type) . ' Library'));
head($head);
?>
<h1><?php echo $head['title']; ?></h1>
<div id="primary">
</div>
<?php foot(); ?>