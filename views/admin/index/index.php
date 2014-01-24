<?php echo head(array('title' => __('Zotero Import'))); ?>
<div id="primary">
<?php echo flash(); ?>
<?php echo $this->form; ?>
<h2>Imports</h2>
<?php if (!count($this->imports)): ?>
<p>No imports found.</p>
<?php else: ?>
<table>
    <thead>
        <th>ID</th>
        <th>Collection Name</th>
        <th>Status</th>
        <th>Started</th>
        <th>Stopped</th>
        <th></th>
    </thead>
    <tbody>
    <?php $imports = array_reverse($this->imports); ?>
    <?php foreach ($imports as $import): ?>
    <tr>
        <td><?php echo $import->id; ?></td>
        <td><a href="<?php echo $this->url(array('module'     => 'default', 
                                                 'controller' => 'items', 
                                                 'action'     => 'browse', 
                                                 'collection' => $import->collection_id)); ?>"><?php echo metadata(get_record_by_id('collection', $import->collection_id), array('Dublin Core', 'Title')); ?></a></td>
        <td><strong><?php echo ucwords($import->status); ?></strong></td>
        <td><?php echo $import->started; ?></td>
        <td><?php echo $import->stopped; ?></td>
        <td><?php if ($import->pid): ?>
        <form action="<?php echo $this->url(array('action' => 'stop-import')); ?>" method="post">
            <?php echo $this->formHidden('processId', $import->process_id); ?>
            <?php echo $this->formSubmit('submit-stop-process', 
                                         'Stop Import', 
                                         array('class' => 'submit')); ?>
        </form>
        <?php else: ?>
        <form action="<?php echo $this->url(array('action' => 'delete-import')); ?>" method="post">
            <?php echo $this->formHidden('processId', $import->process_id); ?>
            <?php echo $this->formSubmit('submit-delete-process', 
                                         'Delete Import', 
                                         array('class' => 'submit delete')); ?>
        </form>
        <?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>
<?php echo foot(); ?>
