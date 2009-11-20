<?php
$head = array('title' => html_escape('Zotero Import | Index'));
head($head);
?>
<h1><?php echo $head['title']; ?></h1>
<div id="primary">
<?php echo flash(); ?>
<?php echo $this->form; ?>
</div>
<?php foot(); ?>