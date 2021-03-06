<?php

class MigrateWild extends Migration {
  public $base_dir;
  //public $partimp = '';
  public $maindir = '/Users/ogs22/importwild/wild.maths.org';
  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();

    $this->map = new MigrateSQLMap($this->machineName,
        array(
          'sourceid' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
          )
        ),
        MigrateDestinationNode::getKeySchema()
    );
    // The source fields.
    $fields = array(
      'title' => t('Title'),
      'body' => t('Body'),
      'uid' => t('User id'),
      'facpath' => t('the path'),
      'tags' => t('field page tags'),
        'ct' => t('creta tags?'),
        'ic' => t ('ignore case of tag')
    );

    $this->base_dir = $this->maindir;

    // Match HTML files.
    $regex = '/(?!(catam|madeup)).*\.htm/';
 
    // The source of the migration is HTML files from the old site.
    $list_files = new MigrateListFiles(array($this->base_dir), $this->base_dir, $regex);
    $item_file = new MigrateItemFile($this->base_dir);
    $this->source = new MigrateSourceList($list_files, $item_file, $fields);
 
    // The destination is the mynode content type.
    $this->destination = new MigrateDestinationNode('page');
 
    // Map the fields, pretty straightforward in this case.
    $this->addFieldMapping('uid', 'uid');
    $this->addFieldMapping('title', 'title');
    $this->addFieldMapping('body', 'body')
      ->arguments(array('format' => 'full_html'));
    $this->addFieldMapping('path', 'facpath');
    $this->addFieldMapping('pathauto', TRUE);
    $this->addFieldMapping('field_page_tags', 'tags');
    $this->addFieldMapping('field_page_tags:create_term','ct');
    $this->addFieldMapping('field_page_tags:ignore_case','ic');

  }
 
  /**
   * Prepare a row.
   */
  public function prepareRow($row) {
      //print_r($row);
    // Set to admin for now.
    $row->uid = 1;
 
    // Create a new SourceParser to handle HTML content.
    $source_parser = new SourceParser(substr($row->sourceid, 1), $row->filedata,$this);
 
    // The title is the filename.
    $row->facpath = substr($row->sourceid,1);
//    if (basename($row->sourceid) == "index.html") {
//        $row->alt[1] = substr(dirname($row->sourceid),1);
//    }
    
    $row->ct = TRUE;
    $row->ic = TRUE;
    $tmp = explode('/',  dirname($row->sourceid));
    //$tags = join(',', $tmp);
    $row->tags = $tmp[1];
    
    //$row->title = $source_parser->getTitle($this->base_dir,$row->facpath);
    $row->title = $source_parser->toph1;
    if ($row->title == "") {
        $row->title = $row->sourceid;
    }
    
    //remove h1s
    
    
    $body = $source_parser->getBody();
    $body = preg_replace('/<h1 class="import">.*<\/h1>/', '', $body);
    $row->body = preg_replace('/<h1 class="import"\/>/', '', $body);


    
    
  }
}








