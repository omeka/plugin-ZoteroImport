<?php
class ZoteroImport_IndexController extends Omeka_Controller_Action
{
    public function indexAction()
    {
        $dropboxDir = PLUGIN_DIR . DIRECTORY_SEPARATOR . 'ZoteroImport' . DIRECTORY_SEPARATOR . 'dropbox';
        $exportDirs = array();
        foreach (new DirectoryIterator($dropboxDir) as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $exportDirs[] = $fileInfo->getPathname();
            }
        }
        $this->view->exportDirs = $exportDirs;
    }
    
    public function importAction()
    {
        $this->_import();
        $this->flashSuccess("Import successful.");
        $this->redirect->goto('index');
    }
    
    // @todo: create a collection and assign items to it.
    // @todo: DONE: make all items public.
    // @todo: make the import a background script.
    // @todo: map dcterms:isPartOf to Omeka:dc:relation
    // @todo: DONE: map bib:contributors to Omeka:dc:contributor
    // @todo: DONE: map dc:subject to Omeka:dc:subject
    // @todo: DONE: not all link:link have RDF:resource, but only dc:identifier
    private function _import()
    {
        // Raising the memory limit is necessary for now.
        ini_set('memory_limit', '500M');
        
        $zoteroRdf = new ZoteroRdf($_POST['export_directory']);
        
        foreach ($zoteroRdf->items as $item) {
            $metadata = array('public' => true);
            
            $elementTexts = array(
                'Dublin Core' => array(
                    'Title' => array(
                        array('text' => $item['@dc:title'], 'html' => false), 
                        array('text' => $item['@z:shortTitle'], 'html' => false)
                    ), 
                    'Date' => array(
                        array('text' => $item['@dc:date'], 'html' => false)
                    ), 
                    'Identifier' => array(
                        array('text' => $item['@dc:identifier'], 'html' => false), 
                        array('text' => $item['@RDF:about'], 'html' => false)
                    ), 
                    'Type' => array(
                        array('text' => $item['@z:itemType'], 'html' => false)
                    ), 
                    'Publisher' => array(
                        array('text' => $item['dc:publisher'], 'html' => false)
                    )
                )
            );
            
            foreach ($item['bib:authors'] as $author) {
                $elementTexts['Dublin Core']['Creator'][] = array(
                    'text' => trim("{$author['givenname']} {$author['surname']}"), 
                    'html' => false
                );
            }
            
            foreach ($item['bib:contributors'] as $contributor) {
                $elementTexts['Dublin Core']['Contributor'][] = array(
                    'text' => trim("{$contributor['givenname']} {$contributor['surname']}"), 
                    'html' => false
                );
            }
            
            foreach ($item['dc:subject'] as $subject) {
                $elementTexts['Dublin Core']['Subject'][] = array(
                    'text' => $subject, 
                    'html' => false
                );
            }
            
            $files = array();
            foreach ($item['link:link'] as $link) {
                // Ingest only those items that have a path to an export file.
                if ($link['RDF:resource']) {
                    $files[] = $_POST['export_directory'] . DIRECTORY_SEPARATOR . $link['RDF:resource'];
                }
            }
            $fileMetadata = array('file_transfer_type' => 'Filesystem', 'files' => $files);
            
            //print_r($elementTexts);
            //print_r($fileMetadata);
            
            try {
                insert_item($metadata, $elementTexts, $fileMetadata);
            } catch (Exception $e) {
                print_r($e);exit;
            }
        }
        //exit;
    }
}