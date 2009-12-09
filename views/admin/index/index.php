<?php
$head = array('title' => html_escape('Zotero Import | Index'));
head($head);
?>
<h1><?php echo $head['title']; ?></h1>
<div id="primary">
<?php echo flash(); ?>
<?php echo $this->form; ?>
<?php if (count($this->processes)): ?>
<h2>Imports</h2>
<table>
    <thead>
        <th>Collection Name</th>
        <th>Status</th>
        <!--<th></th>-->
    </thead>
    <tbody>
    <?php $processes = array_reverse($this->processes); ?>
    <?php foreach ($processes as $process): ?>
    <?php $args = $process->getArguments(); ?>
    <tr>
        <td><a href="<?php echo $this->url(array('module'     => 'default', 
                                                 'controller' => 'items', 
                                                 'action'     => 'browse', 
                                                 'collection' => $args['collectionId'])); ?>"><?php echo $args['collectionName']; ?></a></td>
        <td><strong><?php echo ucwords($process->status); ?></strong></td>
        <!--<td><?php if ($process->pid): ?>
        <form action="<?php echo $this->url(array('action' => 'stop-process')); ?>" method="post">
            <?php echo $this->formHidden('processId', $process->id); ?>
            <?php echo $this->formSubmit('submit-stop-process', 
                                         'Stop Process', 
                                         array('class' => 'submit')); ?>
        </form>
        <?php endif; ?></td>-->
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>
<?php foot(); ?>